<?php
session_start();
require_once 'includes/db_connection.php';

// --- Filtering & Sorting ---
$page_title = "Our Products";
$sql = "SELECT p.*, GROUP_CONCAT(DISTINCT c.name) as categories, GROUP_CONCAT(DISTINCT t.name) as tags
        FROM products p
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id
        LEFT JOIN product_tags pt ON p.id = pt.product_id
        LEFT JOIN tags t ON pt.tag_id = t.id";
$params = [];
$where_clauses = [];

// Category/Tag Filters
if (!empty($_GET['category'])) {
    $where_clauses[] = "p.id IN (SELECT product_id FROM product_categories WHERE category_id = (SELECT id FROM categories WHERE slug = :category_slug))";
    $params[':category_slug'] = $_GET['category'];
}
if (!empty($_GET['tag'])) {
    $where_clauses[] = "p.id IN (SELECT product_id FROM product_tags WHERE tag_id = (SELECT id FROM tags WHERE slug = :tag_slug))";
    $params[':tag_slug'] = $_GET['tag'];
}

// Price Range Filter
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
if (is_numeric($min_price)) {
    $where_clauses[] = "p.sale_price >= :min_price";
    $params[':min_price'] = $min_price;
}
if (is_numeric($max_price)) {
    $where_clauses[] = "p.sale_price <= :max_price";
    $params[':max_price'] = $max_price;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// Sorting Logic
$sort_options = ['name_asc' => 'Name (A-Z)', 'price_asc' => 'Price (Low-High)', 'price_desc' => 'Price (High-Low)', 'rating_desc' => 'Rating'];
$sort_key = $_GET['sort'] ?? 'name_asc';
$order_by = 'p.name ASC';
if ($sort_key === 'price_asc') $order_by = 'p.sale_price ASC';
if ($sort_key === 'price_desc') $order_by = 'p.sale_price DESC';
if ($sort_key === 'rating_desc') $order_by = 'p.avg_rating DESC';

$sql .= " GROUP BY p.id ORDER BY " . $order_by;

$products_stmt = $pdo->prepare($sql);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();

// (Other data fetching logic for options, etc. remains the same)
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <!-- Sidebar for Filters -->
    <div class="col-lg-3">
        <h4>Filter & Sort</h4>
        <form method="GET" action="products.php">
            <!-- Price Range Filter -->
            <div class="mb-3">
                <label class="form-label">Price Range</label>
                <div class="d-flex align-items-center">
                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>">
                    <span class="mx-2">-</span>
                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>">
                </div>
            </div>

            <!-- Sorting -->
            <div class="mb-3">
                 <label for="sort" class="form-label">Sort by</label>
                <select name="sort" id="sort" class="form-select">
                    <?php foreach ($sort_options as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $sort_key === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Hidden fields to preserve category/tag filters -->
            <?php if (!empty($_GET['category'])): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>"><?php endif; ?>
            <?php if (!empty($_GET['tag'])): ?><input type="hidden" name="tag" value="<?php echo htmlspecialchars($_GET['tag']); ?>"><?php endif; ?>

            <button type="submit" class="btn btn-primary w-100">Apply</button>
        </form>
    </div>

    <!-- Product Grid -->
    <div class="col-lg-9">
        <h1><?php echo $page_title; ?></h1>
        <div class="row">
            <?php if(empty($products)): ?>
                <div class="col-12"><div class="alert alert-info">No products found matching your criteria.</div></div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <!-- Product Card HTML remains the same as previous version -->
                        <div class="card h-100">...</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- (JavaScript remains the same) -->
<?php include 'includes/footer.php'; ?>
