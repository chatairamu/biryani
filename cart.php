<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$coupon_message = '';
$coupon_code = $_SESSION['cart_coupon'] ?? '';
$discount_amount = 0;

// --- Fetch Settings ---
$settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$gst_rate = floatval($settings['gst_rate'] ?? 5);

// --- Handle Coupon Application ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $coupon_code = trim(strtoupper($_POST['coupon_code']));
    if (!empty($coupon_code)) {
        $coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
        $coupon_stmt->execute([$coupon_code]);
        $coupon = $coupon_stmt->fetch();
        if ($coupon) {
            $_SESSION['cart_coupon'] = $coupon_code;
            $coupon_message = "Coupon '" . htmlspecialchars($coupon_code) . "' applied successfully!";
        } else {
            $errors[] = "Invalid or expired coupon code.";
            unset($_SESSION['cart_coupon']);
            $coupon_code = '';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
    unset($_SESSION['cart_coupon']);
    $coupon_code = '';
    $coupon_message = "Coupon removed.";
}


// --- Fetch cart items ---
$cart_stmt = $pdo->prepare(
    "SELECT c.id as cart_item_id, p.id, p.name, p.sale_price, p.image_url, c.quantity, c.options
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?"
);
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

// --- Calculate Totals ---
$subtotal = 0;
foreach ($cart_items as &$item) {
    $item['options_array'] = json_decode($item['options'], true);
    $item_price = $item['sale_price'];
    // In a real app, you would fetch and add price adjustments from options here.
    // For simplicity, we'll assume the price is fixed for now.
    $item['total_price'] = $item_price * $item['quantity'];
    $subtotal += $item['total_price'];
}
unset($item); // Unset reference

// Apply coupon if valid
if (!empty($coupon_code)) {
    $coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
    $coupon_stmt->execute([$coupon_code]);
    $coupon = $coupon_stmt->fetch();
    if ($coupon) {
        if ($coupon['type'] === 'percentage') {
            $discount_amount = ($subtotal * floatval($coupon['value'])) / 100;
        } else {
            $discount_amount = floatval($coupon['value']);
        }
        // Ensure discount is not more than subtotal
        $discount_amount = min($discount_amount, $subtotal);
    }
}

$gst_amount = ($subtotal - $discount_amount) * ($gst_rate / 100);
$grand_total = ($subtotal - $discount_amount) + $gst_amount; // Delivery charge will be added at checkout

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Your Cart</h1>
    <div class="row">
        <div class="col-lg-8">
            <?php if (empty($cart_items)): ?>
                <div class="card"><div class="card-body"><p>Your cart is empty.</p><a href="products.php" class="btn btn-primary">Start Shopping</a></div></div>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                    <div class="card mb-3 product-row" data-cart-item-id="<?php echo $item['cart_item_id']; ?>">
                        <div class="card-body">
                            <div class="d-flex">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                <div class="flex-grow-1 ms-3">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <?php if (!empty($item['options_array'])): ?>
                                        <small class="text-muted">
                                            <?php foreach($item['options_array'] as $key => $val) { echo htmlspecialchars($key) . ': ' . htmlspecialchars($val) . '<br>'; } ?>
                                        </small>
                                    <?php endif; ?>
                                    <p class="mb-0">Price: ₹<?php echo number_format($item['sale_price'], 2); ?></p>
                                    <div class="d-flex align-items-center mt-2">
                                        <button class="btn btn-outline-secondary btn-sm minus-btn">-</button>
                                        <span class="mx-2 quantity"><?php echo $item['quantity']; ?></span>
                                        <button class="btn btn-outline-secondary btn-sm plus-btn">+</button>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0"><strong>₹<?php echo number_format($item['total_price'], 2); ?></strong></p>
                                    <button class="btn btn-danger btn-sm remove-btn mt-2">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <?php if (!empty($cart_items)): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between"><span>Subtotal</span><span>₹<?php echo number_format($subtotal, 2); ?></span></li>
                            <?php if ($discount_amount > 0): ?>
                                <li class="list-group-item d-flex justify-content-between text-success">
                                    <span>Discount (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                                    <span>- ₹<?php echo number_format($discount_amount, 2); ?></span>
                                </li>
                            <?php endif; ?>
                            <li class="list-group-item d-flex justify-content-between"><span>GST (<?php echo $gst_rate; ?>%)</span><span>₹<?php echo number_format($gst_amount, 2); ?></span></li>
                            <li class="list-group-item d-flex justify-content-between"><strong>Grand Total</strong><strong>₹<?php echo number_format($grand_total, 2); ?></strong></li>
                        </ul>
                        <hr>
                        <form method="POST" class="mb-3">
                            <label class="form-label">Apply Coupon</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" placeholder="Enter coupon" value="<?php echo htmlspecialchars($coupon_code); ?>">
                                <button type="submit" name="apply_coupon" class="btn btn-primary">Apply</button>
                            </div>
                        </form>
                        <?php if(!empty($coupon_code)): ?>
                            <form method="POST"><button type="submit" name="remove_coupon" class="btn btn-link text-danger p-0">Remove Coupon</button></form>
                        <?php endif; ?>
                        <?php if ($coupon_message): ?><div class="alert alert-info mt-2 p-2"><?php echo $coupon_message; ?></div><?php endif; ?>
                        <?php if (!empty($errors)): ?><div class="alert alert-danger mt-2 p-2"><?php echo $errors[0]; ?></div><?php endif; ?>
                        <div class="d-grid mt-3"><a href="checkout.php" class="btn btn-success">Proceed to Checkout</a></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function updateCartItem(cartItemId, newQuantity) {
        $.ajax({
            url: 'update_cart_item.php',
            method: 'POST',
            data: {
                cart_item_id: cartItemId,
                quantity: newQuantity
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    location.reload(); // Reload to see changes and recalculate totals
                } else {
                    alert(response.error || 'An error occurred.');
                }
            },
            error: function() {
                alert('A server error occurred. Please try again later.');
                location.reload();
            }
        });
    }

    // Plus button click
    $('.plus-btn').on('click', function() {
        const row = $(this).closest('.product-row');
        const cartItemId = row.data('cart-item-id');
        const quantityElement = row.find('.quantity');
        let quantity = parseInt(quantityElement.text()) + 1;
        updateCartItem(cartItemId, quantity);
    });

    // Minus button click
    $('.minus-btn').on('click', function() {
        const row = $(this).closest('.product-row');
        const cartItemId = row.data('cart-item-id');
        const quantityElement = row.find('.quantity');
        let quantity = parseInt(quantityElement.text()) - 1;
        updateCartItem(cartItemId, quantity); // Script handles deletion if quantity <= 0
    });

    // Remove button click
    $('.remove-btn').on('click', function() {
        const row = $(this).closest('.product-row');
        const cartItemId = row.data('cart-item-id');
        if(confirm('Are you sure you want to remove this item?')) {
            updateCartItem(cartItemId, 0); // Setting quantity to 0 will delete it
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
