<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/helpers.php';

// --- Filtering & Sorting ---
$page_title = "Our Products";
$sql = "SELECT p.* FROM products p";
$count_sql = "SELECT COUNT(DISTINCT p.id) FROM products p";
$params = [];
$where_clauses = [];

// Join tables only when needed for filtering
$joins = "";
if (!empty($_GET['category'])) {
    $joins .= " LEFT JOIN product_categories pc ON p.id = pc.product_id LEFT JOIN categories c ON pc.category_id = c.id";
    $where_clauses[] = "c.slug = :category_slug";
    $params[':category_slug'] = $_GET['category'];
}
if (!empty($_GET['tag'])) {
    $joins .= " LEFT JOIN product_tags pt ON p.id = pt.product_id LEFT JOIN tags t ON pt.tag_id = t.id";
    $where_clauses[] = "t.slug = :tag_slug";
    $params[':tag_slug'] = $_GET['tag'];
}

$sql .= $joins;
$count_sql .= $joins;

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
    $count_sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " GROUP BY p.id"; // Group by product ID to avoid duplicates from joins

// --- Pagination ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
$items_per_page = 9;
$offset = ($page - 1) * $items_per_page;

$total_items_stmt = $pdo->prepare($count_sql);
$total_items_stmt->execute($params);
$total_items = $total_items_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);


// --- Sorting ---
$sort_options = ['name_asc' => 'Name (A-Z)', 'price_asc' => 'Price (Low-High)', 'price_desc' => 'Price (High-Low)', 'rating_desc' => 'Rating'];
$sort_key = $_GET['sort'] ?? 'name_asc';
$order_by = 'p.name ASC';
if ($sort_key === 'price_asc') $order_by = 'p.sale_price ASC';
if ($sort_key === 'price_desc') $order_by = 'p.sale_price DESC';
if ($sort_key === 'rating_desc') $order_by = 'p.avg_rating DESC';

$sql .= " ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset";

// --- Fetch Products for current page ---
$products_stmt = $pdo->prepare($sql);
$products_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
foreach ($params as $key => $val) {
    $products_stmt->bindValue($key, $val);
}
$products_stmt->execute();
$products = $products_stmt->fetchAll();

// --- Fetch all options for the displayed products ---
$product_ids = array_column($products, 'id');
$all_options = [];
if (!empty($product_ids)) {
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $options_stmt = $pdo->prepare("SELECT po.product_id, po.id as option_id, po.name as option_name, pov.id as value_id, pov.value, pov.price_adjustment FROM product_options po JOIN product_option_values pov ON po.id = pov.option_id WHERE po.product_id IN ($placeholders) ORDER BY po.id, pov.id");
    $options_stmt->execute($product_ids);
    $options_raw = $options_stmt->fetchAll();
    foreach ($options_raw as $option_row) {
        $all_options[$option_row['product_id']][$option_row['option_id']]['name'] = $option_row['option_name'];
        $all_options[$option_row['product_id']][$option_row['option_id']]['values'][] = $option_row;
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <div class="row">
        <!-- Sidebar for Filters -->
        <div class="col-lg-3">
            <h4>Filter & Sort</h4>
            <form method="GET" action="products.php">
                <!-- (Filter and Sort form remains the same) -->
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
                             <!-- Product Card HTML remains the same -->
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="mt-4">
                <?php
                    $query_params = $_GET;
                    echo generate_pagination_links($page, $total_pages, 'products.php', $query_params);
                ?>
            </div>
        </div>
    </div>
</div>

<!-- (JavaScript remains the same) -->
<?php include 'includes/footer.php'; ?>
