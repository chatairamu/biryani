<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in to remove items from the cart
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to manage your cart.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$product_id) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid product ID.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);

    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database error. Could not remove item from cart.']);
        // In a real app, you would log this error.
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
