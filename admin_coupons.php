<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_token($_POST['csrf_token']);

    if (isset($_POST['add_coupon'])) {
        $code = trim(strtoupper($_POST['code']));
        $type = $_POST['type'];
        $value = trim($_POST['value']);
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

        if (!empty($code) && !empty($type) && is_numeric($value)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$code, $type, $value, $start_date, $end_date]);
                $success_message = "Coupon '" . htmlspecialchars($code) . "' added successfully.";
            } catch (PDOException $e) {
                $errors[] = "Database error: " . ($e->errorInfo[1] == 1062 ? "Coupon code must be unique." : "Could not add coupon.");
            }
        } else {
            $errors[] = "Coupon code, type, and a numeric value are required.";
        }
    }

    if (isset($_POST['delete_coupon'])) {
        $coupon_id = filter_input(INPUT_POST, 'coupon_id', FILTER_VALIDATE_INT);
        if ($coupon_id) {
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$coupon_id]);
            $success_message = "Coupon deleted successfully.";
        }
    }
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
$csrf_token = generate_csrf_token();
?>

<?php include_once 'includes/header.php'; ?>

<h1>Manage Coupons</h1>
<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4>Add New Coupon</h4></div>
            <div class="card-body">
                <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" action="admin_coupons.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3"><label class="form-label">Coupon Code</label><input type="text" class="form-control" name="code" required></div>
                    <div class="mb-3"><label class="form-label">Discount Type</label><select class="form-select" name="type"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (₹)</option></select></div>
                    <div class="mb-3"><label class="form-label">Value</label><input type="number" step="0.01" class="form-control" name="value" required></div>
                    <div class="mb-3"><label class="form-label">Start Date (Optional)</label><input type="date" class="form-control" name="start_date"></div>
                    <div class="mb-3"><label class="form-label">End Date (Optional)</label><input type="date" class="form-control" name="end_date"></div>
                    <button type="submit" name="add_coupon" class="btn btn-primary">Add Coupon</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4>Existing Coupons</h4></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Dates</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                <td><?php echo htmlspecialchars(ucfirst($coupon['type'])); ?></td>
                                <td><?php echo $coupon['type'] === 'percentage' ? htmlspecialchars($coupon['value']) . '%' : '₹' . htmlspecialchars(number_format($coupon['value'], 2)); ?></td>
                                <td><?php echo $coupon['start_date'] ? htmlspecialchars($coupon['start_date']) : '...'; ?> → <?php echo $coupon['end_date'] ? htmlspecialchars($coupon['end_date']) : '...'; ?></td>
                                <td>
                                    <form method="POST" action="admin_coupons.php" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                        <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
