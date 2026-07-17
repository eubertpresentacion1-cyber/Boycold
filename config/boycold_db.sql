-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 17, 2026 at 02:34 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `boycold_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(191) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `recipient_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `street_address` varchar(255) NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `zip_code` varchar(10) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `user_name`, `label`, `recipient_name`, `phone`, `street_address`, `barangay`, `city`, `province`, `zip_code`, `is_default`, `created_at`) VALUES
(1, NULL, 'Takt Hoshino', 'Home', 'Takt Hoshino', '', 'N/A', 'San Jose', 'San Luis', 'Pampanga', '2014', 1, '2026-07-17 12:35:29');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int NOT NULL,
  `branch_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 'BAL', 'BoyCold Cafe - Baliuag', 'Baliuag, Bulacan', 'active', '2026-07-14 17:54:00', '2026-07-14 17:54:00'),
(2, 'BUS', 'BoyCold Cafe - Bustos', 'Bustos, Bulacan', 'active', '2026-07-14 17:54:00', '2026-07-14 17:54:00');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `milk` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addons` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sales_summary`
--

CREATE TABLE `daily_sales_summary` (
  `id` int NOT NULL,
  `sale_date` date NOT NULL,
  `branch_id` int DEFAULT NULL,
  `total_orders` int NOT NULL DEFAULT '0',
  `completed_orders` int NOT NULL DEFAULT '0',
  `cancelled_orders` int NOT NULL DEFAULT '0',
  `gross_revenue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `items_sold` int NOT NULL DEFAULT '0',
  `avg_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employee_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`firstname`,_utf8mb4' ',`lastname`)) STORED,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('cashier','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cashier',
  `is_active` tinyint NOT NULL DEFAULT '1',
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `branch_id` int DEFAULT NULL,
  `current_device_id` int DEFAULT NULL,
  `last_login_device_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `firstname`, `lastname`, `email`, `password`, `pin`, `role`, `is_active`, `avatar`, `created_at`, `updated_at`, `branch_id`, `current_device_id`, `last_login_device_id`) VALUES
