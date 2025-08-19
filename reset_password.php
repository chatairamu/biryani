<?php
session_start();
require_once 'includes/db_connection.php';

$errors = [];
$success_message = '';
$token_is_valid = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash('sha256', $token);

    // --- Validate the token ---
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ?");
    $stmt->execute([$token_hash]);
    $reset_request = $stmt->fetch();

    if (!$reset_request) {
        $errors[] = "Invalid or expired password reset link.";
    } elseif (time() > $reset_request['expires_at']) {
        $errors[] = "This password reset link has expired. Please request a new one.";
    } else {
        $token_is_valid = true;
    }
} else {
    $errors[] = "No password reset token provided.";
}


// --- Handle New Password Form Submission ---
if ($token_is_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($password) || empty($password_confirm)) {
        $errors[] = "Please enter and confirm your new password.";
    } elseif ($password !== $password_confirm) {
        $errors[] = "The passwords do not match.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } else {
        try {
            $pdo->beginTransaction();

            // Get the user's email from the reset request
            $email = $reset_request['email'];

            // Hash the new password
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Update the user's password in the users table
            $update_stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $update_stmt->execute([$new_password_hash, $email]);

            // Delete the reset token so it can't be used again
            $delete_stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete_stmt->execute([$email]);

            $pdo->commit();
            $success_message = "Your password has been reset successfully! You can now log in with your new password.";
            $token_is_valid = false; // Hide the form after success

        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "A database error occurred. Please try again.";
        }
    }
}

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                 <div class="card-header">
                    <h1>Reset Your Password</h1>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
                        </div>
                        <a href="forgot_password.php">Request a new reset link</a>
                    <?php elseif ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <?php elseif ($token_is_valid): ?>
                        <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                             <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
