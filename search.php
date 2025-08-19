<?php
session_start();
require_once 'includes/db_connection.php';

$search_term = '';
$search_results = [];

if (isset($_GET['query'])) {
    $search_term = trim($_GET['query']);

    if (!empty($search_term)) {
        $sql = "
            SELECT DISTINCT p.*
            FROM products p
            LEFT JOIN product_categories pc ON p.id = pc.product_id
            LEFT JOIN categories c ON pc.category_id = c.id
            LEFT JOIN product_tags pt ON p.id = pt.product_id
            LEFT JOIN tags t ON pt.tag_id = t.id
            WHERE p.name LIKE :search_term
            OR p.description LIKE :search_term
            OR c.name LIKE :search_term
            OR t.name LIKE :search_term
            GROUP BY p.id
            ORDER BY p.name ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search_term', '%' . $search_term . '%');
        $stmt->execute();
        $search_results = $stmt->fetchAll();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Search Products</h1>
    <form method="GET" action="search.php" class="mb-4">
        <div class="input-group">
            <input type="text" name="query" class="form-control" placeholder="Search for products..." value="<?php echo htmlspecialchars($search_term); ?>" required>
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <hr>

    <?php if (isset($_GET['query'])): ?>
        <h3>Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h3>
        <p><?php echo count($search_results); ?> results found.</p>

        <?php if (empty($search_results)): ?>
            <div class="alert alert-info">No products found matching your search criteria.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($search_results as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                             <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text">
                                    <span class="sale-price fw-bold">₹<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></span>
                                    <small class="mrp text-muted text-decoration-line-through">₹<?php echo htmlspecialchars(number_format($product['mrp'], 2)); ?></small>
                                </p>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
