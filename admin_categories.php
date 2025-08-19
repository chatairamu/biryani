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

// --- Handle Form Submissions ---

// Add or Update a Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);

    if (empty($name) || empty($slug)) {
        $errors[] = "Category name and slug are required.";
    } else {
        try {
            if ($category_id) {
                // Update existing category
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $category_id]);
                $success_message = "Category updated successfully.";
            } else {
                // Insert new category
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmt->execute([$name, $slug]);
                $success_message = "Category added successfully.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                $errors[] = "A category with this slug already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Delete a Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    if ($category_id) {
        try {
            // Note: In a real app, you might want to check if any products are using this category first.
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $success_message = "Category deleted successfully.";
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not delete category. It might be in use by products.";
        }
    }
}


// --- Fetch Data for Display ---
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$category_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $category_to_edit = $edit_stmt->fetch();
}

?>

<?php include 'includes/header.php'; ?>

<h1>Manage Categories</h1>
<p>Create and manage product categories to organize your store.</p>

<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4><?php echo $category_to_edit ? 'Edit Category' : 'Add New Category'; ?></h4></div>
            <div class="card-body">
                <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="admin_categories.php">
                    <input type="hidden" name="category_id" value="<?php echo $category_to_edit['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $category_to_edit['name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo $category_to_edit['slug'] ?? ''; ?>" required>
                        <small class="form-text text-muted">A unique, URL-friendly identifier (e.g., "chicken-biryani").</small>
                    </div>
                    <button type="submit" name="save_category" class="btn btn-primary"><?php echo $category_to_edit ? 'Update' : 'Add'; ?> Category</button>
                    <?php if ($category_to_edit): ?>
                        <a href="admin_categories.php" class="btn btn-light">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4>Existing Categories</h4></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                <td>
                                    <a href="admin_categories.php?edit_id=<?php echo $category['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form method="POST" action="admin_categories.php" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                        <button type="submit" name="delete_category" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
