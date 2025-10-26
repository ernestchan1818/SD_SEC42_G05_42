-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 24, 2025 at 08:49 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u827939212_otpdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `message`, `created_at`) VALUES
(1, 'jesvin', 'wenzhiwu38@gmail.com', 'Oi mana game beli??? nothing', '2025-08-22 06:41:40'),
(3, 'GUEST', 'GUEST@gmail.com', 'HI', '2025-08-23 11:52:35'),
(4, 'guest', 'jjj@gmail.com', 'hi', '2025-08-23 14:28:17'),
(5, 'jesvin', 'jesvin@gmail.com', 'hi,i want buy', '2025-08-25 00:29:20'),
(6, 'zhibin', 'zhibin05@graduate.utm.my', 'hello', '2025-08-27 05:16:52'),
(7, 'jesvin', 'Jesvin0501@gmail.com', 'hi', '2025-09-04 08:23:22'),
(8, 'jesvin', 'Jesvin123@gmail.com', '123', '2025-09-07 16:09:25'),
(9, 'hi', 'hi@gmail.com', 'hi try', '2025-10-20 09:49:03'),
(10, 'CY', 'songcy2005814@gmail.com', 'Testing 1', '2025-10-21 14:33:15'),
(11, 'Musang King', 'musangking1818@gmail.com', 'I like your user interface.', '2025-10-21 14:53:17'),
(12, 'Tete', 'junze007@gmail.com', 'I wan 100% discount', '2025-10-21 14:54:38'),
(13, 'fuwawa', 'audres11@protonmail.com', 'hi', '2025-10-22 02:56:40'),
(14, 'fuwawa', 'wenzhiwu38@gmail.com', 'hi', '2025-10-22 02:57:23'),
(15, 'audres', 'seezilong@gmail.com', 'hello', '2025-10-22 03:21:46'),
(16, 'Topaz', 'seezilong@gmail.com', 'I want to buy your company.', '2025-10-22 03:35:23');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `game_id` int(11) NOT NULL,
  `game_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`game_id`, `game_name`, `description`, `price`, `image`, `created_at`) VALUES
(6, 'GENSHIN', 'MIHOYO', NULL, 'uploads/games/homebg.webp', '2025-09-03 14:37:37'),
(7, '鸣朝', '月卡，skin', NULL, 'uploads/games/mingchao.jpeg', '2025-09-03 14:45:55'),
(8, '星铁', '', NULL, 'uploads/games/xingtie.jpeg', '2025-09-03 15:24:18'),
(9, '王者荣耀', '不好玩', NULL, 'uploads/games/wzry.webp', '2025-09-07 16:13:00'),
(11, 'IdentityV', 'GAME PASS AND SKIN , DIAMOND', NULL, 'uploads/games/IdentityV.png', '2025-09-23 12:53:16'),
(16, 'tower of fantasy', '', NULL, 'uploads/games/TowerOfFantasy_cover.jpg', '2025-10-21 14:43:58'),
(18, 'Reverse 1999', 'BLUEPOCH', NULL, 'uploads/games/VERTIN.jpg', '2025-10-21 14:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `game_items`
--

CREATE TABLE `game_items` (
  `item_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_items`
--

INSERT INTO `game_items` (`item_id`, `game_id`, `item_name`, `price`, `image`) VALUES
(6, 6, '月卡', 22.90, 'uploads/items/yueka.jpeg'),
(7, 6, 'skin1', 29.90, 'uploads/items/skin1.jpg'),
(8, 7, '月卡', 22.90, 'uploads/items/mcyueka.jpeg'),
(9, 8, '月卡', 200.00, 'uploads/items/xtyueka.jpeg'),
(10, 9, '兰陵王', 200.00, 'uploads/items/images.jpeg'),
(13, 11, 'Skin1', 28.80, 'uploads/items/IdentityVSkin 1.jpg'),
(14, 11, '6480Diamond', 648.00, 'uploads/items/IdentityV6480diamond.webp'),
(15, 6, 'Skin 4Star', 28.80, 'uploads/items/genshin-6.avif'),
(16, 9, 'skin', 1.00, '');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `payment_type` enum('TouchNGo','FPX') NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `payment_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `game_id`, `total`, `payment_type`, `status`, `payment_time`, `created_at`) VALUES
(73, 13, 6, 28.80, 'TouchNGo', '', NULL, '2025-10-11 13:16:33'),
(74, 13, 6, 28.80, 'TouchNGo', '', NULL, '2025-10-11 13:20:43'),
(75, 13, 6, 28.80, 'TouchNGo', '', NULL, '2025-10-11 13:33:40'),
(76, 13, 6, 81.60, 'TouchNGo', 'COMPLETE_PAYMENT', NULL, '2025-10-11 13:33:47'),
(77, 13, 6, 58.70, 'TouchNGo', 'DELIVERED', NULL, '2025-10-11 13:50:39'),
(78, 13, 6, 58.70, 'TouchNGo', 'DELIVERED', NULL, '2025-10-11 13:53:14'),
(84, 13, 7, 45.80, 'TouchNGo', 'COMPLETE_PAYMENT', NULL, '2025-10-11 15:30:37'),
(90, 13, 6, 0.82, 'TouchNGo', 'DELIVERED', NULL, '2025-10-11 15:39:24'),
(91, 13, 11, 609.12, 'TouchNGo', 'COMPLETE_PAYMENT', NULL, '2025-10-11 15:55:29'),
(92, 13, 6, 58.70, 'TouchNGo', 'COMPLETE_PAYMENT', NULL, '2025-10-11 16:00:45'),
(106, 13, 11, 72576.00, 'TouchNGo', 'Pending', NULL, '2025-10-13 01:36:41'),
(107, 13, 6, 58.70, 'TouchNGo', 'Pending', NULL, '2025-10-20 08:25:02'),
(108, 13, 6, 28.80, 'TouchNGo', 'Pending', NULL, '2025-10-20 08:39:45'),
(109, 13, 6, 28.80, 'TouchNGo', 'Pending', NULL, '2025-10-20 08:43:49'),
(110, 13, 6, 58.70, 'FPX', 'Pending', NULL, '2025-10-20 08:52:54'),
(111, 13, 7, 22.90, 'FPX', 'Pending', NULL, '2025-10-20 08:57:41'),
(112, 13, 6, 52.80, 'TouchNGo', 'Pending', NULL, '2025-10-20 09:05:37'),
(113, 13, 6, 29.90, 'TouchNGo', 'PENDING_CONFIRMATION', '2025-10-20 17:15:23', '2025-10-20 09:08:11'),
(114, 13, 6, 58.70, 'TouchNGo', 'PENDING_CONFIRMATION', '2025-10-20 17:15:57', '2025-10-20 09:15:52'),
(115, 13, 6, 58.70, 'TouchNGo', 'COMPLETE_PAYMENT', '2025-10-20 17:20:03', '2025-10-20 09:20:01'),
(116, 13, 6, 58.70, 'TouchNGo', 'COMPLETE_PAYMENT', '2025-10-20 17:20:16', '2025-10-20 09:20:11'),
(117, 13, 6, 51.70, 'FPX', 'DELIVERED', '2025-10-20 17:21:12', '2025-10-20 09:21:08'),
(118, 13, 6, 58.70, 'FPX', 'Pending', NULL, '2025-10-20 09:23:31'),
(119, 13, 7, 22.90, 'FPX', 'Pending', NULL, '2025-10-20 09:27:11'),
(120, 13, 6, 29.90, 'TouchNGo', 'Pending', NULL, '2025-10-20 09:29:57'),
(121, 13, 6, 58.70, 'FPX', 'Pending', NULL, '2025-10-20 09:30:04'),
(122, 13, 7, 22.90, 'FPX', 'Pending', NULL, '2025-10-20 09:31:03'),
(123, 13, 6, 28.80, 'TouchNGo', 'DELIVERED', '2025-10-21 17:59:44', '2025-10-21 09:59:38'),
(132, 16, 6, 22.90, 'FPX', 'DELIVERED', '2025-10-21 22:35:52', '2025-10-21 14:35:33'),
(133, 13, 6, 52.80, 'TouchNGo', 'DELIVERED', NULL, '2025-10-21 14:52:26'),
(134, 17, 6, 22.90, 'TouchNGo', 'DELIVERED', '2025-10-21 22:57:10', '2025-10-21 14:54:51'),
(135, 16, 6, 58.70, 'FPX', 'DELIVERED', NULL, '2025-10-21 15:34:31'),
(137, 13, 6, 52.80, 'FPX', 'DELIVERED', NULL, '2025-10-22 02:57:46'),
(138, 13, 9, 1.00, 'FPX', 'DELIVERED', '2025-10-22 11:04:01', '2025-10-22 03:01:36'),
(140, 13, 6, 28.80, 'TouchNGo', 'DELIVERED', '2025-10-22 11:23:02', '2025-10-22 03:22:48'),
(141, 13, 3, 50.16, 'TouchNGo', 'DELIVERED', '2025-10-22 11:24:01', '2025-10-22 03:23:49'),
(148, 20, 6, 28.80, 'TouchNGo', 'DELIVERED', '2025-10-24 12:40:21', '2025-10-24 04:36:06'),
(149, 20, 6, 609.12, 'TouchNGo', 'DELIVERED', '2025-10-24 12:40:16', '2025-10-24 04:36:16'),
(150, 20, 7, 22.90, 'TouchNGo', 'DELIVERED', '2025-10-24 12:37:18', '2025-10-24 04:37:11'),
(151, 20, 9, 1.00, 'FPX', 'DELIVERED', '2025-10-24 12:40:11', '2025-10-24 04:39:36'),
(152, 17, 6, 22.90, 'TouchNGo', 'Pending', NULL, '2025-10-24 04:44:20'),
(153, 17, 6, 22.90, 'TouchNGo', 'Pending', NULL, '2025-10-24 04:44:36'),
(154, 17, 6, 22.90, 'TouchNGo', 'Pending', NULL, '2025-10-24 04:46:25'),
(155, 17, 6, 29.90, 'TouchNGo', 'WAIT_FOR_PAYMENT', NULL, '2025-10-24 04:47:46'),
(156, 20, 6, 22.90, 'TouchNGo', 'COMPLETE_PAYMENT', '2025-10-24 12:56:34', '2025-10-24 04:56:25'),
(157, 17, 11, 108532.80, 'TouchNGo', 'WAIT_FOR_PAYMENT', NULL, '2025-10-24 05:05:34'),
(158, 17, 6, 29.90, 'TouchNGo', 'DELIVERED', '2025-10-24 13:10:22', '2025-10-24 05:05:57'),
(159, 17, 6, 29.90, 'TouchNGo', 'PROCESSING', '2025-10-24 13:16:09', '2025-10-24 05:16:05');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `game_id`, `item_id`, `item_name`, `quantity`, `price`) VALUES
(27, 74, 6, 15, 'Unknown Item', 1, 28.80),
(28, 75, 6, 15, 'Unknown Item', 1, 28.80),
(29, 76, 6, 6, 'Unknown Item', 1, 22.90),
(30, 76, 6, 7, 'Unknown Item', 1, 29.90),
(31, 76, 6, 15, 'Unknown Item', 1, 28.80),
(32, 77, 6, 7, 'Unknown Item', 1, 29.90),
(33, 77, 6, 15, 'Unknown Item', 1, 28.80),
(34, 78, 6, 7, 'Unknown Item', 1, 29.90),
(35, 78, 6, 15, 'Unknown Item', 1, 28.80),
(40, 84, 7, 8, 'Unknown Item', 2, 22.90),
(41, 92, 6, 7, 'Unknown Item', 1, 29.90),
(42, 92, 6, 15, 'Unknown Item', 1, 28.80),
(43, 106, 11, 14, 'Unknown Item', 112, 648.00),
(44, 107, 6, 7, 'Unknown Item', 1, 29.90),
(45, 107, 6, 15, 'Unknown Item', 1, 28.80),
(46, 108, 6, 15, 'Unknown Item', 1, 28.80),
(47, 109, 6, 15, 'Unknown Item', 1, 28.80),
(48, 110, 6, 7, 'Unknown Item', 1, 29.90),
(49, 110, 6, 15, 'Unknown Item', 1, 28.80),
(50, 111, 7, 8, 'Unknown Item', 1, 22.90),
(51, 112, 6, 6, 'Unknown Item', 1, 22.90),
(52, 112, 6, 7, 'Unknown Item', 1, 29.90),
(53, 113, 6, 7, 'Unknown Item', 1, 29.90),
(54, 114, 6, 7, 'Unknown Item', 1, 29.90),
(55, 114, 6, 15, 'Unknown Item', 1, 28.80),
(56, 115, 6, 7, 'Unknown Item', 1, 29.90),
(57, 115, 6, 15, 'Unknown Item', 1, 28.80),
(58, 116, 6, 7, 'Unknown Item', 1, 29.90),
(59, 116, 6, 15, 'Unknown Item', 1, 28.80),
(60, 117, 6, 6, 'Unknown Item', 1, 22.90),
(61, 117, 6, 15, 'Unknown Item', 1, 28.80),
(62, 118, 6, 7, 'Unknown Item', 1, 29.90),
(63, 118, 6, 15, 'Unknown Item', 1, 28.80),
(64, 119, 7, 8, 'Unknown Item', 1, 22.90),
(65, 120, 6, 7, 'Unknown Item', 1, 29.90),
(66, 121, 6, 7, 'Unknown Item', 1, 29.90),
(67, 121, 6, 15, 'Unknown Item', 1, 28.80),
(68, 122, 7, 8, 'Unknown Item', 1, 22.90),
(69, 123, 6, 15, 'Unknown Item', 1, 28.80),
(70, 132, 6, 6, 'Unknown Item', 1, 22.90),
(71, 133, 6, 6, 'Unknown Item', 1, 22.90),
(72, 133, 6, 7, 'Unknown Item', 1, 29.90),
(73, 134, 6, 6, 'Unknown Item', 1, 22.90),
(74, 135, 6, 7, 'Unknown Item', 1, 29.90),
(75, 135, 6, 15, 'Unknown Item', 1, 28.80),
(76, 137, 6, 6, 'Unknown Item', 1, 22.90),
(77, 137, 6, 7, 'Unknown Item', 1, 29.90),
(78, 138, 9, 16, 'Unknown Item', 1, 1.00),
(79, 140, 6, 15, 'Unknown Item', 1, 28.80),
(80, 148, 6, 15, 'Unknown Item', 1, 28.80),
(81, 150, 7, 8, 'Unknown Item', 1, 22.90),
(82, 151, 9, 16, 'Unknown Item', 1, 1.00),
(83, 152, 6, 6, 'Unknown Item', 1, 22.90),
(84, 153, 6, 6, 'Unknown Item', 1, 22.90),
(85, 154, 6, 6, 'Unknown Item', 1, 22.90),
(86, 155, 6, 7, 'Unknown Item', 1, 29.90),
(87, 156, 6, 6, 'Unknown Item', 1, 22.90),
(88, 157, 11, 13, 'Unknown Item', 11, 28.80),
(89, 157, 11, 14, 'Unknown Item', 167, 648.00),
(90, 158, 6, 7, 'Unknown Item', 1, 29.90),
(91, 159, 6, 7, 'Unknown Item', 1, 29.90);

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_items`
--

INSERT INTO `package_items` (`id`, `package_id`, `item_id`) VALUES
(3, 3, 7),
(4, 3, 6),
(13, 6, 14),
(14, 6, 13),
(38, 14, 9),
(39, 15, 8);

-- --------------------------------------------------------

--
-- Table structure for table `staff_admin`
--

CREATE TABLE `staff_admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('staff','admin') NOT NULL,
  `staffid` varchar(10) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_admin`
--

INSERT INTO `staff_admin` (`id`, `username`, `email`, `password`, `role`, `staffid`, `otp`, `created_at`, `avatar`) VALUES
(2, 'huhu', 'wenzhiwu38@gmail.com', '$2y$10$xJpUoF7Zo45fQUxWkCGQcei8M/maPwfZsg1S6D4B2ZNpe9D0EUkFy', 'admin', 'A72076', '', '2025-08-22 04:50:20', 'uploads/68a9964622ee5_WhatsApp Image 2023-07-14 at 22.55.15.jpg'),
(12, 'ASYIKIN', 'nurulasyikinptppd@utmspace.edu.my', '$2y$10$3Yuek.v9WsJnhCbK6TDmVOBcWHD/cc4uMHQyCC6RiDwNwdjR7ctsW', 'admin', 'A64684', '', '2025-08-27 05:38:37', 'uploads/68ae9a6d4bc9e_25102021sports.jpg'),
(15, 'Jesvin文', 'jesvin0502@gmail.com', '$2y$10$tGQQqHHDxBO2osZTFEBhbe9TE4S8o63kd.pjZ0HAK.NlBT6Rld8S6', 'staff', 'S953660', '', '2025-08-28 00:00:00', NULL),
(20, 'see', 'audres11@protonmail.com', '$2y$10$dRFsiaMRs7JiUUKi1pwSae5Iq2Lv6GS5TGnkheDzcgKYD4MnPD3NO', 'staff', 'S90808', '', '2025-10-21 14:23:52', NULL),
(21, 'Vertin', 'songcy2005814@gmail.com', '$2y$10$THV9umfvG8xJuZ3b5luUyeNsv.QyNsaK0tGAKq73/9euk8TeSoIzu', 'admin', 'A05031', '', '2025-10-21 14:36:53', 'uploads/68f79aceab311_VERTIN.jpg'),
(22, 'Vertin', 'songchao.yang@graduate.utm.my', '$2y$10$fWnjaJ.aLl9GdGY8G1BCO.wch1Ry1FvUvueZiCdaoO7SCZwlj.vJO', 'staff', 'S404571', '', '2025-10-21 14:39:03', 'uploads/68f8d388263b4_VERTIN.jpg'),
(23, 'Hande007', 'junze007@gmail.com', '$2y$10$f/yRMmhzB9YzQzQyICHl5egmjTnbSakZzto8OFhv4.aO3/kk2QgIa', 'admin', 'A90277', '', '2025-10-24 02:15:51', 'uploads/68fafcb932b37_CREDIT-TRANSFER-APPLICATION-PROCESS_page-0001.jpg'),
(24, 'Ze', 'jzutmsdsec42@gmail.com', '$2y$10$j64A7z/VKDalJWHoc5S7/e/akz4BDAH3z.ic1a2MNqrJ8yRHd.vq6', 'staff', 'S99256', '', '2025-10-24 04:08:57', NULL),
(25, 'audres', 'zhibin05@graduate.utm.my', '$2y$10$yDK0eaViFtlcmdNQ1hluo.TR9NINstNTrSVYqybnEatri.Gzd2Tqa', 'admin', 'A21587', '', '2025-10-24 08:42:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `topup_packages`
--

CREATE TABLE `topup_packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount` decimal(5,2) DEFAULT 0.00 COMMENT '折扣百分比 0~100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topup_packages`
--

