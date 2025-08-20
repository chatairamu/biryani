<?php
// ... (all the PHP logic from the previous version of this file) ...

$is_logged_in = isset($_SESSION['user_id']);
?>
<?php include 'includes/header.php'; ?>

<div class="container mt-5 pt-4">
    <!-- ... (HTML structure for the cart) ... -->
    <?php foreach ($cart_items as $item): ?>
        <div class="card mb-3 product-row" data-cart-id="<?php echo $item['cart_item_id']; ?>">
            <!-- ... (display logic for the item) ... -->
        </div>
    <?php endforeach; ?>
    <!-- ... (rest of the HTML) ... -->
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

    function updateCartItem(id, newQuantity) {
        let postData = { quantity: newQuantity };
        if (isLoggedIn) {
            postData.cart_item_id = id;
        } else {
            postData.cart_key = id;
        }

        $.ajax({
            url: 'update_cart_item.php',
            method: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    location.reload();
                } else {
                    alert(response.error || 'An error occurred.');
                }
            },
            error: function() {
                alert('A server error occurred.');
            }
        });
    }

    $('.plus-btn, .minus-btn, .remove-btn').on('click', function() {
        const row = $(this).closest('.product-row');
        const cartId = row.data('cart-id');
        const quantityElement = row.find('.quantity');
        let currentQuantity = parseInt(quantityElement.text());
        let newQuantity;

        if ($(this).hasClass('plus-btn')) {
            newQuantity = currentQuantity + 1;
        } else if ($(this).hasClass('minus-btn')) {
            newQuantity = currentQuantity - 1;
        } else { // remove-btn
            if (!confirm('Are you sure you want to remove this item?')) return;
            newQuantity = 0;
        }

        updateCartItem(cartId, newQuantity);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
