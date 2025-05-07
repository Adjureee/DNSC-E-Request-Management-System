<?php
require_once '../config.php';
checkStudentAuth();

// Get statistics for dashboard
$user_id = $_SESSION['user_id'];

$result = $conn->query("SELECT COUNT(*) as total FROM requests WHERE user_id = $user_id");
$totalRequests = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as pending FROM requests WHERE user_id = $user_id AND status = 'pending'");
$pendingRequests = $result->fetch_assoc()['pending'];

$result = $conn->query("SELECT COUNT(*) as approved FROM requests WHERE user_id = $user_id AND status = 'approved'");
$approvedRequests = $result->fetch_assoc()['approved'];

$result = $conn->query("SELECT COUNT(*) as completed FROM requests WHERE user_id = $user_id AND status = 'completed'");
$completedRequests = $result->fetch_assoc()['completed'];

// Get notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get latest requests
$stmt = $conn->prepare("SELECT * FROM requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$latestRequests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - DNSC E-Request System</title>
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
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            padding: 0.25rem 0.6rem;
            font-size: 0.75rem;
            line-height: 1;
            border-radius: 50%;
        }
        .btn-primary {
            background-color: #498428;
            border-color: #498428;
        }
        .btn-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .card-header {
            background-color: #e9f7ef;
            border-bottom: 1px solid #d1e7dd;
        }
        .btn-outline-secondary {
            color: #498428;
            border-color: #498428;
        }
        .btn-outline-secondary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
            color: white;
        }
        .alert-info {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #498428;
        }
        /* #sidebarToggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
        }

        @media (max-width: 768px) {
            #sidebarToggle {
                left: auto !important;
                right: 15px !important;
                top: 15px !important;
            }
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1040;
                background-color: #2d5516;
                transform: translateX(-250px);
                transition: transform 0.3s ease-in-out;
            }
            .sidebar.sidebar-collapsed {
                transform: translateX(0) !important;
                display: block !important;
            }
        }
        .sidebar-collapsed {
            display: none !important;
        }
        main {
            transition: margin-left 0.3s ease-in-out;
        }
        main.full-width {
            margin-left: 0 !important;
        } */

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

    <!-- Profile dropdown -->
    <div class="dropdown ms-auto d-none d-lg-block">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
     <!-- Profile dropdown for smaller screens (visible when navbar is collapsed) -->
     <div class="dropdown d-block d-lg-none">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
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
                        <h5>Student Portal</h5>
                        <!-- <p class="text-light">Student Portal</p> -->
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="new_request.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                New Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_requests.php">
                                <i class="fas fa-clipboard-list me-2"></i>
                                My Requests
                            </a>
                        </li>
                                                                
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="notifications.php">
                                <i class="fas fa-bell me-2"></i>
                                Notifications
                                <?php if (count($notifications) > 0): ?>
                                <span class="notification-badge bg-danger"><?php echo count($notifications); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-12 px-md-4 py-4" id="mainContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Student Dashboard</h1>
                </div>

                <!-- Notifications -->
                <?php if (count($notifications) > 0): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>You have <?php echo count($notifications); ?> new notification(s)!</strong> 
                    <a href="notifications.php" class="alert-link">Click here to view them</a>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
               

                <!-- Stats Cards -->
                <div class="row my-4">
                    <div class="col-md-3 mb-4">
                    <div class="card dashboard-card" style="background-color: #2d5516; color: white;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Requests</h5>
                                    <h3 class="mb-0"><?php echo $totalRequests; ?></h3>
                                </div>
                                <i class="fas fa-clipboard-list fa-3x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card" style="background-color: #498428; color: white;">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Pending</h5>
                                    <h3 class="mb-0"><?php echo $pendingRequests; ?></h3>
                                </div>
                                <i class="fas fa-clock fa-3x"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card" style="background-color: #749E35; color: white;">
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
                        <div class="card dashboard-card" style="background-color: #B3CC50; color: white;">
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

                <!-- Quick Actions Card -->
                <!-- <div class="card dashboard-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="new_request.php" class="btn w-100 d-flex align-items-center justify-content-center gap-2 py-3" style="background-color: #2d5516; color: white;">
                                    <i class="fas fa-plus-circle fa-2x"></i>
                                    <div>
                                        <div class="fw-bold">New Request</div>
                                        <small>Submit a new document request</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="my_requests.php" class="btn w-100 d-flex align-items-center justify-content-center gap-2 py-3" style="background-color: #498428; color: white;">
                                    <i class="fas fa-clipboard-list fa-2x"></i>
                                    <div>
                                        <div class="fw-bold">My Requests</div>
                                        <small>View all your request history</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="notifications.php" class="btn w-100 d-flex align-items-center justify-content-center gap-2 py-3" style="background-color: #B3CC50; color: white;">
                                    <i class="fas fa-bell fa-2x"></i>
                                    <div>
                                        <div class="fw-bold">Notifications</div>
                                        <small>Check your latest updates</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div> -->

                <!-- Recent Requests -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Requests</h5>
                        <a href="my_requests.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Tracking #</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestRequests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['id']; ?></td>
                                        <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'secondary';
                                            if ($request['status'] == 'pending') $statusClass = 'warning';
                                            if ($request['status'] == 'approved') $statusClass = 'info';
                                            if ($request['status'] == 'completed') $statusClass = 'success';
                                            if ($request['status'] == 'rejected') $statusClass = 'danger';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($request['status']); ?></span>
                                        </td>
                                        <td><?php echo $request['tracking_number'] ?: '-'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">View</a>
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
