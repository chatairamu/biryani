<?php
session_start();
require_once 'includes/db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$errors = [];
$success_message = '';

// --- Handle Settings Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the submitted settings from the form
    $settings_to_update = $_POST['settings'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value) ON DUPLICATE KEY UPDATE setting_value = :value");

        foreach ($settings_to_update as $key => $value) {
            $stmt->execute([':key' => $key, ':value' => trim($value)]);
        }

        $pdo->commit();
        $success_message = "Settings updated successfully!";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Database error: Could not update settings.";
    }
}

// --- Fetch all settings from the database ---
$settings_from_db = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Function to safely get a setting value
function get_setting($key, $default = '') {
    global $settings_from_db;
    return isset($settings_from_db[$key]) ? htmlspecialchars($settings_from_db[$key]) : $default;
}

?>

<?php include 'includes/header.php'; ?>

<h1>Store Settings</h1>
<p>Manage global settings for your e-commerce store.</p>

<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
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


<form method="POST" action="admin_settings.php">
    <div class="card">
        <div class="card-header">
            <h4>Pricing &amp; Taxation</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="gst_rate" class="form-label">GST / Tax Rate (%)</label>
                <input type="number" step="0.01" class="form-control" id="gst_rate" name="settings[gst_rate]" value="<?php echo get_setting('gst_rate'); ?>">
                <small class="form-text text-muted">Enter the tax rate as a percentage, e.g., 5 for 5%.</small>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Delivery Charges</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="delivery_mode" class="form-label">Delivery Charge Mode</label>
                <select class="form-select" id="delivery_mode" name="settings[delivery_mode]">
                    <option value="fixed" <?php echo get_setting('delivery_mode') === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                    <option value="per_km" <?php echo get_setting('delivery_mode') === 'per_km' ? 'selected' : ''; ?>>Per Kilometer</option>
                </select>
            </div>
             <div class="mb-3">
                <label for="delivery_charge_fixed" class="form-label">Fixed Delivery Charge (₹)</label>
                <input type="number" step="0.01" class="form-control" id="delivery_charge_fixed" name="settings[delivery_charge_fixed]" value="<?php echo get_setting('delivery_charge_fixed'); ?>">
                <small class="form-text text-muted">Applied if "Fixed" mode is selected.</small>
            </div>
            <div class="mb-3">
                <label for="delivery_charge_per_km" class="form-label">Per Kilometer Charge (₹)</label>
                <input type="number" step="0.01" class="form-control" id="delivery_charge_per_km" name="settings[delivery_charge_per_km]" value="<?php echo get_setting('delivery_charge_per_km'); ?>">
                 <small class="form-text text-muted">Applied if "Per Kilometer" mode is selected.</small>
            </div>
             <div class="mb-3">
                <label for="min_order_for_free_delivery" class="form-label">Minimum Order for Free Delivery (₹)</label>
                <input type="number" step="0.01" class="form-control" id="min_order_for_free_delivery" name="settings[min_order_for_free_delivery]" value="<?php echo get_setting('min_order_for_free_delivery'); ?>">
                <small class="form-text text-muted">Enter 0 to disable free delivery.</small>
            </div>
             <div class="mb-3">
                <label for="delivery_charge_weight_fee" class="form-label">Additional Charge per Weight Unit (₹)</label>
                <input type="number" step="0.01" class="form-control" id="delivery_charge_weight_fee" name="settings[delivery_charge_weight_fee]" value="<?php echo get_setting('delivery_charge_weight_fee'); ?>">
            </div>
             <div class="mb-3">
                <label for="delivery_charge_weight_unit" class="form-label">Weight Unit (kg)</label>
                <input type="number" step="0.01" class="form-control" id="delivery_charge_weight_unit" name="settings[delivery_charge_weight_unit]" value="<?php echo get_setting('delivery_charge_weight_unit'); ?>">
                <small class="form-text text-muted">The weight (in kg) for each unit of the additional charge above.</small>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary mt-4">Save Settings</button>
</form>


<?php include 'includes/footer.php'; ?>
