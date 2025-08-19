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
          <a class="navbar-brand" href="index.php">
              <img src="https://picsum.photos/seed/logo/86/67" alt="Logo" width="86" height="67" class="d-inline-block align-text-top">
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
              <li class="list-group-item"><a href="index.php" class="text-decoration-none">Home</a></li>
              <li class="list-group-item"><a href="products.php" class="text-decoration-none">Products</a></li>
              <li class="list-group-item"><a href="cart.php" class="text-decoration-none">My Cart</a></li>
              <?php if (isset($_SESSION['user_id'])): ?>
                  <li class="list-group-item"><a href="wishlist.php" class="text-decoration-none">My Wishlist</a></li>
                  <li class="list-group-item"><a href="dashboard.php" class="text-decoration-none">My Dashboard</a></li>
                  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                      <li class="list-group-item"><a href="admin_dashboard.php" class="text-decoration-none">Admin Dashboard</a></li>
                  <?php endif; ?>
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
