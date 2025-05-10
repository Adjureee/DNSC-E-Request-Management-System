<?php
require_once '../config.php';
checkAdminAuth();

// Get all student requests
$stmt = $conn->prepare("
    SELECT r.*, u.full_name, 'student' AS source
    FROM requests r 
    JOIN users u ON r.user_id = u.id
");

// Get all alumni requests
$stmt2 = $conn->prepare("
    SELECT r.*, u.full_name, 'alumni' AS source
    FROM alumni_requests r 
    JOIN users u ON r.user_id = u.id
");

$stmt->execute();
$result1 = $stmt->get_result();
$studentRequests = $result1->fetch_all(MYSQLI_ASSOC);

$stmt2->execute();
$result2 = $stmt2->get_result();
$alumniRequests = $result2->fetch_all(MYSQLI_ASSOC);

// Combine and sort
$allRequests = array_merge($studentRequests, $alumniRequests);
usort($allRequests, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Requests - DNSC E-Request System</title>
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
                     <li class="nav-item mt-5">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">All Requests</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="btn btn-sm btn-outline-secondary">Welcome, <?= $_SESSION['full_name']; ?></span>
                </div>
            </div>

            <div class="card dashboard-card mb-4">
                <div class="card-header"><h5 class="mb-0">All Requests</h5></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="requestSearch" class="form-control" placeholder="Search by ID, name, type or status...">
                                <button class="btn btn-outline-success" type="button" id="searchBtn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-success filter-btn" data-filter="all">All</button>
                                <button type="button" class="btn btn-outline-success filter-btn" data-filter="pending">Pending</button>
                                <button type="button" class="btn btn-outline-success filter-btn" data-filter="approved">Approved</button>
                                <button type="button" class="btn btn-outline-success filter-btn" data-filter="completed">Completed</button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allRequests as $request): ?>
                                    <tr>
                                        <td><?= $request['id']; ?></td>
                                        <td><?= htmlspecialchars($request['full_name']); ?></td>
                                        <td><?= htmlspecialchars($request['request_type']); ?></td>
                                        <td><?= ucfirst($request['source']); ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadgeClass($request['status']); ?>">
                                                <?= ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y g:i A', strtotime($request['created_at'])); ?></td>
                                        <td>
                                            <a href="view_request.php?id=<?= $request['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($allRequests)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No requests found</td>
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
$(document).ready(function() {
    // Client-side filtering
    $("#requestSearch").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("table tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // Status filtering
    $(".filter-btn").click(function() {
        var value = $(this).data('filter').toLowerCase();
        
        $(".filter-btn").removeClass("active");
        $(this).addClass("active");
        
        if(value === 'all') {
            $("table tbody tr").show();
        } else {
            $("table tbody tr").filter(function() {
                $(this).toggle($(this).find("td:nth-child(5)").text().toLowerCase().indexOf(value) > -1)
            });
        }
    });
});
</script>
</body>
</html>
