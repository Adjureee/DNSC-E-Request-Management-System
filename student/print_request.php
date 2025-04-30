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

// Check if the request exists
if ($result->num_rows === 0) {
    redirect('my_requests.php');
}

// Fetch the data into the $request variable
$request = $result->fetch_assoc();

// Check if the request status is valid
if (!in_array($request['status'], ['approved', 'completed'])) {
    redirect('my_requests.php');
}

// Debug line - comment out in production
// var_dump($request);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - Request #<?php echo $id; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .receipt {
            max-width: 700px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .logo {
            max-width: 80px;
            max-height: 80px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
        .row {
            display: flex;
            margin-bottom: 10px;
        }
        .col-6 {
            flex: 0 0 50%;
        }
        .text-end {
            text-align: right;
        }
        .label {
            font-weight: bold;
        }
        .barcode {
            text-align: center;
            margin: 15px 0;
            font-family: monospace;
            font-size: 16px;
            letter-spacing: 2px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
        }
        .buttons {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        
        @media print {
            .buttons {
                display: none;
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
        <div class="header">
            <img src="../assets/img/logo.png" alt="DNSC Logo" class="logo">
            <h1>Davao del Norte State College</h1>
            <p>New Visayas, Panabo City, Davao del Norte</p>
            <div class="title">E-REQUEST RECEIPT</div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <span class="label">Request ID:</span> <?php echo isset($request['id']) ? $request['id'] : 'N/A'; ?>
            </div>
            <div class="col-6 text-end">
                <span class="label">Date:</span> 
                <?php echo isset($request['created_at']) ? date('F d, Y', strtotime($request['created_at'])) : 'N/A'; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <span class="label">Tracking Number:</span> 
                <?php echo isset($request['tracking_number']) ? $request['tracking_number'] : 'N/A'; ?>
            </div>
        </div>
        
        <div class="barcode">
            *<?php echo isset($request['tracking_number']) ? $request['tracking_number'] : 'N/A'; ?>*
        </div>
        
        <div class="row">
            <div class="col-6">
                <span class="label">Student Name:</span> 
                <?php echo isset($request['full_name']) ? htmlspecialchars($request['full_name']) : 'N/A'; ?>
            </div>
            <div class="col-6">
                <span class="label">Email:</span> 
                <?php echo isset($request['email']) ? htmlspecialchars($request['email']) : 'N/A'; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <span class="label">Request Type:</span> 
                <?php echo isset($request['request_type']) ? htmlspecialchars($request['request_type']) : 'N/A'; ?>
            </div>
            <div class="col-6">
                <span class="label">Status:</span> 
                <?php echo isset($request['status']) ? ucfirst($request['status']) : 'N/A'; ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-6">
                <span class="label">Pickup Date/Time:</span> 
                <?php 
                if (isset($request['pickup_datetime']) && $request['pickup_datetime']) {
                    echo date('F d, Y h:i A', strtotime($request['pickup_datetime']));
                } else {
                    echo 'To be determined';
                }
                ?>
            </div>
        </div>
        
        <?php if (isset($request['details']) && !empty($request['details'])): ?>
        <div class="row">
            <div class="col-6">
                <span class="label">Additional Details:</span><br>
                <?php echo nl2br(htmlspecialchars($request['details'])); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; background-color: #f8f9fa;">
            <strong>Important:</strong> Please present this receipt and a valid ID when picking up your requested document.
        </div>
        
        <div class="footer">
            <p>This is an electronically generated receipt and does not require a signature.</p>
            <p>For inquiries, please contact the Registrar's Office at registrar@dnsc.edu.ph</p>
        </div>
        
        <div class="buttons">
            <button id="printBtn" class="btn">Print Receipt</button>
            <a href="view_request.php?id=<?php echo $id; ?>" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <script>
        document.getElementById('printBtn').addEventListener('click', function() {
            // Keep the print function simple
            window.print();
        });
    </script>
</body>
</html>
