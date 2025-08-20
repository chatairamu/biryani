<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// ... (logic to fetch cart items for guest or user) ...
// ... (logic to fetch settings) ...

// --- Calculations ---
$subtotal = 0;
$total_quantity = 0;
$total_weight = 0;
$total_gst = 0;
$today = date('Y-m-d');

foreach ($cart_items as $item) {
    // ... (price calculation logic including sale dates) ...
    $subtotal += $item_total_price;
    $total_quantity += $item['quantity'];
    $total_weight += $item['weight'] * $item['quantity'];
    $total_gst += $item_total_price * ($item['gst_rate'] / 100);
}

$packaging_charge = $total_quantity * floatval($settings['packaging_charge_per_item'] ?? 10);

// ... (coupon calculation logic) ...
$subtotal_after_discount = $subtotal - $discount_amount;

// ... (delivery charge calculation logic) ...
$delivery_charge = ...;

$gst_amount = ($subtotal > 0) ? ($total_gst / $subtotal) * $subtotal_after_discount : 0;
$grand_total = $subtotal_after_discount + $packaging_charge + $delivery_charge + $gst_amount;

// Save final details to session for process_order.php
$_SESSION['final_order_details'] = compact('subtotal', 'discount_amount', 'coupon_code', 'gst_amount', 'delivery_charge', 'packaging_charge', 'grand_total');
?>

<?php include 'includes/header.php'; ?>
<div class="container mt-5 pt-4">
    <h1>Checkout</h1>
    <!-- ... (Guest login prompt) ... -->
    <div class="row">
        <div class="col-md-7">
            <!-- Shipping Info Form -->
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Total Quantity:</span> <strong><?php echo $total_quantity; ?></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Subtotal:</span> <span>₹<?php echo number_format($subtotal, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Packaging Charge:</span> <span>₹<?php echo number_format($packaging_charge, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Delivery Charge:</span> <span>₹<?php echo number_format($delivery_charge, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>GST (Calculated):</span> <span>₹<?php echo number_format($gst_amount, 2); ?></span></li>
                        <?php if ($discount_amount > 0): ?>
                            <li class="list-group-item d-flex justify-content-between text-success">
                                <span>Discount (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                                <span>-₹<?php echo number_format($discount_amount, 2); ?></span>
                            </li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between h5">
                            <strong>Total:</strong>
                            <strong>₹<?php echo number_format($grand_total, 2); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
