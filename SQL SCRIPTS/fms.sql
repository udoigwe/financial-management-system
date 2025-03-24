-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 05:50 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fms`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `account_type` enum('Savings','Current','Fixed Deposit') NOT NULL DEFAULT 'Savings',
  `pin` int(6) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `user_id`, `account_type`, `pin`, `balance`, `created_at`) VALUES
(1000000001, NULL, 'Savings', 1234, 0, '2025-03-17 19:11:57'),
(1000000002, NULL, 'Savings', 1234, 0, '2025-03-17 19:14:20'),
(1000000003, NULL, 'Savings', 1234, 0, '2025-03-17 19:44:11'),
(1000000004, NULL, 'Savings', 1234, 0, '2025-03-17 19:45:23'),
(1000000005, NULL, 'Savings', 1234, 0, '2025-03-17 19:50:11'),
(1000000006, NULL, 'Savings', 1234, 0, '2025-03-17 19:51:17'),
(1000000007, NULL, 'Savings', 1234, 0, '2025-03-17 19:52:54'),
(1000000008, NULL, 'Savings', 1234, 0, '2025-03-17 20:08:51'),
(1000000009, NULL, 'Savings', 1234, 0, '2025-03-17 20:15:05'),
(1000000010, NULL, 'Savings', 1234, 0, '2025-03-17 20:16:08'),
(1000000011, NULL, 'Savings', 1234, 0, '2025-03-17 20:17:47'),
(1000000012, NULL, 'Savings', 1234, 0, '2025-03-17 20:18:27'),
(1000000013, NULL, 'Savings', 1234, 0, '2025-03-17 20:21:22'),
(1000000014, NULL, 'Savings', 1234, 0, '2025-03-17 20:23:34'),
(1000000015, NULL, 'Savings', 1234, 0, '2025-03-17 20:26:16'),
(1000000016, NULL, 'Savings', 1234, 0, '2025-03-17 20:27:39'),
(1000000017, NULL, 'Savings', 1234, 0, '2025-03-17 20:29:20'),
(1000000018, NULL, 'Savings', 1234, 0, '2025-03-17 20:31:16'),
(1000000019, NULL, 'Savings', 1234, 0, '2025-03-17 20:32:38'),
(1000000020, NULL, 'Savings', 1234, 0, '2025-03-17 20:40:49'),
(1000000021, 22, 'Savings', 1234, 0, '2025-03-17 23:22:15'),
(1000000022, 23, 'Savings', 1234, 0, '2025-03-19 00:15:14');

--
-- Triggers `account`
--
DELIMITER $$
CREATE TRIGGER `after_account_insert` AFTER INSERT ON `account` FOR EACH ROW BEGIN
    DECLARE new_card_number VARCHAR(16);
    DECLARE expiry DATE;

    -- Generate a random 16-digit card number
    SET new_card_number = CONCAT(
        FLOOR(RAND() * 9000) + 1000, -- 4 digits
        FLOOR(RAND() * 9000) + 1000, -- 4 digits
        FLOOR(RAND() * 9000) + 1000, -- 4 digits
        FLOOR(RAND() * 9000) + 1000  -- 4 digits
    );

    -- Set the expiry date (4 years from now)
    SET expiry = DATE_ADD(CURDATE(), INTERVAL 4 YEAR);

    -- Insert into the cards table
    INSERT INTO cards (account_id, card_number, expiry_date, card_type)
    VALUES (NEW.account_id, new_card_number, expiry, 'DEBIT');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branch_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `zip_code` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `branch_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_categories`
--

CREATE TABLE `budget_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `card_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `card_number` varchar(255) NOT NULL,
  `expiry_date` date NOT NULL,
  `card_type` enum('CREDIT','DEBIT') NOT NULL,
  `issue_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cards`
--

INSERT INTO `cards` (`card_id`, `account_id`, `card_number`, `expiry_date`, `card_type`, `issue_date`) VALUES
(2, 1000000001, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:11:57'),
(3, 1000000002, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:14:20'),
(4, 1000000003, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:44:11'),
(5, 1000000004, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:45:23'),
(6, 1000000005, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:50:11'),
(7, 1000000006, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:51:17'),
(8, 1000000007, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 19:52:54'),
(9, 1000000008, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:08:51'),
(10, 1000000009, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:15:05'),
(11, 1000000010, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:16:08'),
(12, 1000000011, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:17:47'),
(13, 1000000012, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:18:27'),
(14, 1000000013, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:21:22'),
(15, 1000000014, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:23:34'),
(16, 1000000015, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:26:16'),
(17, 1000000016, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:27:39'),
(18, 1000000017, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:29:20'),
(19, 1000000018, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:31:16'),
(20, 1000000019, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:32:38'),
(21, 1000000020, '2147483647', '2029-03-17', 'DEBIT', '2025-03-17 20:40:49'),
(22, 1000000021, '2147483647', '2029-03-18', 'DEBIT', '2025-03-17 23:22:15'),
(23, 1000000022, '2147483647', '2029-03-19', 'DEBIT', '2025-03-19 00:15:14');

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `otp_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `otp` int(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `spending_limit`
--

CREATE TABLE `spending_limit` (
  `limit_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `limit_amount` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `gender` enum('Male','Female','Others') NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `zip_code` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `identification` enum('Drivers License','SSN') NOT NULL,
  `identification_number` varchar(255) NOT NULL,
  `role` enum('Admin','Account Office','Customer') NOT NULL DEFAULT 'Customer',
  `password` varchar(255) NOT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `hash_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_seen` timestamp NULL DEFAULT NULL,
  `account_status` enum('Active','Inactive') NOT NULL DEFAULT 'Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `gender`, `dob`, `address`, `zip_code`, `phone`, `email`, `identification`, `identification_number`, `role`, `password`, `hash`, `hash_time`, `last_seen`, `account_status`, `created_at`, `updated_at`) VALUES
(22, 'Uchechukwu Udo', '', 'Male', '2025-03-22', 'Texas ', '', '09089098789', 'udoigweuchechukwu1@gmail.com', 'Drivers License', '', 'Customer', 'finovate', 'd707329bece455a462b58ce00d1194c9', '2025-03-19 00:12:40', NULL, 'Active', '2025-03-17 23:22:15', NULL),
(23, 'Uchechukwu Udo', '', 'Male', '2025-03-02', 'Texas', '', '09089098788', 'udoigweuchechukwu@gmail.com', 'Drivers License', '', 'Customer', 'finhive', '647bba344396e7c8170902bcf2e15551', '2025-03-19 00:18:33', NULL, 'Active', '2025-03-19 00:15:14', NULL);

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    DECLARE user_role_value ENUM('Customer', 'Admin', 'Account Officer');

    -- Get the user's role from the users table
    SELECT role INTO user_role_value FROM users WHERE user_id = NEW.user_id;

    -- Only create an account if the user role is 'customer'
    IF user_role_value = 'Customer' THEN
        INSERT INTO account (user_id, account_type, balance, pin)
        VALUES (NEW.user_id, 'SAVINGS', 0.00, 1234);
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`card_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `otp`
--
ALTER TABLE `otp`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `spending_limit`
--
ALTER TABLE `spending_limit`
  ADD PRIMARY KEY (`limit_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000000023;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_categories`
--
ALTER TABLE `budget_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `spending_limit`
--
ALTER TABLE `spending_limit`
  MODIFY `limit_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cards`
--
ALTER TABLE `cards`
  ADD CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `otp`
--
ALTER TABLE `otp`
  ADD CONSTRAINT `otp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `spending_limit`
--
ALTER TABLE `spending_limit`
  ADD CONSTRAINT `spending_limit_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `spending_limit_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `budget_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
