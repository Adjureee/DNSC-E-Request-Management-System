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
    $pre_select_role = $_POST['pre_selected_role'];
    $photo = $_FILES['photo'];

    $validationErrors = [];

    if (empty($full_name)) $validationErrors[] = 'Full name is required.';
    if (empty($stud_id)) $validationErrors[] = 'Student ID is required.';
    if (empty($institute)) $validationErrors[] = 'Institute is required.';
    if (empty($program)) $validationErrors[] = 'Program is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $validationErrors[] = 'Valid email is required.';
    if (empty($password) || strlen($password) < 6) $validationErrors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $validationErrors[] = 'Passwords do not match.';
    if (empty($pre_select_role)) $validationErrors[] = 'Role is required.';
    if ($photo['error'] !== 0) $validationErrors[] = 'Photo upload failed.';

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) $validationErrors[] = 'Email already exists.';
    $stmt->close();

    if (empty($validationErrors)) {
        $uploadDir = "uploads/";
        $photoName = uniqid() . '_' . basename($photo['name']);
        $targetFile = $uploadDir . $photoName;
        move_uploaded_file($photo['tmp_name'], $targetFile);

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (stud_id, full_name, institute, program, email, password, uploadphoto, verification_status, pre_select_role, role, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NULL, NOW())");
        $stmt->bind_param("ssssssss", $stud_id, $full_name, $institute, $program, $email, $hashed_password, $photoName, $pre_select_role);

        if ($stmt->execute()) {
            echo "<script>
                setTimeout(() => { window.location.href = 'login.php'; }, 3000);
            </script>";
            $success = "Registration successful! Redirecting to login in 3 seconds...";
        } else {
            $error = "Something went wrong. Please try again.";
        }
        $stmt->close();
    } else {
        $error = implode("<br>", $validationErrors);
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - DNSC E-Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
         :root {
            --primary: #2d5516;
            --secondary: #C1D95C;
            --tertiary: #498428;
         }
        body {
            background: linear-gradient(to right, #C1D95C, #498428);  
        }
        .register-container {
            max-width: 550px;
            margin: 25px auto ;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px #000000;
        }
        .card-header {
            background-color: #2d5516;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            width: 100%;
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-primary:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-success {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        .btn-success:hover {
            background-color: #2d5516;
            border-color: #2d5516;
        }
        a {
            color: #2d5516;
        }
        a:hover {
            color: #2d5516;
        }
        .form-control:focus {
            border-color: #2d5516;
            box-shadow: 0 0 0 0.25rem #198754;
        }
        .loading {
            display: none;
            margin: 0 auto;
        }
        .modal-lg { 
            max-width: 450px;
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="card">
            <div class="card-header text-center py-3">
                <h4>Register for DNSC E-Request System</h4>
            </div>
            <div class="card-body p-8">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-success">Go to Login</a>
                    </div>
                <?php else: ?>
                
                <form id="registerForm" method="POST" action="" novalidate>
            
<!-- wala pa na implement --> 
            <div class="mb-3">
                <label for="Account-type" class="form-label">Account Type</label>
                <select class="form-select" id="Account-type" name="account-type" required>
                    <option value="">-- Select Role --</option>
                    <option value="students">Student</option>
                    <option value="alumni">Alumni</option>
                </select>
            </div>

              <!-- Students Input -->
              <div class="mb-3" id="studentsInputWrapper" style="display: none;">
                <label for="students-role" class="form-label">Identification Number:</label>
                <input type="text" class="form-control" id="students-role" name="students-role" placeholder="Enter your Student ID" required>
            </div>
            <!-- Alumni Input -->
            <div class="mb-3" id="alumniInputWrapper" style="display: none;">
                <label for="alumni-role" class="form-label">Identification Number:</label>
                <input type="text" class="form-control" id="alumni-role" name="alumni-role" placeholder="Enter your Alumni ID" required>
            </div>
        
            <script>
                const roleSelect = document.getElementById('Account-type');
                const inputSections = {
                    students: document.getElementById('studentsInputWrapper'),
                    alumni: document.getElementById('alumniInputWrapper'),
                };
                roleSelect.addEventListener('change', function () {
                    const selected = this.value;
                    for (const key in inputSections) {
                        inputSections[key].style.display = (key === selected) ? 'block' : 'none';
                    }
                });
            </script>
<!-- wala pa na implement -->

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($formData['full_name']); ?>" required>
                        <div class="invalid-feedback">Please enter your full name</div>
                    </div>
                </div>

                <!-- Image Modal -->
                <div class="modal fade" id="imageModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Photo Preview</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="modal-img" src="" class="img-fluid" />
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
        IC: ['BSIT', 'BSCS'],
        IE: ['BSCE', 'BSEE'],
        IT: ['BSEd Math', 'BSEd English'],
        IAS: ['AB English', 'BS Biology'],
        IM: ['BSBA', 'BS Accountancy']
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

function validateAndShowModal() {
    const form = document.getElementById('registerForm');
    if (form.checkValidity()) {
        $('#confirmModal').modal('show');
    } else {
        form.reportValidity();
    }
}

function previewPhoto(input) {
    const preview = document.getElementById('preview-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openImageModal() {
    const src = document.getElementById('preview-img').src;
    document.getElementById('modal-img').src = src;
    $('#imageModal').modal('show');
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
