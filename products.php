<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// --- (Complex PHP logic for filtering, sorting, pagination, and data fetching) ---
// This is assumed to be here and correct. It produces the $products array.
// For example:
$products_stmt = $pdo->query("SELECT id, name, sale_price, thumbnail_url FROM products ORDER BY name ASC");
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
// In the real file, this query is much more complex to handle filters.
?>

<?php include_once 'includes/header.php'; ?>

<style>
.product-item {
  border-radius: 15px;
  overflow: hidden;
  background: #fff;
  transition: all 0.2s ease-in-out;
  border: 1px solid #eee;
}
.product-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.product-image-container {
  position: relative;
}
.product-image-container img {
  width: 100%;
  height: 220px;
  object-fit: cover;
}
.add-button-container {
  position: absolute;
  bottom: -20px; /* Start hidden */
  left: 50%;
  transform: translateX(-50%);
  transition: all 0.3s ease;
  opacity: 0;
}
.product-item:hover .add-button-container {
  bottom: 15px;
  opacity: 1;
}
.product-info {
  padding: 1rem 1.2rem;
}
.product-name {
  font-size: 1.1rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 0.25rem;
}
.product-price {
  font-size: 1rem;
  color: #555;
  font-weight: 500;
}
</style>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-3">
            <!-- Filter Sidebar -->
            <div class="card sticky-top">
                <div class="card-body">
                    <h5>Filters</h5>
                    <hr>
                    <!-- Placeholder for filter options -->
                    <p>Filter options will go here.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-9">
            <h1 class="mb-4">Our Menu</h1>
            <div class="row">
                <?php if (empty($products)): ?>
                    <p>No products found.</p>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4 mb-4 product-card" data-product-id="<?php echo $product['id']; ?>">
                            <div class="product-item">
                                <div class="product-image-container">
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                        <img src="<?php echo htmlspecialchars($product['thumbnail_url'] ?? 'assets/images/default.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </a>
                                    <div class="add-button-container">
                                        <button class="btn btn-success ajax-add-to-cart">ADD</button>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h5 class="product-name" title="<?php echo htmlspecialchars($product['name']); ?>"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="product-price">₹<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <!-- Pagination Placeholder -->
            <nav class="mt-4 d-flex justify-content-center">
                <p>Pagination will go here.</p>
            </nav>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
