<?php
require_once '../config.php';
checkAdminAuth();

// Get count of unread admin notifications (from triggers)
$query = $conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
$result = $query->fetch_assoc();
$count = $result['count'];

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?>
