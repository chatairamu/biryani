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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_coupon'])) {
        $couponCode = strtoupper($_POST['coupon_code']); // Convert to uppercase for case-insensitive comparison
        if ($couponCode === 'SAVE10') { // 10% off
            $couponApplied = true;
            $discount = 0.10 * $subtotal; // Apply discount only to subtotal (product amount)
            setcookie('applied_coupon', $couponCode, time() + (86400 * 30), '/'); // Save coupon in cookie
            $appliedCoupon = $couponCode;
        } elseif ($couponCode === 'SAVE30') { // 30% off
            $couponApplied = true;
            $discount = 0.30 * $subtotal; // Apply discount only to subtotal
            setcookie('applied_coupon', $couponCode, time() + (86400 * 30), '/'); // Save coupon in cookie
            $appliedCoupon = $couponCode;
        } elseif ($couponCode === 'SAVE50') { // 50% off
            $couponApplied = true;
            $discount = 0.50 * $subtotal; // Apply discount only to subtotal
            setcookie('applied_coupon', $couponCode, time() + (86400 * 30), '/'); // Save coupon in cookie
            $appliedCoupon = $couponCode;
        }
    } elseif (isset($_POST['remove_coupon'])) {
        // Remove coupon
        setcookie('applied_coupon', '', time() - 3600, '/'); // Delete coupon cookie
        $appliedCoupon = null;
        $couponApplied = false;
        $discount = 0;
    }
}

