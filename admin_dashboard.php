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
$total_revenue_stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'");
$total_revenue = $total_revenue_stmt->fetch_row()[0] ?? 0;
$total_orders_stmt = $conn->query("SELECT COUNT(*) FROM orders");
$total_orders = $total_orders_stmt->fetch_row()[0] ?? 0;
$total_users_stmt = $conn->query("SELECT COUNT(*) FROM users");
$total_users = $total_users_stmt->fetch_row()[0] ?? 0;

$low_stock_threshold = 10;
$low_stock_stmt = $conn->prepare("SELECT id, name, stock FROM products WHERE stock > 0 AND stock < ? ORDER BY stock ASC");
$low_stock_stmt->bind_param("i", $low_stock_threshold);
$low_stock_stmt->execute();
$low_stock_products = $low_stock_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>

<?php include_once 'includes/header.php'; ?>

<div class="container-fluid my-4">
    <h1>Admin Dashboard</h1>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0">Welcome, Admin! Manage your store from here.</p>
        <div class="btn-group">
             <button id="add-product-btn" class="btn btn-success" style="display:none;">Add New Product</button>
            <a href="admin_categories.php" class="btn btn-secondary">Categories</a>
            <a href="admin_tags.php" class="btn btn-secondary">Tags</a>
            <a href="admin_coupons.php" class="btn btn-info">Coupons</a>
            <a href="admin_settings.php" class="btn btn-primary">Settings</a>
            <a href="admin_reviews.php" class="btn btn-dark">Reviews</a>
        </div>
    </div>

    <!-- Stats & Alerts Section -->
    <div class="row mb-4">
        <div class="col-md-3"><div class="card text-white bg-success"><div class="card-body"><h5 class="card-title">Total Revenue</h5><p class="card-text fs-4">₹<?php echo number_format($total_revenue, 2); ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-info"><div class="card-body"><h5 class="card-title">Total Orders</h5><p class="card-text fs-4"><?php echo $total_orders; ?></p></div></div></div>
        <div class="col-md-3"><div class="card text-white bg-primary"><div class="card-body"><h5 class="card-title">Total Users</h5><p class="card-text fs-4"><?php echo $total_users; ?></p></div></div></div>
        <div class="col-md-3">
            <div class="card <?php echo !empty($low_stock_products) ? 'border-danger' : ''; ?>">
                <div class="card-header">Low Stock Alert</div>
                <div class="card-body" style="max-height: 150px; overflow-y: auto;">
                    <?php if (!empty($low_stock_products)): ?>
                        <ul class="list-unstyled mb-0">
                        <?php foreach ($low_stock_products as $product): ?>
                            <li><strong><?php echo htmlspecialchars($product['name']); ?>:</strong> <?php echo htmlspecialchars($product['stock']); ?> left</li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">All stock levels are healthy.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation"><a class="nav-link active" data-table="products">Products</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" data-table="orders">Orders</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" data-table="users">Users</a></li>
        <li class="nav-item" role="presentation"><a class="nav-link" data-table="user_addresses">User Addresses</a></li>
    </ul>

    <!-- Reusable Table Structure -->
    <div class="mt-3" id="admin-table-container">
        <div class="d-flex justify-content-center my-5" id="table-loader"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle" style="display:none;">
                <thead id="admin-table-head"></thead>
                <tbody id="admin-table-body"></tbody>
            </table>
        </div>
        <div id="admin-table-pagination" class="d-flex justify-content-center"></div>
    </div>
</div>

