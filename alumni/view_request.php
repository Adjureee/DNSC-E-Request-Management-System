<?php
require_once '../config.php';
checkAlumniAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM alumni_requests WHERE id = ? AND user_id = ?");
;
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
    <title>View Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .request-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .timeline {
            list-style: none;
            padding-left: 30px;
            position: relative;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 9px;
            top: 0;
            width: 2px;
            height: 100%;
            background-color: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -31px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #dee2e6;
            z-index: 1;
        }
        .timeline-item.active::before {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-primary {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-primary:hover {
            background-color: #146c43;
            border-color: #146c43;
        }
        .card-header {
            background-color: #e9f7ef;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Request Details</h3>
        <a href="my_requests.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card request-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Request #<?php echo $request['id']; ?></h5>
                    <?php
                    $status = strtolower($request['status']);
                    $statusClass = match ($status) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'secondary',
                    };
                    ?>
                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($status); ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-4 fw-bold">Request Type:</div>
                        <div class="col-md-8"><?php echo htmlspecialchars($request['request_type']); ?></div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-4 fw-bold">Date Submitted:</div>
                        <div class="col-md-8"><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-4 fw-bold">Last Updated:</div>
                        <div class="col-md-8"><?php echo date('F d, Y h:i A', strtotime($request['updated_at'])); ?></div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-4 fw-bold">Tracking Number:</div>
                        <div class="col-md-8">
                            <?php echo $request['tracking_number'] ? htmlspecialchars($request['tracking_number']) : '<span class="text-muted">Not assigned yet</span>'; ?>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-4 fw-bold">Pickup Date/Time:</div>
                        <div class="col-md-8">
                            <?php echo $request['pickup_datetime'] ? date('F d, Y h:i A', strtotime($request['pickup_datetime'])) : '<span class="text-muted">Not scheduled yet</span>'; ?>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-4 fw-bold">Details:</div>
                        <div class="col-md-8"><?php echo nl2br(htmlspecialchars($request['details'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="col-md-4">
            <div class="card request-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Request Status</h5>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li class="timeline-item active">
                            <h6>Submitted</h6>
                            <p class="text-muted"><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></p>
                            <p>Your request has been submitted successfully.</p>
                        </li>
                        <li class="timeline-item <?php echo in_array($status, ['approved', 'completed', 'rejected']) ? 'active' : ''; ?>">
                            <h6>Processing</h6>
                            <?php if ($status === 'rejected'): ?>
                                <p class="text-muted"><?php echo date('M d, Y', strtotime($request['updated_at'])); ?></p>
                                <p class="text-danger">Your request was rejected. Contact the registrar for more info.</p>
                            <?php elseif ($status === 'approved' || $status === 'completed'): ?>
                                <p class="text-muted"><?php echo date('M d, Y', strtotime($request['updated_at'])); ?></p>
                                <p>Your request was approved and is being processed.</p>
                            <?php else: ?>
                                <p>Your request is waiting for approval.</p>
                            <?php endif; ?>
                        </li>
                        <li class="timeline-item <?php echo $status === 'completed' ? 'active' : ''; ?>">
                            <h6>Ready for Pickup</h6>
                            <?php if ($status === 'completed'): ?>
                                <p class="text-muted"><?php echo date('M d, Y', strtotime($request['updated_at'])); ?></p>
                                <p>Your document is ready for pickup.</p>
                                <?php if ($request['pickup_datetime']): ?>
                                    <p class="text-success">Pick up on <?php echo date('F d, Y h:i A', strtotime($request['pickup_datetime'])); ?></p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Your document is being prepared.</p>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
                <?php if ($status === 'approved' || $status === 'completed'): ?>
                    <div class="card-footer">
                        <a href="print_request.php?id=<?php echo $request['id']; ?>" target="_blank" class="btn btn-primary w-100">
                            <i class="fas fa-print me-1"></i> Print Receipt
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
