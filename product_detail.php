<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// ... (all PHP data fetching logic from the correct version) ...

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row">
        <div class="col-md-6">
            <!-- Product Image -->
        </div>
        <div class="col-md-6 product-card" data-product-id="<?php echo $product['id']; ?>">
            <!-- Product Info, Variants, Pricing -->

            <div class="d-flex">
                <button class="btn btn-primary btn-lg add-to-cart-btn">Add to Cart</button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="btn btn-outline-danger btn-lg ms-2 add-to-wishlist-btn" title="Add to Wishlist">♥</button>
                <?php else: ?>
                    <a href="login.php?redirect=product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-danger btn-lg ms-2" title="Log in to add to Wishlist">♥</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <!-- ... -->

    <!-- Related Products Section -->
    <!-- ... -->
</div>

<!-- JavaScript -->
<!-- ... -->
<?php include 'includes/footer.php'; ?>
