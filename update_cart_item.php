<?php
session_start();
require_once 'includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
if ($new_quantity === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid quantity.']);
    exit();
}

if (isset($_SESSION['user_id'])) {
    // --- LOGGED-IN USER: Use database ---
    $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);
    if (!$cart_item_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid cart item ID.']);
        exit();
    }
    // (The database logic from the previous version of this file goes here)
    // ...
    echo json_encode(['success' => true, 'message' => 'DB Cart updated.']);

} else {
    // --- GUEST USER: Use cookies ---
    $cart_key = $_POST['cart_key'] ?? null;
    if (!$cart_key) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid cart key.']);
        exit();
    }

    $cart = isset($_COOKIE['guest_cart']) ? json_decode($_COOKIE['guest_cart'], true) : [];

    if (isset($cart[$cart_key])) {
        if ($new_quantity <= 0) {
            unset($cart[$cart_key]);
        } else {
            $cart[$cart_key]['quantity'] = $new_quantity;
        }
    }

    $total_items = array_sum(array_column($cart, 'quantity'));
    setcookie('guest_cart', json_encode($cart), time() + (86400 * 365), "/");
    echo json_encode(['success' => true, 'message' => 'Guest cart updated.', 'total_items' => $total_items]);
}
?>
