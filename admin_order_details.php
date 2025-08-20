<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security & Validation ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$order_id = $_GET['order_id'];

// --- Fetch Order Details (Admin can view any order) ---
$order_stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = ?"
);
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch();

if (!$order) {
    header("Location: admin_dashboard.php?error=not_found");
    exit();
}

// --- Fetch Order Items ---
$items_stmt = $pdo->prepare(
    "SELECT oi.quantity, oi.price, oi.options, p.name as product_name
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?"
);
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll();
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <a href="admin_dashboard.php#orders" class="btn btn-secondary mb-3">← Back to Orders</a>
    <h1>Order Details</h1>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>Order ID:</strong> #<?php echo htmlspecialchars($order['id']); ?><br>
                    <strong>Customer:</strong> <?php echo htmlspecialchars($order['username']); ?> (<?php echo htmlspecialchars($order['email']); ?>)<br>
                    <strong>Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($order['created_at'])); ?>
                </div>
                <div>
                    <strong>Status:</strong>
                    <form method="POST" action="admin_update_order_status.php" class="d-inline-flex">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <select name="status" class="form-select form-select-sm">
                            <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary ms-2">Update</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            <h5 class="card-title">Items Ordered</h5>
            <table class="table">
                <thead><tr><th>Product</th><th>Quantity</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['product_name']); ?>
                            <?php
                                $options = json_decode($item['options'], true);
                                if ($options && json_last_error() === JSON_ERROR_NONE) {
                                    echo '<br><small class="text-muted">';
                                    foreach ($options as $key => $value) { echo htmlspecialchars($key) . ': ' . htmlspecialchars(strtok($value, '(')) . '<br>'; }
                                    echo '</small>';
                                }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td class="text-end">₹<?php echo number_format($item['price'], 2); ?></td>
                        <td class="text-end">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span>₹<?php $subtotal = $order['total_amount'] - $order['gst_amount'] - $order['delivery_charge'] + $order['discount_amount']; echo number_format($subtotal, 2); ?></span>
                        </li>
                         <?php if ($order['discount_amount'] > 0): ?>
                        <li class="list-group-item d-flex justify-content-between text-success"><span>Discount (<?php echo htmlspecialchars($order['coupon_code']); ?>)</span> <span>- ₹<?php echo number_format($order['discount_amount'], 2); ?></span></li>
                        <?php endif; ?>
                        <li class="list-group-item d-flex justify-content-between"><span>GST</span> <span>+ ₹<?php echo number_format($order['gst_amount'], 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Delivery Charge</span> <span>+ ₹<?php echo number_format($order['delivery_charge'], 2); ?></span></li>
                        <li class="list-group-item d-flex justify-content-between h5"><strong>Grand Total</strong> <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></li>
                    </ul>
                </div>
            </div>
            <hr>
            <h5 class="card-title">Shipping Address</h5>
            <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
