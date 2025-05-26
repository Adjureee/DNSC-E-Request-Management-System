-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 17, 2025 at 11:01 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dnsc_e-request`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetActiveUnviewedAnnouncement` (IN `p_user_id` INT)   BEGIN
    SELECT `id`, `title`, `body`, `photo`, `start_date`, `end_date`
    FROM announcements
    WHERE NOW() BETWEEN start_date AND end_date
      AND is_active = 1
      AND id NOT IN (
        SELECT announcement_id FROM announcement_views WHERE user_id = p_user_id
      )
    ORDER BY start_date DESC;
  END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_unseen_announcement_for_user` (IN `p_user_id` INT)   BEGIN
    SELECT a.*
    FROM announcements a
    WHERE a.start_date <= CURDATE()
      AND a.end_date >= CURDATE()
      AND NOT EXISTS (
        SELECT 1 FROM announcement_views v
        WHERE v.announcement_id = a.id AND v.user_id = p_user_id
      )
    ORDER BY a.created_at DESC
    LIMIT 1;
  END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateAlumniRequest` (IN `p_user_id` INT, IN `p_request_type` VARCHAR(100), IN `p_institute` VARCHAR(50), IN `p_program` VARCHAR(50), IN `p_details` TEXT)   BEGIN
    INSERT INTO alumni_requests (
        user_id, request_type, institute, program, details, status
    ) VALUES (
        p_user_id, p_request_type, p_institute, p_program, p_details, 'pending'
    );
    
    -- Update the new requests counter
    UPDATE system_settings SET new_requests_count = new_requests_count + 1 WHERE id = 1;
    
    -- Return the created request ID
    SELECT LAST_INSERT_ID() AS request_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_CreateStudentRequest` (IN `p_user_id` INT, IN `p_request_type` VARCHAR(100), IN `p_institute` VARCHAR(50), IN `p_program` VARCHAR(50), IN `p_year_level` VARCHAR(50), IN `p_semester` VARCHAR(50), IN `p_details` TEXT)   BEGIN
    INSERT INTO requests (
        user_id, request_type, institute, program, 
        year_level, semester, details, status
    ) VALUES (
        p_user_id, p_request_type, p_institute, p_program,
        p_year_level, p_semester, p_details, 'pending'
    );
    
    -- Update the new requests counter
    UPDATE system_settings SET new_requests_count = new_requests_count + 1;
    
    -- Return the created request ID
    SELECT LAST_INSERT_ID() AS request_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetActiveAnnouncementForUser` (IN `p_user_id` INT)   BEGIN
      SELECT a.*
      FROM announcements a
      WHERE a.is_active = 1
        AND NOW() BETWEEN a.start_date AND a.end_date
        AND NOT EXISTS (
            SELECT 1
            FROM announcement_views av
            WHERE av.announcement_id = a.id AND av.user_id = p_user_id
        )
      ORDER BY a.start_date DESC
      LIMIT 1;
  END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetActiveAnnouncements` ()   BEGIN
    SELECT 
      id,
      title,
      photo,
      description,
      start_date,
      end_date
    FROM announcements
    WHERE CURDATE() BETWEEN start_date AND end_date
    ORDER BY start_date DESC;
  END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetAdminDashboardStats` ()   BEGIN
    SELECT 
        -- Total requests from both tables
        (SELECT COUNT(*) FROM requests) + 
        (SELECT COUNT(*) FROM alumni_requests) AS total_requests,
        
        -- Pending requests
        (SELECT COUNT(*) FROM requests WHERE status = 'pending') + 
        (SELECT COUNT(*) FROM alumni_requests WHERE status = 'pending') AS pending_requests,
        
        -- Approved requests
        (SELECT COUNT(*) FROM requests WHERE status = 'approved') + 
        (SELECT COUNT(*) FROM alumni_requests WHERE status = 'approved') AS approved_requests,
        
        -- Completed requests
        (SELECT COUNT(*) FROM requests WHERE status = 'completed') + 
        (SELECT COUNT(*) FROM alumni_requests WHERE status = 'completed') AS completed_requests,
        
        -- New requests (not seen)
        (SELECT COUNT(*) FROM requests WHERE status = 'pending' AND is_seen = 0) + 
        (SELECT COUNT(*) FROM alumni_requests WHERE status = 'pending' AND is_seen = 0) AS new_requests,
        
        -- Pending registrations
        (SELECT COUNT(*) FROM users WHERE verification_status = 'pending') AS pending_registrations;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetRequestDetails` (IN `p_request_id` INT, IN `p_table_source` VARCHAR(10))   BEGIN
    IF p_table_source = 'student' THEN
        SELECT r.*, u.full_name, u.email, u.stud_id
        FROM requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = p_request_id;
        
        -- Mark as seen if it's pending
        UPDATE requests SET is_seen = 1 WHERE id = p_request_id AND status = 'pending';
    ELSE
        SELECT r.*, u.full_name, u.email, u.stud_id
        FROM alumni_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = p_request_id;
        
        -- Mark as seen if it's pending
        UPDATE alumni_requests SET is_seen = 1 WHERE id = p_request_id AND status = 'pending';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetUserRecentRequests` (IN `p_user_id` INT, IN `p_limit` INT)   BEGIN
    -- Create temporary tables for both types of requests
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_student_requests AS
    SELECT id, user_id, request_type, status, tracking_number, created_at, 
           'student' AS source
    FROM requests
    WHERE user_id = p_user_id
    ORDER BY created_at DESC
    LIMIT p_limit;
    
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_alumni_requests AS
    SELECT id, user_id, request_type, status, tracking_number, created_at,
           'alumni' AS source
    FROM alumni_requests
    WHERE user_id = p_user_id
    ORDER BY created_at DESC
    LIMIT p_limit;
    
    -- Union the results and sort by date
    SELECT * FROM temp_student_requests
    UNION ALL
    SELECT * FROM temp_alumni_requests
    ORDER BY created_at DESC
    LIMIT p_limit;
    
    -- Clean up temporary tables
    DROP TEMPORARY TABLE IF EXISTS temp_student_requests;
    DROP TEMPORARY TABLE IF EXISTS temp_alumni_requests;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetUserRequestsStats` (IN `p_user_id` INT)   BEGIN
    -- Get counts from student requests
    SELECT 
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id) AS total_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'pending') AS pending_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'approved') AS approved_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'completed') AS completed_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'rejected') AS rejected_requests,
        (SELECT COUNT(*) FROM alumni_requests WHERE user_id = p_user_id) AS total_alumni_requests,
        (SELECT COUNT(*) FROM notifications WHERE user_id = p_user_id AND is_read = 0) AS unread_notifications;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_MarkAllNotificationsAsRead` (IN `p_user_id` INT)   BEGIN
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = p_user_id;
    
    SELECT ROW_COUNT() AS updated_count;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ProcessRegistration` (IN `p_user_id` INT, IN `p_approve` BOOLEAN, IN `p_role` VARCHAR(10), IN `p_rejection_reason` TEXT)   BEGIN
    DECLARE v_user_email VARCHAR(100);
    DECLARE v_user_name VARCHAR(100);
    
    -- Get user email for notification
    SELECT email, full_name INTO v_user_email, v_user_name
    FROM users
    WHERE id = p_user_id;
    
    IF p_approve = TRUE THEN
        -- Approve the user
        IF p_role = 'student' THEN
            UPDATE users 
            SET verification_status = 'approved_student',
                role = 'student',
                approved_at = NOW()
            WHERE id = p_user_id;
        ELSE
            UPDATE users 
            SET verification_status = 'approved_alumni',
                role = 'alumni',
                approved_at = NOW()
            WHERE id = p_user_id;
        END IF;
        
        -- Return success status
        SELECT 'approved' AS status, v_user_email AS email, v_user_name AS name, p_role AS role;
    ELSE
        -- Reject the user
        UPDATE users 
        SET verification_status = 'rejected',
            rejection_reason = IFNULL(p_rejection_reason, 'Rejected by administrator'),
            rejected_at = NOW()
        WHERE id = p_user_id;
        
        -- Return rejection status
        SELECT 'rejected' AS status, v_user_email AS email, v_user_name AS name;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_UpdateRequestStatus` (IN `p_request_id` INT, IN `p_table_source` VARCHAR(10), IN `p_status` VARCHAR(20), IN `p_tracking_number` VARCHAR(20), IN `p_pickup_datetime` DATETIME)   BEGIN
    DECLARE v_user_id INT;
    DECLARE v_notification_message TEXT;
    DECLARE v_request_type VARCHAR(100);
    DECLARE v_full_name VARCHAR(100);
    
    -- Get user_id based on source table
    IF p_table_source = 'student' THEN
        SELECT r.user_id, r.request_type, u.full_name
        INTO v_user_id, v_request_type, v_full_name
        FROM requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = p_request_id;
        
        -- Update the request status
        IF p_tracking_number IS NOT NULL AND p_pickup_datetime IS NOT NULL THEN
            UPDATE requests 
            SET status = p_status, 
                tracking_number = p_tracking_number, 
                pickup_datetime = p_pickup_datetime 
            WHERE id = p_request_id;
        ELSE
            UPDATE requests 
            SET status = p_status
            WHERE id = p_request_id;
        END IF;
    ELSE
        SELECT r.user_id, r.request_type, u.full_name
        INTO v_user_id, v_request_type, v_full_name
        FROM alumni_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = p_request_id;
        
        -- Update the request status
        IF p_tracking_number IS NOT NULL AND p_pickup_datetime IS NOT NULL THEN
            UPDATE alumni_requests 
            SET status = p_status, 
                tracking_number = p_tracking_number, 
                pickup_datetime = p_pickup_datetime 
            WHERE id = p_request_id;
        ELSE
            UPDATE alumni_requests 
            SET status = p_status
            WHERE id = p_request_id;
        END IF;
    END IF;
    
    -- Create notification message based on status
    IF p_status = 'approved' THEN
        SET v_notification_message = CONCAT('Hello ', v_full_name, ', your request for ', v_request_type, ' has been approved. Please pick up your document on ', 
            DATE_FORMAT(p_pickup_datetime, '%M %d, %Y at %h:%i %p'), '. Your tracking number is: ', p_tracking_number);
    ELSEIF p_status = 'rejected' THEN
        SET v_notification_message = CONCAT('Hello ', v_full_name, ', your request for ', v_request_type, ' has been rejected. Please contact the office for more information.');
    ELSEIF p_status = 'completed' THEN
        SET v_notification_message = CONCAT('Hello ', v_full_name, ', your request for ', v_request_type, ' has been completed. Thank you for using our service.');
    END IF;
    
    -- Insert notification
    IF v_notification_message IS NOT NULL THEN
        INSERT INTO notifications (user_id, message) 
        VALUES (v_user_id, v_notification_message);
    END IF;
    
    -- Return the updated request
    IF p_table_source = 'student' THEN
        SELECT r.*, u.full_name, u.email, u.stud_id
        FROM requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = p_request_id;
    ELSE
        SELECT r.*, u.full_name, u.email, u.stud_id
        FROM alumni_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = p_request_id;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int NOT NULL,
  `message` text NOT NULL,
  `user_id` int DEFAULT NULL,
  `request_id` int DEFAULT NULL,
  `request_type` varchar(20) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alumni_requests`
--

CREATE TABLE `alumni_requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `institute` varchar(50) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `details` text,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `tracking_number` varchar(20) DEFAULT NULL,
  `pickup_datetime` datetime DEFAULT NULL,
  `is_seen` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `alumni_requests`
