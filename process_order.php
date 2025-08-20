<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}
validate_csrf_token($_POST['csrf_token']);

$user_id = $_SESSION['user_id'] ?? null;
$order_details = $_SESSION['final_order_details'] ?? null;
$shipping_address = trim($_POST['shipping_address']);
$customer_email = trim($_POST['email']); // New field for guests

if (!$order_details || empty($shipping_address) || empty($customer_email)) {
    header("Location: checkout.php?error=missing_data");
    exit();
}

try {
    $pdo->beginTransaction();

    // --- Determine cart source and fetch items ---
    $cart_items = [];
    if ($user_id) {
        // Logged-in user: fetch from DB
        $cart_stmt = $pdo->prepare("SELECT p.id as product_id, p.sale_price, c.quantity, p.stock, c.options FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? FOR UPDATE");
        $cart_stmt->execute([$user_id]);
        $cart_items = $cart_stmt->fetchAll();
    } else {
        // Guest user: fetch from cookie and then DB
        $guest_cart = isset($_COOKIE['guest_cart']) ? json_decode($_COOKIE['guest_cart'], true) : [];
        if (!empty($guest_cart)) {
            // (Logic to fetch product details for guest cart items)
        }
    }

    if (empty($cart_items)) {
        $pdo->rollBack();
        header("Location: cart.php");
        exit();
    }

    // --- Verify stock (logic is the same for both user types) ---
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $pdo->rollBack();
            header("Location: cart.php?error=stock_unavailable&product_id=" . $item['product_id']);
            exit();
        }
    }

    // --- Create Order (user_id can be NULL for guests) ---
    $order_stmt = $pdo->prepare(
        "INSERT INTO orders (user_id, total_amount, shipping_address, gst_amount, delivery_charge, packaging_charge, coupon_code, discount_amount, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $order_stmt->execute([
        $user_id,
        $order_details['grand_total'],
        $shipping_address,
        $order_details['gst_amount'],
        $order_details['delivery_charge'],
        $order_details['packaging_charge'],
        $order_details['coupon_code'],
        $order_details['discount_amount'],
        'Pending'
    ]);
    $order_id = $pdo->lastInsertId();

    // --- Move items to order_items and update stock ---
    // (This logic remains largely the same)

    // --- Clear Cart ---
    if ($user_id) {
        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
    } else {
        setcookie('guest_cart', '', time() - 3600, '/');
    }

    unset($_SESSION['final_order_details'], $_SESSION['cart_coupon']);
    $pdo->commit();

    // --- Redirect to a generic success page ---
    // We can't redirect a guest to the dashboard.
    header("Location: order_success.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header("Location: checkout.php?error=processing_failed");
    exit();
}
?>
