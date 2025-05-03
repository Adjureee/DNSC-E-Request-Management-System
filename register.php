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
$success = '';
$formData = [
    'username' => '',
    'email' => '',
    'full_name' => ''
];

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store form data to repopulate form on error
    $formData = [
        'username' => isset($_POST['username']) ? sanitize($_POST['username']) : '',
        'email' => isset($_POST['email']) ? sanitize($_POST['email']) : '',
        'full_name' => isset($_POST['full_name']) ? sanitize($_POST['full_name']) : ''
    ];
    
    $username = $formData['username'];
    $email = $formData['email'];
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $full_name = $formData['full_name'];
    
    // Server-side validation
    $validationErrors = [];
    
    // Validate required fields
    if (empty($username)) {
        $validationErrors[] = 'Username is required';
    }
    
    if (empty($email)) {
        $validationErrors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors[] = 'Please enter a valid email address';
    }
    
    if (empty($full_name)) {
        $validationErrors[] = 'Full name is required';
    }
    
    if (empty($password)) {
        $validationErrors[] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $validationErrors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $validationErrors[] = 'Passwords do not match';
    }
    
    // Only proceed with database checks if basic validation passes
    if (empty($validationErrors)) {
        try {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $validationErrors[] = 'Username already exists';
            }
            $stmt->close();
            
            // Only check email if username is unique
            if (empty($validationErrors)) {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $stmt->bind_param("s", $email);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $validationErrors[] = 'Email already exists';
                }
                $stmt->close();
                
                // Only insert if both username and email are unique
                if (empty($validationErrors)) {
                    // Begin transaction to ensure data integrity
                    $conn->begin_transaction();
                    
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'student')");
                    if (!$stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    
                    $stmt->bind_param("ssss", $username, $hashed_password, $email, $full_name);
                    if (!$stmt->execute()) {
                        throw new Exception("Registration failed: " . $stmt->error);
                    }
                    
                    // Commit the transaction
                    $conn->commit();
                    
                    $success = 'Registration successful! You can now login.';
                    // Clear form data after successful registration
                    $formData = ['username' => '', 'email' => '', 'full_name' => ''];
                    
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            // Rollback the transaction if an error occurred
            if ($conn->connect_errno === 0) {
                $conn->rollback();
            }
            $error = $e->getMessage();
        }
    }
    
    // Set error message if validation failed
    if (!empty($validationErrors)) {
        $error = implode('<br>', $validationErrors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - DNSC E-Request Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
         :root {
            --primary: #2d5516;
            --secondary: #C1D95C;
            --tertiary: #498428;
         }
        body {
            background: linear-gradient(to right, #C1D95C, #498428);  
        }
        .register-container {
            max-width: 550px;
            margin: 25px auto ;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px #000000;
        }
        .card-header {
            background-color: #2d5516;
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
        .btn-success {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-success:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        a {
            color: #2d5516;
        }
        a:hover {
            color: #2d5516;
        }
        .form-control:focus {
            border-color: #2d5516;
            box-shadow: 0 0 0 0.25rem #198754;
        }
        .loading {
            display: none;
            margin: 0 auto;
        }
        .error-list {
            padding-left: 20px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="card">
            <div class="card-header text-center py-3">
                <h4>Register for DNSC E-Request System</h4>
            </div>
            <div class="card-body p-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-success">Go to Login</a>
                    </div>
                <?php else: ?>
                
                <form id="registerForm" method="POST" action="" novalidate>
            
<!-- wala pa na implement --> 
<div class="mb-3">
                <label for="Account-type" class="form-label">Account Type</label>
                <select class="form-select" id="Account-type" name="account-type" required>
                    <option value="">-- Select Role --</option>
                    <option value="students">Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="alumni">Alumni</option>
                </select>
            </div>

            <!-- Students Input -->
            <div class="mb-3" id="studentsInputWrapper" style="display: none;">
                <label for="students-role" class="form-label">Enter your Student ID</label>
                <input type="text" class="form-control" id="students-role" name="students-role" placeholder="Enter your Student ID" required>
            </div>

            <!-- Faculty Input -->
            <div class="mb-3" id="facultyInputWrapper" style="display: none;">
                <label for="faculty-role" class="form-label">Enter your Faculty ID</label>
                <input type="text" class="form-control" id="faculty-role" name="faculty-role" placeholder="Enter your Faculty ID" required>
            </div>

            <!-- Alumni Input -->
            <div class="mb-3" id="alumniInputWrapper" style="display: none;">
                <label for="alumni-role" class="form-label">Enter your Alumni ID</label>
                <input type="text" class="form-control" id="alumni-role" name="alumni-role" placeholder="Enter your Alumni ID" required>
            </div>

            <!-- Authorized Guest Input -->
            <script>
                const roleSelect = document.getElementById('Account-type');
                const inputSections = {
                    students: document.getElementById('studentsInputWrapper'),
                    faculty: document.getElementById('facultyInputWrapper'),
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
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($formData['full_name']); ?>" required>
                        <div class="invalid-feedback">Please enter your full name</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        <div class="invalid-feedback">Please enter a valid email address</div>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($formData['username']); ?>" required>
                        <div class="invalid-feedback">Please choose a username</div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="invalid-feedback">Password is required (minimum 6 characters)</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <div class="invalid-feedback">Passwords do not match</div>
                    </div>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-primary" id="registerBtn">
                            <span class="btn-text">Register</span>
                            <span class="loading spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const $registerForm = $('#registerForm');
            const $registerBtn = $('#registerBtn');
            const $btnText = $registerBtn.find('.btn-text');
            const $loading = $registerBtn.find('.loading');
            
            // Client-side validation
            $registerForm.on('submit', function(e) {
                let isValid = true;
                const errors = [];
                
                // Reset previous validation
                $registerForm.find('.is-invalid').removeClass('is-invalid');
                
                // Validate full name
                const fullName = $('#full_name').val().trim();
                if (fullName === '') {
                    $('#full_name').addClass('is-invalid');
                    errors.push('Full name is required');
                    isValid = false;
                }
                
                // Validate email
                const email = $('#email').val().trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email === '') {
                    $('#email').addClass('is-invalid');
                    errors.push('Email is required');
                    isValid = false;
                } else if (!emailRegex.test(email)) {
                    $('#email').addClass('is-invalid');
                    errors.push('Please enter a valid email address');
                    isValid = false;
                }
                
                // Validate username
                const username = $('#username').val().trim();
                if (username === '') {
                    $('#username').addClass('is-invalid');
                    errors.push('Username is required');
                    isValid = false;
                }
                
                // Validate password
                const password = $('#password').val();
                if (password === '') {
                    $('#password').addClass('is-invalid');
                    errors.push('Password is required');
                    isValid = false;
                } else if (password.length < 6) {
                    $('#password').addClass('is-invalid');
                    errors.push('Password must be at least 6 characters');
                    isValid = false;
                }
                
                // Validate confirm password
                const confirmPassword = $('#confirm_password').val();
                if (confirmPassword === '') {
                    $('#confirm_password').addClass('is-invalid');
                    errors.push('Please confirm your password');
                    isValid = false;
                } else if (password !== confirmPassword) {
                    $('#confirm_password').addClass('is-invalid');
                    errors.push('Passwords do not match');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Create error alert
                    let errorHtml = '<div class="alert alert-danger"><p>Please fix the following errors:</p><ul class="error-list">';
                    errors.forEach(error => {
                        errorHtml += `<li>${error}</li>`;
                    });
                    errorHtml += '</ul></div>';
                    
                    // Remove any existing error alerts and add the new one
                    $('.alert-danger').remove();
                    $registerForm.prepend(errorHtml);
                    
                    // Scroll to the top of the form
                    $('html, body').animate({
                        scrollTop: $registerForm.offset().top - 100
                    }, 200);
                } else {
                    // Show loading state
                    $btnText.hide();
                    $loading.show();
                    $registerBtn.prop('disabled', true);
                }
            });
            
            // Real-time validation for password matching
            $('#confirm_password, #password').on('keyup', function() {
                const password = $('#password').val();
                const confirmPassword = $('#confirm_password').val();
                
                if (confirmPassword !== '') {
                    if (password !== confirmPassword) {
                        $('#confirm_password').addClass('is-invalid').removeClass('is-valid');
                    } else {
                        $('#confirm_password').removeClass('is-invalid').addClass('is-valid');
                    }
                }
            });
            
            // Prevent submitting form again when page is refreshed
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
        });
    </script>
</body>
</html>
