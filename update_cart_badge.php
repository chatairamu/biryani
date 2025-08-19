<?php
session_start();
require_once 'includes/db_connection.php';

$totalQuantity = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    try {
        // Calculate the total number of items in the cart for the logged-in user
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();

        if ($result && $result['total']) {
            $totalQuantity = $result['total'];
        }

    } catch (PDOException $e) {
        // If there's a database error, we can't get the quantity.
        // In a real app, you would log this error.
        $totalQuantity = 0;
    }
}

echo $totalQuantity;
?>
