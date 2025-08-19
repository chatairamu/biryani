<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security and Pre-flight Checks ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}
if (!isset($_SESSION['final_order_details'])) {
    header("Location: checkout.php?error=session_expired");
    exit();
}

$user_id = $_SESSION['user_id'];
$shipping_address = trim($_POST['shipping_address']);
$order_details = $_SESSION['final_order_details'];

if (empty($shipping_address)) {
    header("Location: checkout.php?error=address_required");
    exit();
}

try {
    // --- Begin Database Transaction ---
    $pdo->beginTransaction();

    // --- Fetch cart items and lock rows ---
    $cart_stmt = $pdo->prepare(
        "SELECT p.id as product_id, p.sale_price, c.quantity, p.stock, c.options
         FROM cart c JOIN products p ON c.product_id = p.id
         WHERE c.user_id = ? FOR UPDATE"
    );
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll();

    if (empty($cart_items)) {
        $pdo->rollBack();
        header("Location: cart.php");
        exit();
    }

    // --- Verify stock and create order ---
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $pdo->rollBack();
            header("Location: cart.php?error=stock_unavailable&product_id=" . $item['product_id']);
            exit();
        }
    }

    $order_stmt = $pdo->prepare(
        "INSERT INTO orders (user_id, total_amount, shipping_address, gst_amount, delivery_charge, coupon_code, discount_amount, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $order_stmt->execute([
        $user_id,
        $order_details['grand_total'],
        $shipping_address,
        $order_details['gst_amount'],
        $order_details['delivery_charge'],
        $order_details['coupon_code'],
        $order_details['discount_amount'],
        'Pending'
    ]);
    $order_id = $pdo->lastInsertId();

    // --- Move items from cart to order_items and update stock ---
    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, options) VALUES (?, ?, ?, ?, ?)");
    $update_stock_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cart_items as $item) {
        // In a real app, you'd calculate the final price with variant adjustments here again as a final check.
        $order_item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['sale_price'], $item['options']]);
        $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // --- Clear the cart and session data ---
    $clear_cart_stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart_stmt->execute([$user_id]);

    unset($_SESSION['final_order_details']);
    unset($_SESSION['cart_coupon']);

    // --- Commit and Redirect ---
    $pdo->commit();
    header("Location: dashboard.php?order_success=true&order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // For debugging: error_log($e->getMessage());
    header("Location: checkout.php?error=processing_failed");
    exit();
}
?>
