-- Sample Data for Orugallu Biryani E-commerce Site

--
-- Sample Categories
--
INSERT INTO `categories` (`name`, `slug`, `meta_title`, `meta_description`) VALUES
('Biryani', 'biryani', 'Authentic Biryani Varieties', 'The best and most authentic Hyderabadi biryani.'),
('Starters', 'starters', 'Delicious Starters', 'A range of delicious starters to whet your appetite.'),
('Desserts', 'desserts', 'Sweet Desserts', 'Sweet desserts to complete your meal.');

--
-- Sample Tags
--
INSERT INTO `tags` (`name`, `slug`) VALUES
('Spicy', 'spicy'),
('Chicken', 'chicken'),
('Mutton', 'mutton'),
('Vegetarian', 'vegetarian'),
('Most Popular', 'most-popular');

--
-- Sample Products
--
INSERT INTO `products` (`name`, `description`, `mrp`, `sale_price`, `image_url`, `stock`, `weight`, `gst_rate`, `extra_packaging_charge`, `avg_rating`) VALUES
('Hyderabadi Chicken Dum Biryani', 'A classic Hyderabadi biryani with succulent chicken pieces cooked in a sealed pot with aromatic spices and long-grain basmati rice.', 350.00, 320.00, 'assets/images/products/chicken_biryani.jpg', 50, 0.75, 5.00, 15.00, 4.5),
('Mutton Zafrani Biryani', 'A rich and flavorful biryani made with tender mutton pieces, saffron-infused rice, and a blend of exotic spices.', 450.00, 450.00, 'assets/images/products/mutton_biryani.jpg', 30, 0.75, 5.00, 20.00, 4.8),
('Special Veg Dum Biryani', 'A fragrant and delicious vegetable biryani cooked with a variety of fresh vegetables, paneer, and aromatic spices.', 280.00, 280.00, 'assets/images/products/veg_biryani.jpg', 40, 0.75, 5.00, 15.00, 4.2),
('Chicken 65', 'A popular spicy, deep-fried chicken dish originating from Chennai, India. A perfect starter.', 250.00, 225.00, 'assets/images/products/chicken_65.jpg', 60, 0.50, 5.00, 10.00, 4.6),
('Paneer Tikka Kebab', 'Cubes of paneer marinated in spices and yogurt, then grilled in a tandoor. A vegetarian delight.', 220.00, 220.00, 'assets/images/products/paneer_tikka.jpg', 50, 0.50, 5.00, 10.00, 4.4),
('Qubani ka Meetha', 'A traditional Hyderabadi dessert made from dried apricots, garnished with almonds. Served with fresh cream.', 150.00, 150.00, 'assets/images/products/qubani_ka_meetha.jpg', 100, 0.25, 5.00, 5.00, 4.9);

--
-- Link Products to Categories
--
INSERT INTO `product_categories` (`product_id`, `category_id`) VALUES
(1, 1), -- Chicken Biryani -> Biryani
(2, 1), -- Mutton Biryani -> Biryani
(3, 1), -- Veg Biryani -> Biryani
(4, 2), -- Chicken 65 -> Starters
(5, 2), -- Paneer Tikka -> Starters
(6, 3); -- Qubani ka Meetha -> Desserts

--
-- Link Products to Tags
--
INSERT INTO `product_tags` (`product_id`, `tag_id`) VALUES
(1, 1), -- Chicken Biryani -> Spicy
(1, 2), -- Chicken Biryani -> Chicken
(1, 5), -- Chicken Biryani -> Most Popular
(2, 1), -- Mutton Biryani -> Spicy
(2, 3), -- Mutton Biryani -> Mutton
(2, 5), -- Mutton Biryani -> Most Popular
(3, 4), -- Veg Biryani -> Vegetarian
(4, 1), -- Chicken 65 -> Spicy
(4, 2), -- Chicken 65 -> Chicken
(5, 4); -- Paneer Tikka -> Vegetarian
