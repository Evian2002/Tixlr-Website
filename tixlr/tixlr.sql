-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 09:38 AM
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
-- Database: `tixlr`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `booking_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `total_cost`, `booking_date`) VALUES
(13, 6, 20.00, '2026-01-11 08:57:57'),
(14, 6, 10.00, '2026-01-11 09:09:46'),
(15, 6, 16.00, '2026-01-11 10:05:25'),
(16, 6, 14.40, '2026-01-11 10:29:46');

-- --------------------------------------------------------

--
-- Table structure for table `booking_items`
--

CREATE TABLE `booking_items` (
  `item_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `ticket_type` enum('regular','vip') NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_each` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking_items`
--

INSERT INTO `booking_items` (`item_id`, `booking_id`, `event_id`, `ticket_type`, `quantity`, `price_each`) VALUES
(13, 13, 2, 'regular', 2, 10.00),
(14, 14, 2, 'regular', 1, 10.00),
(15, 15, 1, 'regular', 1, 16.00),
(16, 16, 1, 'regular', 1, 16.00);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `status` enum('active','cancelled') DEFAULT 'active',
  `venue_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `regular_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vip_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `regular_tickets` int(11) NOT NULL DEFAULT 0,
  `vip_tickets` int(11) NOT NULL DEFAULT 0,
  `has_regular` tinyint(1) NOT NULL DEFAULT 1,
  `has_vip` tinyint(1) NOT NULL DEFAULT 1,
  `good_to_know` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `title`, `description`, `date`, `status`, `venue_id`, `image`, `regular_price`, `vip_price`, `regular_tickets`, `vip_tickets`, `has_regular`, `has_vip`, `good_to_know`) VALUES
(1, 'Winter Concert', 'This is a concert to celebrate the winter in the best way possible. It will include a rock band that has been popular across the country to liven up the city and ring in Christmas and the New Year!', '2025-12-20', 'active', 1, 'uploads/rock-2658355_1920.jpg', 16.00, 40.00, 500, 32, 1, 1, '- Pack your Jacket! The event will be held outside and will be very cold.\r\n- Any ages are welcome to attend.\r\n- Event will be 3 hours long.'),
(2, 'Valentine\'s Day Gala', 'Bring your valentine and celebrate!', '2026-02-14', 'active', 2, 'uploads/valentines-day-3699995_1280.jpg', 10.00, 0.00, 1100, 0, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `expires_at` datetime NOT NULL,
  `usage_limit` int(11) NOT NULL DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`id`, `code`, `discount_type`, `discount_value`, `expires_at`, `usage_limit`, `used_count`) VALUES
(1, 'NEW10', 'percent', 10.00, '2026-01-15 10:28:00', 500, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `purchase_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('client','admin') DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`) VALUES
(1, 'emma@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'client'),
(2, 'liam@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'client'),
(3, 'olivia@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'client'),
(4, 'admin@example.com', '$2y$10$C7u8rKamDVwaBe7pA7DaLO.Oigx94wWdRxjFrnC1IOZWiGB.F3DWW', 'admin'),
(5, 'bob@example.com', '$2y$10$UnaXqOU0rJp7UlggJ73X/e6Yv0H5acb8MUsQth.QBIg7eF4Cf4Rn6', 'client'),
(6, 'evan@example.com', '$2y$10$AHgoT1Togfg66n3x.1M4AeadZN7ykgkWYfwCOcnMR6ThkH.qURfUi', 'client'),
(7, 'gabija965@gmail.com', '$2y$10$oE47veOhXr7X3ysr2xGgOeNRSj2a1phiF0YiIscmOGGw2bn9qw9qW', 'client');

-- --------------------------------------------------------

--
-- Table structure for table `venues`
--

CREATE TABLE `venues` (
  `venue_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `venues`
--

INSERT INTO `venues` (`venue_id`, `name`, `city`, `address`) VALUES
(1, 'Grand Hall', 'Vilnius', NULL),
(2, 'Open Air Arena', 'Kaunas', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `venue_id` (`venue_id`),
  ADD KEY `idx_events_date` (`date`),
  ADD KEY `idx_events_regular_price` (`regular_price`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venues`
--
ALTER TABLE `venues`
  ADD PRIMARY KEY (`venue_id`),
  ADD KEY `idx_venues_city` (`city`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `booking_items`
--
ALTER TABLE `booking_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `venues`
--
ALTER TABLE `venues`
  MODIFY `venue_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD CONSTRAINT `booking_items_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `booking_items_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
