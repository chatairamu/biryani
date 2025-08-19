<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// --- Security Check & Initial Setup ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
$csrf_token = generate_csrf_token();

// --- Fetch Stats & Alerts (these are loaded once on page load) ---
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$low_stock_threshold = 10;
$low_stock_products = $pdo->query("SELECT id, name, stock FROM products WHERE stock < $low_stock_threshold ORDER BY stock ASC")->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-4">
    <h1>Admin Dashboard</h1>
    <!-- ... (header content, stats, alerts) ... -->
    <hr>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation"><a class="nav-link active" id="products-tab" data-bs-toggle="tab" href="#tab-pane" data-table="products">Products</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#tab-pane" data-table="orders">Orders</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" id="users-tab" data-bs-toggle="tab" href="#tab-pane" data-table="users">Users</a></li>
    </ul>

    <!-- Reusable Table Structure -->
    <div class="mt-3" id="admin-table-container">
        <div class="d-flex justify-content-center my-5" id="table-loader"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
        <table class="table table-striped table-bordered" style="display:none;">
            <thead id="admin-table-head"></thead>
            <tbody id="admin-table-body"></tbody>
        </table>
        <div id="admin-table-pagination" class="d-flex justify-content-center"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentState = { table: 'products', page: 1, sort: 'id', dir: 'desc' };
    const csrfToken = '<?php echo $csrf_token; ?>';
    const statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    const tableConfigs = {
        products: {
            columns: { id: 'ID', name: 'Name', sale_price: 'Price', stock: 'Stock', avg_rating: 'Rating' },
            renderRow: (item) => `<tr><td>${item.id}</td><td>${item.name}</td><td>₹${item.sale_price}</td><td>${item.stock}</td><td>${item.avg_rating}</td><td><a href="admin_product_variants.php?product_id=${item.id}" class="btn btn-sm btn-warning">Edit</a></td></tr>`
        },
        orders: {
            columns: { id: 'ID', username: 'Customer', total_amount: 'Total', status: 'Status', created_at: 'Date' },
            renderRow: (item) => {
                let options = statuses.map(s => `<option value="${s}" ${s === item.status ? 'selected' : ''}>${s}</option>`).join('');
                return `<tr>
                    <td>#${item.id}</td><td>${item.username}</td><td>₹${item.total_amount}</td>
                    <td>
                        <form class="status-update-form d-flex"><input type="hidden" name="order_id" value="${item.id}"><select name="status" class="form-select form-select-sm">${options}</select><button type="submit" class="btn btn-sm btn-primary ms-2">Save</button></form>
                        <div class="status-update-feedback" style="display:none;"></div>
                    </td>
                    <td>${new Date(item.created_at).toLocaleDateString()}</td><td><a href="admin_order_details.php?order_id=${item.id}" class="btn btn-sm btn-info">Details</a></td>
                </tr>`;
            }
        },
        users: { /* ... */ }
    };

    function renderTableHeaders(state) { /* ... */ }

    function fetchData(state) {
        $('#admin-table-container table').hide();
        $('#table-loader').show();
        // ... (AJAX call to api_admin_data.php)
        $.ajax({
            // ...
            success: function(response) {
                // ... (render table body)
                $('#table-loader').hide();
                $('#admin-table-container table').show();
            }
        });
    }

    // --- Event Handlers ---
    $('#adminTab a').on('click', function(e) { /* ... */ });
    $(document).on('click', '.sort-link', function(e) { /* ... */ });
    $(document).on('click', '#admin-table-pagination a', function(e) { /* ... */ });

    // New handler for status updates
    $(document).on('submit', '.status-update-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const feedbackDiv = form.siblings('.status-update-feedback');
        const data = form.serialize() + '&csrf_token=' + encodeURIComponent(csrfToken);

        $.ajax({
            url: 'admin_update_order_status.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                feedbackDiv.text('Status updated!').addClass('text-success').show().fadeOut(2000);
                // Optionally, refetch data to show updated table, but this is faster
            },
            error: function() {
                feedbackDiv.text('Error!').addClass('text-danger').show().fadeOut(2000);
            }
        });
    });

    // Initial Load
    renderTableHeaders(currentState);
    fetchData(currentState);
});
</script>

<?php include 'includes/footer.php'; ?>