INSERT INTO `topup_packages` (`package_id`, `package_name`, `price`, `description`, `image`, `created_at`, `discount`) VALUES
(3, 'GENSHIN PACKAGE', 0.00, 'GAME PASS AND SKIN1', 'uploads/packages/homebg.webp', '2025-09-03 15:12:17', 5.00),
(6, 'IdentityV', 0.00, 'Skin1 and 648 Diamond', 'uploads/packages/IdentityV.png', '2025-09-23 13:03:33', 10.00),
(13, '0CT PROMOTION', 0.00, 'VALUE', 'uploads/packages/VERTIN.jpg', '2025-10-21 14:46:16', 10.00),
(14, 'Honkai Star Rail Starter Package', 0.00, '', 'uploads/packages/herta-contract-quest-sharing-item_icon_figure.webp', '2025-10-24 04:42:48', 20.00),
(15, 'wuwa starter pack', 0.00, '', 'uploads/packages/unnamed.jpg', '2025-10-24 04:44:15', 55.00);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `otp` varchar(10) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `otp`, `is_verified`, `created_at`, `profile_pic`, `avatar`) VALUES
(13, 'huhu', 'wenzhiwu38@gmail.com', '$2y$10$YneWK74kPaTuD5uyNHbdHeY13Cs6LX8AfXVG6SguuasYBzky9rMhe', '214605', 1, '2025-09-04 00:00:00', NULL, 'uploads/68bdade484070_315cdf4cf438bb0e6c361d3c7a8981f1_preview.jpg'),
(16, 'Vertin', 'songcy2005814@gmail.com', '$2y$10$Y5gefYuYRQ0wElyAh26D3.TAvRQvU65Xx71GxxXwlvjqvT3j2yxYq', '315642', 1, '2025-10-21 14:28:30', NULL, 'uploads/68f79957ba662_VERTIN.jpg'),
(17, 'Hande', 'junze007@gmail.com', '$2y$10$LgOG0VpMo3Z8GSUM0nnjp.pvqRD78Aki8HL0i8S2B2uHqSLXaHWGG', '596028', 1, '2025-10-21 14:52:07', NULL, 'uploads/68f79e593a67c_ely.jpg'),
(18, 'Jesvin', 'jesvin0501@gmail.com', '$2y$10$ml3CFwGB6IVJF18p0c9sPuPvOr5a/6ywgT9lQj7yH7ZzueetTxccK', '974350', 1, '2025-10-22 02:50:02', NULL, NULL),
(19, 'Jesvin', 'jesvin@graduate.utm.my', '$2y$10$4bBDw.QD6YOZddGZUSHSEebHAH24a6q1Ps0D21NcDDNYQ.Gwx8W0i', '792681', 0, '2025-10-22 00:00:00', NULL, NULL),
(20, 'audres', 'zhibin05@graduate.utm.my', '$2y$10$exgNSTyGYgzmyhK71kizUuNWN.Bo/jaNifSy83gmROn42NTSJ.PZi', '026738', 1, '2025-10-24 04:33:56', NULL, 'uploads/68fb01ff12985_Screenshot (850).png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `game_items`
--
ALTER TABLE `game_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `staff_admin`
--
ALTER TABLE `staff_admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `staffid` (`staffid`);

--
-- Indexes for table `topup_packages`
--
ALTER TABLE `topup_packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `game_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `game_items`
--
ALTER TABLE `game_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `staff_admin`
--
ALTER TABLE `staff_admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `topup_packages`
--
ALTER TABLE `topup_packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `game_items`
--
ALTER TABLE `game_items`
  ADD CONSTRAINT `game_items_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `game_items` (`item_id`);

--
-- Constraints for table `package_items`
--
ALTER TABLE `package_items`
  ADD CONSTRAINT `package_items_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `topup_packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `game_items` (`item_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
