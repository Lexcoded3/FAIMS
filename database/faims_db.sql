-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 01:39 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agriconnect`
--

-- --------------------------------------------------------

--
-- Table structure for table `buyer_favorites`
--

CREATE TABLE `buyer_favorites` (
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buyer_requests`
--

CREATE TABLE `buyer_requests` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_offer` decimal(10,2) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'kg',
  `preferred_harvest_from` date DEFAULT NULL,
  `preferred_harvest_to` date DEFAULT NULL,
  `quality_notes` text DEFAULT NULL,
  `views` int(10) UNSIGNED DEFAULT 0,
  `responses_count` int(10) UNSIGNED DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer_requests`
--

INSERT INTO `buyer_requests` (`id`, `buyer_id`, `category_id`, `quantity`, `price_offer`, `location`, `status`, `created_at`, `title`, `description`, `unit`, `preferred_harvest_from`, `preferred_harvest_to`, `quality_notes`, `views`, `responses_count`) VALUES
(1, 2, 1, 3, '1500.00', 'Wakiso', 'open', '2026-02-13 15:37:19', NULL, NULL, 'kg', NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `buyer_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-03-01 16:05:37', '2026-03-01 16:05:37');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(12,3) NOT NULL DEFAULT 1.000,
  `price_at_add` decimal(10,2) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`id`, `cart_id`, `product_id`, `quantity`, `price_at_add`, `added_at`) VALUES
(16, 1, 2, '3.000', '3000.00', '2026-03-20 07:33:10');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `image`, `created_at`) VALUES
(1, 'Tubers', 'images/categories/tuber.svg', '2026-02-05 09:45:01'),
(2, 'Vegetables', 'images/categories/vegetables.svg', '2026-02-05 09:45:01'),
(3, 'Fruits', 'images/categories/fruits.svg', '2026-02-05 09:45:01'),
(4, 'Grains', 'images/categories/grains.svg', '2026-02-05 09:45:01'),
(5, 'Legumes', 'images/categories/legumes.svg', '2026-02-05 09:45:01'),
(6, 'Livestock', 'images/categories/livestock.svg', '2026-02-05 09:45:01'),
(7, 'Poultry', 'images/categories/poultry.svg', '2026-02-05 09:45:01');

-- --------------------------------------------------------

--
-- Table structure for table `extension_reports`
--

CREATE TABLE `extension_reports` (
  `id` int(11) NOT NULL,
  `extension_id` int(11) NOT NULL,
  `district` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `report` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `type` enum('disease','yield','soil','water','other') DEFAULT 'other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `extension_reports`
--

INSERT INTO `extension_reports` (`id`, `extension_id`, `district`, `title`, `report`, `created_at`, `type`) VALUES
(1, 4, 'Wakiso', 'Armyworm', 'Terrible', '2026-03-23 06:54:14', 'other'),
(18, 4, 'Tororo', 'Fungus', 'Maize', '2026-03-23 14:46:53', 'other'),
(19, 4, 'sadf', ',mnb', 'tyuikl.', '2026-03-26 06:53:06', 'other'),
(20, 4, 'sadf', ',mnb', 'tyuikl.', '2026-03-26 06:53:29', 'other'),
(21, 4, 'Wakiso', 'oiuytrtyio', 'jhgfdhxfgclhfklkh;hlg.khfmzgdn', '2026-03-26 06:53:42', 'other'),
(22, 4, 'Wakiso', 'oiuytrtyio', 'jhgfdhxfgclhfklkh;hlg.khfmzgdn', '2026-03-26 06:53:51', 'other');

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

CREATE TABLE `forum_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_categories`
--

INSERT INTO `forum_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Crop Production', 'Discuss farming techniques, planting schedules, soil management, and pest control.', '2026-02-14 06:20:35'),
(2, 'Livestock Management', 'Talk about animal husbandry, feeding, breeding, and disease prevention.', '2026-02-14 06:20:35'),
(3, 'Market & Pricing', 'Share information about current crop prices, demand trends, and selling tips.', '2026-02-14 06:20:35'),
(4, 'Agri-Biz & Finance', 'Discuss loans, farm records, SACCOs, and managing farm business.', '2026-02-14 06:20:35'),
(5, 'Technology & Tools', 'Share advice about farm tools, irrigation systems, weather apps, and new tech.', '2026-02-14 06:20:35');

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_replies`
--

INSERT INTO `forum_replies` (`id`, `topic_id`, `user_id`, `content`, `created_at`) VALUES
(8, 1, 1, 'whaaaat are u saying', '2026-02-15 09:13:07'),
(9, 1, 1, 'whaaaat are u saying', '2026-02-14 09:13:33'),
(10, 1, 1, 'wertyuytre', '2026-02-16 09:02:50'),
(11, 1, 1, 'ty', '2026-02-16 15:41:46'),
(13, 1, 1, 'dfgh', '2026-02-16 16:34:03'),
(14, 1, 1, 'dfgherty', '2026-02-16 16:34:10'),
(15, 1, 1, 'dfghertyrtyuio', '2026-02-16 16:34:15'),
(16, 17, 1, 'hry', '2026-03-14 14:56:43'),
(17, 17, 1, 'holla', '2026-03-14 14:57:02'),
(18, 17, 1, 'sdfgn', '2026-03-14 15:07:30'),
(19, 17, 1, 'sdfgn', '2026-03-14 15:08:40'),
(20, 17, 6, 'sdfgn', '2026-03-14 15:10:53');

-- --------------------------------------------------------

--
-- Table structure for table `forum_tags`
--

CREATE TABLE `forum_tags` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_tags`
--

INSERT INTO `forum_tags` (`id`, `name`) VALUES
(1, 'pest'),
(2, 'weeds');

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE `forum_topics` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('active','hidden','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `best_reply_id` int(11) DEFAULT NULL,
  `views` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_topics`
--

INSERT INTO `forum_topics` (`id`, `category_id`, `user_id`, `title`, `content`, `status`, `created_at`, `best_reply_id`, `views`) VALUES
(1, 1, 1, 'Sample Topic', 'This is a sample topic content for testing.', 'active', '2026-02-14 06:27:21', NULL, 63),
(4, 4, 1, '', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:16:07', NULL, 3),
(5, 3, 1, 'ASDFHGJKLJHGFD', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:34:29', NULL, 1),
(6, 3, 1, 'ASDFHGJKLJHGFD', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:36:10', NULL, 2),
(7, 2, 1, 'ASDFHGJKLJHaefghjkl;kjhgfdsaSDFGJH', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:43:47', NULL, 1),
(8, 2, 1, 'ASDFHGJKLJHaefghjkl;kjhgfdsaSDFGJH', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:45:41', NULL, 11),
(9, 2, 1, 'ASDFHGJKLJHaefghjkl;kjhgfdsaSDFGJH', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:46:13', NULL, 1),
(10, 2, 1, 'ASDFHGJKLJHaefghjkl;kjhgfdsaSDFGJH', '', 'active', '2026-02-16 12:47:45', NULL, 2),
(11, 1, 1, 'qwertyuiop[poiuytewq', 'This is a sample topic content for testing.', 'active', '2026-02-16 12:51:05', NULL, 3),
(12, 1, 1, 'qwertyuiop[poiuytewq', 'This is a sample topic content for testing.', 'active', '2026-02-16 13:20:40', NULL, 3),
(13, 1, 1, 'xcvbnc', 'This is a sample topic content for testing.', 'active', '2026-02-17 05:56:34', NULL, 1),
(14, 1, 1, 'xcvbnc', '', 'active', '2026-02-17 05:58:08', NULL, 1),
(15, 1, 1, 'xcvbnc', 'This is a sample topic content for testing.', 'active', '2026-02-17 06:00:01', NULL, 1),
(16, 1, 1, 'xcvbnc', 'This is a sample topic content for testing.', 'active', '2026-02-17 06:07:21', NULL, 1),
(17, 1, 1, 'xcvbnc', 'This is a sample topic content for testing.', 'active', '2026-02-17 06:11:27', NULL, 20),
(18, 5, 1, 'xcvbnc', 'This is a sample topic content for testing.', 'active', '2026-02-17 06:15:15', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `forum_topic_images`
--

CREATE TABLE `forum_topic_images` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_topic_images`
--

INSERT INTO `forum_topic_images` (`id`, `topic_id`, `image_path`, `uploaded_at`) VALUES
(5, 1, 'uploads/forum/1_1771307311_c4681c64.jpg', '2026-02-17 05:48:31'),
(9, 17, 'uploads/forum/17_1771308688_7945ef7f.jpg', '2026-02-17 06:11:28'),
(10, 18, 'uploads/forum/18_1771308915_209c73bf.jpg', '2026-02-17 06:15:18');

-- --------------------------------------------------------

--
-- Table structure for table `forum_topic_likes`
--

CREATE TABLE `forum_topic_likes` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_topic_likes`
--

INSERT INTO `forum_topic_likes` (`id`, `topic_id`, `user_id`, `created_at`) VALUES
(15, 11, 1, '2026-02-16 12:57:51'),
(21, 8, 1, '2026-02-16 17:05:58'),
(24, 17, 1, '2026-02-19 08:58:13'),
(25, 4, 1, '2026-03-14 14:54:23'),
(26, 17, 6, '2026-03-14 15:10:48');

-- --------------------------------------------------------

--
-- Table structure for table `forum_topic_tags`
--

CREATE TABLE `forum_topic_tags` (
  `topic_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forum_votes`
--

CREATE TABLE `forum_votes` (
  `id` int(11) NOT NULL,
  `reply_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('farmer_coop','buyer_group','village','other') DEFAULT 'other',
  `leader_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `approved` tinyint(1) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `description`, `type`, `leader_id`, `created_by`, `location`, `created_at`, `updated_at`, `is_active`, `approved`, `approved_by`, `approved_at`) VALUES
(1, 'Wakiso Maize Farmers Co-op', 'Group of maize farmers in Wakiso for collective selling and input buying', 'farmer_coop', 3, 3, 'Wakiso District', '2026-03-05 11:22:59', '2026-03-20 11:22:59', 1, 1, 1, '2026-03-20 11:22:59'),
(2, 'Kampala Vegetable Buyers Group', 'Small group of buyers sourcing vegetables in bulk from farms around Kampala', 'buyer_group', 5, 5, 'Kampala Central', '2026-03-08 11:22:59', '2026-03-20 11:22:59', 1, 1, 1, '2026-03-10 11:22:59'),
(3, 'Mbale Coffee Collectives', 'Coffee farmers in Mbale working together for better export pricess', 'farmer_coop', 7, 7, 'Mbale District', '2026-03-17 11:22:59', '2026-03-22 14:02:40', 1, 0, NULL, NULL),
(4, 'Gulu Youth Agri Group', 'Young farmers and buyers in Gulu supporting local food production', 'village', NULL, 9, 'Gulu District', '2026-03-19 11:22:59', '2026-03-20 12:59:21', 1, 1, 3, '2026-03-20 12:59:21'),
(5, 'Busoga Rice Producers', 'Rice farmers association in Busoga region', 'farmer_coop', 11, 11, 'Busoga Region', '2026-03-13 11:22:59', '2026-03-20 11:22:59', 1, 1, 1, '2026-03-15 11:22:59');

-- --------------------------------------------------------

--
-- Table structure for table `group_members`
--

CREATE TABLE `group_members` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','admin','leader') DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_members`
--

INSERT INTO `group_members` (`group_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 3, 'leader', '2026-03-05 11:23:20'),
(1, 4, 'member', '2026-03-06 11:23:20'),
(1, 6, 'member', '2026-03-08 11:23:20'),
(1, 8, 'admin', '2026-03-10 11:23:20'),
(2, 5, 'leader', '2026-03-08 11:23:20'),
(2, 10, 'member', '2026-03-09 11:23:20'),
(2, 12, 'member', '2026-03-11 11:23:20'),
(3, 3, 'leader', '2026-03-22 11:58:33'),
(3, 4, 'admin', '2026-03-22 12:05:46'),
(3, 6, 'leader', '2026-03-20 13:00:58'),
(3, 12, 'member', '2026-03-22 12:14:59'),
(3, 28, 'member', '2026-03-22 12:09:52'),
(4, 9, 'leader', '2026-03-19 11:23:20'),
(5, 11, 'leader', '2026-03-13 11:23:20'),
(5, 13, 'member', '2026-03-14 11:23:20'),
(5, 14, 'admin', '2026-03-15 11:23:20'),
(5, 15, 'member', '2026-03-16 11:23:20');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` bigint(20) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `application_date` date NOT NULL DEFAULT curdate(),
  `requested_amount` decimal(12,2) NOT NULL,
  `approved_amount` decimal(12,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `duration_months` tinyint(3) UNSIGNED NOT NULL,
  `purpose` varchar(200) DEFAULT NULL,
  `status` enum('pending','under_review','approved','rejected','disbursed','active','overdue','repaid','defaulted','written_off') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `disbursed_at` datetime DEFAULT NULL,
  `first_repayment_due` date DEFAULT NULL,
  `total_repayable` decimal(14,2) GENERATED ALWAYS AS (`approved_amount` * (1 + `interest_rate` / 100 * (`duration_months` / 12))) STORED,
  `total_paid` decimal(14,2) DEFAULT 0.00,
  `last_payment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `farmer_id`, `product_id`, `application_date`, `requested_amount`, `approved_amount`, `interest_rate`, `duration_months`, `purpose`, `status`, `rejection_reason`, `approved_by`, `approved_at`, `disbursed_at`, `first_repayment_due`, `total_paid`, `last_payment_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2026-01-15', '8000000.00', '7500000.00', '18.00', 6, 'Maize seeds & fertilizer', 'repaid', NULL, 3, '2026-01-20 00:00:00', '2026-01-25 00:00:00', '2026-03-25', '1800000.00', NULL, NULL, '2026-02-23 13:55:51', '2026-02-26 14:51:25'),
(2, 2, 2, '2026-02-01', '12000000.00', '10000000.00', '15.50', 9, 'Maize storage bags & silo', 'approved', NULL, 3, '2026-02-23 18:07:12', '2026-02-10 00:00:00', '2026-05-10', '800000.00', NULL, NULL, '2026-02-23 13:55:51', '2026-02-23 17:07:12'),
(3, 3, 1, '2026-02-10', '6000000.00', NULL, '18.00', 6, 'Beans & pesticides', 'approved', NULL, 3, '2026-02-27 09:38:14', NULL, NULL, '0.00', NULL, NULL, '2026-02-23 13:55:51', '2026-02-27 08:38:14'),
(4, 1, 3, '2025-12-01', '15000000.00', '14000000.00', '16.00', 12, 'Buy dairy cows', 'approved', NULL, 3, '2026-02-23 18:02:43', '2025-12-10 00:00:00', '2026-01-10', '3200000.00', NULL, NULL, '2026-02-23 13:55:51', '2026-02-23 17:02:43'),
(5, 2, 2, '2026-01-20', '9000000.00', '9000000.00', '15.50', 6, 'Grain dryer', 'repaid', NULL, NULL, '2026-01-25 00:00:00', '2026-01-30 00:00:00', '2026-04-30', '9697500.00', NULL, NULL, '2026-02-23 13:55:51', '2026-02-23 13:55:51');

-- --------------------------------------------------------

--
-- Table structure for table `loan_collateral`
--

CREATE TABLE `loan_collateral` (
  `id` int(11) NOT NULL,
  `loan_id` bigint(20) NOT NULL,
  `type` enum('land_title','livestock','group_guarantee','savings','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `value_estimate` decimal(12,2) DEFAULT NULL,
  `document_path` varchar(255) DEFAULT NULL,
  `status` enum('submitted','verified','rejected') DEFAULT 'submitted',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_products`
--

CREATE TABLE `loan_products` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `min_amount` decimal(12,2) NOT NULL DEFAULT 500000.00,
  `max_amount` decimal(12,2) NOT NULL DEFAULT 50000000.00,
  `interest_rate_annual` decimal(5,2) NOT NULL DEFAULT 18.00,
  `duration_months_min` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `duration_months_max` tinyint(3) UNSIGNED NOT NULL DEFAULT 12,
  `grace_period_days` smallint(5) UNSIGNED DEFAULT 30,
  `repayment_frequency` enum('monthly','quarterly','at_maturity') DEFAULT 'monthly',
  `requires_collateral` tinyint(1) DEFAULT 0,
  `requires_group` tinyint(1) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_products`
--

INSERT INTO `loan_products` (`id`, `name`, `code`, `description`, `min_amount`, `max_amount`, `interest_rate_annual`, `duration_months_min`, `duration_months_max`, `grace_period_days`, `repayment_frequency`, `requires_collateral`, `requires_group`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Seasonal Crop Input Loan', 'SEASONAL-001', NULL, '500000.00', '15000000.00', '18.00', 3, 9, 30, 'monthly', 0, 0, 1, '2026-02-23 13:43:31', '2026-02-23 13:43:31'),
(2, 'Post-Harvest Storage Loan', 'POSTHARV-001', NULL, '2000000.00', '30000000.00', '15.50', 6, 12, 30, 'monthly', 0, 0, 1, '2026-02-23 13:43:31', '2026-02-23 13:43:31'),
(3, 'Livestock Expansion Loan', 'LIVESTOCK-001', NULL, '1000000.00', '20000000.00', '16.00', 6, 18, 30, 'monthly', 0, 0, 1, '2026-02-23 13:43:31', '2026-02-23 13:43:31'),
(10, 'Seasonal Crop Input Loan', 'SEASONAL-002', NULL, '500000.00', '15000000.00', '18.00', 3, 9, 30, 'monthly', 0, 0, 1, '2026-02-23 13:55:51', '2026-02-23 13:55:51'),
(11, 'Post-Harvest Storage Loan', 'POSTHARV-002', NULL, '2000000.00', '30000000.00', '15.50', 6, 12, 30, 'monthly', 0, 0, 1, '2026-02-23 13:55:51', '2026-02-23 13:55:51'),
(12, 'Livestock Expansion Loan', 'LIVESTOCK-002', NULL, '1000000.00', '20000000.00', '16.00', 6, 18, 30, 'monthly', 0, 0, 1, '2026-02-23 13:55:51', '2026-02-23 13:55:51');

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` bigint(20) NOT NULL,
  `loan_id` bigint(20) NOT NULL,
  `payment_number` smallint(5) UNSIGNED NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL DEFAULT curdate(),
  `payment_method` enum('cash','mobile_money','bank','group_collection','other') DEFAULT 'mobile_money',
  `received_by` int(11) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_repayments`
--

INSERT INTO `loan_repayments` (`id`, `loan_id`, `payment_number`, `amount_paid`, `payment_date`, `payment_method`, `received_by`, `receipt_number`, `notes`, `created_at`) VALUES
(1, 1, 1, '1250000.00', '2026-02-23', 'mobile_money', NULL, 'REP/MM/20260223/001', NULL, '2026-02-23 14:09:25'),
(2, 1, 2, '550000.00', '2026-02-23', 'mobile_money', NULL, 'REP/MM/20260223/002', NULL, '2026-02-23 14:09:25'),
(3, 2, 1, '2000000.00', '2026-02-10', 'bank', NULL, 'REP/BANK/20260210/001', NULL, '2026-02-23 14:09:25'),
(4, 2, 2, '1500000.00', '2026-02-23', 'mobile_money', NULL, 'REP/MM/20260223/003', NULL, '2026-02-23 14:09:25'),
(5, 4, 1, '2800000.00', '2026-01-15', 'cash', NULL, 'REP/CASH/20260115/001', NULL, '2026-02-23 14:09:25'),
(6, 5, 1, '4500000.00', '2026-02-23', 'mobile_money', NULL, 'REP/MM/20260223/004', NULL, '2026-02-23 14:09:25');

-- --------------------------------------------------------

--
-- Table structure for table `market_prices`
--

CREATE TABLE `market_prices` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `crop` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `market_prices`
--

INSERT INTO `market_prices` (`id`, `category_id`, `crop`, `price`, `date`) VALUES
(1, 1, 'Tubers', '1200.00', '2026-03-15'),
(2, 2, 'Vegetables', '2300.00', '2026-03-16'),
(3, 3, 'Fruits', '3800.00', '2026-03-18'),
(4, 4, 'Grains', '1500.00', '2026-02-19'),
(5, 5, 'Legumes', '2700.00', '2026-02-20'),
(6, 6, 'Livestock', '4100.00', '2026-02-13'),
(7, 7, 'Poultry', '1950.00', '2026-03-01'),
(8, 1, 'Tubers', '1100.00', '2026-03-08'),
(9, 2, 'Vegetables', '2200.00', '2026-03-15'),
(10, 3, 'Fruits', '3600.00', '2026-03-22'),
(11, 4, 'Grains', '1400.00', '2026-02-07'),
(12, 5, 'Legumes', '2600.00', '2026-02-07'),
(13, 6, 'Livestock', '4000.00', '2026-02-07'),
(14, 7, 'Poultry', '1800.00', '2026-03-01'),
(15, 1, 'Tubers', '1150.00', '2026-03-08'),
(16, 2, 'Vegetables', '2250.00', '2026-03-15'),
(17, 3, 'Fruits', '3700.00', '2026-03-22'),
(18, 4, 'Grains', '1450.00', '2026-02-08'),
(19, 5, 'Legumes', '2650.00', '2026-02-08'),
(20, 6, 'Livestock', '4050.00', '2026-02-08'),
(21, 7, 'Poultry', '1850.00', '2026-03-01'),
(22, 1, 'Tubers', '1200.00', '2026-03-08'),
(23, 2, 'Vegetables', '2300.00', '2026-03-15'),
(24, 3, 'Fruits', '3800.00', '2026-03-22'),
(25, 4, 'Grains', '1500.00', '2026-02-13'),
(26, 5, 'Legumes', '2700.00', '2026-02-13'),
(27, 6, 'Livestock', '4100.00', '2026-02-13'),
(28, 7, 'Poultry', '1950.00', '2026-03-01'),
(29, 3, 'Fruits', '1857.00', '2026-03-08'),
(30, 4, 'Grains', '3413.00', '2026-03-15'),
(31, 5, 'Legumes', '1491.00', '2026-03-22'),
(32, 6, 'Livestock', '2219.00', '2026-02-13'),
(33, 7, 'Poultry', '2622.00', '2026-02-13'),
(34, 1, 'Tubers', '2453.00', '2026-02-13'),
(35, 2, 'Vegetables', '3402.00', '2026-03-01');

-- --------------------------------------------------------

--
-- Table structure for table `mobile_money_payments`
--

CREATE TABLE `mobile_money_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `wallet_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `type` enum('deposit','withdrawal') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `provider` enum('MTN','AIRTEL','other') NOT NULL,
  `status` enum('pending','success','failed','cancelled') DEFAULT 'pending',
  `response_code` varchar(50) DEFAULT NULL,
  `response_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `negotiations`
--

CREATE TABLE `negotiations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `buyer_request_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `initiator` enum('buyer','farmer') NOT NULL,
  `proposed_quantity` decimal(12,3) DEFAULT NULL,
  `proposed_price` decimal(10,2) DEFAULT NULL,
  `proposed_unit` varchar(30) DEFAULT 'kg',
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected','withdrawn','countered') DEFAULT 'pending',
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `negotiations`
--

INSERT INTO `negotiations` (`id`, `buyer_request_id`, `product_id`, `buyer_id`, `farmer_id`, `initiator`, `proposed_quantity`, `proposed_price`, `proposed_unit`, `message`, `status`, `parent_id`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 2, 1, 'buyer', '6.000', '2000.00', 'kg', '', 'pending', NULL, '2026-03-01 11:51:39', '2026-03-01 11:51:39'),
(2, NULL, 1, 2, 1, 'buyer', '6.000', '2000.00', 'kg', '', 'accepted', NULL, '2026-03-01 11:55:12', '2026-03-17 11:39:44'),
(3, NULL, 1, 2, 1, 'buyer', '6.000', '1200.00', 'kg', '', 'accepted', NULL, '2026-03-04 16:25:12', '2026-03-17 11:39:44'),
(4, NULL, 1, 2, 1, 'buyer', '12.000', '1100.00', 'kg', 'its fair', 'pending', NULL, '2026-03-04 16:28:52', '2026-03-04 16:28:52'),
(5, NULL, 2, 2, 1, 'buyer', '9.000', '2899.00', 'kg', 'rouh', 'pending', NULL, '2026-03-09 12:21:02', '2026-03-09 12:21:02'),
(6, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:22:22', '2026-03-09 12:22:22'),
(7, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:23:24', '2026-03-09 12:23:24'),
(8, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:24:09', '2026-03-09 12:24:09'),
(9, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'accepted', NULL, '2026-03-09 12:25:35', '2026-03-17 11:39:45'),
(10, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:26:19', '2026-03-09 12:26:19'),
(11, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:27:58', '2026-03-09 12:27:58'),
(12, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:28:59', '2026-03-09 12:28:59'),
(13, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:31:21', '2026-03-09 12:31:21'),
(14, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:32:46', '2026-03-09 12:32:46'),
(15, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:34:26', '2026-03-09 12:34:26'),
(16, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:36:32', '2026-03-09 12:36:32'),
(17, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', '', 'pending', NULL, '2026-03-09 12:38:10', '2026-03-09 12:38:10'),
(18, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:38:23', '2026-03-09 12:38:23'),
(19, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:38:37', '2026-03-09 12:38:37'),
(20, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:42:30', '2026-03-09 12:42:30'),
(21, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:43:15', '2026-03-09 12:43:15'),
(22, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:44:14', '2026-03-09 12:44:14'),
(23, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:49:27', '2026-03-09 12:49:27'),
(24, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:50:06', '2026-03-09 12:50:06'),
(25, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:51:01', '2026-03-09 12:51:01'),
(26, NULL, 2, 2, 1, 'buyer', '3.000', '1500.00', 'kg', 'aa', 'pending', NULL, '2026-03-09 12:53:25', '2026-03-09 12:53:25'),
(27, NULL, 2, 2, 1, 'buyer', '22.000', '2900.00', 'kg', 'a', 'pending', NULL, '2026-03-13 09:32:47', '2026-03-13 09:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('new_offer','offer_update','order_update','system') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_type` enum('negotiation','order') DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `reference_id`, `reference_type`, `is_read`, `created_at`) VALUES
(1, 1, 'new_offer', 'New Offer on Red Beans', 'Buyer Mary Buyer offered UGX 1,500 for 3 kg. Check your negotiations.', 26, 'negotiation', 0, '2026-03-09 12:53:25'),
(2, 1, 'new_offer', 'New Offer on Red Beans', 'Buyer Mary Buyer offered UGX 2,900 for 22 kg. Check your negotiations.', 27, 'negotiation', 0, '2026-03-13 09:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `farmer_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) NOT NULL,
  `status` enum('pending','completed','cancelled','confirmed','processing') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_location` varchar(150) DEFAULT NULL,
  `delivery_window_start` date DEFAULT NULL,
  `delivery_window_end` date DEFAULT NULL,
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `payment_method` enum('mobile_money','bank','cash','credit') DEFAULT 'mobile_money',
  `escrow_released` tinyint(1) DEFAULT 0,
  `dispute_status` enum('none','opened','resolved','escalated') DEFAULT 'none',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_code`, `amount`, `farmer_id`, `buyer_id`, `status`, `created_at`, `delivery_location`, `delivery_window_start`, `delivery_window_end`, `payment_status`, `payment_method`, `escrow_released`, `dispute_status`, `completed_at`) VALUES
(1, 'ORD 0001', '0.00', 1, 2, 'pending', '2026-02-07 20:03:09', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(2, 'ORD 0002', '15000.00', 1, 2, 'completed', '2026-02-12 20:03:09', NULL, NULL, NULL, 'paid', 'mobile_money', 0, 'none', NULL),
(3, 'ORD 0003', '0.00', 1, 2, 'completed', '2026-01-30 20:03:09', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(7, 'ORD 0007', '0.00', 1, 2, 'cancelled', '2026-01-30 20:15:57', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(9, 'ORD 0009', '0.00', 1, 2, 'completed', '2026-01-30 20:15:57', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(10, 'ORD 0011', '4500000.00', 1, 2, 'completed', '2026-02-15 13:04:17', 'Kampala Central Market', '2026-03-05', '2026-03-07', 'pending', 'mobile_money', 0, 'none', NULL),
(11, 'ORD 0012', '0.00', 12, 13, 'completed', '2026-02-21 11:35:54', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(12, 'ORD 0013', '0.00', 12, 13, 'completed', '2026-02-22 12:04:39', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(13, 'ORD-0012', '3200000.00', 3, 2, 'pending', '2026-02-28 11:48:22', 'Nakawa Market, Kampala', NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(14, 'ORD-0013', '5800000.00', 4, 2, '', '2026-02-28 11:48:22', 'Owino Market, Kampala', NULL, NULL, 'partial', 'mobile_money', 0, 'none', NULL),
(17, 'ORD-0014', '3200000.00', 3, 2, 'pending', '2026-02-28 11:52:42', 'Nakawa Market, Kampala', NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(22, 'ORD-20260306123113-5', '48000.00', NULL, 2, 'processing', '2026-03-06 11:31:13', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(23, 'ORD-20260306123400-2', '1500.00', NULL, 2, 'processing', '2026-03-06 11:34:00', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(24, 'ORD-20260307103908-5', '9000.00', NULL, 2, 'pending', '2026-03-07 09:39:08', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(25, 'ORD-20260307110849-7', '3000.00', NULL, 2, 'pending', '2026-03-07 10:08:49', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(26, 'ORD-20260307114020-2', '9000.00', NULL, 2, 'pending', '2026-03-07 10:40:20', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL),
(27, 'ORD-20260318132525-3', '1500.00', NULL, 2, 'pending', '2026-03-18 12:25:25', NULL, NULL, NULL, 'pending', 'mobile_money', 0, 'none', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `delivered_quantity` decimal(12,3) DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `status` enum('pending','confirmed','delivered','shortage','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`, `delivered_quantity`, `delivery_date`, `status`) VALUES
(1, 10, 1, '2000.000', '2250.00', NULL, NULL, 'confirmed'),
(3, 17, 2, '1500.000', '2133.33', NULL, NULL, 'pending'),
(4, 22, 1, '9.000', '1500.00', NULL, NULL, 'pending'),
(5, 22, 2, '4.000', '3000.00', NULL, NULL, 'pending'),
(6, 22, 1, '7.000', '1500.00', NULL, NULL, 'pending'),
(7, 22, 8, '5.000', '2400.00', NULL, NULL, 'pending'),
(8, 23, 1, '1.000', '1500.00', NULL, NULL, 'pending'),
(9, 24, 2, '3.000', '3000.00', NULL, NULL, 'pending'),
(10, 25, 2, '1.000', '3000.00', NULL, NULL, 'pending'),
(11, 26, 2, '3.000', '3000.00', NULL, NULL, 'pending'),
(12, 27, 1, '1.000', '1500.00', NULL, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `title`, `content`, `created_at`) VALUES
(1, 4, 'Alert', 'whowww', '2026-03-27 11:13:45'),
(4, 1, 'Market', 'Sure', '2026-03-24 08:52:35'),
(5, 4, 'Best', 'Wahala', '2026-03-24 08:56:49'),
(6, 4, 'Best', 'Wahala', '2026-03-24 08:59:41'),
(7, 4, 'sas', 'sas', '2026-03-24 10:51:36'),
(8, 4, 'asasas', 'asasasa', '2026-03-24 10:51:53');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `farmer_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `harvest_date` date DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'kg',
  `status` enum('pending','approved','rejected','active','out','expired') NOT NULL DEFAULT 'pending',
  `rejection_reason` text DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `views` int(10) UNSIGNED DEFAULT 0,
  `min_order_quantity` int(11) DEFAULT NULL,
  `max_order_quantity` int(11) DEFAULT NULL,
  `available_until` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `farmer_id`, `category_id`, `name`, `description`, `image`, `price`, `quantity`, `harvest_date`, `unit`, `status`, `rejection_reason`, `reviewed_at`, `reviewed_by`, `approved`, `created_at`, `views`, `min_order_quantity`, `max_order_quantity`, `available_until`) VALUES
(1, 1, 4, 'Fresh Maize', 'Organic fresh maize from farm', 'uploads/products/maize.jpg', '1500.00', 50, NULL, 'kg', 'active', NULL, NULL, NULL, 0, '2026-02-28 07:50:20', 0, NULL, NULL, NULL),
(2, 1, 5, 'Red Beans', 'High quality red beans', 'uploads/products/beans.jpg', '3000.00', 40, NULL, 'kg', 'active', NULL, NULL, NULL, 0, '2026-02-28 07:50:20', 0, NULL, NULL, NULL),
(3, 1, 1, 'Sweet Potatoes', 'Naturally grown sweet potatoes', 'uploads/products/potatoes.jpg', '1200.00', 60, NULL, 'kg', 'active', NULL, NULL, NULL, 0, '2026-02-28 07:50:20', 0, NULL, NULL, NULL),
(4, 1, 1, 'Cassava', 'Fresh cassava roots', 'uploads/products/cassava.jpg', '1000.00', 70, NULL, 'kg', 'active', NULL, NULL, NULL, 0, '2026-02-28 07:50:20', 0, NULL, NULL, NULL),
(5, 1, 3, 'Green Bananas (Matooke)', 'Farm fresh matooke', 'uploads/products/banana.jpg', '2500.00', 30, NULL, 'kg', 'out', NULL, NULL, NULL, 0, '2026-02-28 07:50:20', 0, NULL, NULL, NULL),
(6, 1, 7, 'Croilers', 'Fat and healthy Croilers', 'uploads/products/chicken.jpg', '25000.00', 200, NULL, 'Birds', 'rejected', NULL, NULL, NULL, 0, '2026-02-05 10:59:44', 0, NULL, NULL, NULL),
(7, 5, 1, 'Fresh Matooke (Grade A)', 'From Wakiso - ready for collection', NULL, '1800.00', 4500, NULL, 'kg', 'active', NULL, NULL, NULL, 0, '2026-02-28 11:50:20', 0, NULL, NULL, NULL),
(8, 6, 2, 'Nylon Beans (cleaned)', 'Mbale origin - 50 bags available', NULL, '2400.00', 2000, NULL, 'kg', 'active', NULL, NULL, NULL, 0, '2026-02-28 11:50:20', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `rater_id` int(11) NOT NULL,
  `rated_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training`
--

CREATE TABLE `training` (
  `id` int(11) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `file` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_courses`
--

CREATE TABLE `training_courses` (
  `id` int(11) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_courses`
--

INSERT INTO `training_courses` (`id`, `title`, `description`, `category`, `thumbnail`, `created_by`, `created_at`) VALUES
(1, 'Maize Farming', 'Modern maize techniques', 'Crop', NULL, 4, '2026-02-04 18:34:09'),
(2, 'Poultry Rearing', 'Chicken management', 'Livestock', NULL, NULL, '2026-02-04 18:34:09'),
(3, 'Soil Health', 'Improve productivity', 'Crop', NULL, NULL, '2026-02-04 18:34:09'),
(4, 'Modern Passion Techniques', 'By the end of this lesson. Farmers will...', 'agronomy', 'thumb_1774528594_4.png', 4, '2026-03-26 12:36:34'),
(5, 'hahaha', 'hsghhljak', 'agronomy', 'thumb_1774545487_4.png', 4, '2026-03-26 17:18:07'),
(6, 'hahaha', '', '', 'thumb_1774545530_4.png', 4, '2026-03-26 17:18:50'),
(7, 'aaa', 'aaa', 'agronomy', 'thumb_1774545604_4.jpg', 4, '2026-03-26 17:20:04'),
(8, 'aaa', 'aaa', 'agronomy', 'thumb_1774546085_4.png', 4, '2026-03-26 17:28:05'),
(11, 'asdf', 'asd', 'climate', '', 4, '2026-03-27 08:45:15'),
(12, 'kjhfdfdfgh', '', '', '', 4, '2026-03-27 10:28:57');

-- --------------------------------------------------------

--
-- Table structure for table `training_lessons`
--

CREATE TABLE `training_lessons` (
  `id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `posted_by` int(11) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_lessons`
--

INSERT INTO `training_lessons` (`id`, `course_id`, `posted_by`, `title`, `content`, `video`, `pdf`, `created_at`) VALUES
(1, 1, 3, 'Seed selection', 'Best seed selection procedures', NULL, NULL, '2026-02-09 09:39:59'),
(2, 1, 3, 'Land Preparation and Site Selection', 'Learn how to select well-drained loamy soil and prepare the seedbed by clearing, ploughing, and harrowing to ensure optimal germination.', 'maize_land_prep.mp4', 'maize_site_selection_guide.pdf', '2026-02-10 07:18:07'),
(3, 1, 3, 'Maize Planting and Spacing', 'This lesson covers the correct planting depth (5-7 cm) and spacing (75cm x 25cm) to achieve the best plant population per acre.', 'maize_planting_tips.mp4', 'spacing_and_seed_rate.pdf', '2026-02-10 07:18:07'),
(4, 1, 3, 'Managing the Fall Armyworm', 'Identify common maize pests like the Fall Armyworm and learn integrated pest management (IPM) strategies to protect your yield.', 'armyworm_control.mp4', 'maize_pest_management.pdf', '2026-02-10 07:18:07'),
(5, 1, 3, 'Nutrient Management and Top Dressing', 'Understand when to apply basal fertilizers and the importance of Nitrogen top-dressing at the knee-high stage.', 'maize_fertilization.mp4', 'maize_nutrient_guide.pdf', '2026-02-10 07:18:07'),
(6, 11, 4, 'hahaha', 'dfsdfxxx', '', '', '2026-03-27 09:49:56'),
(7, 11, 4, 'ss', 'sasa', '', '', '2026-03-27 10:05:21'),
(8, 11, 4, 'ASDF', 'Asdfdsa', '', '', '2026-03-27 10:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `training_progress`
--

CREATE TABLE `training_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `status` enum('started','completed') NOT NULL DEFAULT 'started',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `training_progress`
--

INSERT INTO `training_progress` (`id`, `user_id`, `lesson_id`, `status`, `started_at`, `completed_at`) VALUES
(1, 1, 3, 'completed', '2026-02-09 21:00:00', '2026-02-10 08:18:42'),
(3, 1, 1, 'completed', '2026-02-09 21:00:00', '2026-02-10 09:06:49'),
(4, 1, 5, 'completed', '2026-02-09 21:00:00', '2026-02-10 09:31:25'),
(6, 1, 2, 'completed', '2026-02-09 21:00:00', '2026-03-14 14:44:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','farmer','buyer','extension') NOT NULL,
  `status` enum('active','suspended','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `password` varchar(255) DEFAULT NULL,
  `image_paths` varchar(255) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `tin` varchar(20) DEFAULT NULL COMMENT 'Tax Identification Number',
  `business_type` enum('wholesaler','processor','exporter','retailer','cooperative','other') DEFAULT NULL,
  `preferred_districts` varchar(255) DEFAULT NULL COMMENT 'comma-separated or JSON',
  `verified_business` tinyint(1) DEFAULT 0,
  `verification_docs` text DEFAULT NULL COMMENT 'JSON array of paths or comma-separated',
  `location_lat` decimal(10,7) DEFAULT NULL,
  `location_lon` decimal(10,7) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT 'Kampala, Uganda'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `company_name`, `email`, `phone`, `role`, `status`, `created_at`, `password`, `image_paths`, `location`, `last_login`, `tin`, `business_type`, `preferred_districts`, `verified_business`, `verification_docs`, `location_lat`, `location_lon`, `location_name`) VALUES
(1, 'John Farmer', NULL, 'farmer@test.com', '256784165935', 'farmer', 'active', '2026-02-19 09:52:01', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Nagongera', '2026-03-27 14:40:00', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Nagongera, Uganda'),
(2, 'Mary Buyer', NULL, 'buyer@test.com', '256788874442', 'buyer', 'active', '2026-02-19 09:52:01', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Wakiso', '2026-03-18 11:25:29', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(3, 'Admin', NULL, 'admin@test.com', '256783874407', 'admin', 'active', '2026-02-19 09:52:01', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'System', '2026-03-26 13:18:50', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(4, 'Alex Extensions', NULL, 'extension@test.com', '256789748707', 'extension', 'active', '2026-02-19 09:52:01', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Nagongera', '2026-03-26 13:26:51', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(5, 'John Admin', NULL, 'john.admin1@example.com', '256789120318', 'admin', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Kampala', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(6, 'Sarah Farmer', NULL, 'sarah.farmer1@example.com', '256786355467', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Gulu', '2026-03-14 18:10:20', NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(7, 'Peter Buyer', NULL, 'peter.buyer1@example.com', '256783416381', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Mbarara', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(8, 'Grace Extension', NULL, 'grace.extension1@example.com', '256786015508', 'extension', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Jinja', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(9, 'Michael Farmer', NULL, 'michael.farmer2@example.com', '256789828395', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Lira', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(10, 'Ruth Buyer', NULL, 'ruth.buyer2@example.com', '256783095454', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Mbale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(11, 'David Extension', NULL, 'david.extension2@example.com', '256782992085', 'extension', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Arua', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(12, 'Alice Farmer', NULL, 'alice.farmer3@example.com', '256784674062', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Fort Portal', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(13, 'Brian Buyer', NULL, 'brian.buyer3@example.com', '256784394058', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Masaka', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(14, 'Esther Extension', NULL, 'esther.extension3@example.com', '256786948104', 'extension', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Hoima', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(15, 'Samuel Farmer', NULL, 'samuel.farmer4@example.com', '256782558346', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Soroti', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(16, 'Linda Buyer', NULL, 'linda.buyer4@example.com', '256788947421', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Kabale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(17, 'Daniel Admin', NULL, 'daniel.admin2@example.com', '256789062068', 'admin', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Kampala', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(18, 'Kevin Farmer', NULL, 'kevin.farmer5@example.com', '256788468075', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Gulu', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(19, 'Patricia Buyer', NULL, 'patricia.buyer5@example.com', '256785154173', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Mbarara', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(20, 'Joseph Extension', NULL, 'joseph.extension4@example.com', '256788366641', 'extension', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Jinja', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(22, 'Mark Buyer', NULL, 'mark.buyer6@example.com', '256787370686', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Mbale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(25, 'Irene Buyer', NULL, 'irene.buyer7@example.com', '256781753510', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Masaka', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(26, 'George Extension', NULL, 'george.extension6@example.com', '256783655492', 'extension', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Hoima', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(27, 'Nancy Farmer', NULL, 'nancy.farmer8@example.com', '256783016930', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Soroti', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(28, 'Chris Buyer', NULL, 'chris.buyer8@example.com', '256783118176', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Kabale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(29, 'Olivia Extension', NULL, 'olivia.extension7@example.com', '256785540092', 'extension', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Kampala', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(30, 'Andrew Farmer', NULL, 'andrew.farmer9@example.com', '256788345931', 'farmer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Gulu', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(31, 'Faith Buyer', NULL, 'faith.buyer9@example.com', '256786109378', 'buyer', 'active', '2026-02-19 16:15:14', '$2y$10$iSWN6XwzAnSrLSrQfcMlbumDy3xPWNGKBhEPXk4WOC5YSaFzQ6avm', '', 'Mbarara', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(35, 'Okello Moses', NULL, 'okello@example.com', '256784509100', 'farmer', 'active', '2026-02-23 13:54:04', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f1.jpg', 'Lira', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(36, 'Nabwire Sarah', NULL, 'sarah@example.com', '256783217366', 'farmer', 'active', '2026-02-23 13:54:04', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f2.jpg', 'Mbale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(37, 'Kizza John', NULL, 'kizza@example.com', '256781559530', 'farmer', 'active', '2026-02-23 13:54:04', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f3.jpg', 'Wakiso', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(38, 'Okello Moses', NULL, 'okello@example.com', '256786145553', 'farmer', 'active', '2026-02-23 13:55:20', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f1.jpg', 'Lira', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(39, 'Nabwire Sarah', NULL, 'sarah@example.com', '256787049177', 'farmer', 'active', '2026-02-23 13:55:20', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f2.jpg', 'Mbale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(40, 'Kizza John', NULL, 'kizza@example.com', '256786809227', 'farmer', 'active', '2026-02-23 13:55:20', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f3.jpg', 'Wakiso', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(41, 'Okello Moses', NULL, 'okello@example.com', '256782898605', 'farmer', 'active', '2026-02-23 13:55:50', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f1.jpg', 'Lira', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(42, 'Nabwire Sarah', NULL, 'sarah@example.com', '256782065344', 'farmer', 'active', '2026-02-23 13:55:50', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f2.jpg', 'Mbale', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda'),
(43, 'Kizza John', NULL, 'kizza@example.com', '256789630905', 'farmer', 'active', '2026-02-23 13:55:50', '$2y$10$4SL6wd63qIq73Qqf/uZ1Z.gijDUhg2oAHSor8MO7WR3Vok.u2V0HG', 'avatars/f3.jpg', 'Wakiso', NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, 'Kampala, Uganda');

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `held_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` enum('UGX') NOT NULL DEFAULT 'UGX',
  `status` enum('active','frozen','suspended') DEFAULT 'active',
  `last_transaction_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `balance`, `held_balance`, `currency`, `status`, `last_transaction_at`, `created_at`, `updated_at`) VALUES
(1, 2, '18500000.00', '7200000.00', 'UGX', 'active', NULL, '2026-02-28 11:49:50', '2026-02-28 11:49:50');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `wallet_id` bigint(20) UNSIGNED NOT NULL,
  `type` enum('deposit','withdrawal','payment','release','refund','fee','adjustment') NOT NULL,
  `provider` enum('mtn_momo','airtel_momo','bank_transfer','other') DEFAULT 'other',
  `amount` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `held_after` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reference_type` enum('order','buyer_request','manual','mobile_money','bank') DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','reversed') DEFAULT 'completed',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `wallet_id`, `type`, `provider`, `amount`, `balance_after`, `held_after`, `reference_id`, `reference_type`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'deposit', 'airtel_momo', '25000000.00', '25000000.00', '0.00', NULL, 'mobile_money', 'MoMo deposit - initial funding', 'completed', NULL, '2026-02-18 11:49:50', '2026-03-09 10:05:12'),
(2, 1, 'payment', 'bank_transfer', '-7200000.00', '17800000.00', '7200000.00', 10, 'order', 'Payment hold for order ORD 0011 (maize)', 'completed', NULL, '2026-02-25 11:49:50', '2026-03-09 10:05:12'),
(3, 1, 'deposit', 'bank_transfer', '5000000.00', '22800000.00', '7200000.00', NULL, 'mobile_money', 'MoMo top-up', 'completed', NULL, '2026-02-27 11:49:50', '2026-03-09 10:13:23'),
(4, 1, 'deposit', 'mtn_momo', '2500000.00', '2500000.00', '0.00', NULL, NULL, 'MoMo deposit via MTN', 'completed', NULL, '2026-03-09 10:03:12', '2026-03-09 10:03:12');

-- --------------------------------------------------------

--
-- Table structure for table `weather_data`
--

CREATE TABLE `weather_data` (
  `id` int(11) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `temperature` float DEFAULT NULL,
  `humidity` int(11) DEFAULT NULL,
  `wind_speed` float DEFAULT NULL,
  `weather_main` varchar(50) DEFAULT NULL,
  `weather_desc` varchar(255) DEFAULT NULL,
  `forecast_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buyer_favorites`
--
ALTER TABLE `buyer_favorites`
  ADD PRIMARY KEY (`buyer_id`,`product_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `buyer_requests`
--
ALTER TABLE `buyer_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_buyer` (`buyer_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cart_product` (`cart_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `extension_reports`
--
ALTER TABLE `extension_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `extension_id` (`extension_id`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_tags`
--
ALTER TABLE `forum_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_topic_images`
--
ALTER TABLE `forum_topic_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`);

--
-- Indexes for table `forum_topic_likes`
--
ALTER TABLE `forum_topic_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topic_id` (`topic_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_topic_tags`
--
ALTER TABLE `forum_topic_tags`
  ADD PRIMARY KEY (`topic_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `forum_votes`
--
ALTER TABLE `forum_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reply_id` (`reply_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leader_id` (`leader_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `group_members`
--
ALTER TABLE `group_members`
  ADD PRIMARY KEY (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_farmer_status` (`farmer_id`,`status`),
  ADD KEY `idx_status_created` (`status`,`created_at`),
  ADD KEY `idx_product_status` (`product_id`,`status`),
  ADD KEY `idx_first_due` (`first_repayment_due`);

--
-- Indexes for table `loan_collateral`
--
ALTER TABLE `loan_collateral`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `loan_products`
--
ALTER TABLE `loan_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_active_name` (`active`,`name`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_loan_date` (`loan_id`,`payment_date`),
  ADD KEY `idx_payment_date` (`payment_date`);

--
-- Indexes for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_market_category` (`category_id`);

--
-- Indexes for table `mobile_money_payments`
--
ALTER TABLE `mobile_money_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tx_id` (`transaction_id`),
  ADD KEY `wallet_id` (`wallet_id`);

--
-- Indexes for table `negotiations`
--
ALTER TABLE `negotiations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_participants` (`buyer_id`,`farmer_id`),
  ADD KEY `buyer_request_id` (`buyer_request_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `farmer_id` (`farmer_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_orders_farmer_date` (`farmer_id`,`created_at`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_buyer_status` (`buyer_id`,`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bulletin_fk_1` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`),
  ADD KEY `idx_products_farmer` (`farmer_id`),
  ADD KEY `fk_products_reviewed_by` (`reviewed_by`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rating` (`rater_id`,`order_id`),
  ADD KEY `rated_id` (`rated_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `training`
--
ALTER TABLE `training`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_courses`
--
ALTER TABLE `training_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_course_author` (`created_by`);

--
-- Indexes for table `training_lessons`
--
ALTER TABLE `training_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `fk_lesson_admin` (`posted_by`);

--
-- Indexes for table `training_progress`
--
ALTER TABLE `training_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_type` (`wallet_id`,`type`),
  ADD KEY `idx_reference` (`reference_id`,`reference_type`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `weather_data`
--
ALTER TABLE `weather_data`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buyer_requests`
--
ALTER TABLE `buyer_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `extension_reports`
--
ALTER TABLE `extension_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `forum_tags`
--
ALTER TABLE `forum_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `forum_topic_images`
--
ALTER TABLE `forum_topic_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `forum_topic_likes`
--
ALTER TABLE `forum_topic_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `forum_votes`
--
ALTER TABLE `forum_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `loan_collateral`
--
ALTER TABLE `loan_collateral`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_products`
--
ALTER TABLE `loan_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `market_prices`
--
ALTER TABLE `market_prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `mobile_money_payments`
--
ALTER TABLE `mobile_money_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `negotiations`
--
ALTER TABLE `negotiations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training`
--
ALTER TABLE `training`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `training_courses`
--
ALTER TABLE `training_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `training_lessons`
--
ALTER TABLE `training_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `training_progress`
--
ALTER TABLE `training_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `weather_data`
--
ALTER TABLE `weather_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyer_favorites`
--
ALTER TABLE `buyer_favorites`
  ADD CONSTRAINT `fk_buyer_fav_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_buyer_fav_user` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buyer_requests`
--
ALTER TABLE `buyer_requests`
  ADD CONSTRAINT `buyer_requests_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `buyer_requests_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `extension_reports`
--
ALTER TABLE `extension_reports`
  ADD CONSTRAINT `extension_reports_ibfk_1` FOREIGN KEY (`extension_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`),
  ADD CONSTRAINT `forum_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD CONSTRAINT `forum_topics_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`),
  ADD CONSTRAINT `forum_topics_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `forum_topic_images`
--
ALTER TABLE `forum_topic_images`
  ADD CONSTRAINT `forum_topic_images_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_topic_likes`
--
ALTER TABLE `forum_topic_likes`
  ADD CONSTRAINT `forum_topic_likes_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `forum_topic_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forum_topic_tags`
--
ALTER TABLE `forum_topic_tags`
  ADD CONSTRAINT `forum_topic_tags_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `forum_topics` (`id`),
  ADD CONSTRAINT `forum_topic_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `forum_tags` (`id`);

--
-- Constraints for table `forum_votes`
--
ALTER TABLE `forum_votes`
  ADD CONSTRAINT `forum_votes_ibfk_1` FOREIGN KEY (`reply_id`) REFERENCES `forum_replies` (`id`),
  ADD CONSTRAINT `forum_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `groups`
--
ALTER TABLE `groups`
  ADD CONSTRAINT `groups_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `groups_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `group_members`
--
ALTER TABLE `group_members`
  ADD CONSTRAINT `group_members_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `loan_products` (`id`),
  ADD CONSTRAINT `loans_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_collateral`
--
ALTER TABLE `loan_collateral`
  ADD CONSTRAINT `loan_collateral_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_collateral_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD CONSTRAINT `loan_repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_repayments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `market_prices`
--
ALTER TABLE `market_prices`
  ADD CONSTRAINT `fk_market_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `mobile_money_payments`
--
ALTER TABLE `mobile_money_payments`
  ADD CONSTRAINT `mobile_money_payments_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `negotiations`
--
ALTER TABLE `negotiations`
  ADD CONSTRAINT `negotiations_ibfk_1` FOREIGN KEY (`buyer_request_id`) REFERENCES `buyer_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `negotiations_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `negotiations_ibfk_3` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `negotiations_ibfk_4` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `negotiations_ibfk_5` FOREIGN KEY (`parent_id`) REFERENCES `negotiations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_products_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_products_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`rated_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `training_courses`
--
ALTER TABLE `training_courses`
  ADD CONSTRAINT `fk_course_author` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `training_lessons`
--
ALTER TABLE `training_lessons`
  ADD CONSTRAINT `fk_lesson_admin` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `training_lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `training_courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_progress`
--
ALTER TABLE `training_progress`
  ADD CONSTRAINT `training_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `training_lessons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
