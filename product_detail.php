<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    header("Location: products.php");
    exit();
}
$product_id = $_GET['id'];

// Fetch main product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit();
}

// Fetch gallery images
$img_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$img_stmt->execute([$product_id]);
$gallery_images = $img_stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Use thumbnail as the first image if no gallery images exist
if (empty($gallery_images) && !empty($product['thumbnail_url'])) {
    $gallery_images[] = $product['thumbnail_url'];
}

// ... other data fetching logic for reviews, related products, etc.
?>

<?php include_once 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <div class="image-gallery">
                <img src="<?php echo !empty($gallery_images) ? htmlspecialchars($gallery_images[0]) : 'assets/images/default.jpg'; ?>" id="main-product-image" class="img-fluid rounded mb-3" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="thumbnails d-flex gap-2">
                    <?php foreach ($gallery_images as $img_url): ?>
                        <img src="<?php echo htmlspecialchars($img_url); ?>" class="img-thumbnail" style="width: 80px; height: 80px; cursor: pointer; object-fit: cover;" alt="Thumbnail">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 product-card" data-product-id="<?php echo $product['id']; ?>">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>

            <!-- Pricing, options, etc. would go here -->

            <div class="d-flex mt-4">
                <button class="btn btn-primary btn-lg ajax-add-to-cart">Add to Cart</button>
                <!-- Wishlist button -->
            </div>
        </div>
    </div>

    <!-- Other sections like reviews, related products -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.image-gallery .thumbnails img');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            mainImage.src = this.src;
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
