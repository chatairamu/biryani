<?php
session_start();
require_once 'includes/db_connection.php';

// --- Authentication & Cart Check ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$cart_check_stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
$cart_check_stmt->execute([$user_id]);
if ($cart_check_stmt->fetchColumn() == 0) {
    header("Location: cart.php");
    exit();
}

// --- Fetch Data ---
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$cart_items_stmt = $pdo->prepare(
    "SELECT p.name, p.sale_price, p.weight, c.quantity, c.options
     FROM cart c JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?"
);
$cart_items_stmt->execute([$user_id]);
$cart_items = $cart_items_stmt->fetchAll();

// --- Calculations ---
$subtotal = 0;
$total_weight = 0;
foreach ($cart_items as $item) {
    // This calculation should be more robust in a real app, fetching variant prices
    $subtotal += $item['sale_price'] * $item['quantity'];
    $total_weight += $item['weight'] * $item['quantity'];
}

$discount_amount = 0;
$coupon_code = $_SESSION['cart_coupon'] ?? '';
if ($coupon_code) {
    $coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
    $coupon_stmt->execute([$coupon_code]);
    $coupon = $coupon_stmt->fetch();
    if ($coupon) {
        $discount_amount = ($coupon['type'] === 'percentage') ? ($subtotal * floatval($coupon['value']) / 100) : floatval($coupon['value']);
        $discount_amount = min($discount_amount, $subtotal);
    }
}

$subtotal_after_discount = $subtotal - $discount_amount;
$gst_rate = floatval($settings['gst_rate'] ?? 5);
$gst_amount = $subtotal_after_discount * ($gst_rate / 100);

$delivery_charge = 0;
$min_order_free_delivery = floatval($settings['min_order_for_free_delivery'] ?? 0);
if ($min_order_free_delivery > 0 && $subtotal_after_discount >= $min_order_free_delivery) {
    $delivery_charge = 0;
} else {
    if (($settings['delivery_mode'] ?? 'fixed') === 'fixed') {
        $delivery_charge = floatval($settings['delivery_charge_fixed'] ?? 50);
    } else {
        // Per KM logic would need distance. For now, we'll just use the base charge.
        $delivery_charge = floatval($settings['delivery_charge_per_km'] ?? 10) * 10; // Placeholder distance of 10km
    }
    // Add weight-based charge
    $weight_fee = floatval($settings['delivery_charge_weight_fee'] ?? 0);
    $weight_unit = floatval($settings['delivery_charge_weight_unit'] ?? 0);
    if ($weight_fee > 0 && $weight_unit > 0) {
        $delivery_charge += ceil($total_weight / $weight_unit) * $weight_fee;
    }
}

$grand_total = $subtotal_after_discount + $gst_amount + $delivery_charge;
$_SESSION['final_order_details'] = compact('subtotal', 'discount_amount', 'coupon_code', 'gst_amount', 'delivery_charge', 'grand_total');
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Checkout</h1>
    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Shipping & Payment</h5>
                    <form id="checkout-form" method="POST" action="process_order.php">
                        <div class="mb-3">
                            <label class="form-label">Shipping Address</label>
                            <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="form-check"><input class="form-check-input" type="radio" name="payment_method" value="cod" checked><label class="form-check-label">Cash on Delivery</label></div>
                        </div>
                        <div class="d-grid"><button type="submit" class="btn btn-success btn-lg">Place Order</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cart_items as $item): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Subtotal</span> <span>₹<?php echo number_format($subtotal, 2); ?></span></li>
                        <?php if ($discount_amount > 0): ?>
                        <li class="list-group-item d-flex justify-content-between text-success"><span>Discount (<?php echo htmlspecialchars($coupon_code);?>)</span> <span>- ₹<?php echo number_format($discount_amount, 2); ?></span></li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between"><span>GST (<?php echo $gst_rate; ?>%)</span> <span>+ ₹<?php echo number_format($gst_amount, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Delivery Charge</span> <span>+ ₹<?php echo number_format($delivery_charge, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between h5"><strong>Total</strong> <strong>₹<?php echo number_format($grand_total, 2); ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
