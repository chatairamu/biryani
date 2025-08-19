<?php
session_start();



// Get the order IDs from the cookie, which is stored as a comma-separated string.
$userOrderIds = explode(',', $_COOKIE['order_id']);

// Define the orders folder
$ordersFolder = 'orders';

// Get all order files
$orderFiles = glob($ordersFolder . '/*.json');

// Filter orders based on the user's order IDs
$userOrderFiles = [];
foreach ($orderFiles as $orderFile) {
    $orderDetails = json_decode(file_get_contents($orderFile), true);
    if (isset($orderDetails['order_id']) && in_array($orderDetails['order_id'], $userOrderIds)) {
        $userOrderFiles[] = $orderFile;
    }
}

// Sort order files by modification time (newest first)
usort($userOrderFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Your Orders</h1>
    <div class="row">
        <div class="col-md-12">
            
            <?php // Check if the user has order ID cookies (multiple orders)
if (!isset($_COOKIE['order_id'])) {
    echo "<h1>No orders found.</h1>";
    echo "<p><a href='products.php'>Continue Shopping</a></p>";
} ?>
           
            <div class="card">
                <div class="card-body">
                    <?php if (empty($userOrderFiles)): ?>
                        <p>No orders found for your account.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($userOrderFiles as $orderFile): ?>
                                <?php
                                    $orderDetails = json_decode(file_get_contents($orderFile), true);
                                    $orderId = $orderDetails['order_id'];
                                    $orderDate = $orderDetails['timestamp'];
                                    $orderTotal = isset($orderDetails['total']) ? $orderDetails['total'] : 100;
                                    $orderStatus = isset($orderDetails['status']) ? $orderDetails['status'] : "Processing";
                                ?>
                                <a href="order-details.php?order_id=<?php echo $orderId; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Order ID: <?php echo $orderId; ?></h5>
                                        <small><?php echo $orderDate; ?></small>
                                    </div>
                                    <p class="mb-1">Total: $<?php echo number_format($orderTotal, 2); ?></p>
                                    <small>Status: <?php echo $orderStatus; ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>