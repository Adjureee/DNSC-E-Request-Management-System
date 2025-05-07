<?php
require_once 'config.php';

// Check if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } elseif (isStudent()) {
        redirect('student/dashboard.php');
    } elseif (isAlumni()) {
        redirect('alumni/dashboard.php');
    } else {
        redirect('select_role.php'); // Handle null or unknown roles
    }
}

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stud_id = sanitize($_POST['stud_id']);
    $password = $_POST['password'];

    $sql = "SELECT id, stud_id, password, role, full_name, verification_status FROM users WHERE stud_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $stud_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['verification_status'] === 'pending') {
            $error = 'Your registration is still pending approval.';
        } elseif ($user['verification_status'] === 'rejected') {
            $error = 'Your registration was rejected. Please contact the registrar.';
        } elseif (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['stud_id'] = $user['stud_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('admin/dashboard.php');
            } elseif ($user['role'] === 'student') {
                redirect('student/dashboard.php');
            } elseif ($user['role'] === 'alumni') {
                redirect('alumni/dashboard.php');
            } else {
                redirect('select_role.php'); // Redirect if role is NULL or unknown
            }
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Student ID not found';
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DNSC E-Request Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 450px;
            margin: 100px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #198754;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            width: 100%;
            background-color: #198754;
            border-color: #198754;
        }
        .btn-primary:hover {
            background-color: #146c43;
            border-color: #146c43;
        }
        a {
            color: #198754;
        }
        a:hover {
            color: #146c43;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header text-center py-3">
                <h4>DNSC E-Request Management System</h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form id="loginForm" method="POST" action="">
                    <div class="mb-3">
                        <label for="stud_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="stud_id" name="stud_id" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <div class="text-center">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
