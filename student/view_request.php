<?php
require_once '../config.php';
checkStudentAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get request details - make sure it belongs to the current user
$stmt = $conn->prepare("SELECT * FROM requests WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('dashboard.php');
}

$request = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2d5516;
            color: white;
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
        .request-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .timeline {
            list-style-type: none;
            position: relative;
            padding-left: 30px;
        }
        .timeline:before {
            content: ' ';
            background: #dee2e6;
            display: inline-block;
            position: absolute;
            left: 9px;
            width: 2px;
            height: 100%;
            z-index: 1;
        }
        .timeline-item {
            margin: 20px 0;
            position: relative;
        }
        .timeline-item:before {
            content: ' ';
            background: white;
            display: inline-block;
            position: absolute;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            left: -36px;
            width: 20px;
            height: 20px;
            z-index: 2;
        }
        .timeline-item.active:before {
            background: #2d5516;
            border-color: #2d5516;
        }
        .timeline-item.completed:before {
            background: #2d5516;
            border-color: #2d5516;
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
                        <p class="text-white">Student Portal</p>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
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
                            <a class="nav-link active" href="my_requests.php">
                                <i class="fas fa-clipboard-list me-2"></i>
                                My Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell me-2"></i>
                                Notifications
                            </a>
                        </li>
                        <!-- Logout removed from sidebar -->
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Request Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="my_requests.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to My Requests
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card request-card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Request #<?php echo $request['id']; ?></h5>
                                <?php 
                                $statusClass = 'secondary';
                                if ($request['status'] == 'pending') $statusClass = 'warning';
                                if ($request['status'] == 'approved') $statusClass = 'info';
                                if ($request['status'] == 'completed') $statusClass = 'success';
                                if ($request['status'] == 'rejected') $statusClass = 'danger';
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($request['status']); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Request Type:</div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($request['request_type']); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Date Submitted:</div>
                                    <div class="col-md-8"><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Last Updated:</div>
                                    <div class="col-md-8"><?php echo date('F d, Y h:i A', strtotime($request['updated_at'])); ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Tracking Number:</div>
                                    <div class="col-md-8">
                                        <?php echo $request['tracking_number'] ? htmlspecialchars($request['tracking_number']) : '<span class="text-muted">Not assigned yet</span>'; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Pickup Date/Time:</div>
                                    <div class="col-md-8">
                                        <?php echo $request['pickup_datetime'] ? date('F d, Y h:i A', strtotime($request['pickup_datetime'])) : '<span class="text-muted">Not scheduled yet</span>'; ?>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-4 fw-bold">Details:</div>
                                    <div class="col-md-8"><?php echo nl2br(htmlspecialchars($request['details'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card request-card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Request Status</h5>
                            </div>
                            <div class="card-body">
                                <ul class="timeline">
                                    <li class="timeline-item <?php echo in_array($request['status'], ['pending', 'approved', 'completed', 'rejected']) ? 'active' : ''; ?> <?php echo in_array($request['status'], ['approved', 'completed']) ? 'completed' : ''; ?>">
                                        <div class="timeline-item-content">
                                            <h6>Submitted</h6>
                                            <p class="text-muted"><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></p>
                                            <p>Your request has been submitted successfully.</p>
                                        </div>
                                    </li>
                                    <li class="timeline-item <?php echo in_array($request['status'], ['approved', 'completed']) ? 'active completed' : ''; ?> <?php echo $request['status'] === 'rejected' ? 'active' : ''; ?>">
                                        <div class="timeline-item-content">
                                            <h6>Processing</h6>
                                            <?php if (in_array($request['status'], ['approved', 'completed', 'rejected'])): ?>
                                                <p class="text-muted"><?php echo date('M d, Y', strtotime($request['updated_at'])); ?></p>
                                                <?php if ($request['status'] === 'rejected'): ?>
                                                    <p class="text-danger">Your request has been rejected. Please contact the registrar for more information.</p>
                                                <?php else: ?>
                                                    <p>Your request has been processed and approved.</p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p>Your request is waiting for approval.</p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                    <li class="timeline-item <?php echo $request['status'] === 'completed' ? 'active completed' : ''; ?>">
                                        <div class="timeline-item-content">
                                            <h6>Ready for Pickup</h6>
                                            <?php if ($request['status'] === 'completed'): ?>
                                                <p class="text-muted"><?php echo date('M d, Y', strtotime($request['updated_at'])); ?></p>
                                                <p>Your document is ready for pickup.</p>
                                                <?php if ($request['pickup_datetime']): ?>
                                                    <p class="text-success">Please pick up your document on <?php echo date('F d, Y h:i A', strtotime($request['pickup_datetime'])); ?></p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p>Your document is being prepared.</p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <?php if ($request['status'] === 'approved' || $request['status'] === 'completed'): ?>
                            <div class="card-footer">
                                <div class="d-grid">
                                    <a href="print_request.php?id=<?php echo $request['id']; ?>" class="btn btn-primary" target="_blank">
                                        <i class="fas fa-print me-1"></i> Print Receipt
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
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
