<?php
// Include config file
require_once '../config.php';

// Check if the user is logged in and is a student
checkStudentAuth();

// Define variables and initialize with empty values
$request_type = $details = "";
$request_type_err = $details_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate request type
    if (empty(trim($_POST["request_type"]))) {
        $request_type_err = "Please select a request type.";
    } else {
        $request_type = sanitize(trim($_POST["request_type"]));
    }

    // Validate details
    if (empty(trim($_POST["details"]))) {
        $details_err = "Please provide additional details.";
    } else {
        $details = sanitize(trim($_POST["details"]));
    }

    // Check for errors before inserting in database
    if (empty($request_type_err) && empty($details_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO requests (user_id, request_type, details, status) VALUES (?, ?, ?, 'Pending')";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("iss", $_SESSION['user_id'], $request_type, $details);

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Request submitted successfully, redirect to request list page
                redirect('request_list.php');
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #198754;
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
        .form-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #198754;
            border-color: #198754;
        }
        .btn-primary:hover {
            background-color: #146c43;
            border-color: #146c43;
        }
        .btn-outline-primary {
            color: #198754;
            border-color: #198754;
        }
        .btn-outline-primary:hover {
            background-color: #198754;
            border-color: #198754;
            color: white;
        }
        .card-header {
            background-color: #e9f7ef;
            border-bottom: 1px solid #d1e7dd;
        }
        .alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Submit a New Request</h4>
                    </div>
                    <div class="card-body">
                        <form id="requestForm" action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="request_type" class="form-label">Request Type</label>
                                <select class="form-select" id="request_type" name="request_type">
                                    <option value="">Select Request Type</option>
                                    <option value="Certificate of Enrollment">Certificate of Enrollment</option>
                                    <option value="Certificate of Grades">Certificate of Grades</option>
                                    <option value="Transcript of Records">Transcript of Records</option>
                                    <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
                                    <option value="Diploma Request">Diploma Request</option>
                                    <option value="Authentication of Documents">Authentication of Documents</option>
                                    <option value="Other">Other (Please specify in details)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="details" class="form-label">Additional Details</label>
                                <textarea class="form-control" id="details" name="details" rows="5" placeholder="Please provide any specific information about your request..."></textarea>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Client-side validation if needed
            $('#requestForm').on('submit', function(e) {
                // You can add client-side validation here if needed
            });
        });
    </script>
</body>
</html>