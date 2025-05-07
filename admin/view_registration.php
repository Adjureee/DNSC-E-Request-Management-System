<?php
require_once '../config.php';
checkAdminAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('registration_list.php');
}

$id = $_GET['id'];
$message = '';

// Fetch user function
function fetchUser($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$user = fetchUser($conn, $id);
if (!$user) {
    redirect('registration_list.php');
}

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if (in_array($action, ['approve_student', 'approve_alumni'])) {
        $assigned_role = $action === 'approve_student' ? 'student' : 'alumni';
        $verification_status = $action === 'approve_student' ? 'approved_student' : 'approved_alumni';

        $stmt = $conn->prepare("UPDATE users SET verification_status = ?, role = ?, approved_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $verification_status, $assigned_role, $id);
        $stmt->execute();
        $message = 'Account has been approved as ' . ucfirst($assigned_role) . '.';

    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'rejected', rejection_reason = 'Rejected by admin', rejected_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = 'Account has been rejected.';
    }

    // Refresh user data after update
    $user = fetchUser($conn, $id);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Registration - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .info-label { font-weight: 600; }
        .info-box {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .photo-preview {
            max-width: 200px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .status-badge {
            font-weight: bold;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .action-buttons button {
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .action-buttons button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h3 class="mb-4">Registration Details</h3>
    <a href="registration_list.php" class="btn btn-success mb-3">← Back to List</a>

    <?php if ($message): ?>
        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body info-box">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><span class="info-label">Full Name:</span> <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><span class="info-label">Student ID:</span> <?php echo htmlspecialchars($user['stud_id']); ?></p>
                    <p><span class="info-label">Institute:</span> <?php echo htmlspecialchars($user['institute']); ?></p>
                    <p><span class="info-label">Program:</span> <?php echo htmlspecialchars($user['program']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><span class="info-label">Email:</span> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><span class="info-label">Requested Role:</span> 
                        <?php echo htmlspecialchars(ucfirst($user['pre_select_role'] ?? 'Not specified')); ?>
                    </p>
                    <p><span class="info-label">Assigned Role:</span> 
                        <?php echo htmlspecialchars($user['role'] ?? 'Not assigned'); ?>
                    </p>
                    <p><span class="info-label">Verification Status:</span>
                        <span class="badge status-badge bg-<?php
                            echo match($user['verification_status']) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending' => 'warning',
                                default => 'secondary'
                            };
                        ?>">
                            <?php echo ucwords($user['verification_status']); ?>
                        </span>
                    </p>
                    <?php if ($user['verification_status'] === 'rejected' && $user['rejection_reason']): ?>
                        <p><span class="info-label">Rejection Reason:</span> <?php echo htmlspecialchars($user['rejection_reason']); ?></p>
                    <?php endif; ?>
                    <?php if ($user['approved_at']): ?>
                        <p><span class="info-label">Approved At:</span> <?php echo htmlspecialchars($user['approved_at']); ?></p>
                    <?php endif; ?>
                    <?php if ($user['rejected_at']): ?>
                        <p><span class="info-label">Rejected At:</span> <?php echo htmlspecialchars($user['rejected_at']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <p class="info-label">Uploaded Photo:</p>
                <?php if (!empty($user['uploadphoto'])): ?>
                    <img src="../uploads/<?php echo htmlspecialchars($user['uploadphoto']); ?>" alt="User Photo" class="photo-preview">
                <?php else: ?>
                    <p class="text-muted">No photo uploaded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($user['verification_status'] === 'pending'): ?>
        <form method="post" class="mt-4">
            <div class="d-flex flex-column flex-md-row gap-2 mb-3 action-buttons">
                <button name="action" value="approve_student" class="btn btn-success">Approve as Student</button>
                <button name="action" value="approve_alumni" class="btn btn-primary">Approve as Alumni</button>
            </div>
            <button name="action" value="reject" class="btn btn-danger mt-2"
                    onclick="return confirm('Are you sure you want to reject and delete this registration? This action cannot be undone.');">
                Reject Registration
            </button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
