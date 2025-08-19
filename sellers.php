<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orugallu Biryani</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../css/styles.css">
  <style>
    /* Additional styles for homepage sections */
    .carousel-caption h5 {
      font-size: 2rem;
    }
    /* Product slider wrapper */
    .slider-wrapper {
      position: relative;
      margin-bottom: 2rem;
    }
    /* Product slider styling */
    .product-slider {
      display: flex;
      overflow-x: auto;
      gap: 1rem;
      padding-bottom: 1rem;
      scroll-behavior: smooth;
    }
    .product-slider::-webkit-scrollbar {
      display: none;
    }
    .product-slider {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    .product-slider > div {
      flex: 0 0 auto;
      width: 150px;
      text-align: center;
      border: 1px solid #ddd;
      padding: 0.5rem;
      border-radius: 5px;
      background-color: #fff;
    }
    .product-slider img {
      max-width: 100%;
      border-radius: 5px;
    }
    /* Slider arrow buttons */
    .slider-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(0,0,0,0.5);
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      cursor: pointer;
      z-index: 10;
      display: none;
    }
    .slider-btn-left {
      left: 5px;
    }
    .slider-btn-right {
      right: 5px;
    }
    .slider-wrapper:hover .slider-btn {
      display: block;
    }
    .section-title {
      margin: 2rem 0 1rem;
      text-align: center;
    }
    /* Extra spacing for Featured Categories */
    .featured-categories {
      margin-bottom: 3rem;
    }
    /* Optional: Add more spacing after Newsletter if needed */
    .newsletter {
      margin-bottom: 3rem;
    }
  </style>
</head>
<body>
  <!-- Top Bar -->
  <nav class="navbar navbar-light bg-light fixed-top">
      <div class="container-fluid">
          <!-- Logo on the left -->
          <a class="navbar-brand" href="../index.php">
              <img src="../images/logo.jpg" alt="Logo" width="86" height="67" class="d-inline-block align-text-top">
          </a>
          <!-- Sidebar Toggle Button on the right -->
          <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
              <span class="navbar-toggler-icon"></span>
          </button>
      </div>
  </nav>

  <!-- Expandable Sidebar -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="sidebar" aria-labelledby="sidebarLabel">
      <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="sidebarLabel">Menu</h5>
          <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
          <ul class="list-group">
              <li class="list-group-item"><a href="../index.php" class="text-decoration-none">Home</a></li>
              <li class="list-group-item"><a href="products.php" class="text-decoration-none">Products</a></li>
              <li class="list-group-item"><a href="#" class="text-decoration-none">Categories</a></li>
              <li class="list-group-item"><a href="#" class="text-decoration-none">Orders</a></li>
              <li class="list-group-item"><a href="#" class="text-decoration-none">Settings</a></li>
              <?php if (isset($_SESSION['user_id'])): ?>
                  <li class="list-group-item"><a href="logout.php" class="text-decoration-none">Logout</a></li>
              <?php else: ?>
                  <li class="list-group-item"><a href="login.php" class="text-decoration-none">Login</a></li>
                  <li class="list-group-item"><a href="signup.php" class="text-decoration-none">Signup</a></li>
              <?php endif; ?>
          </ul>
      </div>
  </div>

  <!-- Main Content -->
  <div class="container mt-5 pt-4">
    <!-- Hero Carousel -->
    <div id="heroCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
      <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
      </div>
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="https://picsum.photos/id/1003/1200/400" class="d-block w-100" alt="Slide 1">
          <div class="carousel-caption d-none d-md-block">
            <h5>Exclusive Offer</h5>
            <p>Discover our latest exclusive deals and discounts.</p>
            <a href="#" class="btn btn-primary">Shop Now</a>
          </div>
        </div>
        <div class="carousel-item">
          <img src="https://picsum.photos/id/1004/1200/400" class="d-block w-100" alt="Slide 2">
          <div class="carousel-caption d-none d-md-block">
            <h5>New Arrivals</h5>
            <p>Be the first to try our newest collection.</p>
            <a href="#" class="btn btn-primary">Discover More</a>
          </div>
        </div>
        <div class="carousel-item">
          <img src="https://picsum.photos/id/1005/1200/400" class="d-block w-100" alt="Slide 3">
          <div class="carousel-caption d-none d-md-block">
            <h5>Seasonal Sale</h5>
            <p>Enjoy discounts on selected items for a limited time.</p>
            <a href="#" class="btn btn-primary">Explore Now</a>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    </div>

    <!-- Best Sellers Section -->
    <section class="product-section">
      <h2 class="section-title">Best Sellers</h2>
      <div class="slider-wrapper">
        <div class="product-slider" id="bestSellers"></div>
        <button class="slider-btn slider-btn-left" onclick="scrollSlider('bestSellers', -200)">&#10094;</button>
        <button class="slider-btn slider-btn-right" onclick="scrollSlider('bestSellers', 200)">&#10095;</button>
      </div>
    </section>

    <!-- Trending Products Section -->
    <section class="product-section">
      <h2 class="section-title">Trending Products</h2>
      <div class="slider-wrapper">
        <div class="product-slider" id="trendingProducts"></div>
        <button class="slider-btn slider-btn-left" onclick="scrollSlider('trendingProducts', -200)">&#10094;</button>
        <button class="slider-btn slider-btn-right" onclick="scrollSlider('trendingProducts', 200)">&#10095;</button>
      </div>
    </section>

    <!-- Latest Products Section -->
    <section class="product-section">
      <h2 class="section-title">Latest Products</h2>
      <div class="slider-wrapper">
        <div class="product-slider" id="latestProducts"></div>
        <button class="slider-btn slider-btn-left" onclick="scrollSlider('latestProducts', -200)">&#10094;</button>
        <button class="slider-btn slider-btn-right" onclick="scrollSlider('latestProducts', 200)">&#10095;</button>
      </div>
    </section>

    <!-- Featured Categories -->
    <section class="featured-categories">
      <h2 class="section-title">Featured Categories</h2>
      <div class="row" id="featuredCategories"></div>
    </section>

    <!-- About Us Section -->
    <section class="about-us text-center my-4">
      <h2>About Us</h2>
      <p>We are passionate about providing the best products and services to our customers.</p>
      <img src="https://picsum.photos/id/1035/600/300" alt="About Us" class="img-fluid">
    </section>

    <!-- Newsletter Subscription -->
    <section class="newsletter text-center my-4">
      <h2>Subscribe to Our Newsletter</h2>
      <form class="row g-2 justify-content-center">
        <div class="col-sm-8">
          <input type="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">Subscribe</button>
        </div>
      </form>
    </section>
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