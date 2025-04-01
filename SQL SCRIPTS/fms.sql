-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2025 at 01:26 AM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `checkTransactionBudget` (IN `p_originating_account` INT, IN `p_budget_category_id` INT, IN `p_amount` DECIMAL(10,2), IN `p_source` VARCHAR(20))   BEGIN
    DECLARE v_budget_limit DECIMAL(10,2);
    DECLARE v_budget_start_time DATETIME;
    DECLARE v_budget_end_time DATETIME;
    DECLARE v_total_spent DECIMAL(10,2);
    DECLARE v_current_time DATETIME;
    DECLARE v_status VARCHAR(50);

    SET v_current_time = NOW();

    -- If source is 'Safe Lock', always return 'Within Budget'
    IF p_source = 'Safe Lock' THEN
        SET v_status = 'Within Budget';
    ELSE
        -- Get budget limit and time frame
        SELECT budget_limit, budget_limit_start_time, budget_limit_end_time 
        INTO v_budget_limit, v_budget_start_time, v_budget_end_time
        FROM budget_categories
        WHERE category_id = p_budget_category_id;

        -- Calculate total spent within budget period
        SELECT IFNULL(SUM(amount), 0) INTO v_total_spent
        FROM transactions
        WHERE account_it = p_originating_account
        AND budget_category_id = p_budget_category_id
        AND created_at BETWEEN v_budget_start_time AND v_budget_end_time;

        -- Determine budget status
        IF (v_total_spent + p_amount) > v_budget_limit THEN
            SET v_status = 'Exceeds Budget';
        ELSE
            SET v_status = 'Within Budget';
        END IF;
    END IF;

    -- Return the status
    SELECT v_status AS transaction_budget_status;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `fundsTransfer` (IN `p_originating_account_id` INT, IN `p_destination_account_id` INT, IN `p_amount` FLOAT, IN `p_budget_category_id` INT, IN `p_source` VARCHAR(20))   BEGIN
    DECLARE v_budget_limit FLOAT;
    DECLARE v_budget_start_time DATETIME;
    DECLARE v_budget_end_time DATETIME;
    DECLARE v_total_spent FLOAT DEFAULT 0;
    DECLARE v_originating_balance FLOAT;
    DECLARE v_destination_balance FLOAT;
    DECLARE v_transaction_budget_status VARCHAR(20);
    DECLARE v_transaction_fee FLOAT DEFAULT 0;
    DECLARE v_lock_start_time DATETIME;
    DECLARE v_lock_end_time DATETIME;
    DECLARE v_current_time DATETIME;
    DECLARE v_category_name VARCHAR(50);
    
    SET v_current_time = NOW();
    
    -- Get the budget limit details and category name
    SELECT budget_limit, budget_limit_start_time, budget_limit_end_time, category_name
    INTO v_budget_limit, v_budget_start_time, v_budget_end_time, v_category_name
    FROM budget_categories
    WHERE category_id = p_budget_category_id;
    
    -- Determine if category is 'Savings'
    IF v_category_name = 'Savings' THEN
        SET v_transaction_budget_status = 'Within Budget';
    ELSE
        -- Calculate total spent within budget time frame
        SELECT COALESCE(SUM(amount), 0)
        INTO v_total_spent
        FROM transactions
        WHERE account_id = p_originating_account_id
          AND budget_category_id = p_budget_category_id
          AND created_at BETWEEN v_budget_start_time AND v_budget_end_time;
        
        -- Determine if transaction exceeds budget
        IF (v_total_spent + p_amount) > v_budget_limit THEN
            SET v_transaction_budget_status = 'Exceeds Budget';
        ELSE
            SET v_transaction_budget_status = 'Within Budget';
        END IF;
    END IF;
    
    -- Check balance from the appropriate source
    IF p_source = 'Main Account' THEN
        SELECT balance INTO v_originating_balance FROM account WHERE account_id = p_originating_account_id;
    ELSEIF p_source = 'Safe Lock' THEN
        SELECT balance, lock_start_time, lock_end_time 
        INTO v_originating_balance, v_lock_start_time, v_lock_end_time
        FROM safe_lock WHERE account_id = p_originating_account_id;
        
        -- Apply transaction fee if outside lock period
        IF v_current_time BETWEEN v_lock_start_time AND v_lock_end_time THEN
            SET v_transaction_fee = p_amount * 0.05;
        END IF;
    END IF;
    
    -- Check if sufficient balance exists
    IF v_originating_balance < (p_amount + v_transaction_fee) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient funds';
    END IF;
    
    -- Deduct amount from originating account
    IF p_source = 'Main Account' THEN
        UPDATE account SET balance = balance - (p_amount + v_transaction_fee)
        WHERE account_id = p_originating_account_id;
    ELSEIF p_source = 'Safe Lock' THEN
        UPDATE safe_lock SET balance = balance - (p_amount + v_transaction_fee)
        WHERE account_id = p_originating_account_id;
    END IF;
    
    -- Credit the destination account
    IF v_category_name = 'Savings' THEN
        UPDATE safe_lock SET balance = balance + p_amount WHERE account_id = p_destination_account_id;
    ELSE
        UPDATE account SET balance = balance + p_amount WHERE account_id = p_destination_account_id;
    END IF;
    
    -- Get updated balances
    IF v_category_name = 'Savings' THEN
        SELECT balance INTO v_originating_balance FROM account WHERE account_id = p_originating_account_id;
        SELECT balance INTO v_destination_balance FROM safe_lock WHERE account_id = p_destination_account_id;
    ELSE
        SELECT balance INTO v_originating_balance FROM account WHERE account_id = p_originating_account_id;
        SELECT balance INTO v_destination_balance FROM account WHERE account_id = p_destination_account_id;
    END IF;
    
    -- Insert transaction record
    INSERT INTO transactions (
        account_id, budget_category_id, transaction_type, amount, transaction_fee,
        transaction_budget_status, transaction_description, transaction_source
    ) VALUES (
        p_originating_account_id, p_budget_category_id, 'Debit', p_amount, v_transaction_fee,
        v_transaction_budget_status, 'Fund Transfer', p_source
    );
    
    INSERT INTO transactions (
        account_id, budget_category_id, transaction_type, amount, transaction_fee,
        transaction_budget_status, transaction_description, transaction_source
    ) VALUES (
        p_destination_account_id, NULL, 'Credit', p_amount, 0,
        'Within Budget', 'Fund Received', 'Main Account'
    );
    
    -- Return updated balances
    SELECT v_originating_balance AS originating_balance, v_destination_balance AS destination_balance;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `newAccount` (IN `new_user_id` INT, OUT `new_account_id` INT)   BEGIN
    DECLARE min_officer_id INT DEFAULT NULL;

    -- Find the account officer with the least number of assigned customers
    SELECT user_id 
    INTO min_officer_id
    FROM (
        SELECT u.user_id AS user_id, COUNT(a.user_id) AS customer_count
        FROM users u
        LEFT JOIN account a ON u.user_id = a.account_officer_id
        WHERE u.role = 'Account Officer'
        GROUP BY u.user_id
        ORDER BY customer_count ASC
        LIMIT 1
    ) AS selected_officer;

    -- If no account officer is found, do nothing
    IF min_officer_id IS NOT NULL THEN
        -- Insert new account record for the customer and assign the selected account officer
        INSERT INTO account (user_id, account_officer_id, account_type, balance, pin)
        VALUES (new_user_id, min_officer_id, 'SAVINGS', 1500.00, 1234);

        -- Retrieve the last inserted account_id
        SET new_account_id = LAST_INSERT_ID();

        -- create a new budget category for this account and call it SAVINGS
        INSERT INTO budget_categories (account_id, category_name, category_description, budget_limit, color_code)
        VALUES (new_account_id, 'Savings', 'Budget category associated with the SafeLock', 0, '#FB23231F'); 
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `storeNotification` (IN `p_user_id` INT, IN `p_title` VARCHAR(255), IN `p_message` TEXT)   BEGIN
    INSERT INTO notifications (user_id, title, message)
    VALUES (p_user_id, p_title, p_message);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `account_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `account_officer_id` int(11) DEFAULT NULL,
  `account_type` enum('Savings','Current','Fixed Deposit') NOT NULL DEFAULT 'Savings',
  `pin` int(6) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`account_id`, `user_id`, `account_officer_id`, `account_type`, `pin`, `balance`, `created_at`, `updated_at`) VALUES
(1000000029, 31, 30, 'Savings', 1234, 1500, '2025-03-31 22:12:43', NULL),
(1000000032, 34, 28, 'Savings', 1234, 1500, '2025-04-01 05:47:17', '2025-04-01 21:11:16'),
(1000000033, 35, 23, 'Savings', 1234, 1500, '2025-04-01 21:48:19', '2025-04-01 23:01:59');

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
DELIMITER $$
CREATE TRIGGER `before_update_account` BEFORE UPDATE ON `account` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `account_view`
-- (See below for the actual view)
--
CREATE TABLE `account_view` (
`account_id` int(11)
,`user_id` int(11)
,`account_officer_id` int(11)
,`account_type` enum('Savings','Current','Fixed Deposit')
,`pin` int(6)
,`balance` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`first_name` varchar(255)
,`last_name` varchar(255)
,`dob` date
,`email` varchar(255)
,`password` varchar(255)
,`gender` enum('Male','Female','Others')
,`identification` enum('Drivers License','SSN')
,`identification_number` varchar(255)
,`phone` varchar(255)
,`role` enum('Admin','Account Officer','Customer')
,`last_seen` timestamp
,`joined_at` timestamp
,`account_officer_first_name` varchar(255)
,`account_officer_last_name` varchar(255)
,`account_officer_phone` varchar(255)
,`account_officer_email` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `budget_categories`
--

CREATE TABLE `budget_categories` (
  `category_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_description` text NOT NULL,
  `budget_limit` float NOT NULL DEFAULT 0,
  `budget_limit_start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `budget_limit_end_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `color_code` varchar(255) NOT NULL,
  `budget_category_status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_categories`
--

INSERT INTO `budget_categories` (`category_id`, `account_id`, `category_name`, `category_description`, `budget_limit`, `budget_limit_start_time`, `budget_limit_end_time`, `color_code`, `budget_category_status`, `created_at`, `edited_at`) VALUES
(3, 1000000029, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-03-31 22:12:43', '2025-03-31 22:12:43', '#FB23231F', 'Active', '2025-03-31 22:12:43', NULL),
(6, 1000000032, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-01 05:47:17', '2025-04-01 05:47:17', '#FB23231F', 'Active', '2025-04-01 05:47:17', NULL),
(11, 1000000032, 'Entertainment/Subscriptions', 'Budget category to manage my entertainment lifestyles', 160, '2025-04-01 12:24:00', '2025-04-25 12:24:00', '#501b1b', 'Active', '2025-04-01 12:25:13', NULL),
(12, 1000000032, 'Transportation/Fuel', 'This is it', 145, '2025-04-01 18:29:00', '2025-04-26 15:29:00', '#b38080', 'Active', '2025-04-01 12:29:24', NULL),
(13, 1000000032, 'Rent', 'sdsdsd', 212, '2025-04-01 12:30:00', '2025-04-16 12:30:00', '#71762e', 'Active', '2025-04-01 12:31:03', NULL),
(14, 1000000032, 'Housing/Mortgage', 'aaddad', 1244, '2025-04-01 12:33:00', '2025-04-30 12:33:00', '#9f7ec9', 'Active', '2025-04-01 12:33:33', NULL),
(15, 1000000032, 'Miscellaneous', 'I want to manage my miscelllaneous spendings', 1212, '2025-04-01 12:36:00', '2025-04-30 12:36:00', '#a55f5f', 'Active', '2025-04-01 12:38:04', NULL),
(16, 1000000032, 'Investments', 'Monitoring my investment strategies', 323, '2025-04-11 13:13:00', '2025-04-25 13:13:00', '#9787ab', 'Active', '2025-04-01 13:13:30', NULL),
(17, 1000000032, 'Drinks', 'I want to save 230 this month on drinks', 230, '2025-04-03 13:14:00', '2025-04-27 13:14:00', '#473e3e', 'Active', '2025-04-01 13:14:46', NULL),
(18, 1000000033, 'Savings', 'Budget category associated with the SafeLock', 0, '2025-04-01 21:48:19', '2025-04-01 21:48:19', '#FB23231F', 'Active', '2025-04-01 21:48:19', NULL),
(19, 1000000033, 'Miscellaneous', 'This will guide me in my miscellaneous spendings', 140, '2025-04-01 00:59:00', '2025-04-23 02:00:00', '#55a595', 'Active', '2025-04-01 21:55:26', NULL),
(20, 1000000033, 'Drinks', 'Budget on drinking', 140, '2025-04-10 21:56:00', '2025-04-17 21:56:00', '#48934a', 'Active', '2025-04-01 21:56:52', NULL);

--
-- Triggers `budget_categories`
--
DELIMITER $$
CREATE TRIGGER `before_update_budget_category` BEFORE UPDATE ON `budget_categories` FOR EACH ROW BEGIN
    SET NEW.edited_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `budget_categories_view`
-- (See below for the actual view)
--
CREATE TABLE `budget_categories_view` (
`category_id` int(11)
,`account_id` int(11)
,`category_name` varchar(255)
,`category_description` text
,`budget_limit` float
,`budget_limit_start_time` timestamp
,`budget_limit_end_time` timestamp
,`color_code` varchar(255)
,`budget_category_status` enum('Active','Inactive')
,`created_at` timestamp
,`edited_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
);

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
(30, 1000000029, '1609902532986417', '2029-03-31', 'DEBIT', '2025-03-31 22:12:43'),
(33, 1000000032, '8826857163765170', '2029-04-01', 'DEBIT', '2025-04-01 05:47:17'),
(34, 1000000033, '7339800880236091', '2029-04-01', 'DEBIT', '2025-04-01 21:48:19');

