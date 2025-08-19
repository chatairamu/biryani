# Orugallu Biryani - Full-Featured E-commerce Platform

This project is a dynamic, database-driven e-commerce website for a restaurant, built with PHP and MySQL. It was developed from a static template into a full-featured application with a complete administrative backend and a user-facing storefront, including advanced features for store management and user experience.

## Features

### User-Facing Features
- **Product Discovery:**
    - **Product Listings:** Browse all products with dynamic pricing.
    - **Search:** A comprehensive search engine queries product names, descriptions, categories, and tags.
    - **Filtering & Sorting:** Users can filter products by category/tag and sort them by price, name, and average rating.
    - **Pagination:** All product and search result pages are paginated for better performance and usability.
- **Product Details:**
    - A dedicated page for each product showing all details.
    - **Product Variations:** Select options like Size and Spice Level, with prices that update dynamically.
    - **Related Products:** A "You Might Also Like" section shows products from the same category.
    - **Reviews & Ratings:** Users who have purchased a product can leave a star rating and a review. Only admin-approved reviews are displayed.
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
- **Paginated & Sortable Data Tables:** All major data tables (Products, Orders, Users, Reviews) have clickable headers for sorting and are paginated.
- **Full Product Management:**
    - Admins can add/edit products, including MRP, sale price, weight, and a product-specific GST rate.
    - **Variant Management:** A detailed interface for creating product options (e.g., Size) and their values (e.g., Large, +₹100).
- **Taxonomy Management:**
    - **Categories & Tags:** Admins can create, edit, and delete product categories and tags.
- **Order Management:** Admins can view all orders and update their status (e.g., from 'Processing' to 'Shipped').
- **Review Management:** A dedicated page for admins to view, approve, and delete user-submitted reviews before they appear on the site.
- **Coupon Management:** Admins can create and manage percentage-based or fixed-amount coupon codes with start and end dates.
- **Store Settings Panel:** A dedicated page to manage global settings like delivery charge rules.

---

## Project Setup Instructions

(Instructions remain the same)

---

## How to Create an Admin User

(Instructions remain the same)
