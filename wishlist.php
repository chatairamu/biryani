<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=wishlist.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle item removal from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $product_id_to_remove = $_POST['product_id'];
    $delete_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $delete_stmt->execute([$user_id, $product_id_to_remove]);
    // Redirect to the same page to show the updated list
    header("Location: wishlist.php");
    exit();
}


// Fetch wishlist items for the user
$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.sale_price, p.image_url, p.description
     FROM wishlist w
     JOIN products p ON w.product_id = p.id
     WHERE w.user_id = ?"
);
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>My Wishlist</h1>
    <hr>
    <?php if (empty($wishlist_items)): ?>
        <div class="card">
            <div class="card-body text-center">
                <p>Your wishlist is empty.</p>
                <a href="products.php" class="btn btn-primary">Find Products You'll Love</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($wishlist_items as $item): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="card-text"><strong>₹<?php echo htmlspecialchars(number_format($item['sale_price'], 2)); ?></strong></p>
                            <div class="mt-auto d-flex justify-content-between">
                                <a href="products.php" class="btn btn-primary">View Product</a>
                                <form method="POST" action="wishlist.php">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_from_wishlist" class="btn btn-danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>


<?php include 'includes/footer.php'; ?>