(1, NULL, NULL, 'jdelamerced933@gmail.com', '$2y$10$yhefk.fb8uaFILW5EQAkROiyOcIusrog5bW0cpQll9VxQdPdljoiC', '$2y$10$PWXYKRLn81us7iMPGLyGMOK9Fy9x4HsFpppT1llwcOv97n7VfQWru', 'cashier', 1, NULL, '2026-07-17 20:16:25', '2026-07-17 20:16:25', 1, NULL, NULL),
(2, NULL, NULL, 'jdelamerced52@gmail.com', '$2y$10$qcjEcFZ1rXxQqGd9UT7FM.v0zgRSEHzOha7Yl3p3t6RaDZ1GpmGPK', '$2y$10$Kb0uTj.GOcAGk77PXzEozu3/k8Kn9DwhieZxRYXTCT2P6KGy.Neau', 'cashier', 1, NULL, '2026-07-17 21:27:24', '2026-07-17 21:27:24', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredients`
--

CREATE TABLE `ingredients` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unit',
  `branch_id` int DEFAULT NULL,
  `stock` decimal(10,3) NOT NULL DEFAULT '0.000',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ingredients`
--

INSERT INTO `ingredients` (`id`, `name`, `unit`, `branch_id`, `stock`, `created_at`, `updated_at`) VALUES
(1, 'Whole Milk', 'ml', 1, 5000.000, '2026-07-12 18:24:58', '2026-07-16 18:25:24'),
(2, 'Oat Milk', 'ml', 1, 2000.000, '2026-07-12 18:24:58', '2026-07-16 18:25:24'),
(3, 'Espresso Shot', 'shot', 1, 1000.000, '2026-07-12 18:24:58', '2026-07-16 18:25:24'),
(4, 'Whipped Cream', 'g', 1, 1000.000, '2026-07-12 18:24:58', '2026-07-16 18:25:24');

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_usage_daily`
--

CREATE TABLE `ingredient_usage_daily` (
  `id` int NOT NULL,
  `usage_date` date NOT NULL,
  `branch_id` int DEFAULT NULL,
  `ingredient_id` int UNSIGNED NOT NULL,
  `amount_used` decimal(10,3) NOT NULL DEFAULT '0.000',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int NOT NULL,
  `employee_id` int DEFAULT NULL,
  `branch_id` int DEFAULT NULL,
  `device_id` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_system` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_status` enum('success','failed','inactive','branch_mismatch','device_not_registered','missing_branch','invalid_credentials') COLLATE utf8mb4_unicode_ci DEFAULT 'failed',
  `login_datetime` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loyalty_transactions`
--

CREATE TABLE `loyalty_transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `card_no` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` int NOT NULL,
  `device_id` int NOT NULL,
  `employee_id` int NOT NULL,
  `transaction_type` enum('bean_award','stamp_award','redemption') COLLATE utf8mb4_unicode_ci DEFAULT 'bean_award',
  `points_awarded` int DEFAULT '1',
  `previous_balance` int DEFAULT '0',
  `new_balance` int DEFAULT '0',
  `order_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','confirmed','preparing','ready','delivered','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `order_type` enum('dine-in','takeout','delivery','pickup') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'dine-in',
  `payment_method` enum('cod','gcash') COLLATE utf8mb4_unicode_ci DEFAULT 'cod',
  `payment_status` enum('unpaid','paid','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_fee` decimal(10,2) DEFAULT '0.00',
  `tax` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `branch_id` int DEFAULT NULL,
  `device_id` int DEFAULT NULL,
  `cashier_id` int DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_name`, `status`, `order_type`, `payment_method`, `payment_status`, `subtotal`, `delivery_fee`, `tax`, `total`, `branch_id`, `device_id`, `cashier_id`, `address`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Takt Hoshino', 'completed', 'delivery', 'gcash', 'paid', 105.00, 30.00, 5.00, 140.00, 1, NULL, NULL, 'N/A, San Jose, San Luis, Pampanga, 2014', '', '2026-07-17 20:35:32', '2026-07-17 20:36:29'),
(2, 'Takt Hoshino', 'completed', 'delivery', 'cod', 'paid', 238.00, 30.00, 5.00, 273.00, 2, NULL, NULL, 'N/A, San Jose, San Luis, Pampanga, 2014', '', '2026-07-17 21:28:07', '2026-07-17 21:31:46');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int NOT NULL,
  `order_id` int NOT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `line_total` decimal(10,2) NOT NULL,
  `milk` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `addons` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_name`, `product_image`, `unit_price`, `quantity`, `line_total`, `milk`, `addons`, `order_type`, `notes`) VALUES
