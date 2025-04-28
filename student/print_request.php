<?php
require_once '../config.php';
checkStudentAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('dashboard.php');
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get request details - make sure it belongs to the current user
$stmt = $conn->prepare("SELECT r.*, u.full_name, u.email FROM requests r JOIN users u ON r.user_id = u.id WHERE r.id = ? AND r.user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0 || !in_array($result->fetch_assoc()['status'], ['approved', 'completed'])) {
    redirect('my_requests.php');
}

$request = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Receipt - Request #<?php echo $id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #d1e7dd;
            padding: 20px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #d1e7dd;
            padding-bottom: 10px;
            color: #198754;
        }
        .receipt-body {
            margin-bottom: 20px;
        }
        .receipt-footer {
            margin-top: 40px;
            border-top: 1px solid #d1e7dd;
            padding-top: 10px;
            text-align: center;
            font-size: 0.8em;
            color: #198754;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .receipt-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #198754;
        }
        .field-label {
            font-weight: bold;
            color: #198754;
        }
        .barcode {
            text-align: center;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            letter-spacing: 2px;
            color: #198754;
        }
        .print-only {
            display: none;
        }
        .alert-info {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }
        .btn-primary {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-primary:hover {
            background-color: #146c43;
            border-color: #146c43;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5c636a;
            border-color: #565e64;
        }
        @media print {
            .no-print {
                display: none;
            }
            .print-only {
                display: block;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .receipt {
                border: none;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <div class="text-center">
                <!-- Placeholder for logo -->
                <img src="../assets/img/logo.png" alt="DNSC Logo" class="logo">
                <h1>Davao del Norte State College</h1>
                <p>New Visayas, Panabo City, Davao del Norte</p>
                <div class="receipt-title">E-REQUEST RECEIPT</div>
            </div>
        </div>
        
        <div class="receipt-body">
            <div class="row mb-3">
                <div class="col-6">
                    <span class="field-label">Request ID:</span> <?php echo $request['id']; ?>
                </div>
                <div class="col-6 text-end">
                    <span class="field-label">Date Submitted:</span> <?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <span class="field-label">Tracking Number:</span> <?php echo $request['tracking_number']; ?>
                </div>
            </div>
            
            <div class="barcode">
                *<?php echo $request['tracking_number']; ?>*
            </div>
            
            <div class="row mb-3">
                <div class="col-6">
                    <span class="field-label">Student Name:</span> <?php echo htmlspecialchars($request['full_name']); ?>
                </div>
                <div class="col-6">
                    <span class="field-label">Email:</span> <?php echo htmlspecialchars($request['email']); ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-6">
                    <span class="field-label">Request Type:</span> <?php echo htmlspecialchars($request['request_type']); ?>
                </div>
                <div class="col-6">
                    <span class="field-label">Status:</span> <?php echo ucfirst($request['status']); ?>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-12">
                    <span class="field-label">Pickup Date/Time:</span> 
                    <?php echo $request['pickup_datetime'] ? date('F d, Y h:i A', strtotime($request['pickup_datetime'])) : 'To be determined'; ?>
                </div>
            </div>
            
            <?php if ($request['details']): ?>
            <div class="row mb-3">
                <div class="col-12">
                    <span class="field-label">Additional Details:</span> 
                    <p><?php echo nl2br(htmlspecialchars($request['details'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="row mt-5">
                <div class="col-12">
                    <div class="alert alert-info">
                        <strong>Important:</strong> Please present this receipt and a valid ID when picking up your requested document.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p>This is an electronically generated receipt and does not require a signature.</p>
            <p>For inquiries, please contact the Registrar's Office at registrar@dnsc.edu.ph</p>
            <p>Date Printed: <?php echo date('F d, Y h:i A'); ?></p>
        </div>
        
        <div class="mt-4 text-center no-print">
            <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
            <a href="view_request.php?id=<?php echo $id; ?>" class="btn btn-secondary">Back to Request</a>
        </div>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
