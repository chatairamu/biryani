</div> <!-- Close Main Content Container -->

  <!-- Sticky Bottom Navigation Bar -->
  <nav class="navbar navbar-light bg-light fixed-bottom">
      <div class="container-fluid">
          <a class="navbar-brand" href="index.php">
              <img src="images/home-icon.svg" alt="Home" width="20">
              <span>Home</span>
          </a>
          <a class="navbar-brand" href="orders.php">
              <img src="images/profile-icon.svg" alt="Profile" width="20">
              <span>Orders</span>
          </a>
          <a class="navbar-brand" href="products.php">
              <img src="images/list.svg" alt="Products" width="20">
              <span>Products</span>
          </a>
          <a class="navbar-brand" href="search.php">
              <img src="images/search.svg" alt="Search" width="20">
              <span>Search</span>
          </a>
          <a class="navbar-brand" href="cart.php">
              <img src="images/cart-icon.svg" alt="Cart" width="20">
              <span>Cart</span>
              <span id="cart-badge" class="badge bg-danger"><?php echo isset($_COOKIE['totalQuantity']) ? $_COOKIE['totalQuantity'] : 0; ?></span>
          </a>
      </div>
  </nav>

  <!-- Bootstrap Bundle JS (Required for components like sidebar, modals, etc.) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <!-- A global script for site-wide functionality like updating the cart badge -->
  <script>
    function updateCartBadge() {
        fetch('update_cart_badge.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('cart-badge');
                if (badge) {
                    badge.textContent = data.totalQuantity;
                    badge.style.display = data.totalQuantity > 0 ? 'inline-block' : 'none';
                }
            })
            .catch(error => console.error('Error updating cart badge:', error));
    }

    // Update badge on page load
    document.addEventListener('DOMContentLoaded', updateCartBadge);

    // AJAX Add to Cart
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('ajax-add-to-cart')) {
            e.preventDefault();
            const button = e.target;
            const productCard = button.closest('.product-card');
            const productId = productCard.dataset.productId;
            const quantityInput = productCard.querySelector('.quantity-input');
            const quantity = quantityInput ? quantityInput.value : 1;

            const options = {};
            productCard.querySelectorAll('.variant-select').forEach(select => {
                options[select.dataset.optionName] = select.value;
            });

            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('options', JSON.stringify(options));

            button.disabled = true;
            button.innerHTML = 'Adding...';

            fetch('add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartBadge();
                    button.innerHTML = 'Added!';
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = 'Add to Cart';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to add item.');
                }
            })
            .catch(error => {
                console.error('Add to cart error:', error);
                alert(error.message);
                button.disabled = false;
                button.innerHTML = 'Add to Cart';
            });
        }
    });
  </script>
</body>
</html>
