</div> <!-- Close Main Content Container -->

  <!-- Sticky Bottom Navigation Bar -->
  <nav class="navbar navbar-light bg-light fixed-bottom">
      <div class="container-fluid">
          <a class="navbar-brand" href="../index.php">
              <img src="../images/home-icon.svg" alt="Home" width="20">
              <span>Home</span>
          </a>
          <a class="navbar-brand" href="../orders.php">
              <img src="../images/profile-icon.svg" alt="Profile" width="20">
              <span>Orders</span>
          </a>
          <a class="navbar-brand" href="../products.php">
              <img src="../images/list.svg" alt="Products" width="20">
              <span>Products</span>
          </a>
          <a class="navbar-brand" href="../search.php">
              <img src="../images/search.svg" alt="Search" width="20">
              <span>Search</span>
          </a>
          <a class="navbar-brand" href="../cart.php">
              <img src="../images/cart-icon.svg" alt="Cart" width="20">
              <span>Cart</span>
              <span id="cart-badge" class="badge bg-danger"><?php echo isset($_COOKIE['totalQuantity']) ? $_COOKIE['totalQuantity'] : 0; ?></span>
          </a>
      </div>
  </nav>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const sampleProducts = [
        { name: "Product 1", price: "$10", img: "https://picsum.photos/id/1011/150/150" },
        { name: "Product 2", price: "$20", img: "https://picsum.photos/id/1012/150/150" },
        { name: "Product 3", price: "$30", img: "https://picsum.photos/id/1013/150/150" },
        { name: "Product 4", price: "$40", img: "https://picsum.photos/id/1014/150/150" },
        { name: "Product 5", price: "$50", img: "https://picsum.photos/id/1015/150/150" },
        { name: "Product 6", price: "$60", img: "https://picsum.photos/id/1016/150/150" },
        { name: "Product 7", price: "$70", img: "https://picsum.photos/id/1018/150/150" },
        { name: "Product 8", price: "$80", img: "https://picsum.photos/id/1019/150/150" }
      ];
  
      const bestSellers = sampleProducts;
      const trendingProducts = sampleProducts;
      const latestProducts = sampleProducts;
  
      const featuredCategories = [
        { name: "Electronics", img: "https://picsum.photos/id/1020/640/480" },
        { name: "Fashion", img: "https://picsum.photos/id/1021/640/480" },
        { name: "Home Decor", img: "https://picsum.photos/id/1022/640/480" },
        { name: "Beauty", img: "https://picsum.photos/id/1023/640/480" },
        { name: "Sports", img: "https://picsum.photos/id/1024/640/480" },
        { name: "Books", img: "https://picsum.photos/id/1025/640/480" },
        { name: "Toys", img: "https://picsum.photos/id/1026/640/480" },
        { name: "Accessories", img: "https://picsum.photos/id/1027/640/480" }
      ];
  
      function createProductSlider(id, products) {
        const slider = document.getElementById(id);
        products.forEach(product => {
          const div = document.createElement("div");
          div.innerHTML = `
            <img src="${product.img}" alt="${product.name}">
            <h6>${product.name}</h6>
            <p>${product.price}</p>
          `;
          slider.appendChild(div);
        });
      }
  
      function createCategoryGrid(id, categories) {
        const grid = document.getElementById(id);
        categories.forEach(category => {
          const div = document.createElement("div");
          div.classList.add("col-6", "col-md-4", "col-lg-3");
          div.innerHTML = `
            <div class="card">
              <img src="${category.img}" class="card-img-top" alt="${category.name}">
              <div class="card-body text-center">
                <h5 class="card-title">${category.name}</h5>
              </div>
            </div>
          `;
          grid.appendChild(div);
        });
      }
  
      createProductSlider("bestSellers", bestSellers);
      createProductSlider("trendingProducts", trendingProducts);
      createProductSlider("latestProducts", latestProducts);
      createCategoryGrid("featuredCategories", featuredCategories);
    });
    
    function scrollSlider(sliderId, scrollAmount) {
      document.getElementById(sliderId).scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
  </script>
</body>
</html>