<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// --- (All the PHP logic for filtering, sorting, pagination, and data fetching goes here) ---
// This part is complex and assumed to be correct from previous steps.
// For brevity, I am not including the 50+ lines of query building logic again.
// The important part is that it produces the $products array.

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row">
        <div class="col-lg-3">
            <!-- Filter and Sort Sidebar -->
        </div>
        <div class="col-lg-9">
            <h1>Our Products</h1>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 product-card" data-product-id="<?php echo $product['id']; ?>" data-base-price="<?php echo $product['sale_price']; ?>">
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($product['name']); ?></a></h5>
                                <!-- (Variant dropdowns would be here) -->
                                <p class="card-text price-display mt-auto">
                                    <span class="sale-price fw-bold fs-5">₹<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></span>
                                </p>
                                <div class="d-flex align-items-center mt-2">
                                    <button class="btn btn-primary btn-sm add-to-cart-btn">Add to Cart</button>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button class="btn btn-outline-danger btn-sm ms-2 add-to-wishlist-btn" title="Add to Wishlist">♥</button>
                                    <?php else: ?>
                                        <a href="login.php?redirect=products.php" class="btn btn-outline-secondary btn-sm ms-2" title="Log in to add to Wishlist">♥</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Pagination links -->
        </div>
    </div>
</div>

<!-- (JavaScript for cart, wishlist, and variants) -->
<?php include 'includes/footer.php'; ?>
