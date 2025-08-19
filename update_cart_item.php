<?php
session_start();
require_once 'includes/db_connection.php';

// --- Function to get total cart items ---
function get_cart_total($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 0;
}

// --- Main Logic ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);
    $new_quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$cart_item_id || $new_quantity === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid data provided.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $verify_stmt = $pdo->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
        $verify_stmt->execute([$cart_item_id, $user_id]);
        if (!$verify_stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied.']);
            exit();
        }

        if ($new_quantity <= 0) {
            $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
            $delete_stmt->execute([$cart_item_id]);
        } else {
            $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update_stmt->execute([$new_quantity, $cart_item_id]);
        }

        $total_items = get_cart_total($pdo, $user_id);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Cart updated.', 'total_items' => $total_items]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}
?>
