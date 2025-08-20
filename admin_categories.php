<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token']);

    if (isset($_POST['save_category'])) {
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name']);
        $slug = trim($_POST['slug']);
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);

        if (empty($name) || empty($slug)) {
            $errors[] = "Category name and slug are required.";
        } else {
            try {
                if ($category_id) {
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, meta_title = ?, meta_description = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $meta_title, $meta_description, $category_id]);
                    $success_message = "Category updated successfully.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, meta_title, meta_description) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $meta_title, $meta_description]);
                    $success_message = "Category added successfully.";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . ($e->errorInfo[1] == 1062 ? "A category with this slug already exists." : $e->getMessage());
            }
        }
    }

    if (isset($_POST['delete_category'])) {
        $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
        if ($category_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $success_message = "Category deleted successfully.";
            } catch (PDOException $e) {
                $errors[] = "Database error: Could not delete category.";
            }
        }
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$category_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $edit_stmt->execute([$_GET['edit_id']]);
    $category_to_edit = $edit_stmt->fetch();
}

$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<h1>Manage Categories</h1>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4><?php echo $category_to_edit ? 'Edit Category' : 'Add New Category'; ?></h4></div>
            <div class="card-body">
                <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" action="admin_categories.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="category_id" value="<?php echo $category_to_edit['id'] ?? ''; ?>">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" class="form-control" name="name" value="<?php echo $category_to_edit['name'] ?? ''; ?>" required></div>
                    <div class="mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" name="slug" value="<?php echo $category_to_edit['slug'] ?? ''; ?>" required></div>
                    <div class="mb-3"><label class="form-label">Meta Title</label><input type="text" class="form-control" name="meta_title" value="<?php echo $category_to_edit['meta_title'] ?? ''; ?>"></div>
                    <div class="mb-3"><label class="form-label">Meta Description</label><textarea class="form-control" name="meta_description" rows="3"><?php echo $category_to_edit['meta_description'] ?? ''; ?></textarea></div>
                    <button type="submit" name="save_category" class="btn btn-primary"><?php echo $category_to_edit ? 'Update' : 'Add'; ?> Category</button>
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
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
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
