<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registerbtn'])) {
    $full_name = trim($_POST['full_name']);
    $stud_id = trim($_POST['stud_id']);
    $institute = $_POST['institute'];
    $program = $_POST['program'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $pre_select_role = isset($_POST['pre_selected_role']) ? $_POST['pre_selected_role'] : '';
    $photo = $_FILES['photo'];

    $validationErrors = [];

    // Validate inputs
    if (empty($full_name)) $validationErrors[] = 'Full name is required.';
    if (empty($stud_id)) $validationErrors[] = 'Student ID is required.';
    if (empty($institute)) $validationErrors[] = 'Institute is required.';
    if (empty($program)) $validationErrors[] = 'Program is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $validationErrors[] = 'Valid email is required.';
    if (empty($password) || strlen($password) < 6) $validationErrors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $validationErrors[] = 'Passwords do not match.';
    if (empty($pre_select_role)) $validationErrors[] = 'Role is required.';
    if ($photo['error'] !== 0) $validationErrors[] = 'Photo upload failed.';

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $validationErrors[] = 'Email already exists.';
    $stmt->close();

    if (empty($validationErrors)) {
        // Upload photo
        $uploadDir = "uploads/";
        $photoName = uniqid() . '_' . basename($photo['name']);
        $targetFile = $uploadDir . $photoName;
        move_uploaded_file($photo['tmp_name'], $targetFile);

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (stud_id, full_name, institute, program, email, password, uploadphoto, verification_status, pre_select_role, role, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NULL, NOW())");
        $stmt->bind_param("ssssssss", $stud_id, $full_name, $institute, $program, $email, $hashed_password, $photoName, $pre_select_role);    

        if ($stmt->execute()) {
            $success = "Registration successful! Please wait for admin verification.";

            // Redirect to login page immediately after successful registration
            header("Location: login.php");
            exit(); // Make sure no further code is executed after the redirect
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $validationErrors);
    }
}
?>

<!-- HTML START -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - DNSC E-Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> 
        :root {
            --primary: #b3cc50;
            --secondary: #6f9733;
            --tertiary: #478026;
            --quaternary: #2c5315;
        }
        body {
            background: linear-gradient(to right, #b3cc50, #478026);      
        }
        .register-container {
            max-width: 550px;
            margin: 50px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #336A29;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            width: 100%;
            background-color: #336A29;
            border-color: #336A29;
        }
        .btn-primary:hover {
            background-color: #283618;
            border-color: #283618;
        }
        .btn-success {
            background-color: #336A29;
            border-color: #336A29;
        }
        .btn-success:hover {
            background-color: #283618;
            border-color: #283618;
        }
        a {
            color: #DDA15E;
        }
        a:hover {
            color: #BC6C25;
        }
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .loading {
            display: none;
            margin: 0 auto;
        }
        .error-list {
            padding-left: 20px;
            margin-bottom: 0;
        }
        #preview-img { 
            max-height: 150px; cursor:pointer;
            margin-top: 10px; display: none;
        }
        .modal-lg { 
            max-width: 450px;
        }
    </style>
</head>
<body>

<div class="container register-container">
    <div class="card">
        <div class="card-header text-center">
            <h4>DNSC E-Request Management System</h4>
            <h4>Register</h4>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Fullname</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="stud_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Institute</label>
                    <select name="institute" id="institute" class="form-control" required onchange="updatePrograms()">
                        <option value="" disabled selected>Select Institute</option>
                        <option value="IC">Institute of Computing (IC)</option>
                        <option value="IAAS">Institute of Aquatic and Applied ScienceS (IAAS)</option>
                        <option value="ILEGG">Institute of Leadership, Entreprenuership, and Good Governance (ILEGG)</option>
                        <option value="ITed">Institute of Teacher Education (ITed)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Program</label>
                    <select name="program" id="program" class="form-control" required>
                        <option value="" disabled selected>Select Program</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Upload Photo</label>
                    <input type="file" name="photo" class="form-control-file" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="pre_selected_role" class="form-control" required>
                        <option value="" disabled selected>Select Status</option>
                        <option value="student">Current Student</option>
                        <option value="alumni">Alumni/Graduate</option>
                    </select>
                </div>

                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#confirmModal">
                    Confirm
                </button>

                <!-- Confirmation Modal -->
                <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirm Submission</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to submit this registration form?
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="registerbtn" class="btn btn-success">Yes, Submit</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function updatePrograms() {
    const institute = document.getElementById('institute').value;
    const programSelect = document.getElementById('program');

    const programs = {
        IC: ['BSIT', 'BSIS'],
        IAAS: ['BSAF', 'BSFAS', 'BSFT', 'BSMB'],
        ILEGG: ['BPA', 'BSDRM', 'BSEntrep', 'BSSW', 'BSTM'],
        ITed: ['BAComm', 'BSEd - English', 'BSEd - Mathematics', 'BSEd - Science', 'BTLEd'],
    };

    programSelect.innerHTML = '<option value="" disabled selected>Select Program</option>';
    if (programs[institute]) {
        programs[institute].forEach(p => {
            const option = document.createElement('option');
            option.value = p;
            option.text = p;
            programSelect.appendChild(option);
        });
    }
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
