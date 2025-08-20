-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2024 at 02:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `biryani_shop`
--

-- All existing tables here...
CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `options` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- ... (categories, coupons, orders, order_items, password_resets)

--
-- Table structure for table `products`
--
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) NOT NULL,
  `sale_start_date` date DEFAULT NULL,
  `sale_end_date` date DEFAULT NULL,
  `thumbnail_url` varchar(255) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(10,2) NOT NULL DEFAULT 0.50,
  `gst_rate` decimal(5,2) NOT NULL DEFAULT 5.00,
  `extra_packaging_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `product_images`
--
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ... (product_categories, product_options, etc. all other tables)


--
-- Indexes for dumped tables
--
-- ... (all existing indexes)

ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--
-- ... (all existing auto_increments)

ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--
-- ... (all existing constraints)

ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

COMMIT;
