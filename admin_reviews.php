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

// --- Handle Delete Review ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $review_id = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT);
    if ($review_id) {
        try {
            // In a real app, deleting a review should trigger a recalculation of the product's average rating.
            // For now, we'll just delete the review itself.
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            $success_message = "Review deleted successfully.";
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not delete review.";
        }
    }
}

// --- Fetch all reviews ---
$reviews_stmt = $pdo->query(
    "SELECT r.id, r.rating, r.comment, r.created_at, p.name as product_name, u.username as user_name
     FROM reviews r
     JOIN products p ON r.product_id = p.id
     JOIN users u ON r.user_id = u.id
     ORDER BY r.created_at DESC"
);
$reviews = $reviews_stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<h1>Manage Product Reviews</h1>
<p>View and moderate all user-submitted product reviews.</p>

<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h4>All Reviews</h4></div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>User</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($review['user_name']); ?></td>
                        <td><?php echo str_repeat('⭐', $review['rating']); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($review['comment'])); ?></td>
                        <td><?php echo date("Y-m-d", strtotime($review['created_at'])); ?></td>
                        <td>
                            <form method="POST" action="admin_reviews.php" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <button type="submit" name="delete_review" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
