<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// --- Security Check & Initial Setup ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$csrf_token = generate_csrf_token();

// --- Fetch Stats & Alerts (these are loaded once on page load) ---
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'")->fetchColumn() ?: 0;
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;

$low_stock_threshold = 10; // This could be a setting in the future
$low_stock_stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE stock > 0 AND stock < ? ORDER BY stock ASC");
$low_stock_stmt->execute([$low_stock_threshold]);
$low_stock_products = $low_stock_stmt->fetchAll();

// Note: The main table data (products, orders, users) is now fetched via AJAX through api_admin_data.php
// No need to fetch it here anymore.
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-4">
    <h1>Admin Dashboard</h1>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0">Welcome, Admin! Manage your store from here.</p>
        <div class="btn-group">
            <a href="admin_categories.php" class="btn btn-secondary">Categories</a>
            <a href="admin_tags.php" class="btn btn-secondary">Tags</a>
            <a href="admin_coupons.php" class="btn btn-info">Coupons</a>
            <a href="admin_settings.php" class="btn btn-primary">Settings</a>
            <a href="admin_reviews.php" class="btn btn-dark">Reviews</a>
        </div>
    </div>

    <!-- Stats & Alerts Section -->
    <div class="row mb-4">
        <!-- Stats Cards -->
        <div class="col-md-3"><div class="card text-white bg-success"><div class="card-body"><h5 class="card-title">Total Revenue</h5><p class="card-text fs-4">₹<?php echo number_format($total_revenue, 2); ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-info"><div class="card-body"><h5 class="card-title">Total Orders</h5><p class="card-text fs-4"><?php echo $total_orders; ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-primary"><div class="card-body"><h5 class="card-title">Total Users</h5><p class="card-text fs-4"><?php echo $total_users; ?></p></div></div></div>

        <!-- Low Stock Alert -->
        <div class="col-md-3">
            <div class="card <?php echo !empty($low_stock_products) ? 'border-danger' : ''; ?>">
                <div class="card-header">Low Stock Alert</div>
                <div class="card-body" style="max-height: 150px; overflow-y: auto;">
                    <?php if (!empty($low_stock_products)): ?>
                        <ul class="list-unstyled mb-0">
                        <?php foreach ($low_stock_products as $product): ?>
                            <li><strong><?php echo htmlspecialchars($product['name']); ?>:</strong> <?php echo htmlspecialchars($product['stock']); ?> left</li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">All stock levels are healthy.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation"><a class="nav-link active" id="products-tab" data-bs-toggle="tab" href="#" data-table="products">Products</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#" data-table="orders">Orders</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" id="users-tab" data-bs-toggle="tab" href="#" data-table="users">Users</a></li>
    </ul>

    <!-- Reusable Table Structure -->
    <div class="mt-3" id="admin-table-container">
        <div class="d-flex justify-content-center my-5" id="table-loader"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
        <table class="table table-striped table-bordered align-middle" style="display:none;">
            <thead id="admin-table-head"></thead>
            <tbody id="admin-table-body"></tbody>
        </table>
        <div id="admin-table-pagination" class="d-flex justify-content-center"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// The full AJAX JavaScript from the previous implementation goes here.
// It is unchanged.
</script>

<?php include 'includes/footer.php'; ?>
