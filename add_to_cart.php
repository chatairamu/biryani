<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in to add items to the cart
if (!isset($_SESSION['user_id'])) {
    // Send an error response and stop execution
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'You must be logged in to manage your cart.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $quantity_change = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$product_id || $quantity_change === null) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid product ID or quantity.']);
        exit();
    }

    try {
        // Check if the product already exists in the user's cart
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch();

        if ($existing_item) {
            // --- Item exists, update quantity ---
            $new_quantity = $existing_item['quantity'] + $quantity_change;

            if ($new_quantity <= 0) {
                // If new quantity is zero or less, remove the item
                $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $delete_stmt->execute([$user_id, $product_id]);
                echo json_encode(['success' => true, 'message' => 'Item removed from cart.']);
            } else {
                // Otherwise, update the quantity
                $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $update_stmt->execute([$new_quantity, $user_id, $product_id]);
                echo json_encode(['success' => true, 'message' => 'Cart updated.']);
            }
        } else {
            // --- Item does not exist, insert new ---
            if ($quantity_change > 0) {
                $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->execute([$user_id, $product_id, $quantity_change]);
                echo json_encode(['success' => true, 'message' => 'Item added to cart.']);
            } else {
                // Do not add item if the initial quantity is zero or negative
                 echo json_encode(['success' => true, 'message' => 'No action taken.']);
            }
        }

    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Database error. Could not update cart.']);
        // In a real app, you would log this error.
    }
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
