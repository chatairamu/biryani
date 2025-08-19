<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // Include helpers

// ... (all data fetching logic remains the same) ...

// Handle Review Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    validate_csrf_token($_POST['csrf_token']);
    if ($has_purchased) {
        // ... (review submission logic remains the same) ...
    }
}

// Generate a new CSRF token for the forms on this page
$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <!-- ... (product display logic) ... -->
    <div class="row">
        <div class="col-md-7">
            <!-- ... (review display logic) ... -->
        </div>
        <div class="col-md-5">
            <h3>Write a Review</h3>
            <?php if(isset($_SESSION['user_id'])): if ($has_purchased): ?>
                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <!-- ... (rest of review form) ... -->
                            <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- ... -->
            <?php endif; else: ?>
                 <!-- ... -->
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ... (JavaScript) ... -->
<?php include 'includes/footer.php'; ?>
