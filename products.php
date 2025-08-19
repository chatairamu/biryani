<?php
session_start();
require_once 'includes/db_connection.php';

// Fetch all products from the database
$stmt = $pdo->query("SELECT id, name, description, price, image_url as image FROM products ORDER BY name ASC");
$products = $stmt->fetchAll();

// Get user's cart from the database
$cart = [];
if (isset($_SESSION['user_id'])) {
    $cart_stmt = $pdo->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
    $cart_stmt->execute([$_SESSION['user_id']]);
    $cart_items = $cart_stmt->fetchAll();
    // Re-key the array by product_id for easy lookup
    foreach ($cart_items as $item) {
        $cart[$item['product_id']] = $item['quantity'];
    }
}
?>

<?php include 'includes/header.php'; ?>

<h1>Our Products</h1>
<div class="row">
    <?php foreach ($products as $product): ?>
        <?php
        $productId = $product['id'];
        $quantity = isset($cart[$productId]) ? $cart[$productId] : 0;
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <p class="card-text flex-grow-1"><?php echo htmlspecialchars($product['description']); ?></p>
                    <p class="card-text"><strong>₹<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></strong></p>
                    <div class="d-flex align-items-center mt-auto">
                        <?php if ($quantity > 0): ?>
                            <button class="btn btn-outline-secondary btn-sm minus-btn" data-id="<?php echo $productId; ?>">-</button>
                            <span class="mx-2 quantity" data-id="<?php echo $productId; ?>"><?php echo $quantity; ?></span>
                            <button class="btn btn-outline-secondary btn-sm plus-btn" data-id="<?php echo $productId; ?>">+</button>
                        <?php else: ?>
                            <button class="btn btn-primary add-to-cart" data-id="<?php echo $productId; ?>">Add to Cart</button>
                        <?php endif; ?>
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
    function handleCartResponse(response) {
        if (response.success) {
            $('#cart-badge').text(response.total_quantity);
        } else {
            alert(response.message || 'An error occurred.');
        }
    }

    function addToCart(productId, quantity) {
        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { id: productId, quantity: quantity },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    handleCartResponse(response);
                } else {
                    alert(response.message);
                }
            }
        });
    }

    // Event delegation for "Add to Cart" button
    $(document).on('click', '.add-to-cart', function() {
        const productId = $(this).data('id');
        addToCart(productId, 1);
        // Update UI immediately
        $(this).replaceWith(`
            <button class="btn btn-outline-secondary btn-sm minus-btn" data-id="${productId}">-</button>
            <span class="mx-2 quantity" data-id="${productId}">1</span>
            <button class="btn btn-outline-secondary btn-sm plus-btn" data-id="${productId}">+</button>
        `);
    });

    // Plus button click
    $(document).on('click', '.plus-btn', function() {
        const productId = $(this).data('id');
        const quantityElement = $(`.quantity[data-id="${productId}"]`);
        let quantity = parseInt(quantityElement.text());
        quantity += 1;
        quantityElement.text(quantity);
        addToCart(productId, 1);
    });

    // Minus button click
    $(document).on('click', '.minus-btn', function() {
        const productId = $(this).data('id');
        const quantityElement = $(`.quantity[data-id="${productId}"]`);
        let quantity = parseInt(quantityElement.text());
        quantity -= 1;
        quantityElement.text(quantity);
        addToCart(productId, -1);

        if (quantity === 0) {
            $(this).closest('.d-flex').html(`<button class="btn btn-primary add-to-cart" data-id="${productId}">Add to Cart</button>`);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
