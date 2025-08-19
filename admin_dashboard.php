<?php
session_start();
require_once 'includes/db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // If not admin, redirect to user dashboard or login page
    header("Location: " . ($_SESSION['user_id'] ? "dashboard.php" : "login.php"));
    exit();
}

$errors = [];
$success_message = '';

// --- Handle Add Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $mrp = trim($_POST['mrp']);
    $sale_price = trim($_POST['sale_price']);
    $stock = trim($_POST['stock']);
    $weight = trim($_POST['weight']);
    $gst_rate = trim($_POST['gst_rate']);
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($description) || empty($mrp) || empty($sale_price) || empty($stock) || empty($weight) || empty($gst_rate) || empty($image_url)) {
        $errors[] = "All product fields are required.";
    } elseif (!is_numeric($mrp) || !is_numeric($sale_price) || !is_numeric($stock) || !is_numeric($weight) || !is_numeric($gst_rate)) {
        $errors[] = "MRP, Sale Price, Stock, Weight, and GST Rate must be numbers.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO products (name, description, mrp, sale_price, stock, weight, gst_rate, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $description, $mrp, $sale_price, $stock, $weight, $gst_rate, $image_url]);
            $success_message = "Product added successfully!";
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not add product. " . $e->getMessage();
        }
    }
}

// --- Fetch Data for Display ---
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$orders = $pdo->query(
    "SELECT o.id, u.username, o.total_amount, o.status, o.created_at
     FROM orders o
     JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC"
)->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<h1>Admin Dashboard</h1>
<div class="d-flex justify-content-between align-items-center">
    <p class="mb-0">Welcome, Admin! Manage your store from here.</p>
    <div>
        <a href="admin_coupons.php" class="btn btn-info">Manage Coupons</a>
        <a href="admin_settings.php" class="btn btn-primary">Store Settings →</a>
    </div>
</div>

<hr>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab" aria-controls="products" aria-selected="true">Manage Products</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">Manage Orders</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">Manage Users</button>
    </li>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="adminTabContent">
    <!-- Products Tab -->
    <div class="tab-pane fade show active" id="products" role="tabpanel" aria-labelledby="products-tab">
        <div class="my-4">
            <h2>Add New Product</h2>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="POST" action="admin_dashboard.php">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="mrp" class="form-label">MRP (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="mrp" name="mrp" required>
                        <small class="form-text text-muted">Maximum Retail Price (original price).</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="sale_price" class="form-label">Sale Price (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" required>
                        <small class="form-text text-muted">The price at which the product will be sold.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="number" step="0.01" class="form-control" id="weight" name="weight" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="gst_rate" class="form-label">GST Rate (%)</label>
                        <input type="number" step="0.01" class="form-control" id="gst_rate" name="gst_rate" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="image_url" class="form-label">Image URL</label>
                    <input type="text" class="form-control" id="image_url" name="image_url" required>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
        <hr>
        <h2>Existing Products</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>MRP</th>
                    <th>Sale Price</th>
                    <th>Stock</th>
                    <th>Weight (kg)</th>
                    <th>GST (%)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>₹<?php echo htmlspecialchars(number_format($product['mrp'], 2)); ?></td>
                        <td>₹<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                        <td><?php echo htmlspecialchars($product['weight']); ?></td>
                        <td><?php echo htmlspecialchars($product['gst_rate']); ?>%</td>
                        <td><a href="admin_product_variants.php?product_id=<?php echo $product['id']; ?>" class="btn btn-sm btn-warning">Edit Variants</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Orders Tab -->
    <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
        <h2 class="my-4">All Orders</h2>
        <table class="table table-striped">
            <thead>
                <tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['created_at']))); ?></td>
                        <td>₹<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                        <td>
                            <form method="POST" action="admin_update_order_status.php" class="d-flex">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary ms-2">Save</button>
                            </form>
                        </td>
                        <td><a href="admin_order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">Details</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Users Tab -->
    <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
        <h2 class="my-4">All Users</h2>
        <table class="table table-striped">
            <thead>
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars(date("F j, Y", strtotime($user['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<hr class="my-4">
<a href="logout.php" class="btn btn-secondary">Logout</a>

<?php include 'includes/footer.php'; ?>
