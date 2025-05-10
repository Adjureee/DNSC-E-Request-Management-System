<?php
require_once '../config.php';
checkAlumniAuth();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();


header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>