(169, 1, 'French Vanilla', '/picture/Franch Vanilla.png', 105.00, 1, 105.00, 'Oat Milk Milk', 'Whipped Cream, Chocolate Drizzle', 'Pick-Up', ''),
(170, 2, 'Matcha banana Pudding', '/picture/Matcha banana Pudding.png', 119.00, 2, 238.00, 'Oat Milk Milk', '', 'Pick-Up', '');

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `id` int NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('register','reset') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'register',
  `status` enum('pending','verified','expired') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `otp_sent` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp`
--

INSERT INTO `otp` (`id`, `firstname`, `lastname`, `email`, `password`, `otp`, `type`, `status`, `attempts`, `otp_sent`, `expires_at`, `ip`) VALUES
(1, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$PDvKma3rXinYlFFoTmTCxuKOgkcCmP8VnM.ki4lK6bUtAUk8CWS1q', '022112', 'register', 'expired', 0, '2026-07-17 20:10:02', '2026-07-17 20:20:02', '::1'),
(2, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$J/W92ASy.XtgG6CUlY3MuuWS5lnXHoEarJAc5M3s1p0Ky2njsnBWK', '745102', 'register', 'expired', 0, '2026-07-17 20:11:04', '2026-07-17 20:21:04', '::1'),
(3, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$xH5teu0/PD6a5XRxy33Ds.FKXhHF5Ata5X/sZ40CsmxEL0/pednty', '771100', 'register', 'expired', 0, '2026-07-17 20:13:57', '2026-07-17 20:23:57', '::1'),
(4, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$6aCoaNXa3OoUs6mtlWjEQ.xHbChkrPUg8HzEDrT23kflcayRpT3EC', '586802', 'register', 'expired', 0, '2026-07-17 20:14:25', '2026-07-17 20:24:25', '::1'),
(5, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$0TZaVPRPnHJUa1Tr0xU0iOIwrxLsHjPBFszQEMdTWGd3wMc26gq9W', '045779', 'register', 'expired', 0, '2026-07-17 20:19:47', '2026-07-17 20:29:47', '::1'),
(6, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$PTjlFvqPQyq0IS9AO6yCGugjrdFWhpu3BHmstFT/Gbfu4Brgwqbei', '163870', 'register', 'expired', 0, '2026-07-17 20:23:22', '2026-07-17 20:33:22', '::1'),
(7, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$G9mbvMZK13mxFBgGquHxBuYztwfw2FjbqXVlg2x1qYWTkC7VqKCAq', '824863', 'register', 'expired', 0, '2026-07-17 20:25:09', '2026-07-17 20:35:09', '::1'),
(8, 'Jefferson', 'Merced', 'jdelamerced933@gmail.com', '$2y$10$A2Gab5uRcBp9BN1M8lH1k.0pu9iXj7vWlC7.qm7z.3T1yJ6/0fnU2', '957295', 'register', 'expired', 0, '2026-07-17 20:25:22', '2026-07-17 20:35:22', '::1'),
(9, 'Jefferson', 'Merced', 'jdelamerced933@gmail.com', '$2y$10$f86yyNsDiSiQqfyPT6eD6.TOv0FIPRAPtqpDwqYsV7qyMIjcE76sq', '177926', 'register', 'expired', 0, '2026-07-17 20:28:27', '2026-07-17 20:38:27', '::1'),
(10, 'Jefferson', 'Merced', 'jdelamerced933@gmail.com', '$2y$10$XOX/KoYG8t29XrjW2UWqq.I3/fQe0rOHect8AyRYzOSdmhoHJYvDa', '636365', 'register', 'verified', 0, '2026-07-17 20:32:55', '2026-07-17 20:42:55', '::1'),
(11, 'Shiro', 'Hoshino', 'jdelamerced52@gmail.com', '$2y$10$vopv5c5gA1mXczs.2oF9AelFoWGCSUD5T7LNvYje6qhrrXWMhdMLe', '969690', 'register', 'verified', 0, '2026-07-17 21:57:22', '2026-07-17 22:07:22', '::1');

--
-- Triggers `otp`
--
DELIMITER $$
CREATE TRIGGER `set_otp_expiry` BEFORE INSERT ON `otp` FOR EACH ROW BEGIN
  SET NEW.expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pos_devices`
--

