-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 08, 2025 at 03:40 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agrisea`
--

-- --------------------------------------------------------

--
-- Table structure for table `business_profiles`
--

CREATE TABLE `business_profiles` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_type` enum('individual','partnership','corporation','cooperative') NOT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `years_in_business` int(11) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_phone` varchar(20) DEFAULT NULL,
  `business_email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_profiles`
--

INSERT INTO `business_profiles` (`id`, `user_id`, `business_name`, `business_type`, `tax_id`, `registration_number`, `years_in_business`, `business_address`, `business_phone`, `business_email`, `website`, `created_at`, `updated_at`) VALUES
(1, 5, 'N/A', 'individual', NULL, NULL, 0, NULL, NULL, NULL, NULL, '2025-12-08 01:28:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `parent_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Vegetables', 'Fresh vegetables from local farms', NULL, NULL, 1, '2025-11-28 00:10:43', NULL),
(2, 'Fruits', 'Fresh fruits from local orchards', NULL, NULL, 1, '2025-11-28 00:10:43', NULL),
(3, 'Fish', 'Fresh fish from local waters', NULL, NULL, 1, '2025-11-28 00:10:43', NULL),
(4, 'Meat', 'Fresh meat from local livestock', NULL, NULL, 1, '2025-11-28 00:10:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_message_replies`
--

CREATE TABLE `contact_message_replies` (
  `id` int(10) UNSIGNED NOT NULL,
  `message_id` int(10) UNSIGNED NOT NULL,
  `reply_text` text NOT NULL,
  `replier_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(10) UNSIGNED DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `status` enum('Pending','Approved','ReadyToShip','Delivered','Rejected') NOT NULL DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `subtotal`, `total`, `status`, `payment_method`, `payment_status`, `transaction_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 4, 'ORD-20251208-69362F5040713', 250.00, 250.00, '', 'gcash', 'paid', NULL, NULL, '2025-12-08 01:52:16', '2025-12-08 01:52:54'),
(2, 4, 'ORD-20251208-6936348050063', 250.00, 250.00, '', 'gcash', 'paid', NULL, NULL, '2025-12-08 02:14:24', '2025-12-08 02:14:27'),
(3, 4, 'ORD-20251208-6936350DA62FF', 250.00, 250.00, '', 'paymaya', 'paid', NULL, NULL, '2025-12-08 02:16:45', '2025-12-08 02:16:49'),
(4, 4, 'ORD-20251208-69363526E9985', 250.00, 250.00, '', 'paymaya', 'paid', NULL, NULL, '2025-12-08 02:17:10', '2025-12-08 02:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `seller_id`, `quantity`, `unit_price`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 5, 1, 250.00, 250.00, '2025-12-08 01:52:16', NULL),
(2, 2, 9, 5, 1, 250.00, 250.00, '2025-12-08 02:14:24', NULL),
(3, 3, 8, 5, 1, 250.00, 250.00, '2025-12-08 02:16:45', NULL),
(4, 4, 8, 5, 1, 250.00, 250.00, '2025-12-08 02:17:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_ratings`
--

CREATE TABLE `order_ratings` (
  `rating_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_history`
--

CREATE TABLE `payment_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `subscription_id` int(10) UNSIGNED DEFAULT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'PHP',
  `payment_method` varchar(50) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_history`
--

INSERT INTO `payment_history` (`id`, `user_id`, `subscription_id`, `order_id`, `amount`, `currency`, `payment_method`, `transaction_id`, `status`, `processed_at`, `created_at`, `updated_at`) VALUES
(1, 4, NULL, 1, 250.00, 'PHP', 'gcash', 'TXN-69362F762C22B', 'completed', '2025-12-08 01:52:54', '2025-12-08 01:52:54', NULL),
(2, 4, NULL, 2, 250.00, 'PHP', 'gcash', 'TXN-69363483A5228', 'completed', '2025-12-08 02:14:27', '2025-12-08 02:14:27', NULL),
(3, 4, NULL, 3, 250.00, 'PHP', 'paymaya', 'TXN-6936351154887', 'completed', '2025-12-08 02:16:49', '2025-12-08 02:16:49', NULL),
(4, 4, NULL, 4, 250.00, 'PHP', 'paymaya', 'TXN-6936352A28821', 'completed', '2025-12-08 02:17:14', '2025-12-08 02:17:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL DEFAULT 'kg',
  `stock` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `seller_id` int(11) DEFAULT NULL,
  `weight_unit` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `user_id`, `category_id`, `name`, `description`, `price`, `unit`, `stock`, `status`, `is_featured`, `sales_count`, `sku`, `barcode`, `weight`, `created_at`, `updated_at`, `seller_id`, `weight_unit`) VALUES
(6, 5, NULL, 'Bangus', 'Bangus', 250.00, 'kg', 249, 'published', 0, 1, NULL, NULL, NULL, '2025-12-08 01:51:27', '2025-12-08 01:52:54', NULL, 'kg'),
(8, 5, NULL, 'Bangus', 'bangus', 250.00, 'kg', 248, 'published', 0, 2, NULL, NULL, NULL, '2025-12-08 02:10:07', '2025-12-08 02:17:14', NULL, 'kg'),
(9, 5, NULL, 'tilapia', 'tilapia', 250.00, 'kg', 249, 'published', 0, 1, NULL, NULL, NULL, '2025-12-08 02:13:51', '2025-12-08 02:14:27', NULL, 'kg'),
(10, 5, NULL, 'Tamban', 'Tamban', 150.00, 'kg', 1500, 'published', 0, 0, NULL, NULL, NULL, '2025-12-08 02:37:49', NULL, NULL, 'kg');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `is_primary`, `created_at`, `sort_order`) VALUES
(6, 8, 'uploads/products/8_1_1765159807.jpg', NULL, 1, '2025-12-08 02:10:07', 0),
(7, 9, 'uploads/products/9_1_1765160031.jpg', NULL, 1, '2025-12-08 02:13:51', 0),
(8, 10, 'uploads/products/10_1_1765161469.jpg', NULL, 1, '2025-12-08 02:37:49', 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `order_item_id` int(10) UNSIGNED DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `review` text NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `seller_analytics`
-- (See below for the actual view)
--
CREATE TABLE `seller_analytics` (
`seller_id` int(10) unsigned
,`total_orders` bigint(21)
,`delivered_orders` decimal(22,0)
,`avg_rating` decimal(7,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `seller_ratings`
--

CREATE TABLE `seller_ratings` (
  `id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `plan_type` enum('basic','premium','pro') NOT NULL,
  `status` enum('pending','active','expired','cancelled') NOT NULL DEFAULT 'pending',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 month),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `name_extension` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `alternate_phone` varchar(20) DEFAULT NULL,
  `preferred_contact` enum('email','phone','sms') NOT NULL DEFAULT 'email',
  `street_address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'approved',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `profile_image` varchar(255) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `gender` enum('male','female','other','prefer-not-to-say') DEFAULT NULL,
  `newsletter` tinyint(1) NOT NULL DEFAULT 0,
  `marketing` tinyint(1) NOT NULL DEFAULT 0,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `remember_token` varchar(100) DEFAULT NULL,
  `verification_token` varchar(100) DEFAULT NULL,
  `verification_expires` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `review_count` int(11) DEFAULT 0,
  `is_verified_seller` tinyint(1) NOT NULL DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `preferred_language` varchar(10) DEFAULT 'en',
  `timezone` varchar(50) DEFAULT 'Asia/Manila',
  `seller_type` enum('farmer','fisher') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `name_extension`, `email`, `phone`, `alternate_phone`, `preferred_contact`, `street_address`, `city`, `province`, `postal_code`, `phone_verified`, `email_verified`, `password_hash`, `role_id`, `status`, `is_active`, `profile_image`, `birthdate`, `gender`, `newsletter`, `marketing`, `meta`, `remember_token`, `verification_token`, `verification_expires`, `reset_token`, `reset_expires`, `last_login`, `created_at`, `updated_at`, `deleted_at`, `rating`, `review_count`, `is_verified_seller`, `verification_date`, `preferred_language`, `timezone`, `seller_type`) VALUES
(1, 'Admin', '', 'User', '', 'admin@agrisea.local', '09123456789', NULL, 'email', 'Admin Street', 'Admin City', 'Admin Province', '1234', 1, 1, '$2y$10$3qE0BXlnlSfIDH0itrXC1uI3pOvOXyk.iSgnx3KQOK/bBw2lJ9HVe', 1, 'approved', 1, NULL, '1990-01-01', 'male', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 00:10:43', '2025-12-08 01:24:51', NULL, 0.00, 0, 0, NULL, 'en', 'Asia/Manila', NULL),
(2, 'John', '', 'Seller', '', 'seller@example.com', '09123456788', NULL, 'email', 'Seller Street', 'Seller City', 'Seller Province', '1235', 1, 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'approved', 1, NULL, '1990-01-01', 'male', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 00:10:43', NULL, NULL, 0.00, 0, 1, '2025-11-28 00:10:43', 'en', 'Asia/Manila', 'farmer'),
(3, 'Jane', '', 'Buyer', '', 'buyer@example.com', '09123456787', NULL, 'email', 'Buyer Street', 'Buyer City', 'Buyer Province', '1236', 1, 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'approved', 1, NULL, '1990-01-01', 'female', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-28 00:10:43', NULL, NULL, 0.00, 0, 0, NULL, 'en', 'Asia/Manila', NULL),
(4, 'Buyer', 'Buyer', 'Buyer', NULL, 'buyer@gmail.com', '09518815909', '09156561099', 'email', '3a', 'Cbr', 'Adn', '8605', 0, 0, '$2y$10$fD1g.ay9MIG/5XxW6OVUe..rLD1JT0PU1kqN5K0/P4CwSuLLdTwW2', 3, 'approved', 1, NULL, '2002-11-10', 'male', 1, 1, '{\"accttype\":\"buyer\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-08 01:26:53', NULL, NULL, 0.00, 0, 0, NULL, 'en', 'Asia/Manila', NULL),
(5, 'Fisher', 'Fisher', 'Fisher', NULL, 'fisher@gmail.com', '09518815908', '09156561098', 'email', '3a', 'Cbr', 'Adn', '8605', 0, 0, '$2y$10$y2JMbllesMhFxAEAsznNQeJs2lPp3OG0Jd7ZEGL0T8EW3YBrFf99q', 2, 'approved', 1, NULL, '2002-10-10', 'male', 1, 1, '{\"accttype\":\"fisher\"}', NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-08 01:28:14', '2025-12-08 01:28:30', NULL, 0.00, 0, 0, NULL, 'en', 'Asia/Manila', 'fisher');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` enum('billing','shipping','both') NOT NULL DEFAULT 'both',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `street_address` varchar(255) NOT NULL,
  `barangay` varchar(100) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `delivery_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`id`, `user_id`, `type`, `is_default`, `street_address`, `barangay`, `city`, `province`, `country`, `postal_code`, `phone`, `delivery_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'both', 1, 'Admin Street', 'Admin Barangay', 'Admin City', 'Admin Province', 'Philippines', '1234', '09123456789', 'Admin delivery notes', '2025-11-28 00:10:43', NULL),
(2, 2, 'both', 1, 'Seller Street', 'Seller Barangay', 'Seller City', 'Seller Province', 'Philippines', '1235', '09123456788', 'Seller delivery notes', '2025-11-28 00:10:43', NULL),
(3, 3, 'both', 1, 'Buyer Street', 'Buyer Barangay', 'Buyer City', 'Buyer Province', 'Philippines', '1236', '09123456787', 'Buyer delivery notes', '2025-11-28 00:10:43', NULL),
(4, 4, 'both', 1, '3a', NULL, 'Cbr', 'Adn', 'Philippines', '8605', '09518815909', NULL, '2025-12-08 01:26:53', NULL),
(5, 5, 'both', 1, '3a', NULL, 'Cbr', 'Adn', 'Philippines', '8605', '09518815908', NULL, '2025-12-08 01:28:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'admin', 'System administrator with full access', '2025-11-28 00:10:43'),
(2, 'seller', 'Seller account for farmers and fishers', '2025-11-28 00:10:43'),
(3, 'buyer', 'Regular buyer account', '2025-11-28 00:10:43');

-- --------------------------------------------------------

--
-- Table structure for table `user_security_questions`
--

CREATE TABLE `user_security_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `question_1` varchar(255) NOT NULL,
  `answer_1` varchar(255) NOT NULL,
  `question_2` varchar(255) NOT NULL,
  `answer_2` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_security_questions`
--

INSERT INTO `user_security_questions` (`id`, `user_id`, `question_1`, `answer_1`, `question_2`, `answer_2`, `created_at`, `updated_at`) VALUES
(1, 4, 'What is your mother\'s maiden name?', '$2y$10$MRRVKCeosecqlpTpnei.NukOImRGieLEwHtA/vahff5e5.4Y7Np.S', 'What is your father\'s middle name?', '$2y$10$8bH8/wHA5LqAdxmQgtxEju9Xg/kCppHYMRC9kd0azgQj4W/GBTuvu', '2025-12-08 01:26:53', NULL),
(2, 5, 'What is your mother\'s maiden name?', '$2y$10$5bOwv1TRMoC98uf3tzT/VOP5poHRiM/HtJRicQcfbq38K3/ZUNQqW', 'What is your father\'s middle name?', '$2y$10$Vsz.qsOQkwu49ZlGxrCdsuxdVpJKhedC7ngxYvjyyrusJLXQGlDai', '2025-12-08 01:28:14', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_category_sales`
-- (See below for the actual view)
--
CREATE TABLE `v_category_sales` (
`category_name` varchar(100)
,`category_id` int(10) unsigned
,`total_income` decimal(32,2)
,`total_quantity_sold` decimal(32,0)
,`total_orders` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_monthly_sales`
-- (See below for the actual view)
--
CREATE TABLE `v_monthly_sales` (
`month` varchar(7)
,`total_sales` decimal(32,2)
,`total_orders` bigint(21)
,`unique_customers` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_sales`
-- (See below for the actual view)
--
CREATE TABLE `v_product_sales` (
`product_id` int(10) unsigned
,`product_name` varchar(255)
,`unit_price` decimal(10,2)
,`category_name` varchar(100)
,`seller_name` varchar(201)
,`total_income` decimal(32,2)
,`total_quantity_sold` decimal(32,0)
,`total_orders` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_seller_performance`
-- (See below for the actual view)
--
CREATE TABLE `v_seller_performance` (
`seller_id` int(10) unsigned
,`seller_name` varchar(201)
,`seller_type` enum('farmer','fisher')
,`total_income` decimal(32,2)
,`total_products_sold` decimal(32,0)
,`total_orders` bigint(21)
,`avg_rating` decimal(7,4)
,`total_ratings` bigint(21)
,`delivered_orders` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_top_sellers_monthly`
-- (See below for the actual view)
--
CREATE TABLE `v_top_sellers_monthly` (
`month` varchar(7)
,`seller_id` int(10) unsigned
,`seller_name` varchar(201)
,`seller_type` enum('farmer','fisher')
,`total_income` decimal(32,2)
,`total_products_sold` decimal(32,0)
,`total_orders` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `seller_analytics`
--
DROP TABLE IF EXISTS `seller_analytics`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `seller_analytics`  AS SELECT `o`.`user_id` AS `seller_id`, count(0) AS `total_orders`, sum(case when `o`.`status` = 'Delivered' then 1 else 0 end) AS `delivered_orders`, avg(`r`.`rating`) AS `avg_rating` FROM (`orders` `o` left join `order_ratings` `r` on(`r`.`order_id` = `o`.`id`)) GROUP BY `o`.`user_id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_category_sales`
--
DROP TABLE IF EXISTS `v_category_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_category_sales`  AS SELECT `c`.`name` AS `category_name`, `c`.`id` AS `category_id`, coalesce(sum(`oi`.`subtotal`),0) AS `total_income`, coalesce(sum(`oi`.`quantity`),0) AS `total_quantity_sold`, count(distinct `o`.`id`) AS `total_orders` FROM (((`categories` `c` left join `products` `p` on(`c`.`id` = `p`.`category_id`)) left join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) left join `orders` `o` on(`oi`.`order_id` = `o`.`id` and `o`.`payment_status` = 'paid')) WHERE `o`.`created_at` is not null GROUP BY `c`.`id`, `c`.`name` ;

-- --------------------------------------------------------

--
-- Structure for view `v_monthly_sales`
--
DROP TABLE IF EXISTS `v_monthly_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_monthly_sales`  AS SELECT date_format(`o`.`created_at`,'%Y-%m') AS `month`, coalesce(sum(`o`.`total`),0) AS `total_sales`, count(distinct `o`.`id`) AS `total_orders`, count(distinct `o`.`user_id`) AS `unique_customers` FROM `orders` AS `o` WHERE `o`.`payment_status` = 'paid' AND `o`.`created_at` is not null GROUP BY date_format(`o`.`created_at`,'%Y-%m') ORDER BY date_format(`o`.`created_at`,'%Y-%m') DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_product_sales`
--
DROP TABLE IF EXISTS `v_product_sales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_product_sales`  AS SELECT `p`.`id` AS `product_id`, `p`.`name` AS `product_name`, `p`.`price` AS `unit_price`, `c`.`name` AS `category_name`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `seller_name`, coalesce(sum(`oi`.`subtotal`),0) AS `total_income`, coalesce(sum(`oi`.`quantity`),0) AS `total_quantity_sold`, count(distinct `o`.`id`) AS `total_orders` FROM ((((`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) left join `users` `u` on(`p`.`user_id` = `u`.`id`)) left join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) left join `orders` `o` on(`oi`.`order_id` = `o`.`id` and `o`.`payment_status` = 'paid')) WHERE `o`.`created_at` is not null GROUP BY `p`.`id`, `p`.`name`, `p`.`price`, `c`.`name`, `u`.`first_name`, `u`.`last_name` ORDER BY coalesce(sum(`oi`.`subtotal`),0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_seller_performance`
--
DROP TABLE IF EXISTS `v_seller_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_seller_performance`  AS SELECT `u`.`id` AS `seller_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `seller_name`, `u`.`seller_type` AS `seller_type`, coalesce(sum(`oi`.`subtotal`),0) AS `total_income`, coalesce(sum(`oi`.`quantity`),0) AS `total_products_sold`, count(distinct `o`.`id`) AS `total_orders`, coalesce(avg(`orating`.`rating`),0) AS `avg_rating`, count(`orating`.`rating_id`) AS `total_ratings`, count(case when `o`.`status` = 'Delivered' then 1 end) AS `delivered_orders` FROM ((((`users` `u` left join `products` `p` on(`u`.`id` = `p`.`user_id`)) left join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) left join `orders` `o` on(`oi`.`order_id` = `o`.`id` and `o`.`payment_status` = 'paid')) left join `order_ratings` `orating` on(`u`.`id` = `orating`.`seller_id`)) WHERE `u`.`role_id` = (select `user_roles`.`id` from `user_roles` where `user_roles`.`name` = 'seller') AND `o`.`created_at` is not null GROUP BY `u`.`id`, `u`.`first_name`, `u`.`last_name`, `u`.`seller_type` ORDER BY coalesce(sum(`oi`.`subtotal`),0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `v_top_sellers_monthly`
--
DROP TABLE IF EXISTS `v_top_sellers_monthly`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_top_sellers_monthly`  AS SELECT date_format(`o`.`created_at`,'%Y-%m') AS `month`, `u`.`id` AS `seller_id`, concat(`u`.`first_name`,' ',`u`.`last_name`) AS `seller_name`, `u`.`seller_type` AS `seller_type`, coalesce(sum(`oi`.`subtotal`),0) AS `total_income`, coalesce(sum(`oi`.`quantity`),0) AS `total_products_sold`, count(distinct `o`.`id`) AS `total_orders` FROM (((`users` `u` join `products` `p` on(`u`.`id` = `p`.`user_id`)) join `order_items` `oi` on(`p`.`id` = `oi`.`product_id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`id` and `o`.`payment_status` = 'paid')) WHERE `u`.`role_id` = (select `user_roles`.`id` from `user_roles` where `user_roles`.`name` = 'seller') AND `o`.`created_at` is not null GROUP BY date_format(`o`.`created_at`,'%Y-%m'), `u`.`id`, `u`.`first_name`, `u`.`last_name`, `u`.`seller_type` ORDER BY date_format(`o`.`created_at`,'%Y-%m') DESC, coalesce(sum(`oi`.`subtotal`),0) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `business_profiles`
--
ALTER TABLE `business_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message_id` (`message_id`),
  ADD KEY `idx_replier_id` (`replier_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_seller` (`user_id`,`seller_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_seller` (`seller_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_related` (`related_id`,`related_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_seller_id` (`seller_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Indexes for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_subscription_id` (`subscription_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_processed_at` (`processed_at`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_primary` (`is_primary`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_product_order` (`user_id`,`product_id`,`order_id`),
  ADD KEY `idx_product_rating` (`product_id`,`rating`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_is_verified` (`is_verified`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `seller_ratings`
--
ALTER TABLE `seller_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_order` (`order_id`),
  ADD KEY `idx_seller_rating` (`seller_id`,`rating`),
  ADD KEY `idx_buyer_seller` (`buyer_id`,`seller_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_status` (`user_id`,`status`),
  ADD KEY `idx_plan_type` (`plan_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role_status` (`role_id`,`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_alt_phone` (`alternate_phone`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type` (`user_id`,`type`),
  ADD KEY `idx_default` (`user_id`,`is_default`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_province` (`province`),
  ADD KEY `idx_postal_code` (`postal_code`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `business_profiles`
--
ALTER TABLE `business_profiles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_ratings`
--
ALTER TABLE `order_ratings`
  MODIFY `rating_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_history`
--
ALTER TABLE `payment_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_ratings`
--
ALTER TABLE `seller_ratings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `business_profiles`
--
ALTER TABLE `business_profiles`
  ADD CONSTRAINT `fk_business_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `contact_message_replies`
--
ALTER TABLE `contact_message_replies`
  ADD CONSTRAINT `fk_replies_admin` FOREIGN KEY (`replier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_replies_message` FOREIGN KEY (`message_id`) REFERENCES `contact_messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `fk_order_items_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_ratings`
--
ALTER TABLE `order_ratings`
  ADD CONSTRAINT `fk_rating_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_history`
--
ALTER TABLE `payment_history`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_ratings`
--
ALTER TABLE `seller_ratings`
  ADD CONSTRAINT `fk_ratings_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ratings_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_security_questions`
--
ALTER TABLE `user_security_questions`
  ADD CONSTRAINT `fk_security_questions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
