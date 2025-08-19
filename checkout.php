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
$user = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();
$settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$cart_items_stmt = $pdo->prepare(
    "SELECT p.name, p.mrp, p.sale_price, p.sale_start_date, p.sale_end_date, p.weight, p.gst_rate, c.quantity, c.options
     FROM cart c JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?"
);
$cart_items_stmt->execute([$user_id]);
$cart_items = $cart_items_stmt->fetchAll();

// --- Calculations ---
$subtotal = 0;
$total_weight = 0;
$total_gst = 0;
$today = date('Y-m-d');

foreach ($cart_items as $item) {
    // Determine the correct price to use based on sale dates
    $price_to_use = $item['mrp'];
    if (!empty($item['sale_price']) &&
        (empty($item['sale_start_date']) || $today >= $item['sale_start_date']) &&
        (empty($item['sale_end_date']) || $today <= $item['sale_end_date'])) {
        $price_to_use = $item['sale_price'];
    }

    $item_total_price = $price_to_use * $item['quantity'];
    $subtotal += $item_total_price;
    $total_weight += $item['weight'] * $item['quantity'];
    $total_gst += $item_total_price * ($item['gst_rate'] / 100);
}

// Coupon Logic
$discount_amount = 0;
$coupon_code = $_SESSION['cart_coupon'] ?? '';
if ($coupon_code) {
    $coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())");
    $coupon_stmt->execute([$coupon_code]);
    $coupon = $coupon_stmt->fetch();
    if ($coupon) {
        $discount_amount = ($coupon['type'] === 'percentage') ? ($subtotal * floatval($coupon['value']) / 100) : floatval($coupon['value']);
        $discount_amount = min($discount_amount, $subtotal);
    }
}

$subtotal_after_discount = $subtotal - $discount_amount;
$gst_amount = ($subtotal > 0) ? ($total_gst / $subtotal) * $subtotal_after_discount : 0;

// Delivery Charge Logic (remains the same)
$delivery_charge = 0;
// ...

$grand_total = $subtotal_after_discount + $gst_amount + $delivery_charge;
$_SESSION['final_order_details'] = compact('subtotal', 'discount_amount', 'coupon_code', 'gst_amount', 'delivery_charge', 'grand_total');
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Checkout</h1>
    <div class="row">
        <div class="col-md-7">
            <!-- Shipping and Payment Form remains the same -->
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush">
                        <!-- Item list remains the same -->
                    </ul>
                    <hr>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Subtotal</span> <span>₹<?php echo number_format($subtotal, 2); ?></span></li>
                        <?php if ($discount_amount > 0): ?>
                        <li class="list-group-item d-flex justify-content-between text-success"><span>Discount (<?php echo htmlspecialchars($coupon_code);?>)</span> <span>- ₹<?php echo number_format($discount_amount, 2); ?></span></li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between"><span>GST (Calculated)</span> <span>+ ₹<?php echo number_format($gst_amount, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Delivery Charge</span> <span>+ ₹<?php echo number_format($delivery_charge, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between h5"><strong>Total</strong> <strong>₹<?php echo number_format($grand_total, 2); ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
