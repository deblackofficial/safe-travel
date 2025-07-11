-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 26, 2025 at 12:04 AM
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
-- Database: `safe_travel`
--

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `rfid_uid` varchar(50) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `model` varchar(100) DEFAULT 'Coaster',
  `capacity` int(11) NOT NULL,
  `route_id` int(11) DEFAULT NULL,
  `status` enum('active','maintenance','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `rfid_uid`, `plate_number`, `model`, `capacity`, `route_id`, `status`, `created_at`) VALUES
(6, '1340C32C', 'RAF 250 B', 'Coaster', 2, 12, 'active', '2025-06-22 17:46:47'),
(8, 'F3D2F82C', 'RAG 123 H', 'Coaster', 4, 14, 'active', '2025-06-23 14:38:41');

-- --------------------------------------------------------

--
-- Table structure for table `bus_active_trips`
--

CREATE TABLE `bus_active_trips` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_active_trips`
--

INSERT INTO `bus_active_trips` (`id`, `bus_id`, `route_id`, `start_time`, `end_time`) VALUES
(44, 6, 12, '2025-06-22 19:49:02', '2025-06-22 20:02:13'),
(45, 6, 12, '2025-06-22 20:02:34', '2025-06-22 20:13:09'),
(46, 6, 12, '2025-06-22 20:18:16', '2025-06-23 14:06:22'),
(47, 6, 12, '2025-06-23 14:06:36', '2025-06-23 14:09:12'),
(48, 6, 12, '2025-06-23 14:47:03', '2025-06-23 15:34:34'),
(49, 6, 12, '2025-06-23 15:38:50', '2025-06-23 16:26:30'),
(50, 6, 12, '2025-06-23 16:26:39', '2025-06-23 16:34:51'),
(51, 8, 14, '2025-06-23 20:01:46', '2025-06-23 20:05:38'),
(52, 6, 12, '2025-06-23 20:05:44', '2025-06-23 20:13:09'),
(53, 8, 14, '2025-06-24 14:39:54', '2025-06-24 14:39:59'),
(54, 8, 14, '2025-06-24 14:47:43', '2025-06-24 15:02:52'),
(55, 8, 14, '2025-06-24 15:04:00', '2025-06-24 15:11:20'),
(56, 6, 12, '2025-06-24 15:05:34', '2025-06-24 15:09:54'),
(57, 6, 12, '2025-06-24 15:11:44', '2025-06-24 15:11:59'),
(58, 8, 14, '2025-06-24 15:12:06', '2025-06-24 15:12:11'),
(59, 8, 14, '2025-06-24 15:38:06', '2025-06-24 15:39:00'),
(60, 8, 14, '2025-06-25 09:30:17', '2025-06-25 09:32:03'),
(61, 6, 12, '2025-06-25 09:32:33', '2025-06-25 09:43:52'),
(62, 6, 12, '2025-06-25 10:48:56', '2025-06-25 10:49:21'),
(63, 8, 14, '2025-06-25 12:16:43', '2025-06-25 12:22:45'),
(64, 6, 12, '2025-06-25 12:22:54', '2025-06-25 12:30:03'),
(65, 6, 12, '2025-06-25 15:36:16', '2025-06-25 16:05:47'),
(66, 8, 14, '2025-06-25 16:05:06', '2025-06-25 16:05:14'),
(67, 8, 14, '2025-06-25 16:05:25', '2025-06-25 16:05:33'),
(68, 6, 12, '2025-06-25 16:06:04', '2025-06-25 19:16:13'),
(69, 6, 12, '2025-06-25 19:16:30', '2025-06-25 19:56:52'),
(70, 8, 14, '2025-06-25 19:56:46', '2025-06-25 19:56:49');

-- --------------------------------------------------------

--
-- Table structure for table `bus_assignments`
--

CREATE TABLE `bus_assignments` (
  `id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bus_occupancy`
--

CREATE TABLE `bus_occupancy` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `current_passengers` int(11) NOT NULL DEFAULT 0,
  `max_capacity` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_occupancy`
--

INSERT INTO `bus_occupancy` (`id`, `bus_id`, `current_passengers`, `max_capacity`, `last_updated`) VALUES
(3, 6, 0, 2, '2025-06-25 17:56:06'),
(4, 7, 0, 2, '2025-06-23 08:38:42'),
(5, 8, 0, 4, '2025-06-25 10:22:33');

-- --------------------------------------------------------

--
-- Table structure for table `driver_report`
--

CREATE TABLE `driver_report` (
  `id` int(20) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `agency` varchar(50) NOT NULL,
  `plate` varchar(50) NOT NULL,
  `place` varchar(50) NOT NULL,
  `datetime` varchar(80) NOT NULL,
  `permit` varchar(79) NOT NULL,
  `latitude` varchar(59) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `accident` varchar(55) NOT NULL,
  `unauthorized` varchar(55) NOT NULL,
  `description` text NOT NULL,
  `archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_report`
--

INSERT INTO `driver_report` (`id`, `phone`, `agency`, `plate`, `place`, `datetime`, `permit`, `latitude`, `longitude`, `accident`, `unauthorized`, `description`, `archived`) VALUES
(31, '0101010101', 'Ritco', '00000', 'KIGALI_HUYE_RUSIZI', '2025-05-30T01:03', 'Screenshot 2024-09-14 173210.png', '-1.962803', '30.103962', '1', '1', 'helloooo', 1),
(32, '0790007115', 'Ritco', 'RAC 345', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T01:06', 'Screenshot 2024-09-14 173210.png', '-1.962803', '30.103962', '1', '0', 'hfyyfhg', 0),
(33, '0790007115', 'Volcano', 'RAC 345', 'KIGALI-MUSANZE_RUBAVU', '2025-05-09T13:43', 'Screenshot 2025-04-08 150042.png', '-1.962803', '30.103962', '0', '0', '', 0),
(34, '0786225458', '35', '11111111111', 'KIGALI_HUYE_RUSIZI', '2025-05-09T13:52', 'Screenshot 2025-04-08 150300.png', '-1.962803', '30.103962', '0', '0', '', 0),
(37, '0786225458', 'qw', 'qwqw', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T14:00', 'Screenshot 2025-03-12 091710.png', '-1.962803', '30.103962', '1', '1', 'biteee csccsc', 0),
(38, '1234567899', 'hfhf12232', 'q12122345455', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T14:04', 'Screenshot 2025-03-12 091710.png', '-1.962803', '30.103962', '1', '1', '12 check', 0),
(39, '0786225458', 'OMEGA', 'RAC 345 B', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T22:18', 'Screenshot 2025-03-12 091710.png', '-1.959526', '30.087578', '1', '0', 'kkxce', 0),
(40, '0711111111', 'Deblack', 'Rad133Blacl', 'Kigali - Runda - Muganza', '2025-05-10T02:20', 'Screenshot 2025-04-08 150300.png', '-1.959526', '30.087578', '1', '1', 'weeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 0),
(41, '0787055950', 'YAHOO', 'RAF 321 B', 'KIGALI  - MUSANZE - RUBAVU', '2025-05-14T16:03', 'ind.p.png', '-1.952973', '30.097408', '1', '1', ';;ohudtfufyfdtdrsrtyturyyfutedfdsrtsfsrssrsrt', 0),
(42, '0787055950', 'YAHOO', 'RAF 321 B', 'KIGALI  - KAYONZA - NYAGATARE', '2025-06-13T18:50', 'hhhh.jpg', '', '', '0', '1', 'I just wanna report someone who have a kind of product that is illegal in this car \r\n', 0);

-- --------------------------------------------------------

--
-- Table structure for table `passenger_report`
--

CREATE TABLE `passenger_report` (
  `id` int(55) NOT NULL,
  `ticket` varchar(50) NOT NULL,
  `agency` varchar(50) NOT NULL,
  `plate` varchar(50) NOT NULL,
  `place` varchar(50) NOT NULL,
  `datetime` varchar(80) NOT NULL,
  `upload` varchar(79) NOT NULL,
  `latitude` varchar(59) NOT NULL,
  `longitude` varchar(50) NOT NULL,
  `overloading` varchar(20) NOT NULL,
  `accident` varchar(20) NOT NULL,
  `unauthorized` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `archived` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passenger_report`
--

INSERT INTO `passenger_report` (`id`, `ticket`, `agency`, `plate`, `place`, `datetime`, `upload`, `latitude`, `longitude`, `overloading`, `accident`, `unauthorized`, `description`, `archived`) VALUES
(1, 'qwqwwe', 'wwewe', 'wqqw', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T15:10', 'Screenshot 2024-09-14 173210.png', '-1.962803', '30.103962', '1', '1', '1', 'byakoze cg nibisekezo', 1),
(2, '12918283', 'International', 'RAD 123K', 'KIGALI-MUSANZE_RUBAVU', '2025-05-09T15:16', 'Screenshot 2025-03-12 091710.png', '-1.962803', '30.103962', '1', '0', '1', 'ibintu birakaze\r\n', 0),
(8, '1111111', 'OMEGA', 'RAC 345', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T22:31', 'Screenshot 2025-04-10 105419.png', '-1.959526', '30.087578', '0', '1', '0', '', 0),
(9, '11111', 'ALFA', 'RAF 321 B', 'KIGALI  - HUYE - RUSIZI', '2025-06-18T09:07', '22.jpg', '-1.962800', '30.064526', '1', '1', '0', 'this is too much ', 0),
(10, '123', 'OMEGA', 'RAB 321 G', 'KIGALI  - NGARAMA  - NYAGATARE', '2025-06-22T23:44', 'KigaliNets.png', '-1.966080', '30.100685', '1', '0', '0', '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `passenger_trips`
--

CREATE TABLE `passenger_trips` (
  `id` int(11) NOT NULL,
  `passenger_id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `entry_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `exit_time` timestamp NULL DEFAULT NULL,
  `status` enum('boarding','completed','over_limit') NOT NULL DEFAULT 'boarding',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `passenger_trips`
--

INSERT INTO `passenger_trips` (`id`, `passenger_id`, `bus_id`, `route_id`, `entry_time`, `exit_time`, `status`, `is_active`) VALUES
(1, 6048, 5, 8, '2025-06-22 17:02:49', '2025-06-22 17:09:51', 'boarding', 0),
(2, 6048, 5, 8, '2025-06-22 17:10:27', '2025-06-22 17:12:05', 'boarding', 0),
(3, 6048, 5, 8, '2025-06-22 17:12:53', '2025-06-22 17:13:25', 'boarding', 0),
(4, 6048, 5, 8, '2025-06-22 17:13:36', '2025-06-22 17:20:12', 'boarding', 0),
(5, 6048, 5, 8, '2025-06-22 17:20:57', '2025-06-22 17:23:54', 'boarding', 0),
(6, 6048, 5, 8, '2025-06-22 17:24:54', '2025-06-22 17:25:06', 'boarding', 0),
(7, 6048, 5, 8, '2025-06-22 17:25:16', '2025-06-22 17:25:31', 'boarding', 0),
(8, 6048, 5, 8, '2025-06-22 17:31:26', '2025-06-22 17:33:20', 'boarding', 0),
(9, 6048, 5, 8, '2025-06-22 17:42:30', NULL, 'boarding', 1),
(10, 6048, 6, 12, '2025-06-22 17:49:15', '2025-06-22 18:02:13', 'boarding', 0),
(11, 6048, 6, 12, '2025-06-22 18:02:43', '2025-06-22 18:13:09', 'boarding', 0),
(12, 6048, 6, 12, '2025-06-22 18:18:25', '2025-06-22 18:18:41', 'boarding', 0),
(13, 6048, 6, 12, '2025-06-22 18:19:44', '2025-06-22 18:20:02', 'boarding', 0),
(14, 2469, 6, 12, '2025-06-22 18:30:06', '2025-06-22 18:32:31', 'boarding', 0),
(15, 1931, 6, 12, '2025-06-23 12:07:07', '2025-06-23 12:07:29', 'boarding', 0),
(16, 2469, 6, 12, '2025-06-23 12:07:43', '2025-06-23 12:09:12', 'boarding', 0),
(17, 1931, 6, 12, '2025-06-23 12:07:56', '2025-06-23 12:09:12', 'boarding', 0),
(18, 6048, 6, 12, '2025-06-23 12:08:02', '2025-06-23 12:09:12', 'boarding', 0),
(19, 6048, 6, 12, '2025-06-23 12:47:09', '2025-06-23 12:47:32', 'boarding', 0),
(20, 1931, 6, 12, '2025-06-23 12:47:17', '2025-06-23 12:49:43', 'boarding', 0),
(21, 6048, 6, 12, '2025-06-23 12:48:02', '2025-06-23 12:48:27', 'boarding', 0),
(22, 2469, 6, 12, '2025-06-23 12:48:42', '2025-06-23 12:49:51', 'boarding', 0),
(23, 6048, 6, 12, '2025-06-23 12:49:00', '2025-06-23 12:49:30', 'over_limit', 0),
(24, 2469, 6, 12, '2025-06-23 12:50:46', '2025-06-23 13:24:24', 'boarding', 0),
(25, 1931, 6, 12, '2025-06-23 12:50:58', '2025-06-23 13:24:21', 'boarding', 0),
(26, 6048, 6, 12, '2025-06-23 12:51:14', '2025-06-23 13:24:15', 'over_limit', 0),
(27, 6048, 6, 12, '2025-06-23 13:39:53', '2025-06-23 13:40:42', 'boarding', 0),
(28, 1931, 6, 12, '2025-06-23 13:40:01', '2025-06-23 13:40:35', 'boarding', 0),
(29, 6048, 6, 12, '2025-06-23 13:40:55', '2025-06-23 13:41:11', 'boarding', 0),
(30, 6048, 6, 12, '2025-06-23 13:41:57', '2025-06-23 13:42:31', 'boarding', 0),
(31, 1931, 6, 12, '2025-06-23 13:42:00', '2025-06-23 13:42:27', 'boarding', 0),
(32, 6048, 6, 12, '2025-06-23 13:46:46', '2025-06-23 13:47:58', 'boarding', 0),
(33, 6048, 6, 12, '2025-06-23 13:48:52', '2025-06-23 13:49:36', 'boarding', 0),
(34, 1931, 6, 12, '2025-06-23 13:48:59', '2025-06-23 13:49:32', 'boarding', 0),
(35, 2469, 6, 12, '2025-06-23 14:26:52', '2025-06-23 14:27:28', 'boarding', 0),
(36, 6048, 6, 12, '2025-06-23 14:27:07', '2025-06-23 14:27:18', 'boarding', 0),
(37, 1931, 8, 14, '2025-06-23 18:02:01', '2025-06-23 18:05:08', 'boarding', 0),
(38, 2469, 8, 14, '2025-06-23 18:02:28', '2025-06-23 18:05:14', 'boarding', 0),
(39, 6048, 8, 14, '2025-06-23 18:03:03', '2025-06-23 18:03:17', 'boarding', 0),
(40, 6048, 6, 12, '2025-06-23 18:07:26', '2025-06-23 18:13:09', 'boarding', 0),
(41, 2469, 6, 12, '2025-06-23 18:07:32', '2025-06-23 18:13:09', 'boarding', 0),
(42, 1931, 6, 12, '2025-06-23 18:07:36', '2025-06-23 18:13:09', 'over_limit', 0),
(43, 2469, 8, 14, '2025-06-24 13:04:16', '2025-06-24 13:11:20', 'boarding', 0),
(44, 6048, 8, 14, '2025-06-24 13:04:23', '2025-06-24 13:11:20', 'boarding', 0),
(45, 1931, 8, 14, '2025-06-24 13:04:28', '2025-06-24 13:11:20', 'boarding', 0),
(46, 2469, 6, 12, '2025-06-24 13:06:04', '2025-06-24 13:07:50', 'boarding', 0),
(47, 6048, 6, 12, '2025-06-24 13:06:11', '2025-06-24 13:07:52', 'boarding', 0),
(48, 1931, 6, 12, '2025-06-24 13:06:30', '2025-06-24 13:07:47', 'over_limit', 0),
(49, 1931, 8, 14, '2025-06-25 07:30:52', '2025-06-25 07:32:03', 'boarding', 0),
(50, 6048, 8, 14, '2025-06-25 07:31:17', '2025-06-25 07:32:03', 'boarding', 0),
(51, 2469, 8, 14, '2025-06-25 07:31:30', '2025-06-25 07:32:03', 'boarding', 0),
(52, 2469, 6, 12, '2025-06-25 07:32:40', '2025-06-25 07:43:41', 'boarding', 0),
(53, 6048, 6, 12, '2025-06-25 07:32:47', '2025-06-25 07:43:44', 'boarding', 0),
(54, 1931, 6, 12, '2025-06-25 07:32:54', '2025-06-25 07:43:39', 'over_limit', 0),
(55, 6048, 8, 14, '2025-06-25 10:19:18', '2025-06-25 10:22:33', 'boarding', 0),
(56, 1931, 8, 14, '2025-06-25 10:20:19', '2025-06-25 10:22:30', 'boarding', 0),
(57, 2469, 6, 12, '2025-06-25 10:23:03', '2025-06-25 10:26:17', 'boarding', 0),
(58, 6048, 6, 12, '2025-06-25 10:23:10', '2025-06-25 10:29:06', 'boarding', 0),
(59, 2469, 6, 12, '2025-06-25 10:26:29', '2025-06-25 10:29:02', 'boarding', 0),
(60, 1931, 6, 12, '2025-06-25 10:26:37', '2025-06-25 10:28:58', 'over_limit', 0),
(61, 6048, 6, 12, '2025-06-25 13:36:31', '2025-06-25 14:05:47', 'boarding', 0),
(62, 1931, 6, 12, '2025-06-25 13:36:37', '2025-06-25 14:05:47', 'boarding', 0),
(63, 2469, 6, 12, '2025-06-25 13:36:53', '2025-06-25 14:05:47', 'over_limit', 0),
(64, 2469, 6, 12, '2025-06-25 14:15:37', '2025-06-25 14:16:52', 'boarding', 0),
(65, 6048, 6, 12, '2025-06-25 14:15:42', '2025-06-25 14:16:56', 'boarding', 0),
(66, 1931, 6, 12, '2025-06-25 14:16:02', '2025-06-25 14:17:01', 'over_limit', 0),
(67, 6048, 6, 12, '2025-06-25 17:16:59', '2025-06-25 17:37:34', 'boarding', 0),
(68, 1931, 6, 12, '2025-06-25 17:17:02', '2025-06-25 17:21:11', 'boarding', 0),
(69, 2469, 6, 12, '2025-06-25 17:17:12', '2025-06-25 17:20:57', 'over_limit', 0),
(70, 1931, 6, 12, '2025-06-25 17:21:21', '2025-06-25 17:34:27', 'boarding', 0),
(71, 2469, 6, 12, '2025-06-25 17:21:27', '2025-06-25 17:34:59', 'over_limit', 0),
(74, 2469, 6, 12, '2025-06-25 17:35:33', '2025-06-25 17:37:53', 'boarding', 0),
(75, 1931, 6, 12, '2025-06-25 17:38:03', '2025-06-25 17:43:27', 'boarding', 0),
(76, 2469, 6, 12, '2025-06-25 17:38:12', '2025-06-25 17:52:35', 'boarding', 0),
(77, 6048, 6, 12, '2025-06-25 17:38:20', '2025-06-25 17:43:55', 'over_limit', 0),
(78, 1931, 6, 12, '2025-06-25 17:43:47', '2025-06-25 17:52:46', 'boarding', 0),
(79, 6048, 6, 12, '2025-06-25 17:44:12', '2025-06-25 17:52:50', 'over_limit', 0),
(80, 1931, 6, 12, '2025-06-25 17:53:07', '2025-06-25 17:54:06', 'boarding', 0),
(81, 6048, 6, 12, '2025-06-25 17:53:13', '2025-06-25 17:54:15', 'boarding', 0),
(82, 2469, 6, 12, '2025-06-25 17:53:19', '2025-06-25 17:54:19', 'over_limit', 0),
(83, 2469, 6, 12, '2025-06-25 17:54:29', '2025-06-25 17:56:03', 'boarding', 0),
(84, 6048, 6, 12, '2025-06-25 17:54:32', '2025-06-25 17:56:06', 'boarding', 0),
(85, 1931, 6, 12, '2025-06-25 17:54:40', '2025-06-25 17:56:00', 'over_limit', 0);

-- --------------------------------------------------------

--
-- Table structure for table `rfid_cards`
--

CREATE TABLE `rfid_cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `card_uid` varchar(20) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `card_type` enum('passenger','driver') NOT NULL DEFAULT 'passenger'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rfid_cards`
--

INSERT INTO `rfid_cards` (`id`, `user_id`, `card_uid`, `assigned_at`, `card_type`) VALUES
(5, 2469, 'B3370B42', '2025-06-20 19:43:32', 'passenger'),
(6, 6048, '03F6C641', '2025-06-21 16:34:02', 'passenger'),
(7, 1931, 'AEC4FC03', '2025-06-21 16:50:19', 'passenger');

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `id` int(11) NOT NULL,
  `start_point` varchar(255) NOT NULL,
  `middle_point` varchar(255) DEFAULT NULL,
  `end_point` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `distance` decimal(10,2) DEFAULT NULL,
  `estimated_time` varchar(50) DEFAULT NULL,
  `fare` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `start_point`, `middle_point`, `end_point`, `status`, `created_at`, `distance`, `estimated_time`, `fare`) VALUES
(8, 'KIGALI ', 'MUSANZE', 'RUBAVU', 'active', '2025-05-29 14:02:55', NULL, NULL, NULL),
(10, 'KIGALI ', 'HUYE', 'RUSIZI', 'active', '2025-06-13 16:43:46', NULL, NULL, NULL),
(11, 'KIGALI ', 'HUYE', 'NYARUGURU', 'active', '2025-06-13 16:46:27', NULL, NULL, NULL),
(12, 'KIGALI ', 'KAYONZA', 'NYAGATARE', 'active', '2025-06-13 16:46:40', NULL, NULL, NULL),
(13, 'KIGALI ', 'KAYONZA', 'RUSUMO', 'active', '2025-06-13 16:48:12', NULL, NULL, NULL),
(14, 'KIGALI ', 'KARONGI', 'RUSIZI', 'active', '2025-06-13 16:48:33', NULL, NULL, NULL),
(16, 'KIGALI ', 'NGARAMA ', 'NYAGATARE', 'active', '2025-06-13 16:49:28', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_notifications`
--

CREATE TABLE `system_notifications` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `notification_type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_notifications`
--

INSERT INTO `system_notifications` (`id`, `bus_id`, `notification_type`, `message`, `is_read`, `created_at`) VALUES
(1, 6, 'over_capacity', 'Bus RAF 250 B exceeded capacity (2/2)', 0, '2025-06-25 17:53:24'),
(2, 6, 'over_capacity', 'Bus RAF 250 B exceeded capacity (2/2)', 0, '2025-06-25 17:54:45');

-- --------------------------------------------------------

--
-- Table structure for table `temp_card`
--

CREATE TABLE `temp_card` (
  `id` int(11) NOT NULL,
  `card_uid` varchar(20) NOT NULL,
  `card_status` enum('available','registered') DEFAULT 'available',
  `user_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`user_info`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_card`
--

INSERT INTO `temp_card` (`id`, `card_uid`, `card_status`, `user_info`, `created_at`) VALUES
(38, '1340C32C', '', NULL, '2025-06-23 12:04:03');

-- --------------------------------------------------------

--
-- Table structure for table `temp_rfid_scans`
--

CREATE TABLE `temp_rfid_scans` (
  `uid` varchar(32) NOT NULL,
  `timestamp` int(11) NOT NULL,
  `status` enum('available','registered') NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_rfid_scans`
--

INSERT INTO `temp_rfid_scans` (`uid`, `timestamp`, `status`, `bus_id`, `plate_number`) VALUES
('03F6C641', 1750689849, 'available', NULL, NULL),
('1340C32C', 1750836453, 'registered', 6, 'RAF 250 B'),
('24996F59', 1750857079, 'available', NULL, NULL),
('AEC4FC03', 1750667997, 'available', NULL, NULL),
('B3370B42', 1750668480, 'available', NULL, NULL),
('F3D2F82C', 1750857090, 'registered', 8, 'RAG 123 H');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `receive_notifications` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `phone_number`, `email`, `password`, `role`, `status`, `receive_notifications`) VALUES
(1, 'admin', 'NZIBUKA', 'Emmanuel', '0788907402', 'emmynzibk21@gmail.com', '$2y$10$pD.kQn0JC.WyONsQs0mHeuHKBlCQ/9hwWqu1QMJpa9lAjj2iuxpNG', 'admin', 'active', 1),
(9, 'UMUBYEYI', 'UMUBYEYI', 'Esperance', '0780748933', 'umubyeyi@gmai.com', '$2y$10$07Lz1/1a3NWzA4dFiG4.reXfqqeEhWjk.OXLEqIDtbvi.L1O/wnKW', 'manager', 'active', 1),
(10, 'INGABIRE', 'INGABIRE', 'Gentille', '0780748959', 'ingabire@gmail.com', '$2y$10$11W3zzPyYJaeBkZ9wyZrs.figjCovRkWF4I2CpbbApmcutL/la4Zm', 'user', 'active', 1),
(1931, 'AJENEZA', 'AJENEZA ', 'Mugisha Hussein', '0735098841', 'husseinajenezagi073@gmail.com', '$2y$10$8w2.W5M68Asxhr5pANzi6uOQkebvRdrY7F5lKPxZ7ksO.LtpRNPfS', 'user', 'active', 1),
(2469, 'mannaz', 'nzi', 'Emmy', '0788907402', 'ipcodes253@gmail.com', '$2y$10$gWzN4U3sK9Vac/RiRiy2N.nUhf6vEGODmIK8AJVBq70oS0IXqghdS', 'user', 'active', 1),
(6048, 'NZIBUKA', 'NZIBUKA ', 'Emmanuel', '0789813848', 'emmynzibk21@gmail.com', '$2y$10$5EflwGcU2z1kqd038GGyQuMbTpoVTgZmcR2WJJ1n3FepeIRUfKl6i', 'user', 'active', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfid_uid` (`rfid_uid`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD KEY `fk_bus_route` (`route_id`);

--
-- Indexes for table `bus_active_trips`
--
ALTER TABLE `bus_active_trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `bus_assignments`
--
ALTER TABLE `bus_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bus_occupancy`
--
ALTER TABLE `bus_occupancy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bus_id` (`bus_id`);

--
-- Indexes for table `driver_report`
--
ALTER TABLE `driver_report`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `passenger_report`
--
ALTER TABLE `passenger_report`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQUE` (`ticket`);

--
-- Indexes for table `passenger_trips`
--
ALTER TABLE `passenger_trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `passenger_id` (`passenger_id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_uid` (`card_uid`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`);

--
-- Indexes for table `temp_card`
--
ALTER TABLE `temp_card`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temp_rfid_scans`
--
ALTER TABLE `temp_rfid_scans`
  ADD PRIMARY KEY (`uid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bus_active_trips`
--
ALTER TABLE `bus_active_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `bus_assignments`
--
ALTER TABLE `bus_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bus_occupancy`
--
ALTER TABLE `bus_occupancy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `driver_report`
--
ALTER TABLE `driver_report`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `passenger_report`
--
ALTER TABLE `passenger_report`
  MODIFY `id` int(55) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `passenger_trips`
--
ALTER TABLE `passenger_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `system_notifications`
--
ALTER TABLE `system_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `temp_card`
--
ALTER TABLE `temp_card`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7638;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buses`
--
ALTER TABLE `buses`
  ADD CONSTRAINT `fk_bus_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `bus_active_trips`
--
ALTER TABLE `bus_active_trips`
  ADD CONSTRAINT `bus_active_trips_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`),
  ADD CONSTRAINT `bus_active_trips_ibfk_2` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`);

--
-- Constraints for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD CONSTRAINT `rfid_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `system_notifications`
--
ALTER TABLE `system_notifications`
  ADD CONSTRAINT `system_notifications_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
