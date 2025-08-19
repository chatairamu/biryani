<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security & Helpers ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
function get_sort_vars($table_name, $valid_cols) {
    $sort_col = $_GET['sort'] ?? 'id';
    $sort_dir = $_GET['dir'] ?? 'desc';
    if (!in_array($sort_col, $valid_cols)) $sort_col = 'id';
    $sort_dir = ($sort_dir === 'asc') ? 'asc' : 'desc';
    return [$sort_col, $sort_dir];
}
function sortable_th($title, $column, $current_sort, $current_dir) {
    $dir = ($current_sort === $column && $current_dir === 'asc') ? 'desc' : 'asc';
    $arrow = ($current_sort === $column) ? (($current_dir === 'asc') ? ' ▲' : ' ▼') : '';
    return '<th><a href="?sort=' . $column . '&dir=' . $dir . '">' . $title . $arrow . '</a></th>';
}

$errors = [];
$success_message = '';

// --- Handle Add Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $pdo->beginTransaction();
    try {
        $name = trim($_POST['name']);
        // ... (other fields)
        $sale_start = !empty($_POST['sale_price_start_date']) ? $_POST['sale_price_start_date'] : null;
        $sale_end = !empty($_POST['sale_price_end_date']) ? $_POST['sale_price_end_date'] : null;
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);

        // (Validation logic should be here)

        $stmt = $pdo->prepare(
            "INSERT INTO products (name, description, mrp, sale_price, sale_price_start_date, sale_price_end_date, stock, weight, gst_rate, meta_title, meta_description, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$_POST['name'], $_POST['description'], $_POST['mrp'], $_POST['sale_price'], $sale_start, $sale_end, $_POST['stock'], $_POST['weight'], $_POST['gst_rate'], $meta_title, $meta_description, $_POST['image_url']]);
        $product_id = $pdo->lastInsertId();

        // (Category and Tag logic remains the same)

        $pdo->commit();
        $success_message = "Product added successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// --- Fetch Data ---
// (Stats queries remain the same)

$valid_product_cols = ['id', 'name', 'mrp', 'sale_price', 'stock', 'weight', 'gst_rate'];
list($p_sort, $p_dir) = get_sort_vars('products', $valid_product_cols);
$products = $pdo->query("SELECT * FROM products ORDER BY $p_sort $p_dir")->fetchAll();

// (Other data fetching remains the same)
?>

<?php include 'includes/header.php'; ?>

<h1>Admin Dashboard</h1>
<!-- (Header and stats section remain the same) -->
<hr>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button">Manage Products</button></li>
    <!-- Other tabs -->
</ul>

<!-- Tab panes -->
<div class="tab-content" id="adminTabContent">
    <div class="tab-pane fade show active" id="products" role="tabpanel">
        <div class="my-4">
            <h2>Add New Product</h2>
            <!-- (Error/Success messages) -->
            <form method="POST" action="admin_dashboard.php">
                <!-- Basic Info -->
                <div class="mb-3"><label class="form-label">Product Name</label><input type="text" class="form-control" name="name" required></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" required></textarea></div>

                <!-- Pricing -->
                <div class="row">
                    <div class="col-md-6"><label class="form-label">MRP (₹)</label><input type="number" step="0.01" class="form-control" name="mrp" required></div>
                    <div class="col-md-6"><label class="form-label">Sale Price (₹)</label><input type="number" step="0.01" class="form-control" name="sale_price" required></div>
                </div>

                <!-- Sale Scheduling -->
                <div class="row mt-3">
                    <div class="col-md-6"><label class="form-label">Sale Start Date (Optional)</label><input type="date" class="form-control" name="sale_price_start_date"></div>
                    <div class="col-md-6"><label class="form-label">Sale End Date (Optional)</label><input type="date" class="form-control" name="sale_price_end_date"></div>
                </div>

                <!-- Attributes -->
                <div class="row mt-3">
                    <div class="col-md-4"><label class="form-label">Stock</label><input type="number" class="form-control" name="stock" required></div>
                    <div class="col-md-4"><label class="form-label">Weight (kg)</label><input type="number" step="0.01" class="form-control" name="weight" required></div>
                    <div class="col-md-4"><label class="form-label">GST Rate (%)</label><input type="number" step="0.01" class="form-control" name="gst_rate" required></div>
                </div>

                <!-- Taxonomies -->
                <!-- (Categories and Tags checkboxes remain the same) -->

                <!-- SEO -->
                <div class="mt-4">
                    <h5>SEO Settings</h5>
                    <div class="mb-3"><label class="form-label">Meta Title</label><input type="text" class="form-control" name="meta_title"></div>
                    <div class="mb-3"><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="2"></textarea></div>
                </div>

                <div class="mb-3 mt-3"><label class="form-label">Image URL</label><input type="text" class="form-control" name="image_url" required></div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
        <hr>
        <!-- Existing Products Table -->
        <h2>Existing Products</h2>
        <!-- (Table structure with sortable headers remains the same) -->
    </div>
    <!-- Other Tabs -->
</div>

<hr class="my-4">
<a href="logout.php" class="btn btn-secondary">Logout</a>
<?php include 'includes/footer.php'; ?>
