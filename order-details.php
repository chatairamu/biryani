<?php
session_start();

// Check if the order ID is provided in the URL
if (!isset($_GET['order_id'])) {
    echo "<h1>Order not found.</h1>";
    echo "<p><a href='orders.php'>Back to Orders</a></p>";
    exit;
}

// Get the order ID from the URL
$orderId = $_GET['order_id'];

// Define the orders folder
$ordersFolder = 'orders';

// Define the order file path
$orderFilePath = $ordersFolder . '/' . $orderId . '.json';

// Check if the order file exists
if (!file_exists($orderFilePath)) {
    echo "<h1>Order not found.</h1>";
    echo "<p><a href='orders.php'>Back to Orders</a></p>";
    exit;
}

// Get the order details
$orderDetails = json_decode(file_get_contents($orderFilePath), true);
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Order Details</h1>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Order ID: <?php echo $orderDetails['order_id']; ?></h5>
                    <p><strong>Date:</strong> <?php echo $orderDetails['timestamp']; ?></p>
                    <p><strong>Name:</strong> <?php echo $orderDetails['name']; ?></p>
                    <p><strong>Address:</strong> <?php echo $orderDetails['hno']; ?>, <?php echo $orderDetails['address']; ?>, <?php echo $orderDetails['city']; ?>, <?php echo $orderDetails['state']; ?> - <?php echo $orderDetails['pincode']; ?></p>
                    <p><strong>Mobile:</strong> <?php echo $orderDetails['mobile']; ?></p>
                    <p><strong>Email:</strong> <?php echo $orderDetails['email']; ?></p>
                    <p><strong>Payment Method:</strong> <?php echo $orderDetails['payment_method']; ?></p>
                    <hr>
                    <a href="orders.php" class="btn btn-primary">Back to Orders</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>