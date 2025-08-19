<?php
session_start();
require_once 'includes/db_connection.php';

// --- Get Product ID and Fetch Data ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}
$product_id = $_GET['id'];

// Fetch Product
$product_stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$product_stmt->execute([$product_id]);
$product = $product_stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit();
}

// Fetch product variants, categories, and tags
$options_stmt = $pdo->prepare("SELECT po.id as option_id, po.name as option_name, pov.id as value_id, pov.value, pov.price_adjustment FROM product_options po JOIN product_option_values pov ON po.id = pov.option_id WHERE po.product_id = ? ORDER BY po.id, pov.id");
$options_stmt->execute([$product_id]);
$options_raw = $options_stmt->fetchAll();
$options = [];
foreach ($options_raw as $option_row) {
    $options[$option_row['option_id']]['name'] = $option_row['option_name'];
    $options[$option_row['option_id']]['values'][] = $option_row;
}

$categories_stmt = $pdo->prepare("SELECT c.id, c.name FROM categories c JOIN product_categories pc ON c.id = pc.category_id WHERE pc.product_id = ?");
$categories_stmt->execute([$product_id]);
$categories = $categories_stmt->fetchAll();

// --- Fetch Related Products ---
$related_products = [];
if (!empty($categories)) {
    $category_ids = array_column($categories, 'id');
    $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
    $related_stmt = $pdo->prepare(
        "SELECT DISTINCT p.* FROM products p
         JOIN product_categories pc ON p.id = pc.product_id
         WHERE pc.category_id IN ($placeholders) AND p.id != ?
         LIMIT 4"
    );
    $params = array_merge($category_ids, [$product_id]);
    $related_stmt->execute($params);
    $related_products = $related_stmt->fetchAll();
}


// --- Reviews Logic ---
$reviews_stmt = $pdo->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$reviews_stmt->execute([$product_id]);
$reviews = $reviews_stmt->fetchAll();

$has_purchased = false;
if (isset($_SESSION['user_id'])) {
    $purchase_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'Delivered'");
    $purchase_stmt->execute([$_SESSION['user_id'], $product_id]);
    if ($purchase_stmt->fetchColumn() > 0) $has_purchased = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($has_purchased) {
        $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
        $comment = trim($_POST['comment']);
        if($rating >= 1 && $rating <= 5) {
            $insert_review_stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
            $insert_review_stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment]);
            $avg_stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ?");
            $avg_stmt->execute([$product_id]);
            $new_avg = $avg_stmt->fetchColumn();
            $update_avg_stmt = $pdo->prepare("UPDATE products SET avg_rating = ? WHERE id = ?");
            $update_avg_stmt->execute([$new_avg, $product_id]);
            header("Location: product_detail.php?id=" . $product_id);
            exit();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row">
        <div class="col-md-6"><img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>"></div>
        <div class="col-md-6 product-card" data-product-id="<?php echo $product['id']; ?>" data-base-price="<?php echo $product['sale_price']; ?>">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="mb-2"><strong>Rating:</strong> <?php echo number_format($product['avg_rating'], 1); ?> / 5.0 <span class="text-muted">(<?php echo count($reviews); ?> reviews)</span></div>
            <p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>
            <h3 class="price-display"><span class="sale-price fw-bold">₹<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></span> <small class="mrp text-muted text-decoration-line-through">₹<?php echo htmlspecialchars(number_format($product['mrp'], 2)); ?></small></h3>
            <hr>
            <?php if (!empty($options)): ?>
                <div class="product-options mb-3">
                    <?php foreach ($options as $option): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold"><?php echo htmlspecialchars($option['name']); ?>:</label>
                            <select class="form-select variant-select">
                                <?php foreach ($option['values'] as $value): ?>
                                    <option value="<?php echo $value['value_id']; ?>" data-price-adjustment="<?php echo $value['price_adjustment']; ?>"><?php echo htmlspecialchars($value['value']); ?> (<?php echo $value['price_adjustment'] >= 0 ? '+' : ''; ?>₹<?php echo number_format($value['price_adjustment'], 2); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="d-flex">
                <button class="btn btn-primary btn-lg add-to-cart-btn">Add to Cart</button>
                <button class="btn btn-outline-danger btn-lg ms-2 add-to-wishlist-btn" title="Add to Wishlist"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16"><path d="m8 2.748-.717-.737C5.6.271 2.216 1.333 1.053 3.468 0 5.4-1.5 7.822 8 12.502 17.5 7.822 16 5.4 14.947 3.468c-1.163-2.135-4.547-3.2-6.23-2.73L8 2.748zM8 15C-7.333 4.868 3.279-2.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-2.042 23.333 4.867 8 15z"/></svg></button>
            </div>
        </div>
    </div>
    <hr class="my-5">
    <div class="row">
        <div class="col-md-7">
            <h3>Customer Reviews</h3>
            <?php if (empty($reviews)): ?><p>No reviews yet.</p><?php else: foreach ($reviews as $review): ?>
                <div class="card mb-3"><div class="card-body"><strong><?php echo htmlspecialchars($review['username']); ?></strong><div class="mb-1"><?php echo str_repeat('⭐', $review['rating']); ?><span class="text-muted"><?php echo str_repeat('☆', 5 - $review['rating']); ?></span></div><p class="card-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p><small class="text-muted">Reviewed on <?php echo date("F j, Y", strtotime($review['created_at'])); ?></small></div></div>
            <?php endforeach; endif; ?>
        </div>
        <div class="col-md-5">
            <h3>Write a Review</h3>
            <?php if(isset($_SESSION['user_id'])): if ($has_purchased): ?>
                <div class="card"><div class="card-body"><form method="POST"><div class="mb-3"><label for="rating" class="form-label">Your Rating</label><select name="rating" id="rating" class="form-select" required><option value="5">5 Stars</option><option value="4">4 Stars</option><option value="3">3 Stars</option><option value="2">2 Stars</option><option value="1">1 Star</option></select></div><div class="mb-3"><label for="comment" class="form-label">Your Review</label><textarea name="comment" id="comment" rows="4" class="form-control" required></textarea></div><button type="submit" name="submit_review" class="btn btn-primary">Submit</button></form></div></div>
            <?php else: ?><div class="alert alert-info">You must purchase this product to leave a review.</div><?php endif; else: ?>
                 <div class="alert alert-warning">Please <a href="login.php?redirect=product_detail.php?id=<?php echo $product_id; ?>">log in</a> to write a review.</div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($related_products)): ?>
    <div id="related-products-section" class="mt-5">
        <h3>You Might Also Like</h3>
        <div class="row">
            <?php foreach($related_products as $related_product): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <a href="product_detail.php?id=<?php echo $related_product['id']; ?>">
                            <img src="<?php echo htmlspecialchars($related_product['image_url']); ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="<?php echo htmlspecialchars($related_product['name']); ?>">
                        </a>
                        <div class="card-body">
                            <h6 class="card-title"><a href="product_detail.php?id=<?php echo $related_product['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($related_product['name']); ?></a></h6>
                            <p class="card-text fw-bold">₹<?php echo number_format($related_product['sale_price'], 2); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// All JS from previous version of this file...
</script>

<?php include 'includes/footer.php'; ?>
