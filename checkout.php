<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in to check out
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's cart items
$cart_stmt = $pdo->prepare(
    "SELECT p.name, p.price, c.quantity
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?"
);
$cart_stmt->execute([$user_id]);
$cart_items = $cart_stmt->fetchAll();

// If cart is empty, redirect them to the cart page
if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}

// Fetch user's address
$user_stmt = $pdo->prepare("SELECT username, email, address FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Checkout</h1>
    <div class="row">
        <!-- Shipping and Payment Details -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Shipping Information</h5>
                    <form id="checkout-form" method="POST" action="process_order.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Shipping Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            <small class="form-text text-muted">You can update your default address in the user dashboard.</small>
                        </div>

                        <h5 class="card-title mt-4">Payment Method</h5>
                        <div class="mb-3">
                             <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="cod" value="cash_on_delivery" checked required>
                                <label class="form-check-label" for="cod">Cash on Delivery</label>
                            </div>
                            <small class="form-text text-muted">Other payment methods are currently unavailable.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">Place Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order Summary</h5>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cart_items as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>)
                                <span>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <p class="h5"><strong>Total</strong></p>
                        <p class="h5"><strong>₹<?php echo number_format($total, 2); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
