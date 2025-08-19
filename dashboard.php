<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // Include helpers for CSRF

// ... (user authentication check) ...
$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// --- Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    validate_csrf_token($_POST['csrf_token']);
    // ... (rest of the update logic remains the same)
}

// ... (data fetching logic remains the same) ...

// Generate a new CSRF token for the form
$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<h1>User Dashboard</h1>
<!-- ... (rest of the top part of the page) ... -->

<form method="POST" action="dashboard.php">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <!-- ... (rest of the form fields remain the same) ... -->
    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
</form>

<!-- ... (rest of the page) ... -->
<?php include 'includes/footer.php'; ?>
