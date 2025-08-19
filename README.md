# Orugallu Biryani - E-commerce Platform

This project is a dynamic, database-driven e-commerce website for a restaurant, built with PHP and MySQL. It was developed from a static template into a full-featured application with a complete administrative backend and a user-facing storefront.

## Features

### User-Facing Features
- **Product Listings:** Browse products with dynamic pricing and options.
- **Product Variations:** Select product options like Size and Spice Level, with prices that update dynamically.
- **User Authentication:** Secure user registration and login system with password hashing.
- **Shopping Cart:** A persistent, database-driven shopping cart that handles product variants.
- **Checkout Process:** A full checkout flow with calculations for GST, delivery charges, and coupon discounts.
- **User Dashboard:** Allows users to view their order history and update their profile information, including their address.
- **Embedded Map:** Displays the user's saved location on an embedded OpenStreetMap.

### Admin Features
- **Admin Dashboard:** A central hub for store management with sections for products, orders, users, coupons, and settings.
- **Product Management:** Admins can add new products, including setting MRP, sale price, and weight.
- **Product Variant Management:** A detailed interface for creating product options (e.g., Size) and their values (e.g., Large, +₹100), allowing for complex product offerings.
- **Coupon Management:** Admins can create and manage percentage-based or fixed-amount coupon codes.
- **Store Settings Panel:** A dedicated page to manage global store settings, including:
  - GST/Tax Rate
  - Multiple Delivery Charge modes (Fixed, Per KM, Weight-based)
  - Minimum Order Value for free delivery.

### Technical Features
- **Database-Driven:** All data is stored in a MySQL database.
- **Security:** Uses prepared statements (PDO) to prevent SQL injection and `htmlspecialchars` to prevent XSS attacks.
- **Role-Based Access Control:** A simple 'user' and 'admin' role system protects the admin panel.

---

## Project Setup Instructions

To run this project on a local server (like XAMPP or WAMP), follow these steps:

### 1. Database Setup

1.  **Start your Apache and MySQL services.**
2.  Open a database management tool like `phpMyAdmin`.
3.  Create a new database. You can name it whatever you like (e.g., `biryani_db`).
4.  Select the newly created database.
5.  Go to the "Import" tab.
6.  Click "Choose File" and select the `db_setup.sql` file located in the root of this project.
7.  Click "Go" or "Import" to run the SQL script. This will create all the necessary tables and populate them with sample product data.

### 2. Configuration

1.  Open the `config.php` file in the root of the project.
2.  Update the following lines with your database credentials:
    ```php
    define('DB_HOST', 'your_db_host'); // e.g., 'localhost' or '127.0.0.1'
    define('DB_NAME', 'your_db_name'); // The name of the database you created in step 1
    define('DB_USER', 'your_db_user'); // e.g., 'root'
    define('DB_PASS', 'your_db_password'); // Your database password (might be empty by default in XAMPP)
    ```
3.  (Optional) Update the `BASE_URL` if your project is in a subdirectory.

### 3. Running the Application

1.  Place the project files in your web server's root directory (e.g., `htdocs` for XAMPP).
2.  Open your web browser and navigate to the project's URL (e.g., `http://localhost/project-folder-name`).

---

## How to Create an Admin User

For security reasons, an admin user is not created by default. Follow these steps to create one:

1.  **Sign Up:** Navigate to the signup page (`signup.php`) and create a new user account like a regular user.
2.  **Edit in Database:**
    - Open `phpMyAdmin` and select your project's database.
    - Find and open the `users` table.
    - Locate the user you just created.
    - Click "Edit".
    - Change the value in the `role` column from `user` to `admin`.
    - Save the changes.
3.  **Log In:** You can now log in with that user's credentials to access the Admin Dashboard and all its features.
