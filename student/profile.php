<?php
require_once '../config.php';
checkStudentAuth();

$user_id = $_SESSION['user_id'];

// Fetch user details from database
$stmt = $conn->prepare("SELECT full_name, email, created_at, institute, program, role FROM users WHERE id = ?");
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
     <style>
        body {
            background-color: #f8f9fa;
        }
        .profile-initial {
            width: 100px;
            height: 100px;
            font-size: 48px;
            color: white;
            background-color: #a259d4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: auto;
        }
        .card-title {
            margin-top: 10px;
        }
        .section-title {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .nav-links a {
            display: block;
            margin-bottom: 10px;
            color: #333;
            text-decoration: none;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .edit-profile-btn {
            width: 150px; /* or set a desired width */
            font-size: 14px;
            padding: 8px 12px;
        }

    </style>
</head>
<body>
<div class="container mt-5">
    <!-- Profile section -->
    <div class="card mb-4 p-4 shadow-sm">
        <div class="text-center">
        <div class="profile-initial">
            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
        </div>
        <h4 class="card-title mt-3"><?php echo strtoupper($user['full_name']); ?></h4>
        <a href="edit_profile.php" class="btn btn-success mt-2 edit-profile-btn">Edit Profile</a>
</div>
        <div class="card-body text-start">
            <div class="section-title">USER DETAILS:</div>
            <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
            <p><strong>Email address:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Institute:</strong> <?php echo htmlspecialchars($user['institute']); ?></p>
            <p><strong>Program:</strong> <?php echo htmlspecialchars($user['program']); ?></p>
            <p><strong>Member since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
        </div>
    </div>

    <!-- User Details section -->
    <!-- <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="section-title">User details</div>
            <p><strong>Email address:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Member since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
        </div>
    </div> -->

    <!-- Back to Dashboard -->
    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</div>
</body>
</html>
