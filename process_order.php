<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';
require_once 'includes/address_functions.php';
require_once 'includes/cart_functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit();
}
validate_csrf_token($_POST['csrf_token']);

if (!function_exists('format_address_to_string')) {
    function format_address_to_string($address_data, $name = '') {
        $parts = !empty($name) ? [$name] : [];
        $parts[] = $address_data['address_line_1'];
        if (!empty($address_data['address_line_2'])) $parts[] = $address_data['address_line_2'];
        $parts[] = "{$address_data['city']}, {$address_data['state']} {$address_data['postal_code']}";
        $parts[] = $address_data['country'];
        return implode("\n", array_filter($parts));
    }
}

$user_id = $_SESSION['user_id'] ?? null;
$order_details = $_SESSION['final_order_details'] ?? null;
$shipping_address_string = '';
$customer_email = '';

// --- 1. Determine Shipping Address ---
if ($user_id && isset($_POST['selected_address']) && is_numeric($_POST['selected_address'])) {
    $address_id = (int)$_POST['selected_address'];
    $address = get_address_by_id($pdo, $address_id);
    if ($address && (int)$address['user_id'] === (int)$user_id) {
        $user_stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user_result = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $shipping_address_string = format_address_to_string($address, $user_result['username']);
        $customer_email = $user_result['email'];
    } else {
        header("Location: checkout.php?error=invalid_address"); exit();
    }
} else {
    $new_address_data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'address_line_1' => trim($_POST['address_line_1'] ?? ''),
        'address_line_2' => trim($_POST['address_line_2'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'country' => trim($_POST['country'] ?? 'India'),
        'latitude' => !empty($_POST['latitude']) ? trim($_POST['latitude']) : null,
        'longitude' => !empty($_POST['longitude']) ? trim($_POST['longitude']) : null,
    ];
    if (empty($new_address_data['name']) || empty($new_address_data['email']) || empty($new_address_data['address_line_1'])) {
        header("Location: checkout.php?error=missing_details"); exit();
    }
    $shipping_address_string = format_address_to_string($new_address_data, $new_address_data['name']);
    $customer_email = $new_address_data['email'];
    if ($user_id && isset($_POST['save_address'])) {
        $new_address_data['is_default'] = 0;
        add_user_address($pdo, $user_id, $new_address_data);
    }
}

if (empty($shipping_address_string) || !$order_details) {
    header("Location: checkout.php?error=session_expired"); exit();
}

// --- 2. Process Order (database transaction) ---
try {
    $pdo->beginTransaction();
    $cart_items = get_cart_items($pdo, $user_id, true);
    if (empty($cart_items)) throw new Exception("Cart is empty.");

    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception("Product " . htmlspecialchars($item['name']) . " is out of stock.");
        }
    }

    $order_stmt = $pdo->prepare(
        "INSERT INTO orders (user_id, total_amount, shipping_address, gst_amount, delivery_charge, packaging_charge, coupon_code, discount_amount, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
    );
    $order_stmt->execute([
        $user_id, $order_details['grand_total'], $shipping_address_string,
        $order_details['gst_amount'], $order_details['delivery_charge'], $order_details['packaging_charge'],
        $order_details['coupon_code'], $order_details['discount_amount']
    ]);
    $order_id = $pdo->lastInsertId();

    $order_item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, options, extra_packaging_charge) VALUES (?, ?, ?, ?, ?, ?)");
    $update_stock_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cart_items as $item) {
        $price_info = calculate_product_price($item);
        $order_item_stmt->execute([
            $order_id, $item['product_id'], $item['quantity'], $price_info['price'],
            json_encode($item['options'] ?? []), $item['extra_packaging_charge'] ?? 0.00
        ]);
        $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
    }

    clear_cart($pdo, $user_id);
    unset($_SESSION['final_order_details'], $_SESSION['cart_coupon']);
    $pdo->commit();

    header("Location: order_success.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error_message'] = "Order processing failed: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
}
?>
