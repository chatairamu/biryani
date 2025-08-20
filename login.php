<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php'; // Include helpers for CSRF

// If user is already logged in, redirect
if (isset($_SESSION['user_id'])) {
    // ... (redirection logic remains the same)
    header("Location: dashboard.php");
    exit();
}

$error = '';
$info_message = '';
$username = '';

// Check for redirection messages
if (isset($_GET['redirect'])) {
    // ... (info message logic remains the same)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- CSRF Validation ---
    validate_csrf_token($_POST['csrf_token']);

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Password is correct, start session
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                // --- CART MERGING LOGIC ---
                if (isset($_COOKIE['guest_cart'])) {
                    $guest_cart = json_decode($_COOKIE['guest_cart'], true);
                    if (is_array($guest_cart)) {
                        foreach ($guest_cart as $item) {
                            // For each item in guest cart, add it to user's db cart
                            // This uses the same logic as add_to_cart.php
                            $options_json = json_encode($item['options']);
                            $stmt_check = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND options = ?");
                            $stmt_check->execute([$user['id'], $item['product_id'], $options_json]);
                            $existing = $stmt_check->fetch();

                            if ($existing) {
                                $new_qty = $existing['quantity'] + $item['quantity'];
                                $stmt_update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                                $stmt_update->execute([$new_qty, $existing['id']]);
                            } else {
                                $stmt_insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, options) VALUES (?, ?, ?, ?)");
                                $stmt_insert->execute([$user['id'], $item['product_id'], $item['quantity'], $options_json]);
                            }
                        }
                    }
                    // Clear the guest cart cookie
                    setcookie('guest_cart', '', time() - 3600, '/');
                }
                // --- END CART MERGING ---

                // Redirect to the appropriate dashboard
                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: Could not log in.";
        }
    }
}

// Generate a new CSRF token for the login form
$csrf_token = generate_csrf_token();
?>

<?php include_once 'includes/header.php'; ?>

<h1>Login</h1>

<?php if (!empty($info_message)): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($info_message); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST" action="login.php">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
</form>

<div class="mt-3">
    <p>Don't have an account? <a href="signup.php">Signup here</a>.</p>
</div>

<?php include_once 'includes/footer.php'; ?>
