<?php
session_start();
require_once 'includes/db_connection.php';

$message = '';
$message_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_type = 'danger';
    } else {
        try {
            // Check if the user exists
            $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $user_stmt->execute([$email]);
            $user = $user_stmt->fetch();

            if ($user) {
                // --- Generate a secure token ---
                $token = bin2hex(random_bytes(32));
                // Hash the token for database storage to prevent token hijacking
                $token_hash = hash('sha256', $token);
                // Set an expiry time (e.g., 1 hour from now)
                $expiry_time = time() + 3600;

                // --- Store the token in the database ---
                $reset_stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
                $reset_stmt->execute([$email, $token_hash, $expiry_time, $token_hash, $expiry_time]);

                // --- Construct the reset link ---
                // In a real app, get the base URL from a config file.
                $reset_link = "http://localhost/reset_password.php?token=" . $token;

                // =================================================================
                // === EMAIL SENDING PLACEHOLDER ===================================
                // =================================================================
                //
                // In a production environment, you would use a library like
                // PHPMailer or a service like SendGrid to send the email.
                //
                // The email body would contain the $reset_link.
                //
                // Example using a hypothetical send_email function:
                //
                // $subject = "Password Reset Request";
                // $body = "Click here to reset your password: <a href='{$reset_link}'>{$reset_link}</a>";
                // send_email($email, $subject, $body);
                //
                // For now, we will display the link on the page for testing purposes.
                // **IMPORTANT: DO NOT DO THIS IN PRODUCTION!**
                // =================================================================
                $message = "<strong>For Testing Only:</strong> Password reset link: <a href='{$reset_link}'>{$reset_link}</a>";
                $message_type = 'warning';
            }

            if (empty($message)) {
                // To prevent email enumeration attacks, show a generic success message
                // whether the user was found or not.
                $message = "If an account with that email exists, a password reset link has been sent.";
                $message_type = 'success';
            }

        } catch (PDOException $e) {
            $message = "A database error occurred. Please try again later.";
            $message_type = 'danger';
            // In a real app, you would log this error: error_log($e->getMessage());
        }
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h1>Forgot Password</h1>
                </div>
                <div class="card-body">
                    <p>Enter your email address and we will send you a link to reset your password.</p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="forgot_password.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
