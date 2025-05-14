<?php
require_once '../config.php';
checkAdminAuth();

// Mark notifs as read
if(isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $id = (int)$_GET['mark_read'];
    $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    redirect('admin_notifications.php');
}

// Mark all notifs as read
if(isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
    $conn->query("UPDATE system_settings SET new_requests_count = 0, new_registrations_count = 0 WHERE id = 1");
    redirect('admin_notifications.php');
}

// Delete notification
if(isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM admin_notifications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    redirect('admin_notifications.php');
}

// Clear all read notifications
if(isset($_GET['clear_read'])) {
    $conn->query("DELETE FROM admin_notifications WHERE is_read = 1");
    redirect('admin_notifications.php');
}

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get notifications with pagination
$stmt = $conn->prepare("
    SELECT * FROM admin_notifications 
    ORDER BY created_at DESC 
    LIMIT ?, ?
");
$stmt->bind_param("ii", $start, $limit);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count total for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM admin_notifications");
$total_records = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Count unread notifications
$unread_query = $conn->query("SELECT COUNT(*) as unread FROM admin_notifications WHERE is_read = 0");
$unread_count = $unread_query->fetch_assoc()['unread'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Notifications - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.2);
        }
        .notification-badge {
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
        .notification-item {
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            border-left-color: #198754;
            background-color: #f0f7f4;
        }
        .notification-item.registration {
            border-left-color: #0d6efd;
        }
        .notification-item.student_request {
            border-left-color: #20c997;
        }
        .notification-item.alumni_request {
            border-left-color: #6f42c1;
        }
        .notification-actions {
            visibility: hidden;
        }
        .notification-item:hover .notification-actions {
            visibility: visible;
        }
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
                    <li class="nav-item"><a class="nav-link" href="requests.php"><i class="fas fa-clipboard-list me-2"></i>All Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="pending.php"><i class="fas fa-clock me-2"></i>Pending Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="approved.php"><i class="fas fa-check-circle me-2"></i>Approved Requests</a></li>
                    <li class="nav-item"><a class="nav-link" href="completed.php"><i class="fas fa-check-double me-2"></i>Completed Requests</a></li>
                    <li class="nav-item">
                        <a class="nav-link" href="registration_list.php">
                            <i class="fas fa-user-check me-2"></i> Registration List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_notifications.php">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <?php if($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
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

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Notifications</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if($unread_count > 0): ?>
                        <a href="?mark_all_read=1" class="btn btn-sm btn-success me-2">
                            <i class="fas fa-check-double me-1"></i> Mark All as Read
                        </a>
                    <?php endif; ?>
                    <a href="?clear_read=1" class="btn btn-sm btn-outline-danger me-2" onclick="return confirm('Are you sure you want to clear all read notifications?')">
                        <i class="fas fa-trash me-1"></i> Clear Read
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifications</h5>
                    <span class="badge bg-primary"><?php echo $unread_count; ?> unread</span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if(empty($notifications)): ?>
                            <div class="text-center py-4 text-muted">No notifications found</div>
                        <?php else: ?>
                            <?php foreach($notifications as $notification): ?>
                                <div class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?> <?php echo $notification['request_type']; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if($notification['request_type'] == 'registration'): ?>
                                                <i class="fas fa-user-plus text-primary me-2"></i>
                                            <?php elseif($notification['request_type'] == 'student_request'): ?>
                                                <i class="fas fa-file-alt text-success me-2"></i>
                                            <?php elseif($notification['request_type'] == 'alumni_request'): ?>
                                                <i class="fas fa-user-graduate text-info me-2"></i>
                                            <?php endif; ?>
                                            <span class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $notification['request_type'])); ?></span>
                                            <?php if(!$notification['is_read']): ?>
                                                <span class="badge bg-success ms-2">New</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="mt-1"><?php echo $notification['message']; ?></div>
                                    <div class="notification-actions text-end mt-2">
                                        <?php if(!$notification['is_read']): ?>
                                            <a href="?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($notification['user_id']): ?>
                                            <a href="view_registration.php?id=<?php echo $notification['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View User
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($notification['request_id']): ?>
                                            <a href="view_request.php?id=<?php echo $notification['request_id']; ?>&source=<?php echo $notification['request_type'] == 'student_request' ? 'student' : 'alumni'; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> View Request
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?delete=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this notification?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="card-footer bg-light">
                    <nav aria-label="Notifications pagination">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php if($page <= 1) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>" tabindex="-1">Previous</a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php if($page == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php if($page >= $total_pages) echo 'disabled'; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
