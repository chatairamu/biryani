<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate credentials (dummy validation for now)
    if ($username === 'user' && $password === 'password') {
        $_SESSION['user_id'] = 1; // Simulate user ID
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<h1>Login</h1>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>
<form method="POST" action="login.php">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>

<?php include 'includes/footer.php'; ?>