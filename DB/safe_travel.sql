-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 10, 2025 at 09:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(40, '0711111111', 'Deblack', 'Rad133Blacl', 'Kigali - Runda - Muganza', '2025-05-10T02:20', 'Screenshot 2025-04-08 150300.png', '-1.959526', '30.087578', '1', '1', 'weeeeeeeeeeeeeeeeeeeeeeeeeeeeeee', 0);

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
(3, '0788900299', 'OMEGA', 'RAF 123K', 'KIGALI_KAYONZA_KAGITUMBA', '2025-05-09T16:44', 'Screenshot 2025-04-08 150042.png', '-1.959526', '30.087578', '1', '1', '1', 'Uretse ababyeyi be n’inshuti ze byagoye kumva ko Masezerano yaba yarakinnye muri filime asomana n’umukobwa, ahamya ko n’umukobwa bakundana byamugoye kubyakira nubwo yari yabanje no kumuteguza mbere y’uko isohoka.\r\n\r\nMasezerano usanzwe ari umwana wa Pasiteri, avuga ko nyuma y’uko amashusho ye na Natacha Ndahiro agiye hanze yagowe no kubisobanura yaba ku nshuti ze, ababyeyi ndetse n’umukunzi we.\r\n\r\nAhereye ku muryango we, Masezerano ahamya ko ikintu yabonye cyagoranye ari uko bitiranya ubuzima bwe n’ubwitorero umubyeyi we abereye Pasiteri.\r\n\r\nAti “Icya mbere ni uko umuryango ari kimwe na Pasiteri ni ikindi, njye iyo ndi kumwe na muzehe cyangwa na mukecuru tuganira nk’umuryango ntabwo ari nka Pasiteri. Niyo bagiye kumpana ntibampana nk’uwarenze ku mabwiriza y’itorero, bampana nk’uwarenze ku mabwiriza y’umuryango.”\r\n\r\nMasezerano ahamya ko ababyeyi be babona amashusho ari gusomana muri filime byabateye ikibazo, icyakora ashimira Imana ko byagabanutse.\r\n\r\nAti “Mu by’ukuri kuko bwari ubwa mbere byarababaje, ariko uko byagenda kose ubu tumeze neza nta kibazo gihari.”\r\n\r\nMasezerano yavuze ko kimwe mu byamufashije kumvikana n’ababyeyi be ari uko yari yabateguje mbere y’uko filime isohoka.\r\n\r\nUretse ababyeyi, n’inshuti ze byazigoye kubyumva, icyakora ahamya ko uwo byagoye ubwo atari umukunzi w’akazi akora.\r\n\r\nUndi uyu musore yemeza ko yagowe no kumva ibyo yakoze, ni umukobwa basanzwe bakundana.', 1),
(8, '1111111', 'OMEGA', 'RAC 345', 'KIGALI_KARONGI_RUSIZI', '2025-05-09T22:31', 'Screenshot 2025-04-10 105419.png', '-1.959526', '30.087578', '0', '1', '0', '', 0);

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `start_point`, `middle_point`, `end_point`, `status`, `created_at`) VALUES
(1, 'Hello', 'middle', 'end', 'active', '2025-05-09 18:36:05'),
(2, 'Kigali', 'Runda', 'Muganza', 'active', '2025-05-10 00:16:58'),
(3, 'kigali', 'Gatsata', 'Kimisagara', 'active', '2025-05-10 00:40:31'),
(4, 'kigali', 'Gatsata', 'Kimisagara', 'inactive', '2025-05-10 00:42:37'),
(5, 'Gisenyi', 'Muhanga', 'Rwanda', 'active', '2025-05-10 00:43:25'),
(6, 'bishenyiiiii', 'runda', 'ruyenziiii', 'active', '2025-05-10 06:59:19');

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
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `phone_number`, `email`, `password`, `role`, `status`) VALUES
(1, 'admin', 'Nzibuka', 'Emmanuel', '0788907402', 'nzibuka@gmail.com', 'admin123', 'admin', 'active'),
(2, 'user1', 'Eric', 'Dusabe', '0788377373', 'ericblcdusx@gmail.com', '123456', 'manager', 'active'),
(3, 'user2', 'helooo', 'user2', '09999', 'user@gmail.com', 'user123', 'user', 'inactive'),
(4, 'user3', 'kwizera', 'patrick', '0782224273', 'stonerp@gmail.com', '$2y$10$9EnIK8g7qqXTSulLvBd.he2xQTcfbMmRh2vawh2f1yPzmEuowW4uu', 'user', 'inactive'),
(5, 'user4', 'UMUBYEYI', 'Esperance', '0787451859', 'umubyeyi@gmail.com', '$2y$10$KNDa0C2oMICnLUTk0h7DOuF9NO64VbvmlRhq.ThjNU/HJSSasmvjS', 'user', 'active'),
(6, 'user5', 'INGABIRE', 'Gentille', '0783999154', 'ingabire@gmail.com', '$2y$10$FO5yfVg176KIpDThkbEu0./YDwBxzS6ZoGPd/teT3E9oSl.rd9E.a', 'user', 'active');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `driver_report`
--
ALTER TABLE `driver_report`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `passenger_report`
--
ALTER TABLE `passenger_report`
  MODIFY `id` int(55) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