<!-- Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productModalLabel">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="product-form">
          <input type="hidden" id="product-id" name="id">
          <div class="row">
            <div class="col-md-6 mb-3"><label for="product-name">Name</label><input type="text" class="form-control" id="product-name" name="name" required></div>
            <div class="col-md-6 mb-3"><label for="product-stock">Stock</label><input type="number" class="form-control" id="product-stock" name="stock" required></div>
          </div>
          <div class="mb-3"><label for="product-description">Description</label><textarea class="form-control" id="product-description" name="description" rows="3"></textarea></div>
          <div class="row">
            <div class="col-md-6 mb-3"><label for="product-mrp">MRP</label><input type="text" class="form-control" id="product-mrp" name="mrp" required></div>
            <div class="col-md-6 mb-3"><label for="product-sale-price">Sale Price</label><input type="text" class="form-control" id="product-sale-price" name="sale_price" required></div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3"><label for="product-weight">Weight (kg)</label><input type="text" class="form-control" id="product-weight" name="weight"></div>
            <div class="col-md-4 mb-3"><label for="product-gst-rate">GST Rate (%)</label><input type="text" class="form-control" id="product-gst-rate" name="gst_rate"></div>
            <div class="col-md-4 mb-3"><label for="product-extra-packaging-charge">Packaging Charge</label><input type="text" class="form-control" id="product-extra-packaging-charge" name="extra_packaging_charge"></div>
          </div>
          <div class="mb-3"><label for="product-thumbnail-url">Thumbnail URL</label><input type="text" class="form-control" id="product-thumbnail-url" name="thumbnail_url" placeholder="URL for the main list view image"></div>
          <div class="mb-3"><label for="product-gallery-images">Gallery Images (one URL per line)</label><textarea class="form-control" id="product-gallery-images" name="gallery_images" rows="4"></textarea></div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save-product-btn">Save changes</button>
      </div>
    </div>
  </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.getElementById('admin-table-container');
    const table = tableContainer.querySelector('table');
    const thead = document.getElementById('admin-table-head');
    const tbody = document.getElementById('admin-table-body');
    const loader = document.getElementById('table-loader');
    const paginationContainer = document.getElementById('admin-table-pagination');
    const tabs = document.querySelectorAll('#adminTab .nav-link');
    const addProductBtn = document.getElementById('add-product-btn');
    const productModal = new bootstrap.Modal(document.getElementById('productModal'));
    const productForm = document.getElementById('product-form');
    const saveProductBtn = document.getElementById('save-product-btn');

    let currentState = {
        table: 'products',
        page: 1,
        sort: 'id',
        dir: 'desc'
    };

    const columnHeaders = {
        products: { id: 'ID', name: 'Name', mrp: 'MRP', sale_price: 'Sale Price', stock: 'Stock', extra_packaging_charge: 'Packaging', actions: 'Actions' },
        orders: { id: 'ID', username: 'User', total_amount: 'Total', status: 'Status', created_at: 'Date', actions: 'Actions' },
        users: { id: 'ID', username: 'Username', email: 'Email', role: 'Role', created_at: 'Created At' },
        user_addresses: { id: 'ID', username: 'User', address_line_1: 'Address', city: 'City', state: 'State', is_default: 'Default' }
    };

    async function fetchData() {
        loader.style.display = 'flex';
        table.style.display = 'none';
        try {
            const { table, page, sort, dir } = currentState;
            const response = await fetch(`api_admin_data.php?table=${table}&page=${page}&sort=${sort}&dir=${dir}`);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();
            renderTable(result.data);
            renderPagination(result.pagination);
        } catch (error) {
            tbody.innerHTML = `<tr><td colspan="100%" class="text-center text-danger">Failed to load data: ${error.message}</td></tr>`;
            table.style.display = 'table';
        } finally {
            loader.style.display = 'none';
        }
    }

    function renderTable(data) {
        const headers = columnHeaders[currentState.table];
        thead.innerHTML = '';
        tbody.innerHTML = '';

        const trHead = document.createElement('tr');
        for (const key in headers) {
            const th = document.createElement('th');
            if (key !== 'actions') {
                th.innerHTML = `${headers[key]} <i class="fas fa-sort" style="cursor:pointer;" data-sort="${key}"></i>`;
                th.querySelector('i').addEventListener('click', (e) => {
                    const sortKey = e.target.dataset.sort;
                    if (currentState.sort === sortKey) {
                        currentState.dir = currentState.dir === 'asc' ? 'desc' : 'asc';
                    } else {
                        currentState.sort = sortKey;
                        currentState.dir = 'desc';
                    }
                    fetchData();
                });
            } else {
                th.textContent = headers[key];
            }
            trHead.appendChild(th);
        }
        thead.appendChild(trHead);

        if (data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${Object.keys(headers).length}" class="text-center">No data found.</td></tr>`;
        } else {
            data.forEach(row => {
                const tr = document.createElement('tr');
                for (const key in headers) {
                    const td = document.createElement('td');
                    if (key === 'actions') {
                        if (currentState.table === 'products') {
                            td.innerHTML = `<button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">Edit</button>`;
                        } else if (currentState.table === 'orders') {
                             td.innerHTML = `<a href="admin_order_details.php?id=${row.id}" class="btn btn-sm btn-info">Details</a>`;
                        }
                    } else {
                        td.textContent = row[key] ?? 'N/A';
                    }
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            });
        }
        table.style.display = 'table';
    }

    function renderPagination(pagination) {
        // Simple pagination
        paginationContainer.innerHTML = '';
        if (pagination.total_pages > 1) {
            const nav = document.createElement('nav');
            const ul = document.createElement('ul');
            ul.className = 'pagination';
            for (let i = 1; i <= pagination.total_pages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
                const a = document.createElement('a');
                a.className = 'page-link';
                a.href = '#';
                a.textContent = i;
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    currentState.page = i;
                    fetchData();
                });
                li.appendChild(a);
                ul.appendChild(li);
            }
            nav.appendChild(ul);
            paginationContainer.appendChild(nav);
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentState.table = this.dataset.table;
            currentState.page = 1; // Reset to first page
            addProductBtn.style.display = (currentState.table === 'products') ? 'inline-block' : 'none';
            fetchData();
        });
    });

    // --- Modal Logic ---
    addProductBtn.addEventListener('click', () => {
        productForm.reset();
        document.getElementById('product-id').value = '';
        document.getElementById('productModalLabel').textContent = 'Add New Product';
        productModal.show();
    });

    tbody.addEventListener('click', async function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.dataset.id;
            try {
                const response = await fetch(`api_admin_data.php?table=products&id=${id}`);
                if (!response.ok) throw new Error('Product not found.');

                const product = await response.json();

                productForm.reset();
                document.getElementById('product-id').value = product.id;
                document.getElementById('productModalLabel').textContent = `Edit Product #${product.id}`;

                document.getElementById('product-name').value = product.name;
                document.getElementById('product-stock').value = product.stock;
                document.getElementById('product-description').value = product.description;
                document.getElementById('product-mrp').value = product.mrp;
                document.getElementById('product-sale-price').value = product.sale_price;
                document.getElementById('product-weight').value = product.weight;
                document.getElementById('product-gst-rate').value = product.gst_rate;
                document.getElementById('product-extra-packaging-charge').value = product.extra_packaging_charge;
                document.getElementById('product-thumbnail-url').value = product.thumbnail_url;
                document.getElementById('product-gallery-images').value = (product.gallery_images || []).join('\\n');

                productModal.show();
            } catch (error) {
                alert(`Error fetching product details: ${error.message}`);
            }
        }
    });

    saveProductBtn.addEventListener('click', async function() {
        const id = document.getElementById('product-id').value;
        const action = id ? 'update' : 'create';
        const formData = new FormData(productForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api_admin_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    table: 'products',
                    action: action,
                    ...data
                })
            });
            const result = await response.json();
            if (!response.ok || result.error) {
                throw new Error(result.error || 'Save operation failed');
            }
            productModal.hide();
            fetchData(); // Refresh table
        } catch (error) {
            alert(`Error: ${error.message}`);
        }
    });

    // Initial load
    fetchData();
    addProductBtn.style.display = 'inline-block';
});
</script>

<?php include_once 'includes/footer.php'; ?>
