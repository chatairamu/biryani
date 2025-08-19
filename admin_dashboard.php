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

// --- Handle Add Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $pdo->beginTransaction();
    try {
        // ... (validation from previous step remains the same)
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $mrp = trim($_POST['mrp']);
        $sale_price = trim($_POST['sale_price']);
        $stock = trim($_POST['stock']);
        $weight = trim($_POST['weight']);
        $gst_rate = trim($_POST['gst_rate']);
        $image_url = trim($_POST['image_url']);
        $categories = $_POST['categories'] ?? [];
        $tags = $_POST['tags'] ?? [];

        // Insert into products table
        $stmt = $pdo->prepare(
            "INSERT INTO products (name, description, mrp, sale_price, stock, weight, gst_rate, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $description, $mrp, $sale_price, $stock, $weight, $gst_rate, $image_url]);
        $product_id = $pdo->lastInsertId();

        // Insert into product_categories linking table
        if (!empty($categories)) {
            $cat_stmt = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            foreach ($categories as $category_id) {
                $cat_stmt->execute([$product_id, $category_id]);
            }
        }

        // Insert into product_tags linking table
        if (!empty($tags)) {
            $tag_stmt = $pdo->prepare("INSERT INTO product_tags (product_id, tag_id) VALUES (?, ?)");
            foreach ($tags as $tag_id) {
                $tag_stmt->execute([$product_id, $tag_id]);
            }
        }

        $pdo->commit();
        $success_message = "Product added successfully!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Database error: Could not add product. " . $e->getMessage();
    }
}

// --- Fetch Data for Display ---
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$orders = $pdo->query("SELECT o.id, u.username, o.total_amount, o.status, o.created_at FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
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
        <a href="admin_reviews.php" class="btn btn-secondary">Reviews</a>
        <a href="admin_coupons.php" class="btn btn-info">Coupons</a>
        <a href="admin_settings.php" class="btn btn-primary">Settings</a>
    </div>
</div>
<hr>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation"><button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button">Manage Products</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">Manage Orders</button></li>
    <li class="nav-item" role="presentation"><button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">Manage Users</button></li>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="adminTabContent">
    <div class="tab-pane fade show active" id="products" role="tabpanel">
        <div class="my-4">
            <h2>Add New Product</h2>
            <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="POST" action="admin_dashboard.php">
                <!-- Text fields for name, description, etc. -->
                <div class="mb-3"><label for="name" class="form-label">Product Name</label><input type="text" class="form-control" id="name" name="name" required></div>
                <div class="mb-3"><label for="description" class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="3" required></textarea></div>

                <!-- Pricing fields -->
                <div class="row">
                    <div class="col-md-6 mb-3"><label for="mrp" class="form-label">MRP (₹)</label><input type="number" step="0.01" class="form-control" id="mrp" name="mrp" required></div>
                    <div class="col-md-6 mb-3"><label for="sale_price" class="form-label">Sale Price (₹)</label><input type="number" step="0.01" class="form-control" id="sale_price" name="sale_price" required></div>
                </div>

                <!-- Stock, Weight, GST -->
                <div class="row">
                    <div class="col-md-4 mb-3"><label for="stock" class="form-label">Stock</label><input type="number" class="form-control" id="stock" name="stock" required></div>
                    <div class="col-md-4 mb-3"><label for="weight" class="form-label">Weight (kg)</label><input type="number" step="0.01" class="form-control" id="weight" name="weight" required></div>
                    <div class="col-md-4 mb-3"><label for="gst_rate" class="form-label">GST Rate (%)</label><input type="number" step="0.01" class="form-control" id="gst_rate" name="gst_rate" required></div>
                </div>

                <!-- Categories Checkboxes -->
                <div class="mb-3">
                    <label class="form-label">Categories</label>
                    <div class="border p-2 rounded" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach ($all_categories as $category): ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="cat_<?php echo $category['id']; ?>"><label class="form-check-label" for="cat_<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></label></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tags Checkboxes -->
                <div class="mb-3">
                    <label class="form-label">Tags</label>
                     <div class="border p-2 rounded" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach ($all_tags as $tag): ?>
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="tag_<?php echo $tag['id']; ?>"><label class="form-check-label" for="tag_<?php echo $tag['id']; ?>"><?php echo htmlspecialchars($tag['name']); ?></label></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3"><label for="image_url" class="form-label">Image URL</label><input type="text" class="form-control" id="image_url" name="image_url" required></div>
                <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
            </form>
        </div>
        <hr>
        <!-- Existing Products Table remains here -->
        <h2>Existing Products</h2>
        <table class="table table-striped">
            <thead>
                <tr><th>ID</th><th>Name</th><th>MRP</th><th>Sale Price</th><th>Stock</th><th>Weight (kg)</th><th>GST (%)</th><th>Actions</th></tr>
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
    <!-- Other tabs (Orders, Users) remain here -->
    <div class="tab-pane fade" id="orders" role="tabpanel">...</div>
    <div class="tab-pane fade" id="users" role="tabpanel">...</div>
</div>

<hr class="my-4">
<a href="logout.php" class="btn btn-secondary">Logout</a>
<?php include 'includes/footer.php'; ?>
