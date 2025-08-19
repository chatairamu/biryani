<?php
session_start();
require_once 'includes/db_connection.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// --- Handle Profile Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $latitude = !empty($_POST['latitude']) ? trim($_POST['latitude']) : null;
    $longitude = !empty($_POST['longitude']) ? trim($_POST['longitude']) : null;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, address = ?, latitude = ?, longitude = ? WHERE id = ?");
            $stmt->execute([$email, $address, $latitude, $longitude, $user_id]);
            $success_message = "Profile updated successfully!";
        } catch (PDOException $e) {
            // Check for duplicate email
            if ($e->errorInfo[1] == 1062) {
                $errors[] = "This email address is already in use by another account.";
            } else {
                $errors[] = "Database error: Could not update profile.";
            }
        }
    }
}


// --- Fetch User Data ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- Fetch Order History ---
$order_stmt = $pdo->prepare(
    "SELECT o.id, o.total_amount, o.status, o.created_at, GROUP_CONCAT(p.name SEPARATOR ', ') as products
     FROM orders o
     JOIN order_items oi ON o.id = oi.order_id
     JOIN products p ON oi.product_id = p.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$order_stmt->execute([$user_id]);
$orders = $order_stmt->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<h1>User Dashboard</h1>
<p>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</p>

<hr>

<h2>My Profile</h2>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="dashboard.php">
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
    </div>
    <div class="mb-3">
        <label for="address" class="form-label">Address</label>
        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="latitude" class="form-label">Latitude</label>
            <input type="text" class="form-control" id="latitude" name="latitude" value="<?php echo htmlspecialchars($user['latitude']); ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label for="longitude" class="form-label">Longitude</label>
            <input type="text" class="form-control" id="longitude" name="longitude" value="<?php echo htmlspecialchars($user['longitude']); ?>">
        </div>
    </div>
    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
</form>

<?php if ($user['latitude'] && $user['longitude']): ?>
<div class="mt-4">
    <h3>My Location</h3>
    <p>This map shows your currently saved location.</p>
    <!-- Embedded OpenStreetMap using an iframe -->
    <iframe
        width="100%"
        height="300"
        frameborder="0"
        scrolling="no"
        marginheight="0"
        marginwidth="0"
        src="https://www.openstreetmap.org/export/embed.html?bbox=<?php echo $user['longitude']-0.01; ?>,<?php echo $user['latitude']-0.01; ?>,<?php echo $user['longitude']+0.01; ?>,<?php echo $user['latitude']+0.01; ?>&layer=mapnik&marker=<?php echo $user['latitude']; ?>,<?php echo $user['longitude']; ?>">
    </iframe>
    <br/>
    <small>
        <a href="https://www.openstreetmap.org/?mlat=<?php echo $user['latitude']; ?>&mlon=<?php echo $user['longitude']; ?>#map=16/<?php echo $user['latitude']; ?>/<?php echo $user['longitude']; ?>">View Larger Map</a>
    </small>
</div>
<?php endif; ?>


<hr class="my-4">

<h2>My Order History</h2>
<?php if (empty($orders)): ?>
    <p>You have not placed any orders yet.</p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Products</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($order['created_at']))); ?></td>
                    <td><?php echo htmlspecialchars($order['products']); ?></td>
                    <td>₹<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($order['status']); ?></span></td>
                    <td><a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">View Details</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr class="my-4">

<a href="logout.php" class="btn btn-secondary">Logout</a>

<?php include 'includes/footer.php'; ?>
