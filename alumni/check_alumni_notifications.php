<?php
require_once '../config.php';
checkAlumniAuth();

$user_id = $_SESSION['user_id'];

// Check for any new notifications since last check
$last_check = $_SESSION['last_notification_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));
$_SESSION['last_notification_check'] = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = ? AND created_at > ?
");
$stmt->bind_param("is", $user_id, $last_check);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$new_notifications = $result['count'] > 0;

// Return as JSON
header('Content-Type: application/json');
echo json_encode(['new_notifications' => $new_notifications]);
?>
