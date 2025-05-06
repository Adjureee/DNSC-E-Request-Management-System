<?php
require_once '../config.php';
checkStudentAuth();

$request_type = $details = $institute = $program = $year_level = $semester = "";
$request_type_err = $details_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_submit'])) {
    $request_type = sanitize(trim($_POST["request_type"]));
    $institute = sanitize(trim($_POST["institute"]));
    $program = sanitize(trim($_POST["program"]));
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request - DNSC E-Request System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        /* Sidebar Styles */
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
        
        /* Button styles */
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
                            <a class="nav-link active" href="new_request.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                New Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_requests.php">
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
                    <h1 class="h2">Submit a New Request</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header text-white" style="background-color: #2d5516">
                        <h4 class="mb-0">Request Form</h4>
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

                            <div class="mb-3">
                                <label class="form-label">Institute</label>
                                <select class="form-select" name="institute" id="institute" required>
                                    <option value="">Select Institute</option>
                                    <option value="IC">Institute of Computing (IC)</option>
                                    <option value="IAAS">Institute of Aquatic and Applied Sciences (IAAS)</option>
                                    <option value="ILEGG">Institute of Leadership, Entrepreneurship, and Good Governance (ILEGG)</option>
                                    <option value="ITEd">Institute of Teacher Education (ITEd)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Program</label>
                                <select class="form-select" name="program" id="program" required>
                                    <option value="">Select Program</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Year Level</label>
                                <select class="form-select" name="year_level" id="year_level" required>
                                    <option value="">Select Year Level</option>
                                    <option>1st Year</option>
                                    <option>2nd Year</option>
                                    <option>3rd Year</option>
                                    <option>4th Year</option>
                                    <option>Graduate</option>
                                </select>
                            </div>

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
            </main>
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
                        <p><strong>Institute:</strong> <span id="preview_institute"></span></p>
                        <p><strong>Program:</strong> <span id="preview_program"></span></p>
                        <p><strong>Year Level:</strong> <span id="preview_year_level"></span></p>
                        <p id="semester_preview"><strong>Semester:</strong> <span id="preview_semester"></span></p>
                        <p><strong>Details:</strong> <span id="preview_details"></span></p>

                        <input type="hidden" name="request_type" id="hidden_request_type">
                        <input type="hidden" name="institute" id="hidden_institute">
                        <input type="hidden" name="program" id="hidden_program">
                        <input type="hidden" name="year_level" id="hidden_year_level">
                        <input type="hidden" name="semester" id="hidden_semester">
                        <input type="hidden" name="details" id="hidden_details">
                        <input type="hidden" name="confirm_submit" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Edit</button>
                        <button type="submit" class="btn" style="background-color: #2d5516; color: white;">Confirm & Submit</button>

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
                    <a href="dashboard.php" class="btn" style="background-color: #2d5516; color: white;">OK</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Form preview functionality
        const form = document.forms["requestForm"];
        document.getElementById("previewBtn").addEventListener("click", function () {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            document.getElementById("preview_request_type").innerText = form["request_type"].value;
            document.getElementById("preview_institute").innerText = form["institute"].value;
            document.getElementById("preview_program").innerText = form["program"].value;
            document.getElementById("preview_year_level").innerText = form["year_level"].value;
            document.getElementById("preview_details").innerText = form["details"].value;

            // Handle semester preview
            const yearLevel = form["year_level"].value;
            if (yearLevel === "Graduate") {
                document.getElementById("semester_preview").style.display = "none";
            } else {
                document.getElementById("preview_semester").innerText = form["semester"].value;
                document.getElementById("semester_preview").style.display = "block";
            }

            // Hidden inputs
            document.getElementById("hidden_request_type").value = form["request_type"].value;
            document.getElementById("hidden_institute").value = form["institute"].value;
            document.getElementById("hidden_program").value = form["program"].value;
            document.getElementById("hidden_year_level").value = form["year_level"].value;
            document.getElementById("hidden_semester").value = form["semester"].value;
            document.getElementById("hidden_details").value = form["details"].value;

            const previewModal = new bootstrap.Modal(document.getElementById("previewModal"));
            previewModal.show();
        });

        const programsByInstitute = {
            IC: ["BSIS", "BSIT"],
            IAAS: ["BSAF", "BSFAS", "BSFT", "BSMB"],
            ILEGG: ["BPA", "BSDRM", "BS ENTREP", "BSSW", "BSTM"],
            ITEd: ["BACOMM", "BSeD", "BTLEd", "BPEd"]
        };

        document.getElementById("institute").addEventListener("change", function () {
            const programSelect = document.getElementById("program");
            programSelect.innerHTML = '<option value="">Select Program</option>';
            const selected = this.value;
            if (programsByInstitute[selected]) {
                programsByInstitute[selected].forEach(program => {
                    const opt = document.createElement("option");
                    opt.value = program;
                    opt.innerText = program;
                    programSelect.appendChild(opt);
                });
            }
        });

        // Hide semester if "Graduate"
        document.getElementById("year_level").addEventListener("change", function () {
            const semesterField = document.getElementById("semester_field");
            const semesterSelect = document.getElementById("semester");

            if (this.value === "Graduate") {
                semesterField.style.display = "none";
                semesterSelect.removeAttribute("required");
                semesterSelect.value = "";
            } else {
                semesterField.style.display = "block";
                semesterSelect.setAttribute("required", "required");
            }
        });
    </script>
</body>
</html>
