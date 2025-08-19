<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security Check: User must be logged in ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in to add items to your wishlist.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid Product ID.']);
        exit();
    }

    try {
        // Check if the item is already in the wishlist
        $check_stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check_stmt->execute([$user_id, $product_id]);

        if ($check_stmt->fetch()) {
            // Item already exists
            echo json_encode(['success' => true, 'message' => 'Item is already in your wishlist.']);
        } else {
            // Item does not exist, add it
            $insert_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert_stmt->execute([$user_id, $product_id]);
            echo json_encode(['success' => true, 'message' => 'Product added to your wishlist!']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'A database error occurred.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