// Recalculate discount if a coupon is already applied
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
    <h1>Your Cart</h1>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($cart)): ?>
                        <p>Your cart is empty.</p>
                        <a href="products.php" class="btn btn-primary">Shop Now</a>
                    <?php else: ?>
                        <?php foreach ($cart as $productId => $quantity): ?>
                            <?php if (isset($products[$productId])): ?>
                                <div class="d-flex align-items-center mb-4 product-row" data-id="<?php echo $productId; ?>">
                                    <div class="flex-shrink-0">
                                        <img src="<?php echo $products[$productId]['image']; ?>" alt="<?php echo $products[$productId]['name']; ?>" class="img-thumbnail" style="width: 100px; height: 100px;">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5><?php echo $products[$productId]['name']; ?></h5>
                                        <p>Price: ₹<?php echo number_format($products[$productId]['price'], 2); ?></p>
                                        <div class="d-flex align-items-center">
                                            <button class="btn btn-outline-secondary btn-sm minus-btn" data-id="<?php echo $productId; ?>">-</button>
                                            <span class="mx-2 quantity" data-id="<?php echo $productId; ?>"><?php echo $quantity; ?></span>
                                            <button class="btn btn-outline-secondary btn-sm plus-btn" data-id="<?php echo $productId; ?>">+</button>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <p class="text-end mb-0 product-price" data-id="<?php echo $productId; ?>" data-price="<?php echo $products[$productId]['price']; ?>">
                                            ₹<?php echo number_format($products[$productId]['price'] * $quantity, 2); ?>
                                        </p>
                                        <button class="btn btn-danger btn-sm remove-btn" data-id="<?php echo $productId; ?>">Remove</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($cart)): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <div class="d-flex justify-content-between">
                            <p>Total Quantity:</p>
                            <p id="total-quantity"><?php echo $totalQuantity; ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Subtotal:</p>
                            <p id="subtotal">₹<?php echo number_format($subtotal, 2); ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Packaging Charge:</p>
                            <p id="packaging-charge">₹<?php echo number_format($packagingCharge, 2); ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>Delivery Charge:</p>
                            <p id="delivery-charge">₹<?php echo number_format($deliveryCharge, 2); ?></p>
                        </div>
                        <div class="d-flex justify-content-between">
                            <p>GST (5%):</p>
                            <p id="gst">₹<?php echo number_format($gst, 2); ?></p>
                        </div>
                        <?php if ($appliedCoupon): ?>
                            <div class="d-flex justify-content-between">
                                <p>Discount (<?php echo $appliedCoupon === 'SAVE10' ? '10%' : ($appliedCoupon === 'SAVE30' ? '30%' : '50%'); ?>):</p>
                                <p class="text-danger" id="discount">-₹<?php echo number_format($discount, 2); ?></p>
                            </div>
                            <div class="alert alert-success mt-3">
                                Coupon Applied: <strong><?php echo $appliedCoupon; ?></strong>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="remove_coupon" class="btn btn-link p-0">Remove</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <p><strong>Total:</strong></p>
                            <p><strong id="final-price">₹<?php echo number_format($finalPrice, 2); ?></strong></p>
                        </div>
                        <!-- Coupon Form -->
                        <form method="POST" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" placeholder="<?php echo $appliedCoupon ? $appliedCoupon : 'Enter coupon code'; ?>" <?php echo $appliedCoupon ? 'disabled' : ''; ?>>
                                <button type="submit" name="apply_coupon" class="btn btn-<?php echo $appliedCoupon ? 'success' : 'primary'; ?>" <?php echo $appliedCoupon ? 'disabled' : ''; ?>>
                                    <?php echo $appliedCoupon ? 'Applied' : 'Apply'; ?>
                                </button>
                            </div>
                        </form>
                        <!-- Proceed to Checkout Button -->
                        <div class="mb-5">
                            <a href="checkout.php" class="btn btn-success w-100">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function updateCartBadge() {
        $.ajax({
            url: 'update_cart_badge.php',
            method: 'GET',
            success: function(response) {
                $('#cart-badge').text(response);
            }
        });
    }

    function updateOrderSummary() {
        $.ajax({
            url: 'cart.php',
            method: 'GET',
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');
                const summary = {
                    totalQuantity: doc.getElementById('total-quantity').innerText,
                    subtotal: doc.getElementById('subtotal').innerText,
                    packagingCharge: doc.getElementById('packaging-charge').innerText,
                    deliveryCharge: doc.getElementById('delivery-charge').innerText,
                    gst: doc.getElementById('gst').innerText,
                    discount: doc.getElementById('discount') ? doc.getElementById('discount').innerText : '-₹0.00',
                    finalPrice: doc.getElementById('final-price').innerText,
                };

                $('#total-quantity').text(summary.totalQuantity);
                $('#subtotal').text(summary.subtotal);
                $('#packaging-charge').text(summary.packagingCharge);
                $('#delivery-charge').text(summary.deliveryCharge);
                $('#gst').text(summary.gst);
                if (summary.discount) {
                    $('#discount').text(summary.discount);
                }
                $('#final-price').text(summary.finalPrice);
            }
        });
    }

    function updateProductPrice(productId) {
        let quantity = parseInt($(`.quantity[data-id="${productId}"]`).text());
        let price = parseFloat($(`.product-price[data-id="${productId}"]`).data('price'));
        let newPrice = quantity * price;
        $(`.product-price[data-id="${productId}"]`).text(`₹${newPrice.toFixed(2)}`);
    }

    $(document).on('click', '.plus-btn', function() {
        const productId = $(this).data('id');
        const quantityElement = $(`.quantity[data-id="${productId}"]`);
        let quantity = parseInt(quantityElement.text());
        quantity += 1;
        quantityElement.text(quantity);
        updateProductPrice(productId);

        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { id: productId, quantity: 1 },
            success: function(response) {
                updateCartBadge();
                updateOrderSummary();
            }
        });
    });

    $(document).on('click', '.minus-btn', function() {
        const productId = $(this).data('id');
        const quantityElement = $(`.quantity[data-id="${productId}"]`);
        let quantity = parseInt(quantityElement.text());
        if (quantity > 0) {
            quantity -= 1;
            quantityElement.text(quantity);
            updateProductPrice(productId);

            if (quantity === 0) {
                $.ajax({
                    url: 'remove_from_cart.php',
                    method: 'POST',
                    data: { id: productId },
                    success: function(response) {
                        location.reload();
                    }
                });
            }

            $.ajax({
                url: 'add_to_cart.php',
                method: 'POST',
                data: { id: productId, quantity: -1 },
                success: function(response) {
                    updateCartBadge();
                    updateOrderSummary();
                }
            });
        }
    });

    $('.remove-btn').click(function() {
        const productId = $(this).data('id');
        $.ajax({
            url: 'remove_from_cart.php',
            method: 'POST',
            data: { id: productId },
            success: function(response) {
                location.reload();
            }
        });
    });

    updateCartBadge();
    updateOrderSummary();
});
</script>

<?php include 'includes/footer.php'; ?>