--
DELIMITER $$
CREATE TRIGGER `tr_alumni_request_insert` AFTER INSERT ON `alumni_requests` FOR EACH ROW BEGIN
    -- Update system settings counter
    UPDATE system_settings SET new_requests_count = new_requests_count + 1 WHERE id = 1;
    
    -- Create notification for admin
    INSERT INTO admin_notifications (message, request_id, request_type, created_at)
    VALUES (CONCAT('New alumni request from ID: ', NEW.user_id), NEW.id, 'alumni_request', NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_alumni_request_status_update` AFTER UPDATE ON `alumni_requests` FOR EACH ROW BEGIN
    -- Variable declarations must come first in MySQL triggers
    DECLARE v_user_name VARCHAR(100);
    
    -- Check if status changed
    IF NEW.status != OLD.status THEN
        -- Get user's name
        SELECT full_name INTO v_user_name FROM users WHERE id = NEW.user_id;
        
        -- Create notification for user
        INSERT INTO notifications (user_id, message, is_read, created_at)
        VALUES (
            NEW.user_id, 
            CONCAT('Hello ', v_user_name, ', your request for ', NEW.request_type, ' has been updated to: ', UPPER(NEW.status)),
            0,
            NOW()
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `body` text COLLATE utf8mb4_general_ci NOT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_views`
--

CREATE TABLE `announcement_views` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `announcement_id` int NOT NULL,
  `viewed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `institute` varchar(50) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `details` text,
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `tracking_number` varchar(20) DEFAULT NULL,
  `pickup_datetime` datetime DEFAULT NULL,
  `is_seen` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `requests`
--
DELIMITER $$
CREATE TRIGGER `tr_student_request_insert` AFTER INSERT ON `requests` FOR EACH ROW BEGIN
    -- Update system settings counter
    UPDATE system_settings SET new_requests_count = new_requests_count + 1 WHERE id = 1;
    
    -- Create notification for admin
    INSERT INTO admin_notifications (message, request_id, request_type, created_at)
    VALUES (CONCAT('New student request from ID: ', NEW.user_id), NEW.id, 'student_request', NOW());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_student_request_status_update` AFTER UPDATE ON `requests` FOR EACH ROW 
BEGIN
    -- Variable declarations must come first in MySQL triggers
    DECLARE v_user_name VARCHAR(100);
    
    -- Check if status changed
    IF NEW.status != OLD.status THEN
        -- Get user's name
        SELECT full_name INTO v_user_name FROM users WHERE id = NEW.user_id;
        
        -- Create notification for user
        INSERT INTO notifications (user_id, message, is_read, created_at)
        VALUES (
            NEW.user_id, 
            CONCAT('Hello ', v_user_name, ', your request for ', NEW.request_type, ' has been updated to: ', UPPER(NEW.status)),
            0,
            NOW()
        );
    END IF;
END$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `new_requests_count` int DEFAULT '0',
  `new_registrations_count` int DEFAULT '0',
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `new_requests_count`, `new_registrations_count`, `last_updated`) VALUES
(1, 'system_name', 'DNSC E-Request Management System', 1, 0, '2025-05-16 13:50:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `stud_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `institute` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','alumni','admin') DEFAULT NULL,
  `pre_select_role` enum('student','alumni') DEFAULT NULL,
  `uploadphoto` varchar(255) DEFAULT NULL,
  `verification_status` enum('pending','approved_student','approved_alumni','rejected') DEFAULT 'pending',
  `rejection_reason` text,
  `approved_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `stud_id`, `full_name`, `institute`, `program`, `email`, `password`, `role`, `pre_select_role`, `uploadphoto`, `verification_status`, `rejection_reason`, `approved_at`, `rejected_at`, `created_at`) VALUES
(1, 'admin', 'System Administrator', NULL, NULL, 'admin@dnsc.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, 'approved_student', NULL, NULL, NULL, '2025-05-11 06:00:19'),
(4, '2023-00166', 'John Lyold C. Lozada', 'IC', 'BSIT', 'lozada.johnlyold@dnsc.edu.ph', '$2y$10$zELsc9zV.Y0GQzvbWV7ZB.g4o0ENNJauCCLNPelHPT7EhHXPbn.yO', 'alumni', 'student', '682042178e835_ako.jpg', 'approved_alumni', NULL, '2025-05-11 14:23:25', NULL, '2025-05-11 06:22:15'),
(5, '2023-00069', 'Don Dominick Enargan', 'IC', 'BSIT', 'enargan.dondominick@dnsc.edu.ph', '$2y$10$qrkRrB/jvZOriAYKTVxpJO3sQ3BSFl//a2AFFWUWRg8oudUrS2aw6', 'student', 'student', '682042e9da46d_ako.jpg', 'approved_student', NULL, '2025-05-11 14:26:10', NULL, '2025-05-11 06:25:45');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `tr_user_registration` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    -- Check if not an admin account
    IF NEW.role IS NULL OR NEW.role != 'admin' THEN
        -- Update system settings counter for new registrations
        UPDATE system_settings SET new_registrations_count = new_registrations_count + 1 WHERE id = 1;
        
        -- Create admin notification
        INSERT INTO admin_notifications (message, user_id, request_type, created_at)
        VALUES (CONCAT('New user registration: ', NEW.full_name), NEW.id, 'registration', NOW());
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_admin_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `view_admin_dashboard` (
`approved_requests` bigint
,`completed_requests` bigint
,`new_requests` bigint
,`pending_requests` bigint
,`total_requests` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_alumni_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `view_alumni_dashboard` (
`approved_requests` decimal(23,0)
,`completed_requests` decimal(23,0)
,`pending_requests` decimal(23,0)
,`total_requests` bigint
,`unread_notifications` bigint
,`user_id` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_alumni_recent_requests`
-- (See below for the actual view)
--
CREATE TABLE `view_alumni_recent_requests` (
`created_at` timestamp
,`id` int
,`request_type` varchar(100)
,`status` enum('pending','approved','rejected','completed')
,`tracking_number` varchar(20)
,`user_id` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_student_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `view_student_dashboard` (
`approved_requests` decimal(23,0)
,`completed_requests` decimal(23,0)
,`pending_requests` decimal(23,0)
,`total_requests` bigint
,`unread_notifications` bigint
,`user_id` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_student_recent_requests`
-- (See below for the actual view)
--
CREATE TABLE `view_student_recent_requests` (
`created_at` timestamp
,`id` int
,`request_type` varchar(100)
,`status` enum('pending','approved','rejected','completed')
,`tracking_number` varchar(20)
,`user_id` int
);

-- --------------------------------------------------------

--
-- Structure for view `view_admin_dashboard`
--
DROP TABLE IF EXISTS `view_admin_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_admin_dashboard`  AS SELECT ((select count(0) from `requests`) + (select count(0) from `alumni_requests`)) AS `total_requests`, ((select count(0) from `requests` where (`requests`.`status` = 'pending')) + (select count(0) from `alumni_requests` where (`alumni_requests`.`status` = 'pending'))) AS `pending_requests`, ((select count(0) from `requests` where (`requests`.`status` = 'approved')) + (select count(0) from `alumni_requests` where (`alumni_requests`.`status` = 'approved'))) AS `approved_requests`, ((select count(0) from `requests` where (`requests`.`status` = 'completed')) + (select count(0) from `alumni_requests` where (`alumni_requests`.`status` = 'completed'))) AS `completed_requests`, ((select count(0) from `requests` where ((`requests`.`status` = 'pending') and (`requests`.`is_seen` = 0))) + (select count(0) from `alumni_requests` where ((`alumni_requests`.`status` = 'pending') and (`alumni_requests`.`is_seen` = 0)))) AS `new_requests` ;

-- --------------------------------------------------------

--
-- Structure for view `view_alumni_dashboard`
--
DROP TABLE IF EXISTS `view_alumni_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_alumni_dashboard`  AS SELECT `u`.`id` AS `user_id`, count(`r`.`id`) AS `total_requests`, sum((case when (`r`.`status` = 'pending') then 1 else 0 end)) AS `pending_requests`, sum((case when (`r`.`status` = 'approved') then 1 else 0 end)) AS `approved_requests`, sum((case when (`r`.`status` = 'completed') then 1 else 0 end)) AS `completed_requests`, (select count(0) from `notifications` `n` where ((`n`.`user_id` = `u`.`id`) and (`n`.`is_read` = 0))) AS `unread_notifications` FROM (`users` `u` left join `alumni_requests` `r` on((`u`.`id` = `r`.`user_id`))) WHERE (`u`.`role` = 'alumni') GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `view_alumni_recent_requests`
--
DROP TABLE IF EXISTS `view_alumni_recent_requests`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_alumni_recent_requests`  AS SELECT `r`.`id` AS `id`, `r`.`user_id` AS `user_id`, `r`.`request_type` AS `request_type`, `r`.`status` AS `status`, `r`.`tracking_number` AS `tracking_number`, `r`.`created_at` AS `created_at` FROM `alumni_requests` AS `r` ORDER BY `r`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure for view `view_student_dashboard`
--
DROP TABLE IF EXISTS `view_student_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_student_dashboard`  AS SELECT `u`.`id` AS `user_id`, count(`r`.`id`) AS `total_requests`, sum((case when (`r`.`status` = 'pending') then 1 else 0 end)) AS `pending_requests`, sum((case when (`r`.`status` = 'approved') then 1 else 0 end)) AS `approved_requests`, sum((case when (`r`.`status` = 'completed') then 1 else 0 end)) AS `completed_requests`, (select count(0) from `notifications` `n` where ((`n`.`user_id` = `u`.`id`) and (`n`.`is_read` = 0))) AS `unread_notifications` FROM (`users` `u` left join `requests` `r` on((`u`.`id` = `r`.`user_id`))) WHERE (`u`.`role` = 'student') GROUP BY `u`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `view_student_recent_requests`
--
DROP TABLE IF EXISTS `view_student_recent_requests`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_student_recent_requests`  AS SELECT `r`.`id` AS `id`, `r`.`user_id` AS `user_id`, `r`.`request_type` AS `request_type`, `r`.`status` AS `status`, `r`.`tracking_number` AS `tracking_number`, `r`.`created_at` AS `created_at` FROM `requests` AS `r` ORDER BY `r`.`created_at` DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin_notif_is_read` (`is_read`),
  ADD KEY `idx_admin_notif_created` (`created_at`);

--
-- Indexes for table `alumni_requests`
--
ALTER TABLE `alumni_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_announcement_unique` (`user_id`,`announcement_id`),
  ADD KEY `announcement_id` (`announcement_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_id_is_read` (`user_id`,`is_read`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_requests_tracking_number` (`tracking_number`),
  ADD KEY `idx_requests_status` (`status`),
  ADD KEY `idx_requests_request_type` (`request_type`),
  ADD KEY `idx_requests_created_at` (`created_at`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_users_full_name` (`full_name`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `alumni_requests`
--
ALTER TABLE `alumni_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcement_views`
--
ALTER TABLE `announcement_views`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcement_views`
--
ALTER TABLE `announcement_views`
  ADD CONSTRAINT `announcement_views_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_views_ibfk_2` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
