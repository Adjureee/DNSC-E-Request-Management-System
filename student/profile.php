<?php
require_once '../config.php';
checkStudentAuth();

$user_id = $_SESSION['user_id'];

// Fetch user details from database
$stmt = $conn->prepare("SELECT full_name, email, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
       :root {
            --primary: #b3cc50;
            --secondary: #6f9733;
            --tertiary: #478026;
            --quaternary: #2c5315;
        }
        body {
        background-color: white;        
        }
        .profile-container {
            min-height: 100vh;
        }

        .profile-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 8px 20px #000000;
            background-color: #fff;
        }

        .profile-header {
            background-color: var(--quaternary);
            color: white;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            padding: 30px 20px;
            text-align: center;
        }

        .profile-header i {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .profile-body {
            padding: 30px;
            background-color: #f1fdf6;
        }

        .profile-body p {
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .label-title {
            color: var(--primary);
            font-weight: 600;
        }

        .btn-dashboard {
            color: white;
            background-color: var(--tertiary);
            border-color: var(--primary);
        }
            .btn-dashboard:hover {
                color: white;
                background-color: var(--quaternary);
                border-color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center align-items-center profile-container">
        <div class="profile-card w-100" style="max-width: 500px;">
            <div class="profile-header">
                <i class="fas fa-user-circle"></i>
                <h3>Student Profile</h3>
            </div>
                <div class="profile-body">
                    <table class="table table-bordered">
                        <tr><th>Full Name</th><td><?= htmlspecialchars($user['full_name']) ?></td></tr>
                        <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                        <tr><th>Member Since</th><td><?= date('M d, Y', strtotime($user['created_at'])) ?></td></tr>
                    </table>
                    <div class="text-center mt-3">
                        <a href="dashboard.php" class="btn btn-dashboard px-4">← Back to Dashboard</a>
                    </div>
                </div>
        </div>
    </div>
</body>
</html>
