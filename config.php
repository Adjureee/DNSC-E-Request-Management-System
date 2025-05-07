<?php
// Database configuration
$host = 'localhost';
$db = 'dnsc_E-Request';
$user = 'root';
$pass = '';

// Create connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}
function isAlumni() {
    return isLoggedIn() && $_SESSION['role'] === 'alumni';
}

function checkAuth() {
    if (!isLoggedIn()) {
        redirect('login.php'); 
    }
}

function checkAdminAuth() {
    if (!isAdmin()) {
        redirect('login.php');
    }
}

function checkStudentAuth() {
    if (!isStudent()) {
        redirect('login.php');
    }
}

function checkAlumniAuth() {
    if (!isAlumni()) {
        redirect('login.php');
    }
}


// Function to sanitize input data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to get status badge class (with green color scheme)
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-success bg-opacity-75';
        case 'approved':
            return 'bg-success';
        case 'completed':
            return 'bg-success bg-opacity-50';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
