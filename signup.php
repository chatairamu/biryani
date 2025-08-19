<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Simulate user registration (dummy logic for now)
    $_SESSION['user_id'] = 1; // Simulate user ID
    header("Location: index.php");
    exit();
}
?>

<?php include 'includes/header.php'; ?>

<h1>Signup</h1>
<form method="POST" action="signup.php">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Signup</button>
</form>

<?php include 'includes/footer.php'; ?>