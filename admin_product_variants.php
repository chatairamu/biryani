<?php
session_start();
require_once 'includes/db_connection.php';

// --- Security Check ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- Get Product ID ---
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}
$product_id = $_GET['product_id'];

// --- Fetch Product Details ---
$product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch();
if (!$product) {
    header("Location: admin_dashboard.php");
    exit();
}

$errors = [];
$success_message = '';

// --- Handle Form Submissions ---

// Add a new option type (e.g., "Size")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_option'])) {
    $option_name = trim($_POST['option_name']);
    if (!empty($option_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO product_options (product_id, name) VALUES (?, ?)");
            $stmt->execute([$product_id, $option_name]);
            $success_message = "Option '" . htmlspecialchars($option_name) . "' added successfully.";
        } catch (PDOException $e) {
            $errors[] = "Could not add option. It might already exist for this product.";
        }
    } else {
        $errors[] = "Option name cannot be empty.";
    }
}

// Add a new value to an existing option (e.g., "Large" for "Size")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_option_value'])) {
    $option_id = $_POST['option_id'];
    $value_name = trim($_POST['value_name']);
    $price_adjustment = trim($_POST['price_adjustment']);

    if (!empty($value_name) && is_numeric($price_adjustment)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO product_option_values (option_id, value, price_adjustment) VALUES (?, ?, ?)");
            $stmt->execute([$option_id, $value_name, $price_adjustment]);
            $success_message = "Value '" . htmlspecialchars($value_name) . "' added successfully.";
        } catch (PDOException $e) {
            $errors[] = "Could not add value.";
        }
    } else {
        $errors[] = "Value name must not be empty and price adjustment must be a number.";
    }
}

// Delete an option type
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option'])) {
    $option_id = $_POST['option_id'];
    // Deleting an option will cascade and delete all its values due to foreign key constraints
    $stmt = $pdo->prepare("DELETE FROM product_options WHERE id = ? AND product_id = ?");
    $stmt->execute([$option_id, $product_id]);
    $success_message = "Option deleted successfully.";
}

// Delete an option value
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option_value'])) {
    $value_id = $_POST['value_id'];
    $stmt = $pdo->prepare("DELETE FROM product_option_values WHERE id = ?");
    $stmt->execute([$value_id]);
    $success_message = "Value deleted successfully.";
}


// --- Fetch Data for Display ---
$options_stmt = $pdo->prepare(
    "SELECT po.id, po.name, pov.id as value_id, pov.value, pov.price_adjustment
     FROM product_options po
     LEFT JOIN product_option_values pov ON po.id = pov.option_id
     WHERE po.product_id = ?
     ORDER BY po.id, pov.id"
);
$options_stmt->execute([$product_id]);
$results = $options_stmt->fetchAll();

// Group the results by option for easier display
$options_with_values = [];
foreach ($results as $row) {
    $options_with_values[$row['id']]['name'] = $row['name'];
    if ($row['value_id']) {
        $options_with_values[$row['id']]['values'][] = [
            'id' => $row['value_id'],
            'value' => $row['value'],
            'price_adjustment' => $row['price_adjustment']
        ];
    }
}

?>

<?php include_once 'includes/header.php'; ?>

<h1>Manage Variants for: <?php echo htmlspecialchars($product['name']); ?></h1>
<p>Add options like 'Size' or 'Spice Level', then add corresponding values like 'Large' or 'Spicy' with price adjustments.</p>

<a href="admin_dashboard.php" class="btn btn-secondary mb-3">← Back to All Products</a>

<?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h4>Add New Option Type</h4></div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="option_name" class="form-label">Option Name (e.g., Size)</label>
                        <input type="text" class="form-control" id="option_name" name="option_name" required>
                    </div>
                    <button type="submit" name="add_option" class="btn btn-primary">Add Option</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h4>Existing Options &amp; Values</h4></div>
            <div class="card-body">
                <?php if (empty($options_with_values)): ?>
                    <p>No options have been added for this product yet.</p>
                <?php else: ?>
                    <?php foreach ($options_with_values as $option_id => $option_data): ?>
                        <div class="p-3 mb-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($option_data['name']); ?></h5>
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this entire option and all its values?');">
                                    <input type="hidden" name="option_id" value="<?php echo $option_id; ?>">
                                    <button type="submit" name="delete_option" class="btn btn-danger btn-sm">Delete Option</button>
                                </form>
                            </div>
                            <hr>
                            <ul class="list-group list-group-flush">
                                <?php if (isset($option_data['values'])): ?>
                                    <?php foreach ($option_data['values'] as $value): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <?php echo htmlspecialchars($value['value']); ?>
                                                (Adjustment: ₹<?php echo htmlspecialchars(number_format($value['price_adjustment'], 2)); ?>)
                                            </span>
                                            <form method="POST" action="" onsubmit="return confirm('Are you sure?');">
                                                <input type="hidden" name="value_id" value="<?php echo $value['id']; ?>">
                                                <button type="submit" name="delete_option_value" class="btn btn-outline-danger btn-sm">Remove</button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="list-group-item">No values yet.</li>
                                <?php endif; ?>
                            </ul>
                            <form method="POST" action="" class="mt-3 p-2 border bg-light rounded">
                                <input type="hidden" name="option_id" value="<?php echo $option_id; ?>">
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control" name="value_name" placeholder="New Value Name" required>
                                    </div>
                                    <div class="col-sm-4">
                                        <input type="number" step="0.01" class="form-control" name="price_adjustment" placeholder="Price Adj. (₹)" required>
                                    </div>
                                    <div class="col-sm-2">
                                        <button type="submit" name="add_option_value" class="btn btn-success w-100">Add</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
