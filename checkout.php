<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

$user_id = $_SESSION['user_id'] ?? null;
$cart_items = [];
$user = null;

if ($user_id) {
    // --- LOGGED-IN USER ---
    $user = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();
    $cart_stmt = $pdo->prepare("SELECT p.name, p.mrp, p.sale_price, ... FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $cart_stmt->execute([$user_id]);
    $cart_items = $cart_stmt->fetchAll();
} else {
    // --- GUEST USER ---
    $guest_cart = isset($_COOKIE['guest_cart']) ? json_decode($_COOKIE['guest_cart'], true) : [];
    if (!empty($guest_cart)) {
        // (Logic to fetch product details for guest cart items, same as in cart.php)
    }
}

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// ... (All pricing calculation logic remains the same) ...

$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Checkout</h1>

    <?php if (!$user_id): ?>
    <div class="alert alert-info">
        Already have an account? <a href="login.php?redirect=checkout.php">Log In</a> for a faster checkout.
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Shipping Information</h5>
                    <form id="checkout-form" method="POST" action="process_order.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shipping Address</label>
                            <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid"><button type="submit" class="btn btn-success btn-lg">Place Order</button></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <!-- Order Summary Section remains the same -->
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
