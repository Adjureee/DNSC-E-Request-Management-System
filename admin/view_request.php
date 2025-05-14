<?php
require_once '../config.php';
checkAdminAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$id = $_GET['id'];
$message = '';

// Determine the request source (student or alumni)
// Check if source is directly provided in the URL
$request_source = isset($_GET['source']) ? $_GET['source'] : null;

// If source is not provided, try to determine it
if (!$request_source) {
    $check = $conn->prepare("SELECT id FROM requests WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $check = $conn->prepare("SELECT id FROM alumni_requests WHERE id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows === 0) {
            redirect('dashboard.php');
        } else {
            $request_source = 'alumni';
        }
    } else {
        $request_source = 'student';
    }
}

$table = $request_source; // Use the source directly as table name parameter

// Fetch request details using stored procedure
$result = callProcedure($conn, 'sp_GetRequestDetails', 'is', [$id, $table]);

if ($result === false || $result->num_rows === 0) {
    redirect('dashboard.php');
}

$request = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'approve') {
        $tracking_number = 'TR-' . date('Ymd') . '-' . $id . rand(1000, 9999);
        $pickup_datetime = $_POST['pickup_datetime'];
        
        // Use stored procedure to update status
        $result = callProcedure($conn, 'sp_UpdateRequestStatus', 'issss', [
            $id, 
            $table, 
            'approved', 
            $tracking_number, 
            $pickup_datetime
        ]);
        
        if ($result !== false) {
            $updated_request = $result->fetch_assoc();
            $message = "Request has been approved successfully.";
        } else {
            $message = "Error updating request: " . $conn->error;
        }
        
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        
        // Use stored procedure to update status
        $result = callProcedure($conn, 'sp_UpdateRequestStatus', 'iss', [
            $id, 
            $table, 
            'rejected'
        ]);
        
        if ($result !== false) {
            $updated_request = $result->fetch_assoc();
            $message = "Request has been rejected successfully.";
        } else {
            $message = "Error updating request: " . $conn->error;
        }
        
    } elseif ($action === 'complete') {
        // Use stored procedure to update status
        $result = callProcedure($conn, 'sp_UpdateRequestStatus', 'iss', [
            $id, 
            $table, 
            'completed'
        ]);
        
        if ($result !== false) {
            $updated_request = $result->fetch_assoc();
            $message = "Request has been marked as completed.";
        } else {
            $message = "Error updating request: " . $conn->error;
        }
        
    } elseif ($action === 'send_additional_note') {
        $additional_note = trim($_POST['additional_note']);
        if (!empty($additional_note)) {
            // Get user_id from request
            $user_id = $request['user_id'];
            $notification_message = "Update regarding your approved request (ID: $id): $additional_note";

            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $notification_message);
            if ($stmt->execute()) {
                $message = "Additional update sent to the user.";
            } else {
                $message = "Error sending update: " . $conn->error;
            }
            $stmt->close();
        } else {
            $message = "Notification message cannot be empty.";
        }
    }
    
    // Refresh request data after any action
    $result = callProcedure($conn, 'sp_GetRequestDetails', 'is', [$id, $table]);
    if ($result !== false) {
        $request = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Request - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #198754;
            color: white;
        }
        .nav-link { color: rgba(255,255,255,.8); }
        .nav-link:hover { color: white; }
        .nav-link.active { color: white; background-color: rgba(255,255,255,.2); }
        .request-details-card { border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #498428; border-color: #498428; }
        .btn-primary:hover { background-color: #2d5516; border-color: #2d5516; }
        .btn-outline-secondary { color: #498428; border-color: #498428; }
        .btn-outline-secondary:hover { background-color: #2d5516; border-color: #2d5516; color: white; }
        .card-header { background-color: #e9f7ef; border-bottom: 1px solid #d1e7dd; }
        .view-row { margin-bottom: 10px; }
        .view-label { font-weight: bold; display: inline-block; width: 180px; }
        .view-value { display: inline-block; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h5>DNSC E-Request System</h5>
                    <p class="text-white">Admin Panel</p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="requests.php"><i class="fas fa-clipboard-list me-2"></i>All Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="pending.php"><i class="fas fa-clock me-2"></i>Pending Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="approved.php"><i class="fas fa-check-circle me-2"></i>Approved Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="completed.php"><i class="fas fa-check-double me-2"></i>Completed Requests</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="registration_list.php">
                            <i class="fas fa-user-check me-2"></i> Registration List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_notifications.php">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <?php
                            // Get notification count
                            $notifCountQuery = $conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
                            $notifCount = $notifCountQuery->fetch_assoc()['count'];
                            if($notifCount > 0): ?>
                                <span class="badge bg-danger rounded-pill position-absolute top-50 end-0 translate-middle-y me-3"><?php echo $notifCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item mt-5">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Request Details</h1>
                <a href="requests.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Requests</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card request-details-card mb-4">
                        <div class="card-header"><h5>Request Details</h5></div>
                        <div class="card-body">
                          <p><strong>Full Name:</strong> <?= htmlspecialchars($request['full_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($request['email']) ?></p>
                        <p><strong>Student ID:</strong> <?= htmlspecialchars($request['stud_id']) ?></p>
                        <p><strong>Request From:</strong> <?= ucfirst($request_source) ?></p>
                        <p><strong>Institute:</strong> <?= htmlspecialchars($request['institute']) ?></p>
                        <p><strong>Program:</strong> <?= htmlspecialchars($request['program']) ?></p>

                        <?php if ($request_source === 'student'): ?>
                            <p><strong>Year Level:</strong> <?= htmlspecialchars($request['year_level']) ?></p>
                            <p><strong>Semester:</strong> <?= htmlspecialchars($request['semester']) ?></p>
                        <?php else: ?>
                            <p><strong>User Type:</strong> Alumni</p>
                        <?php endif; ?>
<br>
                        <h5>Request Information:</h5>
                        <p><strong>Request Type:</strong> <?= htmlspecialchars($request['request_type']) ?></p>
                        <p><strong>Request ID:</strong> <?= $request['id'] ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($request['status']) ?></p>

                        <?php if (!empty($request['tracking_number'])): ?>
                            <p><strong>Tracking Number:</strong> <?= htmlspecialchars($request['tracking_number']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($request['pickup_datetime'])): ?>
                            <p><strong>Pickup Date/Time:</strong> <?= htmlspecialchars($request['pickup_datetime']) ?></p>
                        <?php endif; ?>

                        <p><strong>Details:</strong> <?= nl2br(htmlspecialchars($request['details'])) ?></p>

                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="col-md-4">
                    <div class="card request-details-card mb-4">
                        <div class="card-header"><h5 class="mb-0">Actions</h5></div>
                        <div class="card-body">
                            <?php if ($request['status'] === 'pending'): ?>
                                <form method="POST" class="mb-3">
                                    <h6>Approve Request</h6>
                                    <div class="mb-3">
                                        <label class="form-label">Pickup Date/Time</label>
                                        <input type="datetime-local" name="pickup_datetime" class="form-control" required>
                                    </div>
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle me-1"></i> Approve</button>
                                </form>
                                <hr>
                                <h6>Reject Request</h6>
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="fas fa-times-circle me-1"></i> Reject</button>
                            <?php elseif ($request['status'] === 'approved'): ?>
                                <form method="POST" class="mb-3">
                                    <h6>Send Additional Notification</h6>
                                    <textarea name="additional_note" class="form-control mb-2" rows="3" placeholder="Type message..."></textarea>
                                    <input type="hidden" name="action" value="send_additional_note">
                                    <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane me-1"></i> Send</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-check-double me-1"></i> Mark as Completed</button>
                                </form>
                            <?php else: ?>
                                <p class="text-muted">No actions available for this request status.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="rejectModalLabel">Reject Request</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to reject this request?</p>
          <div class="mb-3">
              <label for="rejection_reason" class="form-label">Reason (optional)</label>
              <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <input type="hidden" name="action" value="reject">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Confirm Reject</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
