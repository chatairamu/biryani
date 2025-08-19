<?php
session_start();
require_once 'includes/db_connection.php';

// Fetch products for the sliders
// For now, we'll use the same set of latest products for all sliders.
// In a real application, you might have different logic for each (e.g., based on sales, views, etc.)
$latest_products_stmt = $pdo->query("SELECT name, price, image_url as img FROM products ORDER BY created_at DESC LIMIT 8");
$latest_products = $latest_products_stmt->fetchAll();

// We'll use the same data for all sliders for this example
$best_sellers = $latest_products;
$trending_products = $latest_products;

// Fetch categories (dummy data for now, could be a separate table)
$featured_categories = [
    [ 'name' => "Biryani", 'img' => "https://picsum.photos/id/1021/640/480" ],
    [ 'name' => "Kebabs", 'img' => "https://picsum.photos/id/1022/640/480" ],
    [ 'name' => "Desserts", 'img' => "https://picsum.photos/id/1023/640/480" ],
    [ 'name' => "Beverages", 'img' => "https://picsum.photos/id/1024/640/480" ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orugallu Biryani</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/styles.css">
  <style>
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
      height: 100px;
      object-fit: cover;
      border-radius: 5px;
    }
    .slider-wrapper {
      position: relative;
      margin-bottom: 2rem;
    }
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
    .slider-wrapper:hover .slider-btn {
      display: block;
    }
    .slider-btn-left { left: 5px; }
    .slider-btn-right { right: 5px; }
    .section-title { margin: 2rem 0 1rem; text-align: center; }
  </style>
</head>
<body>

  <?php include 'includes/header.php'; ?>

  <!-- Main Content -->
  <div class="container mt-5 pt-4">
    <!-- Hero Carousel -->
    <div id="heroCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <img src="https://picsum.photos/id/1003/1200/400" class="d-block w-100" alt="Slide 1">
          <div class="carousel-caption d-none d-md-block">
            <h5>Exclusive Biryani Offers</h5>
            <p>Discover our latest exclusive deals and discounts.</p>
            <a href="products.php" class="btn btn-primary">Shop Now</a>
          </div>
        </div>
        <div class="carousel-item">
          <img src="https://picsum.photos/id/1004/1200/400" class="d-block w-100" alt="Slide 2">
          <div class="carousel-caption d-none d-md-block">
            <h5>New Arrivals</h5>
            <p>Be the first to try our newest culinary creations.</p>
            <a href="products.php" class="btn btn-primary">Discover More</a>
          </div>
        </div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span><span class="visually-hidden">Next</span>
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
  </div>

  <?php include 'includes/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Get product data from PHP
      const bestSellers = <?php echo json_encode($best_sellers); ?>;
      const trendingProducts = <?php echo json_encode($trending_products); ?>;
      const latestProducts = <?php echo json_encode($latest_products); ?>;
      const featuredCategories = <?php echo json_encode($featured_categories); ?>;

      function createProductSlider(id, products) {
        const slider = document.getElementById(id);
        if (!slider || products.length === 0) return;
        products.forEach(product => {
          const div = document.createElement("div");
          div.innerHTML = `
            <img src="${product.img}" alt="${product.name}">
            <h6>${product.name}</h6>
            <p>₹${parseFloat(product.price).toFixed(2)}</p>
          `;
          slider.appendChild(div);
        });
      }

      function createCategoryGrid(id, categories) {
        const grid = document.getElementById(id);
        if (!grid || categories.length === 0) return;
        categories.forEach(category => {
          const div = document.createElement("div");
          div.classList.add("col-6", "col-md-3", "mb-3");
          div.innerHTML = `
            <div class="card">
              <img src="${category.img}" class="card-img-top" alt="${category.name}" style="height: 150px; object-fit: cover;">
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