-- --------------------------------------------------------

--
-- Stand-in structure for view `cards_view`
-- (See below for the actual view)
--
CREATE TABLE `cards_view` (
`card_id` int(11)
,`account_id` int(11)
,`card_number` varchar(255)
,`expiry_date` date
,`card_type` enum('CREDIT','DEBIT')
,`issue_date` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `notification_status` enum('Read','Unread') NOT NULL DEFAULT 'Unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `message`, `notification_status`, `created_at`, `updated_at`) VALUES
(1, 31, 'Login Successful', 'Welcome onboard Uchechukwu Udo', 'Read', '2025-03-31 23:52:44', NULL),
(2, 31, 'Login Successful', 'Welcome onboard Uchechukwu Udo', 'Read', '2025-03-31 23:53:18', NULL),
(3, 31, 'Login Successful', 'Welcome onboard Uchechukwu Udo', 'Read', '2025-04-01 03:53:54', NULL),
(4, 34, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-01 05:47:22', NULL),
(5, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Unread', '2025-04-01 05:48:26', NULL),
(6, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Unread', '2025-04-01 09:05:24', NULL),
(7, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Read', '2025-04-01 11:56:19', NULL),
(8, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:04:48', NULL),
(9, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:25:13', NULL),
(10, 34, 'Budget Category Created', 'Budget Category created successfully', 'Read', '2025-04-01 12:29:24', NULL),
(11, 34, 'Budget Category Created', 'Budget Category created successfully', 'Read', '2025-04-01 12:31:03', NULL),
(12, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:33:33', NULL),
(13, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 12:38:04', NULL),
(14, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 13:13:30', NULL),
(15, 34, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 13:14:46', NULL),
(16, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 14:35:56', NULL),
(17, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 14:36:53', NULL),
(18, 34, 'Support Email Sent', 'A support email has been sent toudoigweuchechukwu@gmail.com', 'Unread', '2025-04-01 16:14:14', NULL),
(19, 34, 'Support Email Sent', 'A support email has been sent toudoigweuchechukwu@gmail.com', 'Read', '2025-04-01 16:18:35', NULL),
(20, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:01:50', NULL),
(21, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:04:01', NULL),
(22, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:04:06', NULL),
(23, 34, 'Account Update', 'Account updated successfully', 'Unread', '2025-04-01 19:05:22', NULL),
(24, 34, 'Password Update', 'Password update was successful', 'Unread', '2025-04-01 21:00:24', NULL),
(25, 34, 'Pin Update', 'Transaction Pin update was successful', 'Unread', '2025-04-01 21:11:16', NULL),
(26, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:22:38', NULL),
(27, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:23:14', NULL),
(28, 34, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:24:22', NULL),
(29, 34, 'Login Successful', 'Welcome onboard Ikem Abia', 'Unread', '2025-04-01 21:32:53', NULL),
(30, 35, 'Account Verification', 'An account verification email has been sent to udoigweuchechukwu@gmail.com', 'Unread', '2025-04-01 21:48:29', NULL),
(31, 35, 'Login Successful', 'Welcome onboard Tolu Ayo', 'Unread', '2025-04-01 21:50:18', NULL),
(32, 35, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 21:55:26', NULL),
(33, 35, 'Budget Category Created', 'Budget Category created successfully', 'Unread', '2025-04-01 21:56:52', NULL),
(34, 35, 'Budget Category Updated', 'Budget Category updated successfully', 'Unread', '2025-04-01 21:57:28', NULL),
(35, 35, 'Support Email Sent', 'A support email has been sent to udoigweuchechukwu@gmail.com', 'Read', '2025-04-01 21:59:58', NULL),
(36, 35, 'Account Update', 'Account updated successfully', 'Read', '2025-04-01 23:00:42', NULL),
(37, 35, 'Password Update', 'Password update was successful', 'Unread', '2025-04-01 23:01:29', NULL),
(38, 35, 'Pin Update', 'Transaction Pin update was successful', 'Unread', '2025-04-01 23:01:59', NULL);

--
-- Triggers `notifications`
--
DELIMITER $$
CREATE TRIGGER `before_update_notifications` BEFORE UPDATE ON `notifications` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `notifications_view`
-- (See below for the actual view)
--
CREATE TABLE `notifications_view` (
`notification_id` int(11)
,`user_id` int(11)
,`title` varchar(255)
,`message` text
,`notification_status` enum('Read','Unread')
,`created_at` timestamp
,`updated_at` timestamp
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
,`role` enum('Admin','Account Officer','Customer')
);

-- --------------------------------------------------------

--
-- Table structure for table `otp`
--

CREATE TABLE `otp` (
  `otp_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `otp` int(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `otp_view`
-- (See below for the actual view)
--
CREATE TABLE `otp_view` (
`otp_id` int(11)
,`account_id` int(11)
,`otp` int(6)
,`created_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
);

-- --------------------------------------------------------

--
-- Table structure for table `safe_lock`
--

CREATE TABLE `safe_lock` (
  `safe_lock_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `balance` float NOT NULL,
  `lock_start_time` timestamp NULL DEFAULT current_timestamp(),
  `lock_end_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `safe_lock`
--

INSERT INTO `safe_lock` (`safe_lock_id`, `account_id`, `balance`, `lock_start_time`, `lock_end_time`, `created_at`, `updated_at`) VALUES
(3, 1000000029, 0, '2025-03-31 22:12:43', '2025-03-31 22:42:43', '2025-03-31 22:12:43', NULL),
(6, 1000000032, 0, '2025-04-01 05:47:17', '2025-04-01 06:17:17', '2025-04-01 05:47:17', NULL),
(7, 1000000033, 0, '2025-04-01 21:48:19', '2025-04-01 22:18:19', '2025-04-01 21:48:19', NULL);

--
-- Triggers `safe_lock`
--
DELIMITER $$
CREATE TRIGGER `before_update_safe_lock` BEFORE UPDATE ON `safe_lock` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `safe_lock_view`
-- (See below for the actual view)
--
CREATE TABLE `safe_lock_view` (
`safe_lock_id` int(11)
,`account_id` int(11)
,`balance` float
,`lock_start_time` timestamp
,`lock_end_time` timestamp
,`created_at` timestamp
,`updated_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
,`account_type` enum('Savings','Current','Fixed Deposit')
,`pin` int(6)
);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` int(11) NOT NULL,
  `account_id` int(11) DEFAULT NULL,
  `budget_category_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Credit','Debit') NOT NULL,
  `amount` float NOT NULL DEFAULT 0,
  `transaction_fee` float NOT NULL DEFAULT 0,
  `transaction_budget_status` enum('Exceeds Budget','Within Budget') NOT NULL DEFAULT 'Within Budget',
  `transaction_description` text NOT NULL,
  `transaction_source` enum('Main Account','Safe Lock') NOT NULL DEFAULT 'Main Account',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `transactions_view`
-- (See below for the actual view)
--
CREATE TABLE `transactions_view` (
`transaction_id` int(11)
,`account_id` int(11)
,`budget_category_id` int(11)
,`transaction_type` enum('Credit','Debit')
,`amount` float
,`transaction_fee` float
,`transaction_budget_status` enum('Exceeds Budget','Within Budget')
,`transaction_description` text
,`transaction_source` enum('Main Account','Safe Lock')
,`created_at` timestamp
,`user_id` int(11)
,`first_name` varchar(255)
,`last_name` varchar(255)
,`email` varchar(255)
,`phone` varchar(255)
,`account_type` enum('Savings','Current','Fixed Deposit')
,`category_name` varchar(255)
);

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
  `phone` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `identification` enum('Drivers License','SSN') NOT NULL,
  `identification_number` varchar(255) NOT NULL,
  `role` enum('Admin','Account Officer','Customer') NOT NULL DEFAULT 'Customer',
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

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `gender`, `dob`, `address`, `phone`, `email`, `identification`, `identification_number`, `role`, `password`, `hash`, `hash_time`, `last_seen`, `account_status`, `created_at`, `updated_at`) VALUES
(22, 'Mike', 'Mayers', 'Male', '1972-03-22', 'Texas ', '09089098789', 'admin@finhive.com', 'Drivers License', '1234554232', 'Admin', 'finhive', 'd707329bece455a462b58ce00d1194c9', '2025-03-30 00:38:08', NULL, 'Active', '2025-03-17 23:22:15', '2025-03-30 00:38:08'),
(23, 'John', 'Woo', 'Male', '1969-03-02', 'Texas', '09089098788', 'john@gmail.com', 'Drivers License', '12321232', 'Account Officer', 'finhive', '647bba344396e7c8170902bcf2e15551', '2025-03-30 00:40:00', NULL, 'Active', '2025-03-19 00:15:14', '2025-03-30 00:40:00'),
(24, 'Wilson', 'Gihon', 'Male', '1981-09-10', 'State House, Winsconsin', '+1-2910-10291', 'gihon@finhive.com', 'Drivers License', '1110192019', 'Account Officer', 'finhive', NULL, '2025-03-30 00:41:44', NULL, 'Active', '2025-03-30 00:41:44', NULL),
(26, 'James', 'Earl', 'Male', '2001-03-30', 'Washington DC', '+1-902-01992', 'james-earl@gmail.com', 'SSN', '238923-298923', 'Account Officer', 'finhive', NULL, '2025-03-31 17:34:20', NULL, 'Active', '2025-03-30 01:34:07', '2025-03-31 17:34:20'),
(27, 'China', 'Aduino', 'Female', '2001-03-30', 'Texas Houston', '090817291', 'china@finhive.com', 'Drivers License', '12112121', 'Account Officer', 'finhive', NULL, '2025-03-31 17:34:25', NULL, 'Active', '2025-03-30 01:35:59', '2025-03-31 17:34:25'),
(28, 'Jazmine', 'Sullivan', 'Female', '2001-03-30', 'Texas Houston', '+1-291-29182', 'jazmine@finhive.com', 'Drivers License', '128981729', 'Account Officer', 'finhive', NULL, '2025-03-31 17:34:30', NULL, 'Active', '2025-03-30 01:38:01', '2025-03-31 17:34:30'),
(30, 'Yaz', 'Sullivan', 'Female', '2001-02-03', 'Winsconsin', '+1-2910-1029', 'yaz@gmail.com', 'Drivers License', '1213233', 'Account Officer', 'finhive', NULL, '2025-03-31 17:34:34', NULL, 'Active', '2025-03-30 22:08:43', '2025-03-31 17:34:34'),
(31, 'Uchechukwu', 'Udo', 'Male', '2001-03-30', 'Winsconsin', '08065198300', 'udoigweuchechukwu1@gmail.com', 'Drivers License', '112232323', 'Customer', 'finhive2025', '8f7d807e1f53eff5f9efbe5cb81090fb', '2025-04-01 05:35:50', '2025-04-01 03:53:54', 'Active', '2025-03-31 22:12:43', '2025-04-01 05:35:50'),
(34, 'Ikem', 'Abia', 'Male', '1998-07-10', 'New York', '0909817281', 'udoigweuchechukwu2@gmail.com', 'Drivers License', '332242', 'Customer', 'finhive2025', '1fc214004c9481e4c8073e85323bfd4b', '2025-04-01 21:38:45', '2025-04-01 21:32:53', 'Active', '2025-04-01 05:47:17', '2025-04-01 21:38:45'),
(35, 'Tolu', 'Ayo', 'Male', '2001-07-05', 'Baltimore, Maryland', '0908789185', 'udoigweuchechukwu@gmail.com', 'Drivers License', '76837364', 'Customer', 'finhive2025', '34ed066df378efacc9b924ec161e7639', '2025-04-01 23:01:29', '2025-04-01 21:50:18', 'Active', '2025-04-01 21:48:19', '2025-04-01 23:01:29');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `age_check_before_customer_insert` BEFORE INSERT ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'Customer' AND TIMESTAMPDIFF(YEAR, NEW.dob, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Customers must be at least 18 years old';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `age_check_before_customer_update` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    IF NEW.role = 'Customer' AND TIMESTAMPDIFF(YEAR, NEW.dob, CURDATE()) < 18 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Customers must be at least 18 years old';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_users` BEFORE UPDATE ON `users` FOR EACH ROW BEGIN
    SET NEW.updated_at = NOW();
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `create_account_after_user_insert` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    DECLARE new_account_id INT;

    -- Only process if the new user is a customer
    IF NEW.role = 'Customer' THEN
        -- Call the stored procedure to create an account and get the account_id
        CALL newAccount(NEW.user_id, new_account_id);

        -- Insert a record into the safe_lock table using the new account_id
        IF new_account_id IS NOT NULL THEN
            INSERT INTO safe_lock (account_id, balance, lock_start_time, lock_end_time)
            VALUES (new_account_id, 0.0, NOW(), NOW() + INTERVAL 30 MINUTE);
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure for view `account_view`
--
DROP TABLE IF EXISTS `account_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `account_view`  AS SELECT `a`.`account_id` AS `account_id`, `a`.`user_id` AS `user_id`, `a`.`account_officer_id` AS `account_officer_id`, `a`.`account_type` AS `account_type`, `a`.`pin` AS `pin`, `a`.`balance` AS `balance`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`dob` AS `dob`, `b`.`email` AS `email`, `b`.`password` AS `password`, `b`.`gender` AS `gender`, `b`.`identification` AS `identification`, `b`.`identification_number` AS `identification_number`, `b`.`phone` AS `phone`, `b`.`role` AS `role`, `b`.`last_seen` AS `last_seen`, `b`.`created_at` AS `joined_at`, `c`.`first_name` AS `account_officer_first_name`, `c`.`last_name` AS `account_officer_last_name`, `c`.`phone` AS `account_officer_phone`, `c`.`email` AS `account_officer_email` FROM ((`account` `a` left join `users` `b` on(`a`.`user_id` = `b`.`user_id`)) left join `users` `c` on(`a`.`account_officer_id` = `c`.`user_id`)) WHERE `b`.`role` = 'Customer' ;

-- --------------------------------------------------------

--
-- Structure for view `budget_categories_view`
--
DROP TABLE IF EXISTS `budget_categories_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `budget_categories_view`  AS SELECT `a`.`category_id` AS `category_id`, `a`.`account_id` AS `account_id`, `a`.`category_name` AS `category_name`, `a`.`category_description` AS `category_description`, `a`.`budget_limit` AS `budget_limit`, `a`.`budget_limit_start_time` AS `budget_limit_start_time`, `a`.`budget_limit_end_time` AS `budget_limit_end_time`, `a`.`color_code` AS `color_code`, `a`.`budget_category_status` AS `budget_category_status`, `a`.`created_at` AS `created_at`, `a`.`edited_at` AS `edited_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone` FROM (`budget_categories` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `cards_view`
--
DROP TABLE IF EXISTS `cards_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cards_view`  AS SELECT `a`.`card_id` AS `card_id`, `a`.`account_id` AS `account_id`, `a`.`card_number` AS `card_number`, `a`.`expiry_date` AS `expiry_date`, `a`.`card_type` AS `card_type`, `a`.`issue_date` AS `issue_date`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone` FROM (`cards` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `notifications_view`
--
DROP TABLE IF EXISTS `notifications_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `notifications_view`  AS SELECT `a`.`notification_id` AS `notification_id`, `a`.`user_id` AS `user_id`, `a`.`title` AS `title`, `a`.`message` AS `message`, `a`.`notification_status` AS `notification_status`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone`, `b`.`role` AS `role` FROM (`notifications` `a` left join `users` `b` on(`a`.`user_id` = `b`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `otp_view`
--
DROP TABLE IF EXISTS `otp_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `otp_view`  AS SELECT `a`.`otp_id` AS `otp_id`, `a`.`account_id` AS `account_id`, `a`.`otp` AS `otp`, `a`.`created_at` AS `created_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone` FROM (`otp` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `safe_lock_view`
--
DROP TABLE IF EXISTS `safe_lock_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `safe_lock_view`  AS SELECT `a`.`safe_lock_id` AS `safe_lock_id`, `a`.`account_id` AS `account_id`, `a`.`balance` AS `balance`, `a`.`lock_start_time` AS `lock_start_time`, `a`.`lock_end_time` AS `lock_end_time`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone`, `b`.`account_type` AS `account_type`, `b`.`pin` AS `pin` FROM (`safe_lock` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `transactions_view`
--
DROP TABLE IF EXISTS `transactions_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `transactions_view`  AS SELECT `a`.`transaction_id` AS `transaction_id`, `a`.`account_id` AS `account_id`, `a`.`budget_category_id` AS `budget_category_id`, `a`.`transaction_type` AS `transaction_type`, `a`.`amount` AS `amount`, `a`.`transaction_fee` AS `transaction_fee`, `a`.`transaction_budget_status` AS `transaction_budget_status`, `a`.`transaction_description` AS `transaction_description`, `a`.`transaction_source` AS `transaction_source`, `a`.`created_at` AS `created_at`, `b`.`user_id` AS `user_id`, `b`.`first_name` AS `first_name`, `b`.`last_name` AS `last_name`, `b`.`email` AS `email`, `b`.`phone` AS `phone`, `b`.`account_type` AS `account_type`, `c`.`category_name` AS `category_name` FROM ((`transactions` `a` left join `account_view` `b` on(`a`.`account_id` = `b`.`account_id`)) left join `budget_categories` `c` on(`a`.`budget_category_id` = `c`.`category_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `account_officer_id` (`account_officer_id`);

--
-- Indexes for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`card_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `otp`
--
ALTER TABLE `otp`
  ADD PRIMARY KEY (`otp_id`),
  ADD KEY `user_id` (`account_id`);

--
-- Indexes for table `safe_lock`
--
ALTER TABLE `safe_lock`
  ADD PRIMARY KEY (`safe_lock_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `budget_category_id` (`budget_category_id`);

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
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000000034;

--
-- AUTO_INCREMENT for table `budget_categories`
--
ALTER TABLE `budget_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `otp`
--
ALTER TABLE `otp`
  MODIFY `otp_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `safe_lock`
--
ALTER TABLE `safe_lock`
  MODIFY `safe_lock_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account`
--
ALTER TABLE `account`
  ADD CONSTRAINT `account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `account_ibfk_2` FOREIGN KEY (`account_officer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD CONSTRAINT `budget_categories_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cards`
--
ALTER TABLE `cards`
  ADD CONSTRAINT `cards_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `otp`
--
ALTER TABLE `otp`
  ADD CONSTRAINT `otp_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `safe_lock`
--
ALTER TABLE `safe_lock`
  ADD CONSTRAINT `safe_lock_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `account` (`account_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`budget_category_id`) REFERENCES `budget_categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
