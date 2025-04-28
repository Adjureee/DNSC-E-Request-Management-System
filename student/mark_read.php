<?php
require_once '../config.php';
checkStudentAuth();

$user_id = $_SESSION['user_id'];

// Mark all notifications as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Return success response for AJAX call
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
