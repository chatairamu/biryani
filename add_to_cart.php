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
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $quantity_change = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
    $options_json = isset($_POST['options']) ? $_POST['options'] : '{}';
    $user_id = $_SESSION['user_id'];

    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid product ID.']);
        exit();
    }

    $options_array = json_decode($options_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid options format.']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND options = ?");
        $stmt->execute([$user_id, $product_id, $options_json]);
        $existing_item = $stmt->fetch();

        if ($existing_item) {
            $new_quantity = $existing_item['quantity'] + $quantity_change;
            if ($new_quantity <= 0) {
                $delete_stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
                $delete_stmt->execute([$existing_item['id']]);
            } else {
                $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_stmt->execute([$new_quantity, $existing_item['id']]);
            }
        } else {
            if ($quantity_change > 0) {
                $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, options) VALUES (?, ?, ?, ?)");
                $insert_stmt->execute([$user_id, $product_id, $quantity_change, $options_json]);
            }
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
