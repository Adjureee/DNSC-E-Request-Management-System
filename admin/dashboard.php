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


$latestRequests = array_merge($studentRequests, $alumniRequests);

// Sort latestRequests by created_at descending
usort($latestRequests, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
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
                    <li class="nav-item mt-5">
                        <a class="nav-link" href="../logout.php">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
