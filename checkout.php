<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';
require_once 'includes/cart_functions.php';

$user_id = $_SESSION['user_id'] ?? null;
$cart_items = get_cart_items($pdo, $user_id);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// --- Fetch Settings ---
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// --- Calculations ---
$subtotal = 0;
$total_quantity = 0;
$total_weight = 0;
$total_gst = 0;
$total_packaging_charge = 0;

foreach ($cart_items as &$item) {
    $price_info = calculate_product_price($item, $item['options'] ?? []);
    $item_total_price = $price_info['price'] * $item['quantity'];

    $subtotal += $item_total_price;
    $total_quantity += $item['quantity'];
    $total_weight += ($item['weight'] ?? 0) * $item['quantity'];
    $total_gst += $item_total_price * (($item['gst_rate'] ?? 0) / 100);
    $total_packaging_charge += ($item['extra_packaging_charge'] ?? 0) * $item['quantity'];
}
unset($item);

$packaging_charge = $total_packaging_charge;
$discount_amount = 0; // Placeholder
$coupon_code = ''; // Placeholder
$subtotal_after_discount = $subtotal - $discount_amount;
$delivery_charge = 50; // Placeholder

$gst_amount = ($subtotal > 0) ? ($total_gst / $subtotal) * $subtotal_after_discount : 0;
$grand_total = $subtotal_after_discount + $packaging_charge + $delivery_charge + $gst_amount;

$_SESSION['final_order_details'] = compact('subtotal', 'discount_amount', 'coupon_code', 'gst_amount', 'delivery_charge', 'packaging_charge', 'grand_total');
$csrf_token = generate_csrf_token();
?>

<?php include_once 'includes/header.php'; ?>
<div class="container my-5">
    <h1>Checkout</h1>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Shipping Information</h4>
                    <form action="process_order.php" method="POST" id="checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <?php if ($user_id): ?>
                            <?php
                            require_once 'includes/address_functions.php';
                            $addresses = get_user_addresses($pdo, $user_id);
                            ?>
                            <h5>Select a Delivery Address</h5>
                            <?php if (!empty($addresses)): ?>
                                <?php foreach ($addresses as $address): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="selected_address" id="address_<?php echo $address['id']; ?>" value="<?php echo $address['id']; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="address_<?php echo $address['id']; ?>">
                                            <strong><?php echo htmlspecialchars($address['address_line_1']); ?></strong><br>
                                            <?php echo htmlspecialchars($address['address_line_1']); ?>,
                                            <?php if (!empty($address['address_line_2'])) echo htmlspecialchars($address['address_line_2']) . ', '; ?>
                                            <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> - <?php echo htmlspecialchars($address['postal_code']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="selected_address" id="add_new_address" value="new">
                                    <label class="form-check-label" for="add_new_address">Add a new address</label>
                                </div>
                            <?php endif; ?>

                            <div id="new-address-form" style="<?php echo empty($addresses) ? 'display:block;' : 'display:none;'; ?>">
                                <h5>New Address Details</h5>
                                <div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name"></div>
                                <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email"></div>
                                <div class="mb-3"><label class="form-label">Address Line 1</label><input type="text" class="form-control" name="address_line_1"></div>
                                <div class="mb-3"><label class="form-label">Address Line 2 (Optional)</label><input type="text" class="form-control" name="address_line_2"></div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">City</label><input type="text" class="form-control" name="city"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">State</label><input type="text" class="form-control" name="state"></div>
                                </div>
                                <div class="row">
                                    <_div class="col-md-6 mb-3"><label class="form-label">Postal Code</label><input type="text" class="form-control" name="postal_code"></_div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Country</label><input type="text" class="form-control" name="country" value="India"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input type="text" class="form-control" name="latitude"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input type="text" class="form-control" name="longitude"></div>
                                </div>
                                <div class="form-check mb-3"><input type="checkbox" class="form-check-input" name="save_address" value="1" checked><label class="form-check-label">Save this address</label></div>
                            </div>
                        <?php else: ?>
                            <h5>Shipping Details</h5>
                            <!-- Guest checkout form fields... -->
                        <?php endif; ?>

                        <hr>
                        <h5>Payment Method</h5>
                        <div class="form-check"><input class="form-check-input" type="radio" name="payment_method" id="cod" value="cod" checked><label class="form-check-label" for="cod">Cash on Delivery (COD)</label></div>
                        <button type="submit" class="btn btn-primary w-100 mt-4">Place Order</button>
                    </form>
                </div>
            </div>
            <!-- JS for toggling new address form -->
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Subtotal:</span> <span>₹<?php echo number_format($subtotal, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Packaging:</span> <span>₹<?php echo number_format($packaging_charge, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Delivery:</span> <span>₹<?php echo number_format($delivery_charge, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>GST:</span> <span>₹<?php echo number_format($gst_amount, 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between h5"><strong>Total:</strong><strong>₹<?php echo number_format($grand_total, 2); ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once 'includes/footer.php'; ?>
