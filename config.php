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

/**
 * Call a stored procedure with parameters
 * 
 * @param mysqli $conn Database connection
 * @param string $procedure Procedure name  
 * @param string $types Parameter types (i for integer, s for string, d for double)
 * @param array $params Parameters array
 * @return mysqli_result|bool Result object or boolean
 */
function callProcedure($conn, $procedure, $types = '', $params = []) {
    // Handle empty parameters case
    if (empty($params)) {
        $query = "CALL $procedure()";
        $stmt = $conn->prepare($query);
    } else {
        // Special case for sp_UpdateRequestStatus which needs 5 parameters
        if ($procedure === 'sp_UpdateRequestStatus' && count($params) === 3) {
            // When only status is being updated (without tracking number and pickup datetime)
            // Add NULL values for missing parameters
            $params[] = NULL;
            $params[] = NULL;
            $types .= 'ss'; // Add two string types for the NULL values
            
            $paramStr = str_repeat('?,', count($params) - 1) . '?';
            $query = "CALL $procedure($paramStr)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
        } else {
            $paramStr = str_repeat('?,', count($params) - 1) . '?';
            $query = "CALL $procedure($paramStr)";
            
            $stmt = $conn->prepare($query);
            
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result;
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    return match($status) {
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-info',
        'completed' => 'bg-success',
        'rejected' => 'bg-danger',
        default => 'bg-secondary',
    };
}
?>
