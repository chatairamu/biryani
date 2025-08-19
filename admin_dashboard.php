<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security Check: Admin only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

// ... (Add Product logic remains the same) ...

// --- Sorting Logic ---
function get_sort_vars($table_name) {
    $valid_cols = [
        'products' => ['id', 'name', 'mrp', 'sale_price', 'stock', 'weight', 'gst_rate'],
        'orders' => ['id', 'username', 'total_amount', 'status', 'created_at'],
        'users' => ['id', 'username', 'email', 'role', 'created_at']
    ];
    $sort_col = $_GET['sort'] ?? 'id';
    $sort_dir = $_GET['dir'] ?? 'desc';
    if (!in_array($sort_col, $valid_cols[$table_name])) {
        $sort_col = 'id';
    }
    $sort_dir = ($sort_dir === 'asc') ? 'asc' : 'desc';
    return [$sort_col, $sort_dir];
}

// Helper function for creating sortable table headers
function sortable_th($title, $column, $current_sort, $current_dir) {
    $dir = ($current_sort === $column && $current_dir === 'asc') ? 'desc' : 'asc';
    $arrow = ($current_sort === $column) ? (($current_dir === 'asc') ? ' ▲' : ' ▼') : '';
    return '<th><a href="?sort=' . $column . '&dir=' . $dir . '">' . $title . $arrow . '</a></th>';
}

// --- Fetch Data for Display ---

// Statistics
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$top_products = $pdo->query(
    "SELECT p.name, SUM(oi.quantity) as total_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     GROUP BY oi.product_id
     ORDER BY total_sold DESC
     LIMIT 5"
)->fetchAll();

$low_stock_threshold = 10;
$low_stock_products_stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE stock < ? ORDER BY stock ASC");
$low_stock_products_stmt->execute([$low_stock_threshold]);
$low_stock_products = $low_stock_products_stmt->fetchAll();

list($users_sort_col, $users_sort_dir) = get_sort_vars('users');
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY $users_sort_col $users_sort_dir")->fetchAll();

list($products_sort_col, $products_sort_dir) = get_sort_vars('products');
$products = $pdo->query("SELECT * FROM products ORDER BY $products_sort_col $products_sort_dir")->fetchAll();

list($orders_sort_col, $orders_sort_dir) = get_sort_vars('orders');
$orders = $pdo->query("SELECT o.id, u.username, o.total_amount, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY $orders_sort_col $orders_sort_dir")->fetchAll();

$all_categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$all_tags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<h1>Admin Dashboard</h1>
<div class="d-flex justify-content-between align-items-center">
    <p class="mb-0">Welcome, Admin! Manage your store from here.</p>
    <div class="btn-group">
        <a href="admin_categories.php" class="btn btn-secondary">Categories</a>
        <a href="admin_tags.php" class="btn btn-secondary">Tags</a>
        <a href="admin_coupons.php" class="btn btn-info">Coupons</a>
        <a href="admin_settings.php" class="btn btn-primary">Settings</a>
    </div>
</div>
<hr>

<!-- Store Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Revenue</h5>
                <p class="card-text fs-4">₹<?php echo number_format($total_revenue ?? 0, 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Orders</h5>
                <p class="card-text fs-4"><?php echo $total_orders ?? 0; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <p class="card-text fs-4"><?php echo $total_users ?? 0; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">Top Selling Products</div>
            <ul class="list-group list-group-flush">
                <?php foreach($top_products as $product): ?>
                    <li class="list-group-item"><?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['total_sold']; ?> sold)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>


<!-- Low Stock Alerts -->
<?php if (!empty($low_stock_products)): ?>
    <div class="alert alert-warning">...</div>
<?php endif; ?>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button">Products</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">Orders</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">Users</button></li>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="adminTabContent">
    <div class="tab-pane fade show active" id="products" role="tabpanel">
        <!-- Add Product Form... -->
        <hr>
        <h2>Existing Products</h2>
        <table class="table table-striped">
            <thead><tr>
                <?php echo sortable_th('ID', 'id', $products_sort_col, $products_sort_dir); ?>
                <?php echo sortable_th('Name', 'name', $products_sort_col, $products_sort_dir); ?>
                <?php echo sortable_th('Sale Price', 'sale_price', $products_sort_col, $products_sort_dir); ?>
                <?php echo sortable_th('Stock', 'stock', $products_sort_col, $products_sort_dir); ?>
                <th>Actions</th>
            </tr></thead>
            <tbody><!-- product rows --></tbody>
        </table>
    </div>

    <div class="tab-pane fade" id="orders" role="tabpanel">
        <h2 class="my-4">All Orders</h2>
        <table class="table table-striped">
            <thead><tr>
                <?php echo sortable_th('Order ID', 'id', $orders_sort_col, $orders_sort_dir); ?>
                <?php echo sortable_th('Customer', 'username', $orders_sort_col, $orders_sort_dir); ?>
                <?php echo sortable_th('Date', 'created_at', $orders_sort_col, $orders_sort_dir); ?>
                <?php echo sortable_th('Total', 'total_amount', $orders_sort_col, $orders_sort_dir); ?>
                <?php echo sortable_th('Status', 'status', $orders_sort_col, $orders_sort_dir); ?>
                <th>Actions</th>
            </tr></thead>
            <tbody><!-- order rows --></tbody>
        </table>
    </div>

    <div class="tab-pane fade" id="users" role="tabpanel">
        <h2 class="my-4">All Users</h2>
        <table class="table table-striped">
            <thead><tr>
                <?php echo sortable_th('ID', 'id', $users_sort_col, $users_sort_dir); ?>
                <?php echo sortable_th('Username', 'username', $users_sort_col, $users_sort_dir); ?>
                <?php echo sortable_th('Email', 'email', $users_sort_col, $users_sort_dir); ?>
                <?php echo sortable_th('Role', 'role', $users_sort_col, $users_sort_dir); ?>
                <?php echo sortable_th('Registered', 'created_at', $users_sort_col, $users_sort_dir); ?>
            </tr></thead>
            <tbody><!-- user rows --></tbody>
        </table>
    </div>
</div>

<hr class="my-4">
<a href="logout.php" class="btn btn-secondary">Logout</a>
<?php include 'includes/footer.php'; ?>
