DELIMITER //

-- Rquest Details for Student and Alumni
CREATE PROCEDURE IF NOT EXISTS sp_GetRequestDetails(IN p_request_id INT, IN p_table_source VARCHAR(10))
BEGIN
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
END //

-- Update Request Status
CREATE PROCEDURE IF NOT EXISTS sp_UpdateRequestStatus(
    IN p_request_id INT, 
    IN p_table_source VARCHAR(10),
    IN p_status VARCHAR(20),
    IN p_tracking_number VARCHAR(20),
    IN p_pickup_datetime DATETIME
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_notification_message TEXT;
    DECLARE v_request_type VARCHAR(100);
    
    -- Get user_id based on source table
    IF p_table_source = 'student' THEN
        SELECT user_id, request_type INTO v_user_id, v_request_type FROM requests WHERE id = p_request_id;
        
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
        SELECT user_id, request_type INTO v_user_id, v_request_type FROM alumni_requests WHERE id = p_request_id;
        
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
        SET v_notification_message = CONCAT('Your request for ', v_request_type, ' (ID: ', p_request_id, ') has been approved. Please pick up your document on ', 
            DATE_FORMAT(p_pickup_datetime, '%M %d, %Y at %h:%i %p'), '. Your tracking number is: ', p_tracking_number);
    ELSEIF p_status = 'rejected' THEN
        SET v_notification_message = CONCAT('Your request for ', v_request_type, ' (ID: ', p_request_id, ') has been rejected. Please contact the office for more information.');
    ELSEIF p_status = 'completed' THEN
        SET v_notification_message = CONCAT('Your request for ', v_request_type, ' (ID: ', p_request_id, ') has been completed. Thank you for using our service.');
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
END //

-- Create New Request (Student)
CREATE PROCEDURE IF NOT EXISTS sp_CreateStudentRequest(
    IN p_user_id INT,
    IN p_request_type VARCHAR(100),
    IN p_institute VARCHAR(50),
    IN p_program VARCHAR(50),
    IN p_year_level VARCHAR(50),
    IN p_semester VARCHAR(50),
    IN p_details TEXT
)
BEGIN
    INSERT INTO requests (
        user_id, request_type, institute, program, 
        year_level, semester, details, status
    ) VALUES (
        p_user_id, p_request_type, p_institute, p_program,
        p_year_level, p_semester, p_details, 'pending'
    );
    
    -- Return the created request ID
    SELECT LAST_INSERT_ID() AS request_id;
END //

-- Create New Request (Alumni)
CREATE PROCEDURE IF NOT EXISTS sp_CreateAlumniRequest(
    IN p_user_id INT,
    IN p_request_type VARCHAR(100),
    IN p_institute VARCHAR(50),
    IN p_program VARCHAR(50),
    IN p_details TEXT
)
BEGIN
    INSERT INTO alumni_requests (
        user_id, request_type, institute, program, details, status
    ) VALUES (
        p_user_id, p_request_type, p_institute, p_program, p_details, 'pending'
    );
    
    -- Return the created request ID
    SELECT LAST_INSERT_ID() AS request_id;
END //

-- Get User Requests Statistics
CREATE PROCEDURE IF NOT EXISTS sp_GetUserRequestsStats(IN p_user_id INT)
BEGIN
    -- Get counts from student requests
    SELECT 
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id) AS total_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'pending') AS pending_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'approved') AS approved_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'completed') AS completed_requests,
        (SELECT COUNT(*) FROM requests WHERE user_id = p_user_id AND status = 'rejected') AS rejected_requests,
        (SELECT COUNT(*) FROM alumni_requests WHERE user_id = p_user_id) AS total_alumni_requests,
        (SELECT COUNT(*) FROM notifications WHERE user_id = p_user_id AND is_read = 0) AS unread_notifications;
END //

-- Get All Dashboard Statistics
CREATE PROCEDURE IF NOT EXISTS sp_GetAdminDashboardStats()
BEGIN
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
END //

-- Mark All Notifications As Read
CREATE PROCEDURE IF NOT EXISTS sp_MarkAllNotificationsAsRead(IN p_user_id INT)
BEGIN
    UPDATE notifications
    SET is_read = 1
    WHERE user_id = p_user_id;
    
    SELECT ROW_COUNT() AS updated_count;
END //

-- Get Recent Requests For User
CREATE PROCEDURE IF NOT EXISTS sp_GetUserRecentRequests(IN p_user_id INT, IN p_limit INT)
BEGIN
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
END //

-- Process Registration
CREATE PROCEDURE IF NOT EXISTS sp_ProcessRegistration(
    IN p_user_id INT,
    IN p_approve BOOLEAN,
    IN p_role VARCHAR(10),
    IN p_rejection_reason TEXT
)
BEGIN
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
END //

DELIMITER ;
