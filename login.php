<?php
require_once 'config.php';

// Check if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$error = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT id, username, password, role, full_name FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('student/dashboard.php');
            }
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'Username not found';
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
        :root {
            --primary: #2d5516;
            --secondary: #C1D95C;
            --tertiary: #498428;
        }
        body {
            background: linear-gradient(to right, #C1D95C, #498428);      
        }
        .login-container {
            max-width: 550px;
            margin: 150px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color:#2d5516;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            width: 100%;
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        a {
            color: #2d5516;
        }
        a:hover {
            color: #2d5516;
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

<!-- wala pa na implement-->
<div class="mb-3">
                <label for="Account-type" class="form-label">Account Type</label>
                <select class="form-select" id="Account-type" name="account-type" required>
                    <option value="">-- Select Role --</option>
                    <option value="students">Student</option>
                    <option value="alumni">Alumni</option>
                </select>
            </div>

            <!-- Students Input -->
            <div class="mb-3" id="studentsInputWrapper" style="display: none;">
                <label for="students-role" class="form-label">Identification Number:</label>
                <input type="text" class="form-control" id="students-role" name="students-role" placeholder="Enter your Student ID" required>
            </div>
            <!-- Alumni Input -->
            <div class="mb-3" id="alumniInputWrapper" style="display: none;">
                <label for="alumni-role" class="form-label">Identification Number:</label>
                <input type="text" class="form-control" id="alumni-role" name="alumni-role" placeholder="Enter your Alumni ID" required>
            </div>

            <script>
                const roleSelect = document.getElementById('Account-type');
                const inputSections = {
                    students: document.getElementById('studentsInputWrapper'),
                    alumni: document.getElementById('alumniInputWrapper'),
                };
                roleSelect.addEventListener('change', function () {
                    const selected = this.value;
                    for (const key in inputSections) {
                        inputSections[key].style.display = (key === selected) ? 'block' : 'none';
                    }
                });
            </script>
<!-- wala pa na implement -->

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
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
    <script>
        $(document).ready(function() {
            // Ajax login can be implemented here
            // For simplicity, using regular form submission for now
        });
    </script>
</body>
</html>
