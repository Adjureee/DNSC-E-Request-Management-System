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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Completed Requests</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <span class="btn btn-sm btn-outline-secondary">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    </div>
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
</body>
</html>
