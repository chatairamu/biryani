<?php
session_start();
require_once 'includes/db_connection.php';

// Fetch all products from the database
$products_stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$products = $products_stmt->fetchAll();

// Fetch all options and values in a more optimized way
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

// Get user's cart from the database
$cart = [];
if (isset($_SESSION['user_id'])) {
    $cart_stmt = $pdo->prepare("SELECT product_id, quantity, options FROM cart WHERE user_id = ?");
    $cart_stmt->execute([$_SESSION['user_id']]);
    $cart_items = $cart_stmt->fetchAll();
    // In a real app, you'd handle variant-specific quantities here. For now, we simplify.
    foreach ($cart_items as $item) {
        $cart[$item['product_id']] = $item['quantity'];
    }
}
?>

<?php include 'includes/header.php'; ?>

<h1>Our Products</h1>
<div class="row">
    <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100 product-card" data-product-id="<?php echo $product['id']; ?>" data-base-price="<?php echo $product['sale_price']; ?>">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
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
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                                <path d="m8 2.748-.717-.737C5.6.271 2.216 1.333 1.053 3.468 0 5.4-1.5 7.822 8 12.502 17.5 7.822 16 5.4 14.947 3.468c-1.163-2.135-4.547-3.2-6.23-2.73L8 2.748zM8 15C-7.333 4.868 3.279-2.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-2.042 23.333 4.867 8 15z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
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
    }).trigger('change'); // Trigger change on page load to set initial price correctly

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
            data: {
                id: productId,
                quantity: 1,
                options: JSON.stringify(selectedOptions) // Send selected options as a JSON string
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    alert('Product added to cart!');
                    // Use the total_items from the response to update the badge
                    $('#cart-badge').text(response.total_items);
                } else {
                    alert(response.error || 'Could not add to cart.');
                }
            },
            error: function(xhr) {
                if (xhr.status == 401) {
                    alert('Please log in to add items to your cart.');
                    window.location.href = 'login.php?redirect=products.php';
                } else {
                    alert('An error occurred.');
                }
            }
        });
    });

    function updateCartBadge() {
        $.ajax({
            url: 'update_cart_badge.php',
            method: 'GET',
            success: function(response) {
                $('#cart-badge').text(response);
            }
        });
    }
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
                if(response.success) {
                    alert(response.message);
                } else {
                    alert(response.error || 'Could not add to wishlist.');
                }
            },
            error: function(xhr) {
                if (xhr.status == 401) {
                    alert('Please log in to add items to your wishlist.');
                    window.location.href = 'login.php?redirect=products.php';
                } else {
                    alert('An error occurred while adding to wishlist.');
                }
            }
        });
    });

    // Initial load
    updateCartBadge();
});
</script>

<?php include 'includes/footer.php'; ?>
