<?php
require_once '../config.php';
checkStudentAuth();

$request_type = $details = $year_level = $semester = "";
$request_type_err = $details_err = "";

// Fetch the logged-in user's institute and program
$user_id = $_SESSION['user_id'];
$sql = "SELECT institute, program FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($institute, $program);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_submit'])) {
    $request_type = sanitize(trim($_POST["request_type"]));
    $year_level = sanitize(trim($_POST["year_level"]));
    $semester = isset($_POST["semester"]) ? sanitize(trim($_POST["semester"])) : "";
    $details = sanitize(trim($_POST["details"]));

    $sql = "INSERT INTO requests (user_id, request_type, institute, program, year_level, semester, details, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issssss", $_SESSION['user_id'], $request_type, $institute, $program, $year_level, $semester, $details);
        if ($stmt->execute()) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    let successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();
                });
            </script>";
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
        :root {
            --primary: #b3cc50;
            --secondary: #6f9733;
            --tertiary: #478026;
            --quaternary: #2c5315;
        }
        body {
            background: #e9f7ef;   
        }
        .btn-primary {
            background-color: var(--quaternary);
            border-color:var(--tertiary);
        }
        .btn-primary:hover {
            background-color: var(--quaternary);
            border-color: var(--primary);
        }
        .btn-danger {
            color: #dc3545 ;
            background-color: white;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }
        .modal-header {
            background-color: var(--quaternary);
            color: white;
        }
        .modal-body p {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f1fdf6;
            border-left: 5px solid var(--quaternary);
        } 
        .card-header h4 {
            color: white; /* optional: change text color */
            padding: 10px; /* optional: add padding */
            border-radius: 4px; /* optional: rounded corners */
        }
       
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Submit a New Request</h4>
                </div>
                <div class="card-body">
                    <form id="requestForm">
                        <div class="mb-3">
                            <label class="form-label">Request Type</label>
                            <select class="form-select" name="request_type" required>
                                <option value="">Select Request Type</option>
                                <option>Certificate of Enrollment</option>
                                <option>Certificate of Grades</option>
                                <option>Transcript of Records</option>
                                <option>Certificate of Good Moral Character</option>
                                <option>Diploma Request</option>
                                <option>Authentication of Documents</option>
                                <option>Other</option>
                            </select>
                        </div>
                        
                        <!-- Year Level -->
                        <div class="mb-3">
                            <label class="form-label">Year Level</label>
                            <select class="form-select" name="year_level" id="year_level" required>
                                <option value="">Select Year Level</option>
                                <option>1st Year</option>
                                <option>2nd Year</option>
                                <option>3rd Year</option>
                                <option>4th Year</option>
                            </select>
                        </div>

                        <!-- Semester -->
                        <div class="mb-3" id="semester_field">
                            <label class="form-label">Semester</label>
                            <select class="form-select" name="semester" id="semester" required>
                                <option value="">Select Semester</option>
                                <option>1st Semester</option>
                                <option>2nd Semester</option>
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
                    <p><strong>Year Level:</strong> <span id="preview_year_level"></span></p>
                    <p id="semester_preview"><strong>Semester:</strong> <span id="preview_semester"></span></p>
                    <p><strong>Details:</strong> <span id="preview_details"></span></p>

                    <input type="hidden" name="request_type" id="hidden_request_type">
                    <input type="hidden" name="institute" id="hidden_institute" value="<?php echo htmlspecialchars($institute); ?>">
                    <input type="hidden" name="program" id="hidden_program" value="<?php echo htmlspecialchars($program); ?>">
                    <input type="hidden" name="year_level" id="hidden_year_level">
                    <input type="hidden" name="semester" id="hidden_semester">
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
        document.getElementById("preview_year_level").innerText = form["year_level"].value;
        document.getElementById("preview_details").innerText = form["details"].value;
        document.getElementById("preview_semester").innerText = form["semester"].value;
        document.getElementById("semester_preview").style.display = "block";

        // Hidden inputs
        document.getElementById("hidden_request_type").value = form["request_type"].value;
        document.getElementById("hidden_year_level").value = form["year_level"].value;
        document.getElementById("hidden_semester").value = form["semester"].value;
        document.getElementById("hidden_details").value = form["details"].value;

        const previewModal = new bootstrap.Modal(document.getElementById("previewModal"));
        previewModal.show();
    });

</script>
</body>
</html>
