<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';
require_once 'includes/address_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
        $errors[] = "CSRF token validation failed.";
    } else {
        $address_data = [
            'address_line_1' => trim($_POST['address_line_1'] ?? ''),
            'address_line_2' => trim($_POST['address_line_2'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'state' => trim($_POST['state'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'country' => trim($_POST['country'] ?? 'India'),
            'latitude' => !empty($_POST['latitude']) ? trim($_POST['latitude']) : null,
            'longitude' => !empty($_POST['longitude']) ? trim($_POST['longitude']) : null,
            'is_default' => isset($_POST['is_default']) ? 1 : 0
        ];

        if (isset($_POST['add_address'])) {
            if (add_user_address($pdo, $user_id, $address_data)) {
                $success_message = "Address added successfully!";
            } else {
                $errors[] = "Failed to add address.";
            }
        } elseif (isset($_POST['update_address'])) {
            $address_id = $_POST['address_id'];
            if (update_user_address($pdo, $address_id, $user_id, $address_data)) {
                $success_message = "Address updated successfully!";
            } else {
                $errors[] = "Failed to update address.";
            }
        } elseif (isset($_POST['delete_address'])) {
            $address_id = $_POST['address_id'];
            if (delete_user_address($pdo, $address_id, $user_id)) {
                $success_message = "Address deleted successfully!";
            } else {
                $errors[] = "Failed to delete address.";
            }
        } elseif (isset($_POST['set_default'])) {
            $address_id = $_POST['address_id'];
            if (set_default_address($pdo, $user_id, $address_id)) {
                $success_message = "Default address updated!";
            } else {
                $errors[] = "Failed to set default address.";
            }
        }
    }
}

// Fetch user data for display
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all user addresses for display
$addresses = get_user_addresses($pdo, $user_id);

$csrf_token = generate_csrf_token();
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <h1>User Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</p>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo $success_message; ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <hr class="my-4">

    <div class="address-management">
        <h2>Manage Addresses</h2>
        <button id="add-address-btn" class="btn btn-success mb-3">Add New Address</button>

        <div id="address-form-container" style="display: none;" class="card card-body mb-4">
            <h3 id="form-title">Add New Address</h3>
            <form method="POST" action="dashboard.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" id="address-id" name="address_id" value="">

                <div class="form-group mb-3"><label for="address_line_1">Address Line 1</label><input type="text" class="form-control" id="address_line_1" name="address_line_1" required></div>
                <div class="form-group mb-3"><label for="address_line_2">Address Line 2 (Optional)</label><input type="text" class="form-control" id="address_line_2" name="address_line_2"></div>
                <div class="row mb-3">
                    <div class="col-md-6 form-group"><label for="city">City</label><input type="text" class="form-control" id="city" name="city" required></div>
                    <div class="col-md-6 form-group"><label for="state">State</label><input type="text" class="form-control" id="state" name="state" required></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 form-group"><label for="postal_code">Postal Code</label><input type="text" class="form-control" id="postal_code" name="postal_code" required></div>
                    <div class="col-md-6 form-group"><label for="country">Country</label><input type="text" class="form-control" id="country" name="country" value="India" required></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6 form-group"><label for="latitude">Latitude (Optional)</label><input type="text" class="form-control" id="latitude" name="latitude"></div>
                    <div class="col-md-6 form-group"><label for="longitude">Longitude (Optional)</label><input type="text" class="form-control" id="longitude" name="longitude"></div>
                </div>
                <div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1"><label class="form-check-label" for="is_default">Set as default address</label></div>
                <button type="submit" id="form-submit-btn" name="add_address" class="btn btn-primary">Save Address</button>
                <button type="button" id="cancel-edit-btn" class="btn btn-secondary">Cancel</button>
            </form>
        </div>

        <div class="address-list">
            <?php if (empty($addresses)): ?>
                <p>You have no saved addresses.</p>
            <?php else: ?>
                <div class="row">
                <?php foreach ($addresses as $address): ?>
                    <div class="col-md-6">
                        <div class="card mb-3 <?php echo $address['is_default'] ? 'border-primary' : ''; ?>">
                            <div class="card-body">
                                <p>
                                    <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                    <?php if (!empty($address['address_line_2'])) echo htmlspecialchars($address['address_line_2']) . '<br>'; ?>
                                    <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['state']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                    <?php if (!empty($address['latitude'])) echo 'Lat: ' . htmlspecialchars($address['latitude']) . ', '; ?>
                                    <?php if (!empty($address['longitude'])) echo 'Lng: ' . htmlspecialchars($address['longitude']); ?>
                                </p>
                                <?php if ($address['is_default']): ?><span class="badge bg-primary">Default</span><?php endif; ?>

                                <div class="mt-2">
                                    <button class="btn btn-sm btn-info edit-address-btn"
                                            data-id="<?php echo $address['id']; ?>"
                                            data-line1="<?php echo htmlspecialchars($address['address_line_1']); ?>"
                                            data-line2="<?php echo htmlspecialchars($address['address_line_2'] ?? ''); ?>"
                                            data-city="<?php echo htmlspecialchars($address['city']); ?>"
                                            data-state="<?php echo htmlspecialchars($address['state']); ?>"
                                            data-postal="<?php echo htmlspecialchars($address['postal_code']); ?>"
                                            data-country="<?php echo htmlspecialchars($address['country']); ?>"
                                            data-lat="<?php echo htmlspecialchars($address['latitude'] ?? ''); ?>"
                                            data-lng="<?php echo htmlspecialchars($address['longitude'] ?? ''); ?>"
                                            data-default="<?php echo $address['is_default']; ?>">
                                        Edit
                                    </button>
                                    <form method="POST" action="dashboard.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <button type="submit" name="delete_address" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this address?');">Delete</button>
                                    </form>
                                    <?php if (!$address['is_default']): ?>
                                        <form method="POST" action="dashboard.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                            <button type="submit" name="set_default" class="btn btn-sm btn-secondary">Set as Default</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const formContainer = document.getElementById('address-form-container');
    const form = formContainer.querySelector('form');
    const title = document.getElementById('form-title');
    const submitBtn = document.getElementById('form-submit-btn');
    const addressIdField = document.getElementById('address-id');

    const fields = {
        line1: document.getElementById('address_line_1'),
        line2: document.getElementById('address_line_2'),
        city: document.getElementById('city'),
        state: document.getElementById('state'),
        postal: document.getElementById('postal_code'),
        country: document.getElementById('country'),
        lat: document.getElementById('latitude'),
        lng: document.getElementById('longitude'),
        isDefault: document.getElementById('is_default')
    };

    function resetForm() {
        form.reset();
        title.innerText = 'Add New Address';
        submitBtn.name = 'add_address';
        submitBtn.innerText = 'Save Address';
        addressIdField.value = '';
        formContainer.style.display = 'none';
    }

    document.getElementById('add-address-btn').addEventListener('click', function () {
        resetForm();
        formContainer.style.display = 'block';
        window.scrollTo(0, formContainer.offsetTop - 20);
    });

    document.getElementById('cancel-edit-btn').addEventListener('click', resetForm);

    document.querySelectorAll('.edit-address-btn').forEach(button => {
        button.addEventListener('click', function () {
            resetForm();
            title.innerText = 'Edit Address';
            submitBtn.name = 'update_address';
            submitBtn.innerText = 'Update Address';

            const data = this.dataset;
            addressIdField.value = data.id;
            fields.line1.value = data.line1;
            fields.line2.value = data.line2;
            fields.city.value = data.city;
            fields.state.value = data.state;
            fields.postal.value = data.postal;
            fields.country.value = data.country;
            fields.lat.value = data.lat;
            fields.lng.value = data.lng;
            fields.isDefault.checked = data.default === '1';

            formContainer.style.display = 'block';
            window.scrollTo(0, formContainer.offsetTop - 20);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
