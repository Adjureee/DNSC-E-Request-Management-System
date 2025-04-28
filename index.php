<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

// Redirect to login page
redirect('login.php');
?>
