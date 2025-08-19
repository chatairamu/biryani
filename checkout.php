<?php
session_start();

// Get cart data from cookies
$cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];

// Dummy product data (replace with database query later)
$products = [
    1 => [
        'name' => 'Product 1',
        'price' => 100,
        'image' => 'images/product1.jpg',
    ],
    2 => [
        'name' => 'Product 2',
        'price' => 200,
        'image' => 'images/product2.jpg',
    ],
    3 => [
        'name' => 'Product 3',
        'price' => 300,
        'image' => 'images/product3.jpg',
    ],
];

// Calculate total quantity, subtotal, packaging charge, and GST
$totalQuantity = array_sum($cart);
$subtotal = 0;
foreach ($cart as $productId => $quantity) {
    if (isset($products[$productId])) {
        $subtotal += $products[$productId]['price'] * $quantity;
    }
}

// Additional charges
$packagingCharge = 10 * $totalQuantity; // ₹10 per item
$deliveryCharge = 30; // ₹30 per order
$gst = 0.05 * $subtotal; // 5% GST
$totalPrice = $subtotal + $packagingCharge + $deliveryCharge + $gst;

// Handle coupon application (dummy logic)
$couponApplied = false;
$discount = 0;
$appliedCoupon = isset($_COOKIE['applied_coupon']) ? $_COOKIE['applied_coupon'] : null;

if ($appliedCoupon) {
    if ($appliedCoupon === 'SAVE10') {
        $discount = 0.10 * $subtotal;
    } elseif ($appliedCoupon === 'SAVE30') {
        $discount = 0.30 * $subtotal;
    } elseif ($appliedCoupon === 'SAVE50') {
        $discount = 0.50 * $subtotal;
    }
}

// Calculate final price after discount
$finalPrice = $totalPrice - $discount;
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Checkout</h1>
    <div class="row">
        <!-- Order Summary -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <div class="d-flex justify-content-between">
                        <p>Total Quantity:</p>
                        <p><?php echo $totalQuantity; ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p>Subtotal:</p>
                        <p>₹<?php echo number_format($subtotal, 2); ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p>Packaging Charge:</p>
                        <p>₹<?php echo number_format($packagingCharge, 2); ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p>Delivery Charge:</p>
                        <p>₹<?php echo number_format($deliveryCharge, 2); ?></p>
                    </div>
                    <div class="d-flex justify-content-between">
                        <p>GST (5%):</p>
                        <p>₹<?php echo number_format($gst, 2); ?></p>
                    </div>
                    <?php if ($appliedCoupon): ?>
                        <div class="d-flex justify-content-between">
                            <p>Discount (<?php echo $appliedCoupon === 'SAVE10' ? '10%' : ($appliedCoupon === 'SAVE30' ? '30%' : '50%'); ?>):</p>
                            <p class="text-danger">-₹<?php echo number_format($discount, 2); ?></p>
                        </div>
                    <?php endif; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <p><strong>Total:</strong></p>
                        <p><strong>₹<?php echo number_format($finalPrice, 2); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping and Payment Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Shipping and Payment Details</h5>
                    <form id="checkout-form" method="POST" action="process_order.php">
                        <!-- Shipping Address -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="hno" class="form-label">House No./Building</label>
                            <input type="text" class="form-control" id="hno" name="hno" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">City</label>
                            <select class="form-control" id="city" name="city" required>
                            <option value="Warangal">Warangal</option>
                            <option value="Hyderabad">Hyderabad</option>
                            <option value="Bengaluru">Bengaluru</option>
                        </select>
                        </div>
                        <div class="mb-3">
                            <label for="state" class="form-label">State</label>
                            <select class="form-control" id="state" name="state" required>
                            <option value="Telangana">Telangana</option>
                            <option value="andhrapradesh">Andhra Pradesh</option>
                            <option value="Karnataka">Karnataka</option>
                        </select>
                        </div>
                        <div class="mb-3">
                            <label for="pincode" class="form-label">Pincode</label>
                            <input type="text" class="form-control" id="pincode" name="pincode" required>
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cash_on_delivery" checked required>
                                <label class="form-check-label" for="cod">Cash on Delivery</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="upi" value="upi" required>
                                <label class="form-check-label" for="upi">UPI</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="razorpay" value="razorpay" required>
                                <label class="form-check-label" for="razorpay">Razorpay</label>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success w-100">Place Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>