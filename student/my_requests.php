<?php
require_once '../config.php';
checkStudentAuth();

$user_id = $_SESSION['user_id'];

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get requests with pagination
$stmt = $conn->prepare("
    SELECT * FROM requests 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ?, ?
");
$stmt->bind_param("iii", $user_id, $start, $limit);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total records for pagination
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM requests WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - DNSC E-Request System</title>
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
        .requests-card {
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
        .btn-success {
            background-color: #498428;
            border-color: #498428;
        }
        .btn-success:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-outline-primary {
            color: #498428;
            border-color: #498428;
        }
        .btn-outline-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
            color: white;
        }
        .card-header {
            background-color: #e9f7ef;
            border-bottom: 1px solid #d1e7dd;
        }
        .page-link {
            color: #498428;
        }
        .page-item.active .page-link {
            background-color: #198754;
            border-color: #198754;
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
                        <p class="text-light">Student Portal</p>
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
                    
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Requests</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="new_request.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-plus me-1"></i> New Request
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card requests-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Request History</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add search box -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" id="requestSearch" class="form-control" placeholder="Search by type, status or tracking number...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Request Type</th>
                                        <th>Status</th>
                                        <th>Tracking Number</th>
                                        <th>Date Submitted</th>
                                        <th>Pickup Date/Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No requests found. <a href="new_request.php">Create a new request</a>.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($requests as $request): ?>
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
                                            <?php echo $request['pickup_datetime'] ? date('M d, Y h:i A', strtotime($request['pickup_datetime'])) : '-'; ?>
                                        </td>
                                        <td>
                                            <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                            <?php if ($request['status'] === 'approved' || $request['status'] === 'completed'): ?>
                                            <a href="print_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $("#requestSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
    </script>
</body>
</html>
