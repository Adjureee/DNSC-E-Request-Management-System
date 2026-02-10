<?php
require_once '../config.php';
checkAlumniAuth();

$request_type = $details = "";
$request_type_err = $details_err = "";

$user_id = $_SESSION['user_id'];
$sql = "SELECT institute, program FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($institute, $program);
    $stmt->store_result();
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_submit'])) {
    $request_type = sanitize(trim($_POST["request_type"]));
    $details = sanitize(trim($_POST["details"]));

    $sql = "INSERT INTO alumni_requests (user_id, request_type, institute, program, details, status)
            VALUES (?, ?, ?, ?, ?, 'pending')";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issss", $_SESSION['user_id'], $request_type, $institute, $program, $details);
        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    let successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                });
            </script>";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .btn-primary {
            background-color: #498428;
            border-color: #498428;
        }
        .btn-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }
        .modal-header {
            background-color: #2d5516;
            color: white;
        }
        .modal-body p {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f1fdf6;
            border-left: 5px solid #2d5516;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Submit a New Request (Alumni)</h4>
                </div>
                <div class="card-body">
                    <form id="requestForm">
                        <div class="mb-3">
                            <label class="form-label">Request Type</label>
                            <select class="form-select" name="request_type" required>
                                <option value="">Select Request Type</option>
                               <option>Transcript of Records (Official)</option>
                                    <option>Diploma Request</option>
                                    <option>Request for Honorable Dismissal</option>
                                    <option>Request for CAV (Certification, Authentication, Verification)</option>
                                    <option>Authentication of Documents</option>
                                    <option>Certified True Copy of TOR / Diploma</option>
                                    <option>Request with SPA (Special Power of Attorney)</option>
                                    <option>Certificate of Graduation / Completion</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Additional Details</label>
                            <textarea class="form-control" name="details" rows="4" required placeholder="Provide any specific information..."></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-danger">Cancel</a>
                            <button type="button" class="btn btn-primary" id="previewBtn">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Your Request</h5>
                </div>
                <div class="modal-body">
                    <p><strong>Request Type:</strong> <span id="preview_request_type"></span></p>
                    <p><strong>Institute:</strong> <span id="preview_institute"><?php echo htmlspecialchars($institute); ?></span></p>
                    <p><strong>Program:</strong> <span id="preview_program"><?php echo htmlspecialchars($program); ?></span></p>
                    <p><strong>Details:</strong> <span id="preview_details"></span></p>

                    <input type="hidden" name="request_type" id="hidden_request_type">
                    <input type="hidden" name="institute" id="hidden_institute" value="<?php echo htmlspecialchars($institute); ?>">
                    <input type="hidden" name="program" id="hidden_program" value="<?php echo htmlspecialchars($program); ?>">
                    <input type="hidden" name="details" id="hidden_details">
                    <input type="hidden" name="confirm_submit" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Edit</button>
                    <button type="submit" class="btn btn-success">Confirm & Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Submitted</h5>
            </div>
            <div class="modal-body">
                <p>Your request has been submitted successfully!</p>
            </div>
            <div class="modal-footer">
                <a href="dashboard.php" class="btn btn-success">OK</a>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const form = document.forms["requestForm"];
    document.getElementById("previewBtn").addEventListener("click", function () {
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        document.getElementById("preview_request_type").innerText = form["request_type"].value;
        document.getElementById("preview_details").innerText = form["details"].value;

        // Hidden inputs
        document.getElementById("hidden_request_type").value = form["request_type"].value;
        document.getElementById("hidden_details").value = form["details"].value;

        const previewModal = new bootstrap.Modal(document.getElementById("previewModal"));
        previewModal.show();
    });
</script>
</body>
</html>
