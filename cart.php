<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// ... (logic to fetch cart_items for guest or user) ...

// --- Fetch Settings ---
$settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$packaging_charge_per_item = floatval($settings['packaging_charge_per_item'] ?? 10);
// Note: We use a global GST rate here for simplicity in the cart view.
// The final per-product GST is calculated at checkout.
$gst_rate = floatval($settings['gst_rate'] ?? 5);

// --- Calculate Totals ---
$subtotal = 0;
$total_quantity = 0;
$checkout_disabled = false;

foreach ($cart_items as &$item) {
    // ... (price calculation logic) ...
    $subtotal += $item['total_price'];
    $total_quantity += $item['quantity'];
    if ($item['quantity'] > $item['stock']) {
        $checkout_disabled = true;
    }
}
unset($item);

$packaging_charge = $total_quantity * $packaging_charge_per_item;

// ... (coupon calculation logic) ...

$subtotal_after_discount = $subtotal - $discount_amount;
$gst_amount = $subtotal_after_discount * ($gst_rate / 100);

// Delivery charge is an estimate here; final charge calculated at checkout
$delivery_charge = floatval($settings['delivery_charge_fixed'] ?? 50);

$grand_total = $subtotal_after_discount + $packaging_charge + $delivery_charge + $gst_amount;

?>
<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Your Cart</h1>
    <div class="row">
        <div class="col-lg-8">
            <!-- Cart items display -->
        </div>
        <div class="col-lg-4">
            <?php if (!empty($cart_items)): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between"><span>Total Quantity:</span> <strong id="total-quantity"><?php echo $total_quantity; ?></strong></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Subtotal:</span> <span id="subtotal">₹<?php echo number_format($subtotal, 2); ?></span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Packaging Charge:</span> <span id="packaging-charge">₹<?php echo number_format($packaging_charge, 2); ?></span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>Delivery Charge:</span> <span id="delivery-charge">₹<?php echo number_format($delivery_charge, 2); ?></span></li>
                            <li class="list-group-item d-flex justify-content-between"><span>GST (<?php echo $gst_rate; ?>%):</span> <span id="gst">₹<?php echo number_format($gst_amount, 2); ?></span></li>
                            <?php if ($discount_amount > 0): ?>
                                <li class="list-group-item d-flex justify-content-between text-success">
                                    <span>Discount (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                                    <span id="discount">-₹<?php echo number_format($discount_amount, 2); ?></span>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item d-flex justify-content-between h5">
                                <strong>Total:</strong>
                                <strong id="final-price">₹<?php echo number_format($grand_total, 2); ?></strong>
                            </li>
                        </ul>
                        <hr>
                        <!-- Coupon Form -->
                        <!-- ... -->
                        <div class="d-grid mt-3">
                            <a href="checkout.php" class="btn btn-success <?php if ($checkout_disabled) echo 'disabled'; ?>">
                                <?php echo $checkout_disabled ? 'Please Fix Stock Issues' : 'Proceed to Checkout'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
