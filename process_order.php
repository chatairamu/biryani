<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in to process an order
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // --- 1. Begin Database Transaction ---
    $pdo->beginTransaction();

    // --- 2. Fetch cart items and lock the rows to prevent changes ---
    $cart_stmt = $pdo->prepare(
        "SELECT p.id, p.price, c.quantity, p.stock
         FROM cart c
         JOIN products p ON c.product_id = p.id
         WHERE c.user_id = ? FOR UPDATE" // Lock rows for this transaction
    );
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll();

    // If cart is empty, rollback and redirect
    if (empty($cart_items)) {
        $pdo->rollBack();
        header("Location: cart.php");
        exit();
    }

    // --- 3. Check stock and calculate total ---
    $total_amount = 0;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            // If not enough stock, rollback and redirect with an error
            $pdo->rollBack();
            header("Location: cart.php?error=stock_unavailable&product_id=" . $item['id']);
            exit();
        }
        $total_amount += $item['price'] * $item['quantity'];
    }

    // --- 4. Create a new order ---
    $order_stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, ?)");
    $order_stmt->execute([$user_id, $total_amount, 'Pending']);
    $order_id = $pdo->lastInsertId();

    // --- 5. Copy cart items to order_items and update stock ---
    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $update_stock_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cart_items as $item) {
        // Add to order_items
        $order_item_stmt->execute([$order_id, $item['id'], $item['quantity'], $item['price']]);
        // Update product stock
        $update_stock_stmt->execute([$item['quantity'], $item['id']]);
    }

    // --- 6. Clear the user's cart ---
    $clear_cart_stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart_stmt->execute([$user_id]);

    // --- 7. Commit the transaction ---
    $pdo->commit();

    // --- 8. Redirect to a success page (the user dashboard) ---
    header("Location: dashboard.php?order_success=true&order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    // If anything goes wrong, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Redirect to checkout with a generic error
    header("Location: checkout.php?error=processing_failed");
    exit();
}
?>
