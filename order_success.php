<?php
session_start();
require_once 'includes/db_connection.php';

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

if (!$order_id) {
    // If no order ID is provided, just redirect to the homepage.
    header("Location: index.php");
    exit();
}
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title text-success">Order Placed Successfully!</h1>
                    <p class="lead">Thank you for your purchase.</p>
                    <p>Your Order ID is: <strong>#<?php echo htmlspecialchars($order_id); ?></strong></p>
                    <p>You will receive an order confirmation email shortly with the details of your order.</p>
                    <hr>
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-secondary">View Order History</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
