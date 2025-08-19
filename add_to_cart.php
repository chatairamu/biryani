<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['id'];
    $quantityChange = $_POST['quantity']; // Can be 1 (add), -1 (remove), or a specific quantity

    // Initialize cart if not exists
    if (!isset($_COOKIE['cart'])) {
        $cart = [];
    } else {
        $cart = json_decode($_COOKIE['cart'], true);
    }

    // Update product quantity in cart
    if (isset($cart[$productId])) {
        $cart[$productId] += $quantityChange;
        if ($cart[$productId] < 0) {
            $cart[$productId] = 0; // Ensure quantity doesn't go below 0
        }
    } else {
        $cart[$productId] = $quantityChange;
    }

    // Remove product if quantity is 0
    if ($cart[$productId] === 0) {
        unset($cart[$productId]);
    }

    // Save cart in cookie
    setcookie('cart', json_encode($cart), time() + (86400 * 30), '/'); // 30 days expiry
    echo json_encode($cart);
}
?>