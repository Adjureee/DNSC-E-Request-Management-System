<?php
require_once '../config.php';
checkAdminAuth();

// Get dashboard statistics using stored procedure
$result = callProcedure($conn, 'sp_GetAdminDashboardStats');
$stats = $result->fetch_assoc();

$totalRequests = $stats['total_requests'];
$pendingRequests = $stats['pending_requests'];
$approvedRequests = $stats['approved_requests'];
$completedRequests = $stats['completed_requests'];
$unseenCount = $stats['new_requests'];

// Get system settings (triggers)
$settingsQuery = $conn->query("SELECT new_requests_count, new_registrations_count FROM system_settings WHERE id = 1");
$systemCounters = $settingsQuery->fetch_assoc();
$newRequestsCount = $systemCounters['new_requests_count'] ?? 0;
$newRegistrationsCount = $systemCounters['new_registrations_count'] ?? 0;

// Get admin notifs (triggers)
$adminNotificationsQuery = $conn->query("
    SELECT * FROM admin_notifications 
    WHERE is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
$adminNotifications = $adminNotificationsQuery->fetch_all(MYSQLI_ASSOC);
$notificationCount = count($adminNotifications);

// Get notification summary by type (from triggers)
$notifTypeQuery = $conn->query("
    SELECT request_type, COUNT(*) as count
    FROM admin_notifications
    WHERE is_read = 0
    GROUP BY request_type
");
$notificationTypes = $notifTypeQuery->fetch_all(MYSQLI_ASSOC);

// Fetch latest 5 from students and 5 from alumni
$studentRequests = $conn->query("
    SELECT r.*, u.full_name, 'student' as source 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$alumniRequests = $conn->query("
    SELECT r.*, u.full_name, 'alumni' as source 
    FROM alumni_requests r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Sort latestRequests by created_at descending
$latestRequests = array_merge($studentRequests, $alumniRequests);
usort($latestRequests, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Mark admin notifications as read
if(isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
    // Reset the counter in system_settings
    $conn->query("UPDATE system_settings SET new_requests_count = 0, new_registrations_count = 0 WHERE id = 1");
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - DNSC E-Request System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #198754;
            color: white;
        }
        .nav-link {
            color: rgba(255,255,255,.8);
            position: relative;
        }
        .nav-link:hover {
            color: white;
        }
        .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.2);
        }
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-primary:hover {
            background-color: #146c43;
            border-color: #146c43;
        }
        .btn-outline-secondary {
            color: #198754;
            border-color: #198754;
        }
        .btn-outline-secondary:hover {
            background-color: #198754;
            color: white;
            border-color: #198754;
        }
        .badge-notification {
            position: absolute;
            top: 5px;
            right: 15px;
            background-color: red;
            color: white;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 50%;
        }
        
        /* Notification dropdown styles */
        .notification-dropdown {
            min-width: 320px;
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s ease;
        }
        .notification-item:hover {
            background-color: #f1f1f1;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item .notification-time {
            font-size: 0.75rem;
            color: #6c757d;
        }
        .notification-footer {
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .notification-badge {
            position: absolute;
            top: 0px;
            right: 0px;
            padding: 0.25rem 0.6rem;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h5>DNSC E-Request System</h5>
                    <p class="text-muted">Admin Panel</p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="requests.php">
                            <i class="fas fa-clipboard-list me-2"></i> All Requests
                        </a>
                    </li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="pending.php">
                            <i class="fas fa-clock me-2"></i> Pending Requests
                            <?php if ($unseenCount > 0): ?>
                                <span class="badge-notification"><?php echo $unseenCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approved.php">
                            <i class="fas fa-check-circle me-2"></i> Approved Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="completed.php">
                            <i class="fas fa-check-double me-2"></i> Completed Requests
                        </a>
                    </li>
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
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-secondary dropdown-toggle position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell me-1"></i> Notifications
                                <?php if($notificationCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                        <?php echo $notificationCount; ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                                <div class="notification-header">
                                    <strong>System Activity</strong>
                                    <?php if($notificationCount > 0): ?>
                                        <a href="?mark_read=all" class="text-decoration-none small">Mark all as read</a>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="p-3">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                <div>
                                                    <h6 class="mb-0" style="font-size: 0.8rem;">New Requests</h6>
                                                    <h5 class="mb-0"><?php echo $newRequestsCount; ?></h5>
                                                </div>
                                                <div class="bg-success text-white p-2 rounded">
                                                    <i class="fas fa-file-alt"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                                <div>
                                                    <h6 class="mb-0" style="font-size: 0.8rem;">Registrations</h6>
                                                    <h5 class="mb-0"><?php echo $newRegistrationsCount; ?></h5>
                                                </div>
                                                <div class="bg-primary text-white p-2 rounded">
                                                    <i class="fas fa-user-plus"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="mb-2" style="font-size: 0.8rem;">Notification Summary</h6>
                                        <ul class="list-group list-group-sm">
                                            <?php foreach($notificationTypes as $type): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center py-1">
                                                <small><?php echo ucfirst(str_replace('_', ' ', $type['request_type'])); ?></small>
                                                <span class="badge bg-primary rounded-pill"><?php echo $type['count']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                            <?php if(empty($notificationTypes)): ?>
                                            <li class="list-group-item text-muted py-1"><small>No notifications</small></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="notification-header">
                                    <strong>Recent Activity</strong>
                                </div>
                                
                                <div class="p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($adminNotifications as $notification): ?>
                                        <li class="list-group-item py-2">
                                            <div class="d-flex justify-content-between">
                                                <?php if($notification['request_type'] == 'registration'): ?>
                                                    <span><i class="fas fa-user-plus text-primary me-2"></i> <?php echo $notification['message']; ?></span>
                                                <?php elseif($notification['request_type'] == 'student_request'): ?>
                                                    <span><i class="fas fa-file-alt text-success me-2"></i> <?php echo $notification['message']; ?></span>
                                                <?php elseif($notification['request_type'] == 'alumni_request'): ?>
                                                    <span><i class="fas fa-user-graduate text-info me-2"></i> <?php echo $notification['message']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mt-1">
                                                <small class="text-muted"><?php echo date('M d, g:i A', strtotime($notification['created_at'])); ?></small>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                        <?php if(empty($adminNotifications)): ?>
                                        <li class="list-group-item text-center py-3 text-muted">No recent activity</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                
                                <div class="notification-footer">
                                    <a href="admin_notifications.php" class="text-decoration-none">View all notifications</a>
                                </div>
                            </div>
                        </div>
                        
                        <span class="btn btn-sm btn-outline-secondary">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row my-4">
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Total Requests</h5>
                                <h3 class="mb-0"><?php echo $totalRequests; ?></h3>
                            </div>
                            <i class="fas fa-clipboard-list fa-3x"></i>
                        </div>
                        <?php if($newRequestsCount > 0): ?>
                        <div class="card-footer bg-success-dark text-white py-1">
                            <small><i class="fas fa-arrow-up me-1"></i> <?php echo $newRequestsCount; ?> new requests</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card" style="background-color: #20c997; color: white;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Pending</h5>
                                <h3 class="mb-0"><?php echo $pendingRequests; ?></h3>
                            </div>
                            <i class="fas fa-clock fa-3x"></i>
                        </div>
                        <?php if($unseenCount > 0): ?>
                        <div class="card-footer bg-info-dark text-white py-1">
                            <small><i class="fas fa-bell me-1"></i> <?php echo $unseenCount; ?> unseen requests</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card" style="background-color: #2dd4bf; color: white;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Approved</h5>
                                <h3 class="mb-0"><?php echo $approvedRequests; ?></h3>
                            </div>
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card dashboard-card" style="background-color: #15803d; color: white;">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">Completed</h5>
                                <h3 class="mb-0"><?php echo $completedRequests; ?></h3>
                            </div>
                            <i class="fas fa-check-double fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Requests Table -->
            <div class="card dashboard-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Requests</h5>
                    <a href="requests.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Submitted By</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestRequests as $request): ?>
                                <tr>
                                    <td><?php echo $request['id']; ?></td>
                                    <td><?php echo htmlspecialchars($request['full_name']) . ' (' . $request['source'] . ')'; ?></td>
                                    <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = match($request['status']) {
                                            'pending' => 'warning',
                                            'approved' => 'info',
                                            'completed' => 'success',
                                            'rejected' => 'danger',
                                            default => 'secondary',
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($request['status']); ?></span>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?></td>
                                    <td>
                                        <a href="view_request.php?id=<?php echo $request['id']; ?>&source=<?php echo $request['source']; ?>" class="btn btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($latestRequests)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No requests found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Kamo ba jud arjean n chan?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="../logout.php" class="btn btn-danger">Yes, Logout</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Notification badge auto-refresh
    $(document).ready(function() {
        // Check for new notifications every 30 seconds
        setInterval(function() {
            $.ajax({
                url: 'check_notifications.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if(data.count > 0) {
                        $('.notification-badge').text(data.count).show();
                    } else {
                        $('.notification-badge').hide();
                    }
                }
            });
        }, 30000);
    });
</script>
</body>
</html>