CREATE TABLE `pos_devices` (
  `id` int NOT NULL,
  `device_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` int NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_status` enum('active','inactive','pending') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `current_employee_id` int DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL,
  `is_locked` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int NOT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_available` tinyint DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `description`, `price`, `image`, `category`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'Americano', 'Classic Americano', 69.00, '/picture/Americano.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(2, 'Cafe Latte', 'Smooth and creamy latte', 85.00, '/picture/Cafe Latte.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(3, 'Spanish Latte', 'Rich condensed milk latte', 95.00, '/picture/Spanish Latte.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(4, 'Dark Mocha', 'Bold mocha blend', 99.00, '/picture/Dark Mocha.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(5, 'White Mocha', 'Smooth white chocolate mocha', 99.00, '/picture/White Mocha.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(6, 'Caramel Macchiato', 'Caramel layered macchiato', 89.00, '/picture/Caramel Macchiato.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:58:15'),
(7, 'Hazelnut Latte', 'Nutty hazelnut latte', 85.00, '/picture/Hazelnut Latte.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(8, 'Tiramisu Latte', 'Tiramisu flavored latte', 95.00, '/picture/Tiramisu Latte.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:57:07'),
(9, 'Sea Salt Latte', 'Savory sea salt latte', 115.00, '/picture/Sea salt Latte.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(10, 'Salted Mango Dream', 'Mango with a hint of salt', 139.00, '/picture/Salted Mango Dream.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(11, 'Biscoff Creamy Latte', 'Biscoff cookie latte', 109.00, '/picture/Biscoff Creamy Latte.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(12, 'Butter scotch latte', 'Butterscotch flavored latte', 105.00, '/picture/Butter scotch latte.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(13, 'Nutella Hazelnut latte', 'Nutella and hazelnut blend', 99.00, '/picture/Nutella Hazelnut latte.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(14, 'Salted Caramel', 'Salted caramel delight', 99.00, '/picture/Salted Caramel.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(15, 'Salted Macadamia', 'Macadamia nut with sea salt', 119.00, '/picture/Salted Macadamia.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(16, 'Pure matcha', 'Traditional pure matcha', 85.00, '/picture/Pure matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(17, 'Dirty Matcha', 'Matcha with espresso', 119.00, '/picture/Dirty Matcha.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:48:26'),
(18, 'Matcha Latte', 'Creamy matcha latte', 95.00, '/picture/Matcha Latte.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(19, 'Cheesecake Matcha', 'Cheesecake matcha fusion', 125.00, '/picture/Cheesecake Matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(20, 'Choco Matcha', 'Chocolate matcha blend', 105.00, '/picture/Choco Matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(21, 'Lavender Matcha', 'Lavender infused matcha', 109.00, '/picture/Lavender Matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(22, 'Strawberry Matcha', 'Strawberry matcha fusion', 105.00, '/picture/Strawberry Matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(23, 'Seasalt Matcha', 'Matcha with sea salt', 99.00, '/picture/Seasalt Matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(24, 'Matcha Frappe', 'Cold matcha frappe', 99.00, '/picture/Matcha Frappe.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-16 14:01:27'),
(25, 'Matcha Freddo', 'Iced matcha freddo', 89.00, '/picture/Matcha Freddo.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(26, 'Matcha banana Pudding', 'Matcha with banana', 119.00, '/picture/Matcha banana Pudding.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(27, 'Lolly Matcha waffle', 'Matcha flavored waffle', 139.00, '/picture/Matcha waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:43:48'),
(28, 'Strawberry Milk', 'Fresh strawberry milk shake', 79.00, '/picture/Strawberry Milk.png', 'non-coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:51:53'),
(29, 'Blueberry Milk', 'Blueberry milk shake', 79.00, '/picture/Blueberry Milk.png', 'non-coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:52:10'),
(30, 'Blueberry shake', 'Premium blueberry shake', 85.00, '/picture/BLUEBERRY SHAKE 1.png', 'fruit-shake', 1, '2026-06-14 15:51:13', '2026-06-16 14:10:00'),
(31, 'Strawberry shake', 'Fresh strawberry shake', 79.00, '/picture/Strawberry shake.png', 'fruit-shake', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(32, 'Mango graham', 'Mango with graham', 89.00, '/picture/Mango graham.png', 'fruit-shake', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(33, 'Mango matcha', 'Mango and matcha fusion', 99.00, '/picture/Mango matcha.png', 'matcha-fusion', 1, '2026-06-14 15:51:13', '2026-06-16 14:00:39'),
(34, 'Berry mango', 'Berry and mango blend', 89.00, '/picture/Berry mango.png', 'fruit-shake', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(35, 'Berry Caramel Bliss', 'Berry with caramel', 99.00, '/picture/Berry Caramel Bliss.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:54:50'),
(36, 'Berry Oreo', 'Berry with Oreo', 99.00, '/picture/Berry Oreo.png', 'fruit-shake', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(37, 'mango oreo', 'Mango and Oreo blend', 99.00, '/picture/mango oreo.png', 'fruit-shake', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(38, 'Caramel Frappe', 'Caramel iced frappe', 99.00, '/picture/Caramel Frappe.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(39, 'Oreo Frappe', 'Oreo cookie frappe', 99.00, '/picture/Oreo Frappe.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(40, 'Biscoff frappe', 'Biscoff cookie frappe', 99.00, '/picture/Biscoff frappe.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(41, 'Cheesecake Frappe', 'Cheesecake flavored frappe', 99.00, '/picture/Cheesecake Frappe.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(42, 'Nuttela Hazelnut Frappe', 'Nutella hazelnut frappe', 99.00, '/picture/Nuttela Hazelnut Frappe.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(43, 'Lolly Chocolate waffle', 'Chocolate flavored waffle', 129.00, '/picture/Chocolate waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:40:09'),
(44, 'Lolly Biscoff waffle', 'Biscoff cookie waffle', 139.00, '/picture/Biscoff waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:38:41'),
(45, 'Lolly Oreo waffle', 'Oreo cookie waffle', 139.00, '/picture/Oreo waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:40:42'),
(46, 'Lolly Strawberry waffle', 'Fresh strawberry waffle', 139.00, '/picture/Strawberry waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:40:34'),
(47, 'Lolly tiramisu waffle', 'Tiramisu flavored waffle', 149.00, '/picture/tiramisu waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:40:49'),
(48, 'Lolly ube waffle', 'Ube flavored waffle', 149.00, '/picture/ube waffle.png', 'waffles', 1, '2026-06-14 15:51:13', '2026-06-16 13:40:57'),
(49, 'French Vanilla', 'French vanilla drink', 75.00, '/picture/Franch Vanilla.png', 'coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:48:58'),
(50, 'White cocoa', 'White chocolate cocoa', 85.00, '/picture/White cocoa.png', 'non-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(51, 'Cheesecake Latte', 'Cheesecake flavored latte', 99.00, '/picture/Cheesecake Latte.png', 'special-coffee', 1, '2026-06-14 15:51:13', '2026-06-16 13:56:20'),
(52, 'Choco Vanilla Cookie', 'Chocolate vanilla cookie drink', 129.00, '/picture/Choco Vanilla Cookie.png', 'non-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(53, 'Choco Banana Pudding', 'Chocolate banana pudding drink', 179.00, '/picture/Choco Banana Pudding.png', 'non-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(54, 'Milky Oreo', 'Oreo milk drink', 89.00, '/picture/Milky Oreo.png', 'non-coffee', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(55, 'Java Chips', 'Java chips blended drink', 99.00, '/picture/Java Chips.png', 'frappe-series', 1, '2026-06-14 15:51:13', '2026-06-16 14:02:14'),
(56, 'French Fries', 'Crispy French fries', 69.00, '/picture/Fries.png', 'bites', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(57, 'Chicken Poppers', 'Crispy chicken poppers', 79.00, '/picture/Chicken Poppers.png', 'bites', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(58, 'Chicken poppers and fries', 'Poppers with fries combo', 99.00, '/picture/Chicken poppers and fries.png', 'bites', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(59, 'Beef Natchos', 'Beef loaded nachos', 149.00, '/picture/Beef Natchos.png', 'bites', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(60, 'Fries and Chicken Poppers', 'Fries with chicken poppers', 99.00, '/picture/Chicken poppers and fries.png', 'bites', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(61, 'Beef Quesadilla', 'Grilled beef quesadilla', 149.00, '/picture/Beef Quesadilla.png', 'quesadilla', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(62, 'Chicken Quesadilla', 'Grilled chicken quesadilla', 159.00, '/picture/Chicken Quesadilla.png', 'quesadilla', 1, '2026-06-14 15:51:13', '2026-06-14 15:51:13'),
(63, 'Messy Tuna Quesadilla', 'Tuna and spinach quesadilla', 129.00, '/picture/Messy Tuna Spinach.png', 'quesadilla', 1, '2026-06-14 15:51:13', '2026-06-16 13:45:58'),
(64, 'Choco Berry', 'Chocolate berry drink', 179.00, '/picture/Choco Berry.png', 'non-coffee', 1, '2026-06-16 13:01:53', '2026-06-16 13:03:33'),
(65, 'Einspanner Latte', 'Strong espresso with whipped cream', 149.00, '/picture/Einspanner Latte.png', 'special-coffee', 1, '2026-06-16 13:13:02', '2026-06-16 13:13:02'),
(66, 'hershey delight', 'Hershey chocolate frappe', 95.00, '/picture/hershey delight.png', 'frappe-series', 1, '2026-06-16 13:34:49', '2026-06-16 13:34:49'),
(67, 'White Smores', 'White chocolate smores frappe', 129.00, '/picture/white smores.png', 'frappe-series', 1, '2026-06-16 14:13:32', '2026-06-16 14:13:32');

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `id` int UNSIGNED NOT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ingredient_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,3) NOT NULL DEFAULT '0.000',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`id`, `product_name`, `ingredient_id`, `amount`, `created_at`) VALUES
(1, 'Spanish Latte', 1, 150.000, '2026-07-12 18:24:58'),
(2, 'French Vanilla', 1, 150.000, '2026-07-12 18:24:58'),
(3, 'Biscoff frappe', 1, 200.000, '2026-07-12 18:24:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_sales_daily`
--

CREATE TABLE `product_sales_daily` (
  `id` int NOT NULL,
  `sale_date` date NOT NULL,
  `product_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity_sold` int NOT NULL DEFAULT '0',
  `revenue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shift_logs`
--

CREATE TABLE `shift_logs` (
  `id` int NOT NULL,
  `branch_id` int DEFAULT NULL,
  `device_id` int DEFAULT NULL,
  `employee_id` int NOT NULL,
  `opening_cash_float` decimal(10,2) NOT NULL DEFAULT '0.00',
  `closing_cash_count` decimal(10,2) DEFAULT NULL,
  `cash_sales` decimal(10,2) DEFAULT '0.00',
  `gcash_sales` decimal(10,2) DEFAULT '0.00',
  `total_sales` decimal(10,2) DEFAULT '0.00',
  `cash_orders` int DEFAULT '0',
  `gcash_orders` int DEFAULT '0',
  `total_orders` int DEFAULT '0',
  `cash_difference` decimal(10,2) DEFAULT '0.00',
  `opened_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shift_logs`
--

INSERT INTO `shift_logs` (`id`, `branch_id`, `device_id`, `employee_id`, `opening_cash_float`, `closing_cash_count`, `cash_sales`, `gcash_sales`, `total_sales`, `cash_orders`, `gcash_orders`, `total_orders`, `cash_difference`, `opened_at`, `closed_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, 1000.00, NULL, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, '2026-07-17 12:16:38', NULL, 'open', '2026-07-17 12:16:38', '2026-07-17 12:16:38'),
(2, 2, NULL, 2, 500.00, NULL, 0.00, 0.00, 0.00, 0, 0, 0, 0.00, '2026-07-17 13:27:37', NULL, 'open', '2026-07-17 13:27:37', '2026-07-17 13:27:37');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`firstname`,_utf8mb4' ',`lastname`)) STORED NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint NOT NULL DEFAULT '0',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `loyalty_beans` int NOT NULL DEFAULT '0',
  `loyalty_stamps` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `is_verified`, `phone`, `address`, `avatar`, `card_no`, `created_at`, `updated_at`, `loyalty_beans`, `loyalty_stamps`) VALUES
(1, 'Takt', 'Hoshino', 'jdelamerced933@gmail.com', '$2y$10$XOX/KoYG8t29XrjW2UWqq.I3/fQe0rOHect8AyRYzOSdmhoHJYvDa', 1, '09205075495', NULL, 'uploads/avatars/avatar_1_1784294080.jpg', 'BY-2026001', '2026-07-17 20:33:32', '2026-07-17 21:14:40', 0, 0),
(2, 'Shiro', 'Hoshino', 'jdelamerced52@gmail.com', '$2y$10$vopv5c5gA1mXczs.2oF9AelFoWGCSUD5T7LNvYje6qhrrXWMhdMLe', 1, NULL, NULL, NULL, 'BY-2026002', '2026-07-17 21:57:49', '2026-07-17 21:57:49', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_name` (`user_name`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user_product` (`user_name`,`product_name`);

--
-- Indexes for table `daily_sales_summary`
--
ALTER TABLE `daily_sales_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_daily_sales_date` (`sale_date`),
  ADD KEY `idx_branch_id` (`branch_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_employees_email` (`email`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fav_user_product` (`user_name`,`product_name`);

--
-- Indexes for table `ingredients`
--
ALTER TABLE `ingredients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ingredient_name` (`name`),
  ADD KEY `idx_branch_id` (`branch_id`);

--
-- Indexes for table `ingredient_usage_daily`
--
ALTER TABLE `ingredient_usage_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ingredient_usage_date_ingredient` (`usage_date`,`ingredient_id`),
  ADD KEY `idx_ingredient_usage_ingredient` (`ingredient_id`),
  ADD KEY `idx_branch_id` (`branch_id`);

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indexes for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_card_no` (`card_no`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_device_id` (`device_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_transaction_type` (`transaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_name`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `idx_orders_created_at` (`created_at`),
  ADD KEY `idx_orders_status_created_at` (`status`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_items_order` (`order_id`);

--
-- Indexes for table `otp`
--
ALTER TABLE `otp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pos_devices`
--
ALTER TABLE `pos_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_code` (`device_code`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `idx_current_employee` (`current_employee_id`),
  ADD KEY `idx_session_id` (`session_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_name` (`product_name`),
  ADD KEY `idx_ingredient_id` (`ingredient_id`);

--
-- Indexes for table `product_sales_daily`
--
ALTER TABLE `product_sales_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_sales_date_name` (`sale_date`,`product_name`),
  ADD KEY `idx_product_sales_product` (`product_name`);

--
-- Indexes for table `shift_logs`
--
ALTER TABLE `shift_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_branch_id` (`branch_id`),
  ADD KEY `idx_device_id` (`device_id`),
  ADD KEY `idx_employee_id` (`employee_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_opened_at` (`opened_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD UNIQUE KEY `uq_users_name` (`user_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_sales_summary`
--
ALTER TABLE `daily_sales_summary`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredients`
--
ALTER TABLE `ingredients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ingredient_usage_daily`
--
ALTER TABLE `ingredient_usage_daily`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loyalty_transactions`
--
ALTER TABLE `loyalty_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `pos_devices`
--
ALTER TABLE `pos_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_sales_daily`
--
ALTER TABLE `product_sales_daily`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shift_logs`
--
ALTER TABLE `shift_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_name`) REFERENCES `users` (`user_name`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_fav_user` FOREIGN KEY (`user_name`) REFERENCES `users` (`user_name`) ON DELETE CASCADE;

--
-- Constraints for table `ingredient_usage_daily`
--
ALTER TABLE `ingredient_usage_daily`
  ADD CONSTRAINT `fk_ingredient_usage_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `login_logs_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `login_logs_ibfk_3` FOREIGN KEY (`device_id`) REFERENCES `pos_devices` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_name`) REFERENCES `users` (`user_name`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `pos_devices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`cashier_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pos_devices`
--
ALTER TABLE `pos_devices`
  ADD CONSTRAINT `pos_devices_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD CONSTRAINT `fk_pi_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
