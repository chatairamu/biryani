<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // Include the new helper file

// --- Security Check: Admin only ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Fetch all reviews ---
// In a real app with many reviews, this would need pagination.
// For now, we fetch all to keep it simple as a first step.
$reviews_stmt = $pdo->query(
    "SELECT r.id, r.comment, r.rating, r.is_approved, r.created_at, u.username, p.name as product_name
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     JOIN products p ON r.product_id = p.id
     ORDER BY r.created_at DESC"
);
$reviews = $reviews_stmt->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Manage Product Reviews</h1>
    <p>Approve or delete reviews submitted by customers.</p>

    <a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Review status updated successfully.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)): ?>
                        <tr><td colspan="7" class="text-center">No reviews submitted yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($review['username']); ?></td>
                                <td><?php echo str_repeat('⭐', $review['rating']); ?></td>
                                <td><?php echo htmlspecialchars(substr($review['comment'], 0, 100)); ?>...</td>
                                <td><?php echo date("Y-m-d", strtotime($review['created_at'])); ?></td>
                                <td>
                                    <?php if ($review['is_approved']): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="admin_update_review.php" class="d-inline">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <?php if (!$review['is_approved']): ?>
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
