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
    $price = trim($_POST['price']);
    $stock = trim($_POST['stock']);
    $image_url = trim($_POST['image_url']);

    if (empty($name) || empty($description) || empty($price) || empty($stock) || empty($image_url)) {
        $errors[] = "All product fields are required.";
    } elseif (!is_numeric($price) || !is_numeric($stock)) {
        $errors[] = "Price and stock must be numbers.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $stock, $image_url]);
            $success_message = "Product added successfully!";
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not add product.";
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
<p>Welcome, Admin! Manage your store from here.</p>

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
                    <div class="col-md-4 mb-3">
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="image_url" class="form-label">Image URL</label>
                        <input type="text" class="form-control" id="image_url" name="image_url" required>
                    </div>
                </div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
        <hr>
        <h2>Existing Products</h2>
        <table class="table table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>₹<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                        <td><a href="#" class="btn btn-sm btn-warning">Edit</a></td>
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
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><a href="#" class="btn btn-sm btn-info">Details</a></td>
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
