# Orugallu Biryani - Full-Featured E-commerce Platform

This project is a dynamic, database-driven e-commerce website for a restaurant, built with PHP and MySQL. It was developed from a static template into a full-featured application with a complete administrative backend and a user-facing storefront, including advanced features for store management and user experience.

## Features

### User-Facing Features
- **Product Discovery:**
    - **Product Listings:** Browse all products with dynamic pricing.
    - **Search:** A comprehensive search engine queries product names, descriptions, categories, and tags.
    - **Filtering & Sorting:** Users can filter products by category/tag and sort them by price, name, and average rating.
- **Product Details:**
    - A dedicated page for each product showing all details.
    - **Product Variations:** Select options like Size and Spice Level, with prices that update dynamically.
    - **Related Products:** A "You Might Also Like" section shows products from the same category.
- **User Accounts:**
    - **Authentication:** Secure user registration and login system with password hashing.
    - **Forgot Password:** A secure system allows users to reset their password via a unique, expiring token (email sending placeholder included).
- **Shopping & Checkout:**
    - **Wishlist:** Users can save products to a personal wishlist for future purchase.
    - **Shopping Cart:** A persistent, database-driven cart that handles product variants and displays warnings for low-stock items.
    - **Complex Pricing:** The checkout process calculates and displays a full price breakdown, including per-product GST, delivery charges, and coupon discounts.
- **Post-Purchase:**
    - **User Dashboard:** Allows users to update their profile information (address, map coordinates).
    - **Detailed Order History:** Users can view a full, itemized history of their past orders and track their status.

### Admin Features
- **Central Dashboard:** A hub for store management with at-a-glance statistics.
- **Store Statistics:** Displays total revenue, total orders, total users, and a list of top-selling products.
- **Low-Stock Alerts:** A prominent widget warns the admin about products with low inventory.
- **Sortable Data Tables:** All major data tables (Products, Orders, Users) have clickable headers for easy sorting.
- **Full Product Management:**
    - Admins can add/edit products, including MRP, sale price, weight, and a product-specific GST rate.
    - **Variant Management:** A detailed interface for creating product options (e.g., Size) and their values (e.g., Large, +₹100).
- **Taxonomy Management:**
    - **Categories:** Admins can create, edit, and delete product categories.
    - **Tags:** Admins can create, edit, and delete product tags.
- **Order Management:** Admins can view all orders and update their status (e.g., from 'Processing' to 'Shipped').
- **Coupon Management:** Admins can create and manage percentage-based or fixed-amount coupon codes.
- **Store Settings Panel:** A dedicated page to manage global settings like delivery charge rules, default GST, etc.

---

## Project Setup Instructions

To run this project on a local server (like XAMPP or WAMP), follow these steps:

### 1. Database Setup
1.  Start your Apache and MySQL services.
2.  Open a database management tool like `phpMyAdmin`.
3.  Create a new database (e.g., `biryani_db`).
4.  Select the new database and go to the "Import" tab.
5.  Click "Choose File" and select the `db_setup.sql` file from this project.
6.  Click "Go" or "Import" to run the script and create all tables.

### 2. Configuration
1.  Open the `config.php` file.
2.  Update the database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) to match your local environment.

### 3. Running the Application
1.  Place the project files in your web server's root directory (e.g., `htdocs`).
2.  Open your web browser and navigate to the project's URL (e.g., `http://localhost/your-project-folder`).

---

## How to Create an Admin User

For security, an admin account is not created by default.
1.  **Sign Up:** Create a new account on the website's registration page.
2.  **Edit in Database:** Open `phpMyAdmin`, find the `users` table, and edit the new user you just created. Change the value in the `role` column from `user` to `admin`.
3.  **Log In:** You can now log in to access the Admin Dashboard.
