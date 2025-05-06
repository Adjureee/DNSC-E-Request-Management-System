<?php
require_once '../config.php';
checkAdminAuth();

// Get statistics for dashboard
$result = $conn->query("SELECT COUNT(*) as total FROM requests");
$totalRequests = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as pending FROM requests WHERE status = 'pending'");
$pendingRequests = $result->fetch_assoc()['pending'];

$result = $conn->query("SELECT COUNT(*) as approved FROM requests WHERE status = 'approved'");
$approvedRequests = $result->fetch_assoc()['approved'];

$result = $conn->query("SELECT COUNT(*) as completed FROM requests WHERE status = 'completed'");
$completedRequests = $result->fetch_assoc()['completed'];

// Count unseen pending requests (for badge)
$unseenStmt = $conn->prepare("SELECT COUNT(*) as unseen FROM requests WHERE status = 'pending' AND is_seen = 0");
$unseenStmt->execute();
$unseenResult = $unseenStmt->get_result();
$unseenCount = $unseenResult->fetch_assoc()['unseen'];

// Get latest requests
$stmt = $conn->query("
    SELECT r.*, u.full_name 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$latestRequests = $stmt->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DNSC E-Request System</title>
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
            background-color: #498428;
            border-color: #498428;
        }
        .btn-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
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
        .sidebar.collapsed-sidebar {
            display: none !important;
        }
        .main-expanded {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
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

    <!-- Profile dropdown (moved to the right) -->
    <div class="dropdown">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar collapse" id="sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5>Admin Portal</h5>
                        <!-- <p class="text-white">Admin Panel</p> -->
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
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
                        <li class="nav-item position-relative">
                            <a class="nav-link" href="pending.php">
                                <i class="fas fa-clock me-2"></i>
                                Pending Requests
                                <?php if ($unseenCount > 0): ?>
                                    <span class="badge-notification"><?php echo $unseenCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="approved.php">
                                <i class="fas fa-check-circle me-2"></i>
                                Approved Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="completed.php">
                                <i class="fas fa-check-double me-2"></i>
                                Completed Requests
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-12 px-md-4 py-4" id="main-content">
            <div class="d-flex justify-content-center align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="display-7">Admin Dashboard</h1>
            </div>
            </main>

                <!-- Stats Cards -->
                <div class="row my-4">
                    <div class="col-md-3 mb-4">
                        <div class="card dashboard-card "style="background-color: #2d5516; color: white">
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

                <!-- Recent Requests -->
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
                                        <th>Student</th>
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
                                        <td><?php echo htmlspecialchars($request['full_name']); ?></td>
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
                                        <td><?php echo date('M d, Y g:i A', strtotime($request['created_at'])); ?></td>
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
        var sidebar = document.getElementById('sidebar');
        var main = document.getElementById('main-content');

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
