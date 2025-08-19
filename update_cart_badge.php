<?php
session_start();

if (isset($_COOKIE['cart'])) {
    $cart = json_decode($_COOKIE['cart'], true);
    $totalQuantity = array_sum($cart);
} else {
    $totalQuantity = 0;
}

// Store total quantity in a cookie
setcookie('totalQuantity', $totalQuantity, time() + (86400 * 30), '/'); // 30 days expiry
echo $totalQuantity;
?>