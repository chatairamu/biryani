<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // Include helpers for CSRF

// If user is already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$errors = [];
$username = '';
$email = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- CSRF Validation ---
    validate_csrf_token($_POST['csrf_token']);

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // --- Validation ---
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters long.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid email is required.";

    // --- Check for existing user ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        }
    }

    // --- Insert new user ---
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $password_hash, $email, $address]);

            // Log the user in automatically, regenerate session and token
            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);

            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'user';

            header("Location: dashboard.php");
            exit();

        } catch (PDOException $e) {
            $errors[] = "Database error: Could not register user.";
        }
    }
}

// Generate a new CSRF token for the signup form
$csrf_token = generate_csrf_token();
?>

<?php include_once 'includes/header.php'; ?>

<h1>Create an Account</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="POST" action="signup.php">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
        <small class="form-text text-muted">Must be at least 8 characters long.</small>
    </div>
    <div class="mb-3">
        <label for="address" class="form-label">Address (for delivery)</label>
        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Signup</button>
</form>

<div class="mt-3"><p>Already have an account? <a href="login.php">Login here</a>.</p></div>

<?php include_once 'includes/footer.php'; ?>
