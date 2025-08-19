<?php
session_start();

// Dummy product data (replace with database query later)
$products = [
    [
        'id' => 1,
        'name' => 'Product 1',
        'description' => 'This is a description for Product 1.',
        'price' => 100,
        'image' => 'images/product1.jpg',
    ],
    [
        'id' => 2,
        'name' => 'Product 2',
        'description' => 'This is a description for Product 2.',
        'price' => 200,
        'image' => 'images/product2.jpg',
    ],
    [
        'id' => 3,
        'name' => 'Product 3',
        'description' => 'This is a description for Product 3.',
        'price' => 300,
        'image' => 'images/product3.jpg',
    ],
];

// Get cart data from cookies
$cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
?>

<?php include 'includes/header.php'; ?>

<h1>Product Listing</h1>
<div class="row">
    <?php foreach ($products as $product): ?>
        <?php
        $productId = $product['id'];
        $quantity = isset($cart[$productId]) ? $cart[$productId] : 0;
        ?>
        <div class="col-md-4 mb-4">
            <div class="card">
                <img src="<?php echo $product['image']; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $product['name']; ?></h5>
                    <p class="card-text"><?php echo $product['description']; ?></p>
                    <p class="card-text"><strong>₹<?php echo $product['price']; ?></strong></p>
                    <div class="d-flex align-items-center">
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
    // Function to update cart badge
    function updateCartBadge() {
        $.ajax({
            url: 'update_cart_badge.php',
            method: 'GET',
            success: function(response) {
                $('#cart-badge').text(response);
            }
        });
    }

    // Event delegation for "Add to Cart" button
    $(document).on('click', '.add-to-cart', function() {
        const productId = $(this).data('id');
        const quantity = 1; // Default quantity when adding to cart

        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { id: productId, quantity: quantity },
            success: function(response) {
                // Update UI
                $(`.add-to-cart[data-id="${productId}"]`).replaceWith(`
                    <button class="btn btn-outline-secondary btn-sm minus-btn" data-id="${productId}">-</button>
                    <span class="mx-2 quantity" data-id="${productId}">1</span>
                    <button class="btn btn-outline-secondary btn-sm plus-btn" data-id="${productId}">+</button>
                `);
                updateCartBadge();
            }
        });
    });

    // Plus button click
    $(document).on('click', '.plus-btn', function() {
        const productId = $(this).data('id');
        const quantityElement = $(`.quantity[data-id="${productId}"]`);
        let quantity = parseInt(quantityElement.text());
        quantity += 1;
        quantityElement.text(quantity);

        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { id: productId, quantity: 1 }, // Increment by 1
            success: function(response) {
                updateCartBadge();
            }
        });
    });

    // Minus button click
    $(document).on('click', '.minus-btn', function() {
        const productId = $(this).data('id');
        const quantityElement = $(`.quantity[data-id="${productId}"]`);
        let quantity = parseInt(quantityElement.text());
        if (quantity > 0) {
            quantity -= 1;
            quantityElement.text(quantity);
            
            if (quantity === 0) {
                        // Replace with "Add to Cart" button
                        $(this).closest('.d-flex').html('<button class="btn btn-primary add-to-cart" data-id="' + productId + '">Add to Cart</button>');
                    }

            $.ajax({
                url: 'add_to_cart.php',
                method: 'POST',
                data: { id: productId, quantity: -1 }, // Decrement by 1
                success: function(response) {
                    
                    updateCartBadge();
                }
            });
        }
    });

    // Initialize cart badge on page load
    updateCartBadge();
});
</script>

<?php include 'includes/footer.php'; ?>