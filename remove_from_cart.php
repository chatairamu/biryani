<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['id'];

    // Get cart data from cookies
    if (isset($_COOKIE['cart'])) {
        $cart = json_decode($_COOKIE['cart'], true);
        if (isset($cart[$productId])) {
            unset($cart[$productId]); // Remove the product
            setcookie('cart', json_encode($cart), time() + (86400 * 30), '/'); // Update cookie
        }
    }
    echo json_encode($cart);
}
?>