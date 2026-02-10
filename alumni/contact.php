<?php
require_once '../config.php';
checkAlumniAuth();

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

$name = $user['full_name'] ?? '';
$email = $user['email'] ?? '';

$feedbackMessage = '';
$feedbackType = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone   = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $feedbackMessage = "Subject and Message are required.";
        $feedbackType = "error";
    } else {
        // Use the universal contact message stored procedure
       $result = callProcedure($conn, 'sp_SaveContactMessage', 'sssss', [
    $name,
    $email,
    $phone,
    $subject,
    $message
]);


        if ($result !== false) {
            $feedbackMessage = "Your message has been sent successfully!";
            $feedbackType = "success";
        } else {
            $feedbackMessage = "There was a problem sending your message. Please try again.";
            $feedbackType = "error";
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact Admin - DNSC E-Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #2d5516;
            color: white;
        }
        .custom-topbar {
         background-color: #2d5516;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
        }
        .contact-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
            padding: 30px;
        }
        .submit-btn {
            background-color: #198754;
            border: none;
            color: #fff;
            padding: 10px 30px;
            font-weight: 600;
            border-radius: 30px;
            transition: background-color 0.3s ease;
        }
        .submit-btn:hover {
            background-color: #146c43;
        }
        .contact-info i {
            color: #198754;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .contact-info li {
            margin-bottom: 12px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
        }
        .form-label {
            font-weight: 500;
        }
        h2.page-title {
            font-weight: 600;
            color: #2d5516;
        }
         .contact-info li {
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  transition: color 0.3s, transform 0.3s;
}

.contact-info li:hover {
  color: #198754;
  transform: translateX(5px);
}

.contact-info li a {
  color: #198754;
  transition: color 0.3s;
}

.contact-info li:hover a {
  color: #198754;
  text-decoration: underline;
}

.contact-info i {
  color: #198754;
  font-size: 20px;
  margin-right: 10px;
  transition: color 0.3s;
}

    </style>
</head>
<body>
    <!-- Topbar/Header -->
<nav class="navbar navbar-expand-lg navbar-dark custom-topbar px-3">
  <div class="container-fluid d-flex justify-content-between align-items-center">
        
        <!-- Left section: Brand + Toggle -->
        <div class="d-flex align-items-center">
            <!-- Sidebar toggle -->
            <button class="btn btn-outline-light me-2" id="sidebarToggleTop">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Brand -->
            <a class="navbar-brand mb-0 h1" href="#">DNSC E-Request System</a>
        </div>

    <!-- Profile dropdown -->
    <div class="dropdown ms-auto d-none d-lg-block">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
     <!-- Profile dropdown for smaller screens (visible when navbar is collapsed) -->
     <div class="dropdown d-block d-lg-none">
        <button class="btn btn-outline-light dropdown-toggle btn-sm" type="button" id="userDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
            Welcome, <?php echo $_SESSION['full_name']; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownMobile">
            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid" id="layoutRow">
    <div class="row">
        <!-- Sidebar -->
         <div class="col-md-3 col-lg-2 sidebar collapse" id="sidebarMenu">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5>Alumni Portal</h5>
                     
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="new_request.php">
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
                     <li class="nav-item">
                            <a class="nav-link active" href="contact.php">
                                <i class="fas fa-envelope me-2">
                                </i> Contact Admin
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        <!-- Main Content -->
        <main class="col-12 px-md-4 py-4" id="mainContent">
            <h2 class="page-title mb-4">Contact Admin</h2>

            <div class="row g-4">
                <!-- Contact Information -->
                <div class="col-md-5">
                    <div class="contact-card h-100">
                        <h5 class="mb-3">Admin Contact Details</h5>
                        <ul class="list-unstyled contact-info">
  <li>
    <i class="fas fa-phone"></i> 
    <a href="tel:+09088184444" class="text-dark text-decoration-none">09088184444</a>
  </li>
  <li>
    <i class="fas fa-envelope"></i>
    <a href="https://mail.google.com/mail/?view=cm&fs=1&to=Duran.chrsitiandave@dnsc.edu.ph" target="_blank" class="text-dark text-decoration-none">Duran.chrsitiandave@dnsc.edu.ph</a>
  </li>
  <li>
    <i class="fas fa-map-marker-alt"></i>
    <a href="https://www.google.com/maps/place/Davao+del+Norte+State+College/@7.3135965,125.6677585,17z/data=!3m1!4b1!4m6!3m5!1s0x32f945c8d1dc5b25:0x7f00ac73405f51a6!8m2!3d7.3135965!4d125.6703334!16s%2Fm%2F09gp9zy?entry=ttu" 
       target="_blank" 
       class="text-dark text-decoration-none">
      8M7C+C4P, Panabo, Davao del Norte
    </a>
  </li>
</ul>
                        <p class="mt-4 text-muted small">For urgent concerns, please reach out via the contact number or email above. Otherwise, leave your message here and we will respond shortly.</p>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="col-md-7">
                    <div class="contact-card">
                        <form method="POST" action="contact.php">
                          <!-- <div class="mb-3">
                              <label class="form-label">Phone (optional)</label>
                              <input type="text" name="phone" class="form-control">
                           </div> -->
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" placeholder="Subject of your message" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="6" placeholder="Write your message here..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="submit-btn">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal -->
<!-- Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow rounded-4">
      <div class="modal-header text-white" style="background: linear-gradient(135deg, #2d5516, #198754); border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
        <h5 class="modal-title w-100 text-center" id="feedbackModalLabel">
          <?php echo ($feedbackType === 'success') ? '<i class="fas fa-check-circle me-2"></i>Success' : '<i class="fas fa-exclamation-circle me-2"></i>Error'; ?>
        </h5>
      </div>
      <div class="modal-body text-center py-4 px-3">
        <p class="mb-0" style="font-size: 0.95rem;"><?php echo $feedbackMessage; ?></p>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
     document.getElementById('sidebarToggleTop').addEventListener('click', function () {
        var sidebar = document.getElementById('sidebarMenu');
        var main = document.getElementById('mainContent');

        sidebar.classList.toggle('show');

        if (sidebar.classList.contains('show')) {
            main.classList.remove('col-12');
            main.classList.add('col-md-9', 'col-lg-10');
        } else {
            main.classList.remove('col-md-9', 'col-lg-10');
            main.classList.add('col-12');
        }
    });
</script>

<?php if (!empty($feedbackMessage)): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
        feedbackModal.show();

        <?php if ($feedbackType === 'success'): ?>
        // Auto-redirect after 3 seconds
        setTimeout(() => {
            window.location.href = 'contact.php';
        }, 3000);
        <?php endif; ?>
    });
</script>
<?php endif; ?>

</body>
</html>

</body>
</html>
