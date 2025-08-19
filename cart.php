<?php
session_start();
require_once 'includes/db_connection.php';

// User must be logged in to view their cart
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch cart items for the user from the database
$stmt = $pdo->prepare(
    "SELECT p.id, p.name, p.price, p.image_url as image, c.quantity
     FROM cart c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?"
);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

// Calculate subtotal
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <h1>Your Cart</h1>
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($cart_items)): ?>
                        <p>Your cart is empty.</p>
                        <a href="products.php" class="btn btn-primary">Start Shopping</a>
                    <?php else: ?>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex align-items-center mb-4 product-row" data-id="<?php echo $item['id']; ?>">
                                <div class="flex-shrink-0">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p>Price: ₹<?php echo number_format($item['price'], 2); ?></p>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-outline-secondary btn-sm minus-btn" data-id="<?php echo $item['id']; ?>">-</button>
                                        <span class="mx-2 quantity"><?php echo $item['quantity']; ?></span>
                                        <button class="btn btn-outline-secondary btn-sm plus-btn" data-id="<?php echo $item['id']; ?>">+</button>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="mb-0"><strong>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></p>
                                    <button class="btn btn-danger btn-sm remove-btn mt-2" data-id="<?php echo $item['id']; ?>">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($cart_items)): ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <div class="d-flex justify-content-between">
                            <p>Subtotal:</p>
                            <p>₹<?php echo number_format($subtotal, 2); ?></p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <p><strong>Total:</strong></p>
                            <p><strong>₹<?php echo number_format($subtotal, 2); ?></strong></p>
                        </div>
                        <div class="d-grid">
                            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {

    // Function to update cart via AJAX
    function updateCart(productId, quantityChange) {
        $.ajax({
            url: 'add_to_cart.php',
            method: 'POST',
            data: { id: productId, quantity: quantityChange },
            dataType: 'json',
            success: function(response) {
                // Reload the page to reflect the change. This is simpler and more robust
                // than trying to update all prices and totals on the client-side.
                location.reload();
            },
            error: function(xhr, status, error) {
                // If the user is not logged in, the server will send a 401 error.
                if (xhr.status == 401) {
                    alert('Your session has expired. Please log in again to manage your cart.');
                    window.location.href = 'login.php?redirect=cart.php';
                } else {
                    alert('An error occurred while updating your cart. Please try again.');
                }
            }
        });
    }

    // Plus button click
    $('.plus-btn').on('click', function() {
        const productId = $(this).data('id');
        updateCart(productId, 1); // Increment by 1
    });

    // Minus button click
    $('.minus-btn').on('click', function() {
        const productId = $(this).data('id');
        updateCart(productId, -1); // Decrement by 1
    });

    // Remove button click
    $('.remove-btn').on('click', function() {
        const productId = $(this).data('id');
        // To remove, we find the current quantity and send a negative of it.
        const quantity = parseInt($(this).closest('.product-row').find('.quantity').text());
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            updateCart(productId, -quantity);
        }
    });

});
</script>

<?php include 'includes/footer.php'; ?>
