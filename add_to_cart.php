<?php
session_start();
require_once 'includes/db_connection.php';

// --- Main Logic ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$quantity_change = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
$options_json = isset($_POST['options']) ? $_POST['options'] : '{}';
$options_array = json_decode($options_json, true);

if (!$product_id || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product data provided.']);
    exit();
}

// Create a unique key for the cart item based on product ID and options
$cart_key = $product_id . '_' . md5($options_json);

// --- HYBRID LOGIC: Check if user is logged in ---
if (isset($_SESSION['user_id'])) {
    // --- LOGGED-IN USER: Use database ---
    $user_id = $_SESSION['user_id'];
    try {
        $pdo->beginTransaction();
        // Note: For simplicity, we're not using the `options` column for logged-in users yet.
        // A full implementation would check product_id AND options.
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch();

        if ($existing_item) {
            $new_quantity = $existing_item['quantity'] + $quantity_change;
            if ($new_quantity <= 0) {
                $pdo->prepare("DELETE FROM cart WHERE id = ?")->execute([$existing_item['id']]);
            } else {
                $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$new_quantity, $existing_item['id']]);
            }
        } elseif ($quantity_change > 0) {
            $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, options) VALUES (?, ?, ?, ?)")->execute([$user_id, $product_id, $quantity_change, $options_json]);
        }

        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_items = $stmt->fetchColumn() ?: 0;

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cart updated.', 'total_items' => $total_items]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }

} else {
    // --- GUEST USER: Use cookies ---
    $cart = isset($_COOKIE['guest_cart']) ? json_decode($_COOKIE['guest_cart'], true) : [];

    if (isset($cart[$cart_key])) {
        $cart[$cart_key]['quantity'] += $quantity_change;
    } else {
        $cart[$cart_key] = [
            'product_id' => $product_id,
            'quantity' => $quantity_change,
            'options' => $options_array
        ];
    }

    if ($cart[$cart_key]['quantity'] <= 0) {
        unset($cart[$cart_key]);
    }

    $total_items = 0;
    foreach ($cart as $item) {
        $total_items += $item['quantity'];
    }

    setcookie('guest_cart', json_encode($cart), time() + (86400 * 365), "/"); // 365-day expiry
    echo json_encode(['success' => true, 'message' => 'Guest cart updated.', 'total_items' => $total_items]);
}
?>
