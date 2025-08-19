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

// Add or Update a Tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tag'])) {
    $tag_id = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']);
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);

    if (empty($name) || empty($slug)) {
        $errors[] = "Tag name and slug are required.";
    } else {
        try {
            if ($tag_id) {
                // Update existing tag
                $stmt = $pdo->prepare("UPDATE tags SET name = ?, slug = ?, meta_title = ?, meta_description = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $meta_title, $meta_description, $tag_id]);
                $success_message = "Tag updated successfully.";
            } else {
                // Insert new tag
                $stmt = $pdo->prepare("INSERT INTO tags (name, slug, meta_title, meta_description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $meta_title, $meta_description]);
                $success_message = "Tag added successfully.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                $errors[] = "A tag with this slug already exists.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Delete a Tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tag'])) {
    $tag_id = filter_input(INPUT_POST, 'tag_id', FILTER_VALIDATE_INT);
    if ($tag_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$tag_id]);
            $success_message = "Tag deleted successfully.";
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not delete tag. It might be in use by products.";
        }
    }
}


// --- Fetch Data for Display ---
$tags = $pdo->query("SELECT * FROM tags ORDER BY name ASC")->fetchAll();
$tag_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
    $edit_stmt->execute([$edit_id]);
    $tag_to_edit = $edit_stmt->fetch();
}

?>

<?php include 'includes/header.php'; ?>

<h1>Manage Tags</h1>
<p>Create and manage product tags to help users find related items.</p>

<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4><?php echo $tag_to_edit ? 'Edit Tag' : 'Add New Tag'; ?></h4></div>
            <div class="card-body">
                <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="admin_tags.php">
                    <input type="hidden" name="tag_id" value="<?php echo $tag_to_edit['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Tag Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $tag_to_edit['name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" value="<?php echo $tag_to_edit['slug'] ?? ''; ?>" required>
                        <small class="form-text text-muted">A unique, URL-friendly identifier (e.g., "best-seller").</small>
                    </div>
                    <div class="mb-3">
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo $tag_to_edit['meta_title'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo $tag_to_edit['meta_description'] ?? ''; ?></textarea>
                    </div>
                    <button type="submit" name="save_tag" class="btn btn-primary"><?php echo $tag_to_edit ? 'Update' : 'Add'; ?> Tag</button>
                    <?php if ($tag_to_edit): ?>
                        <a href="admin_tags.php" class="btn btn-light">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4>Existing Tags</h4></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Name</th><th>Slug</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tag['name']); ?></td>
                                <td><?php echo htmlspecialchars($tag['slug']); ?></td>
                                <td>
                                    <a href="admin_tags.php?edit_id=<?php echo $tag['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <form method="POST" action="admin_tags.php" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                        <button type="submit" name="delete_tag" class="btn btn-danger btn-sm">Delete</button>
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
