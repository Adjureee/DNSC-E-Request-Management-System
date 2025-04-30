<?php
require_once '../config.php';
checkStudentAuth();

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['id'] ?? null;

if ($notification_id && is_numeric($notification_id)) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
}

echo json_encode(['success' => true]);
?>
