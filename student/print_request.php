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
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            background-color: #f8f8f8;
        }
        .receipt {
            width: 90%;
            max-width: 850px;
            margin: 20px auto;
            border: 1px solid #ddd;
            padding: 25px;
            background-color: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .logo-header {
            display: flex;
            align-items: center;
            width: 100%;
            margin-bottom: 15px;
        }
        .logo {
            max-width: 100px;
            max-height: 100px;
            margin-right: 15px;
        }
        .college-name {
            text-align: left;
            flex-grow: 1;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
        }
        .college-name h1 {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            line-height: 1.2;
            text-transform: uppercase;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
        }
        .college-name h2 {
            font-size: 20px;
            margin: 0;
            line-height: 1.2;
            text-transform: uppercase;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
        }
        .college-address {
            font-size: 14px;
            margin: 5px 0 0;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
        }
        .contact-info {
            text-align: right;
            font-size: 14px;
            line-height: 1.5;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            width: 100%;
            text-align: center;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
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
        
        /* Vision, Mission, Core Values Footer */
        .vision-mission-footer {
            margin-top: 30px;
            margin-left: -25px;
            margin-right: -25px;
            padding-left: 25px; 
            display: flex;
            background-color: #006400;
            color: white;
            font-family: 'Century Gothic', 'CenturyGothic', Arial, sans-serif;
            font-size: 11px;
            position: relative;
            width: calc(100% + 50px);
            overflow: hidden;
            min-height: 90px;
            box-sizing: border-box;
        }
        
        .vision-section {
            flex: 1;
            padding: 10px 8px;
            text-align: left;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .mission-section {
            flex: 1.5;
            padding: 10px 8px;
            text-align: left;
            border-right: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .values-section {
            flex: 0.8;
            padding: 10px 8px;
            text-align: left;
            padding-right: 150px; 
        }
        
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 13px;
            text-align: center;
        }
        
        .section-content {
            line-height: 1.3;
        }
        
        .logo-container {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 70%;
            background-color: white;
            padding: 0 15px;
            max-width: 140px;
            border-radius: 10px 0 0 10px;
        }
        
        .footer-logo {
            height: 50px;
            margin: 0 5px;
            background-color: white;
            padding: 5px;
        }
        
        @media print {
            .buttons {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
                background-color: white;
            }
            .receipt {
                width: 100%;
                max-width: none;
                border: none;
                margin: 0;
                padding: 15px 25px;
                box-shadow: none;
                page-break-after: always;
            }
            .vision-mission-footer {
                margin-left: 0;
                margin-right: 0;
                width: 100%;
                padding-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="logo-header">
                <img src="../assets/img/dnsc-logo.png" alt="DNSC Logo" class="logo">
                <div class="college-name">
                    <h2>DAVAO DEL NORTE</h2>
                    <h1>STATE COLLEGE</h1>
                    <p class="college-address">New Visayas, Panabo City, Davao del Norte, 8105</p>
                </div>
                <div class="contact-info">
                    <div>president@dnsc.edu.ph</div>
                    <div>dnsc.edu.ph</div>
                    <div>@davnorstatecollege</div>
                </div>
            </div>
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
        
        <!-- Vision, Mission, Core Values Footer -->
        <div class="vision-mission-footer">
            <div class="vision-section">
                <div class="section-title">VISION</div>
                <div class="section-content">
                    A premier Higher Institution in Agri-Fisheries and Socio-cultural Development in the ASEAN Region.
                </div>
            </div>
            
            <div class="mission-section">
                <div class="section-title">MISSION</div>
                <div class="section-content">
                    DNSC strives to produce competent human resource, generate, and utilize knowledge and technology, uphold good governance and quality management system for sustainable resources and resilient communities.
                </div>
            </div>
            
            <div class="values-section">
                <div class="section-title">CORE VALUES</div>
                <div class="section-content">
                    Excellence<br>
                    Integrity<br>
                    Innovativeness<br>
                    Stewardship<br>
                    Love of God and Country
                </div>
            </div>
            
            <div class="logo-container">
                <img src="../assets/img/bagong-pilipinas-logo.png" alt="Bagong Pilipinas Logo" class="footer-logo">
                <img src="../assets/img/dnsc-seal.png" alt="DNSC Seal" class="footer-logo">
            </div>
        </div>
        
        <div class="buttons">
            <button id="printBtn" class="btn">Print Receipt</button>
            <a href="view_request.php?id=<?php echo $id; ?>" class="btn btn-secondary">Back</a>
        </div>
    </div>
    
    <script>
        document.getElementById('printBtn').addEventListener('click', function() {
            
            window.print();
        });
    </script>
</body>
</html>
