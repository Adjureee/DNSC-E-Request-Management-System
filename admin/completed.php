<?php
require_once '../config.php';
checkAdminAuth();

$stmt1 = $conn->prepare("
    SELECT r.*, u.full_name, 'student' AS origin 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.status = 'completed'
    ORDER BY r.created_at DESC
");
$stmt1->execute();
$studentRequests = $stmt1->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt2 = $conn->prepare("
    SELECT ar.*, u.full_name, 'alumni' AS origin 
    FROM alumni_requests ar 
    JOIN users u ON ar.user_id = u.id 
    WHERE ar.status = 'completed'
    ORDER BY ar.created_at DESC
");
$stmt2->execute();
$alumniRequests = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

$completedRequests = array_merge($studentRequests, $alumniRequests);

// Get dashboard notification variables from admin/dashboard.php
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
$notificationTypes = $notifTypeQuery->fetch_all(MYSQLI_ASSOC)
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Requests - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
   <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2d5516;
            color: white;
        }
        .custom-topbar {
         background-color: #2d5516;
        }
        .nav-link {
            color: rgba(255,255,255,.8);
            position: relative;
        } 
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.2);
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
        .table-hover tbody tr:hover {
            background-color: #f1f1f1;
        }
        .btn-action {
            min-width: 100px;
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

  <!-- Topbar/Header -->
<nav class="navbar navbar-expand-lg navbar-dark custom-topbar px-3">
  <div class="container-fluid d-flex justify-content-between align-items-center">
        
        <!-- Left section: Brand + Toggle -->
        <div class="d-flex align-items-center">
            <!-- Sidebar toggle -->
            <button class="btn btn-outline-light me-2" id="sidebarToggleTop">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Brand -->
            <a class="navbar-brand mb-0 h1" href="#">DNSC E-Request System</a>
        </div>

         <!-- Notification Dropdown (center/right section) -->
    <div class="d-flex align-items-center ms-auto gap-2 d-none d-lg-flex">
      <!-- <div class="btn-group"> -->
        <div class="dropdown">
          <button class="btn btn-outline-light dropdown-toggle  btn-sm px-3"style="min-width: 150px; height: 38px;" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
      </div>
    </div>

    <!-- Profile dropdown -->
    <div class="dropdown">
        <button class="btn btn-outline-light dropdown-toggle btn-sm px-3" style="min-width: 180px; height: 38px;" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <!-- <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li> -->
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
     <!-- Profile dropdown for smaller screens (visible when navbar is collapsed) -->
     <div class="dropdown d-block d-lg-none">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
            <!-- <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li> -->
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>    
</nav>

<div class="container-fluid" id="layoutRow">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar collapse" id="sidebarMenu">
            <div class="position-sticky pt-3">
                <div class="text-center mb-4">
                    <h5>Admin Panel</h5>
                    <!-- <p class="text-white">Admin Panel</p> -->
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="requests.php">
                            <i class="fas fa-clipboard-list me-2"></i>
                            All Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pending.php">
                            <i class="fas fa-clock me-2"></i>
                            Pending Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="approved.php">
                            <i class="fas fa-check-circle me-2"></i>
                            Approved Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="completed.php">
                            <i class="fas fa-check-double me-2"></i>
                            Completed Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="registration_list.php">
                            <i class="fas fa-user-check me-2"></i> Registration List
                        </a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="admin_contact_messages.php">
                            <i class="fas fa-envelope me-2"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_announcement.php">
                            <i class="fas fa-bullhorn me-2"></i>
                            Create Announcement
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_announcements.php">
                            <i class="fas fa-tools me-2"></i>
                            Announcement List
                        </a>
                    </li>
                    <!-- <li class="nav-item">
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
                    </li> -->
                    <!-- <li class="nav-item mt-5">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Logout
                        </a>
                    </li> -->
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-12 px-md-4 py-4" id="mainContent">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Completed Requests</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <!-- <div class="btn-group me-2">
                        <span class="btn btn-sm btn-outline-secondary">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    </div> -->
                </div>
            </div>

            <!-- Completed Requests Table -->
            <div class="card dashboard-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Completed Requests</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                           <tbody>
                            <?php foreach ($completedRequests as $request): ?>
                            <tr>
                                <td><?php echo $request['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($request['full_name']); ?>
                                    <span class="badge bg-secondary ms-1"><?php echo ucfirst($request['origin']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo ucfirst($request['status']); ?></span>
                                </td>
                                <td><?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <?php if ($request['origin'] === 'alumni'): ?>
                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php else: ?>
                                        <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($completedRequests)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No completed requests found</td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script>
    document.getElementById('sidebarToggleTop').addEventListener('click', function () {
        var sidebar = document.getElementById('sidebarMenu');
        var main = document.getElementById('mainContent');

        sidebar.classList.toggle('show');

        if (sidebar.classList.contains('show')) {
            main.classList.remove('col-12');
            main.classList.add('col-md-9', 'col-lg-10');
        } else {
            main.classList.remove('col-md-9', 'col-lg-10');
            main.classList.add('col-12');
        }
    });
</script>
</body>
</html>
