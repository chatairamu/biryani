<?php
session_start();
require_once 'includes/db_connection.php';

// --- Base Query ---
$sql = "SELECT p.*,
        GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as categories,
        GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') as tags
        FROM products p
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id
        LEFT JOIN product_tags pt ON p.id = pt.product_id
        LEFT JOIN tags t ON pt.tag_id = t.id";

$params = [];
$where_clauses = [];
$page_title = "Our Products";

// --- Filtering Logic ---
if (!empty($_GET['category'])) {
    $where_clauses[] = "c.slug = :category_slug";
    $params[':category_slug'] = $_GET['category'];
    // You could fetch the category name to make the title nicer
    $page_title = "Products in Category: " . htmlspecialchars($_GET['category']);
}

if (!empty($_GET['tag'])) {
    $where_clauses[] = "t.slug = :tag_slug";
    $params[':tag_slug'] = $_GET['tag'];
    $page_title = "Products tagged with: " . htmlspecialchars($_GET['tag']);
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

// --- Sorting Logic ---
$sort_options = [
    'name_asc' => 'Name (A-Z)',
    'price_asc' => 'Price (Low to High)',
    'price_desc' => 'Price (High to Low)',
    'rating_desc' => 'Rating (High to Low)'
];
$sort_key = $_GET['sort'] ?? 'name_asc';
$order_by_clause = 'p.name ASC'; // Default
if (isset($sort_options[$sort_key])) {
    switch ($sort_key) {
        case 'price_asc':
            $order_by_clause = 'p.sale_price ASC';
            break;
        case 'price_desc':
            $order_by_clause = 'p.sale_price DESC';
            break;
        case 'rating_desc':
            $order_by_clause = 'p.avg_rating DESC';
            break;
    }
}

$sql .= " GROUP BY p.id ORDER BY " . $order_by_clause;

// --- Fetch all products from the database ---
$products_stmt = $pdo->prepare($sql);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();

// ... (rest of the PHP logic for options and cart remains the same)
$options_stmt = $pdo->query(
    "SELECT po.product_id, po.id as option_id, po.name as option_name, pov.id as value_id, pov.value, pov.price_adjustment
     FROM product_options po
     JOIN product_option_values pov ON po.id = pov.option_id
     ORDER BY po.product_id, po.id, pov.id"
);
$all_options_raw = $options_stmt->fetchAll();
$all_options = [];
foreach ($all_options_raw as $option_row) {
    $all_options[$option_row['product_id']][$option_row['option_id']]['name'] = $option_row['option_name'];
    $all_options[$option_row['product_id']][$option_row['option_id']]['values'][] = $option_row;
}
?>

<?php include 'includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?php echo $page_title; ?></h1>
    <form method="GET" action="products.php" class="d-flex align-items-center">
        <!-- Hidden fields to preserve filters -->
        <?php if (!empty($_GET['category'])): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>"><?php endif; ?>
        <?php if (!empty($_GET['tag'])): ?><input type="hidden" name="tag" value="<?php echo htmlspecialchars($_GET['tag']); ?>"><?php endif; ?>

        <label for="sort" class="form-label me-2 mb-0">Sort by:</label>
        <select name="sort" id="sort" class="form-select" onchange="this.form.submit()">
            <?php foreach ($sort_options as $key => $value): ?>
                <option value="<?php echo $key; ?>" <?php echo $sort_key === $key ? 'selected' : ''; ?>>
                    <?php echo $value; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="row">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 product-card" data-product-id="<?php echo $product['id']; ?>" data-base-price="<?php echo $product['sale_price']; ?>">
                <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </a>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($product['name']); ?></a></h5>

                    <!-- Categories and Tags -->
                    <div class="mb-2">
                        <?php if(!empty($product['categories'])): ?>
                            <small class="text-muted">Categories:
                                <?php
                                    // This part can be improved to link to the category pages
                                    echo htmlspecialchars($product['categories']);
                                ?>
                            </small><br>
                        <?php endif; ?>
                        <?php if(!empty($product['tags'])): ?>
                             <small class="text-muted">Tags:
                                <?php
                                    $tags = explode(', ', $product['tags']);
                                    foreach($tags as $tag) {
                                        // This part can be improved to link to the tag pages
                                        echo '<a href="products.php?tag=' . urlencode(strtolower($tag)) . '" class="badge bg-info me-1 text-decoration-none">' . htmlspecialchars($tag) . '</a>';
                                    }
                                ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>

                    <!-- Product Options -->
                    <?php if (isset($all_options[$product['id']])): ?>
                        <div class="product-options mb-3">
                            <?php foreach ($all_options[$product['id']] as $option): ?>
                                <div class="mb-2">
                                    <label class="form-label fw-bold"><?php echo htmlspecialchars($option['name']); ?>:</label>
                                    <select class="form-select form-select-sm variant-select">
                                        <?php foreach ($option['values'] as $value): ?>
                                            <option value="<?php echo $value['value_id']; ?>" data-price-adjustment="<?php echo $value['price_adjustment']; ?>">
                                                <?php echo htmlspecialchars($value['value']); ?>
                                                (<?php echo $value['price_adjustment'] >= 0 ? '+' : ''; ?>₹<?php echo number_format($value['price_adjustment'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Price Display -->
                    <p class="card-text price-display">
                        <span class="sale-price fw-bold fs-5">₹<?php echo htmlspecialchars(number_format($product['sale_price'], 2)); ?></span>
                        <small class="mrp text-muted text-decoration-line-through">₹<?php echo htmlspecialchars(number_format($product['mrp'], 2)); ?></small>
                    </p>

                    <div class="d-flex align-items-center mt-auto">
                        <button class="btn btn-primary add-to-cart-btn">Add to Cart</button>
                        <button class="btn btn-outline-danger ms-2 add-to-wishlist-btn" title="Add to Wishlist">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16"><path d="m8 2.748-.717-.737C5.6.271 2.216 1.333 1.053 3.468 0 5.4-1.5 7.822 8 12.502 17.5 7.822 16 5.4 14.947 3.468c-1.163-2.135-4.547-3.2-6.23-2.73L8 2.748zM8 15C-7.333 4.868 3.279-2.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-2.042 23.333 4.867 8 15z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- JavaScript remains the same -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// All the JS from the previous version of this file goes here...
// (It is unchanged for this step)
$(document).ready(function() {
    // Update price when a variant is selected
    $('.variant-select').on('change', function() {
        const card = $(this).closest('.product-card');
        let basePrice = parseFloat(card.data('base-price'));
        let finalPrice = basePrice;

        card.find('.variant-select').each(function() {
            const adjustment = parseFloat($(this).find('option:selected').data('price-adjustment'));
            finalPrice += adjustment;
        });

        card.find('.sale-price').text('₹' + finalPrice.toFixed(2));
    }).trigger('change');

    // Add to cart button click
    $('.add-to-cart-btn').on('click', function() {
        const card = $(this).closest('.product-card');
        const productId = card.data('product-id');
        const selectedOptions = {};
        card.find('.variant-select').each(function() {
            const optionName = $(this).siblings('label').text().replace(':', '');
            const optionValue = $(this).find('option:selected').text();
            selectedOptions[optionName] = optionValue;
        });
        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { id: productId, quantity: 1, options: JSON.stringify(selectedOptions) },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Product added to cart!');
                    $('#cart-badge').text(response.total_items);
                } else { alert(response.error || 'Could not add to cart.'); }
            },
            error: function(xhr) {
                if (xhr.status == 401) {
                    alert('Please log in to add items to your cart.');
                    window.location.href = 'login.php?redirect=products.php';
                } else { alert('An error occurred.'); }
            }
        });
    });

    // Add to wishlist button click
    $('.add-to-wishlist-btn').on('click', function() {
        const card = $(this).closest('.product-card');
        const productId = card.data('product-id');
        $.ajax({
            url: 'add_to_wishlist.php',
            method: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            success: function(response) {
                if(response.success) { alert(response.message); }
                else { alert(response.error || 'Could not add to wishlist.'); }
            },
            error: function(xhr) {
                if (xhr.status == 401) {
                    alert('Please log in to add items to your wishlist.');
                    window.location.href = 'login.php?redirect=products.php';
                } else { alert('An error occurred while adding to wishlist.'); }
            }
        });
    });

    function updateCartBadge() {
        $.ajax({ url: 'update_cart_badge.php', method: 'GET', success: function(response) { $('#cart-badge').text(response); } });
    }
    updateCartBadge();
});
</script>

<?php include 'includes/footer.php'; ?>
