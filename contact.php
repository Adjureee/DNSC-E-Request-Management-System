<?php
session_start();
include 'config.php';


// Initialize feedback variables
$showModal = false;
$feedbackMessage = '';
$feedbackType = '';

// Get user_id and role from session (null and 'guest' if not logged in)
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'guest';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $user_id ? null : sanitize($_POST['name'] ?? '');
    $email = $user_id ? null : sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');

    if (($user_id === null && (empty($name) || empty($email))) || empty($message) || empty($subject)) {
        $feedbackMessage = "Please fill in all required fields.";
        $feedbackType = "error";
        $showModal = true;
    } else {
        $params = [
            $user_id,
            $name,
            $email,
            $phone,
            $subject,
            $message,
            $role
        ];

        $result = callProcedure($conn, "sp_SaveContactMessage", "issssss", $params);

        if ($result !== false) {
            // Instead of redirect, show success modal:
            $feedbackMessage = "Your message has been sent successfully.";
            $feedbackType = "success";
            $showModal = true;

            // Optionally clear POST data here to prevent resubmission
            $_POST = [];
        } else {
            $feedbackMessage = "There was a problem sending your message. Please try again.";
            $feedbackType = "error";
            $showModal = true;
        }
    }
}

?>

 



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Contact Us</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
    }
    .navbar {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            padding: 15px 0;
            transition: all 0.4s ease;
            background: linear-gradient(135deg, #2d5516 20%, #388e3c 100%) !important;
        }
        
        .navbar.scrolled {
            padding: 8px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            background: #2d5516 !important;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .navbar-brand:hover {
            transform: translateY(-2px);
        }
        
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
            filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.2));
        }
        
        .nav-item {
            margin: 0 5px;
            position: relative;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 10px 15px !important;
            border-radius: 6px;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.9) !important;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            color: white !important;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background-color: #fff;
            bottom: 5px;
            left: 15px;
            transition: width 0.3s ease;
        }
        
        .nav-link:hover::after, .nav-link.active::after {
            width: calc(100% - 30px);
        }
        .navbar .btn {
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .navbar .btn-success {
            background-color: #498428;
            border-color: #498428;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .navbar .btn-success:hover {
            background-color: #549a2d;
            border-color: #549a2d;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
        }
        
        .navbar .btn-outline-light {
            border-width: 2px;
        }
        
        .navbar .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* Mobile nav toggler */
        .navbar-toggler {
            border: none;
            padding: 10px;
            margin-right: 5px;
            position: relative;
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* For mobile */
        @media (max-width: 991px) {
            .navbar-collapse {
                background-color: #2d5516;
                padding: 15px;
                border-radius: 10px;
                margin-top: 10px;
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            }
            
            .nav-link::after {
                display: none;
            }
            
            .navbar .btn {
                margin-top: 10px;
                display: block;
                width: 100%;
            }
        }

    .contact-container {
      padding: 60px 15px;
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

 .submit-btn {
      background-color: #198754;
      border: none;
      color: #fff;
      padding: 10px 30px;
      font-weight: bold;
      border-radius: 25px;
      transition: background-color 0.3s ease;
    }

    .submit-btn:hover {
      background-color: #146c43;
    }

    @media (max-width: 768px) {
      .contact-container {
        padding: 30px 15px;
      }
    }
  </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="/index.php">
                <img src="assets/img/dnsc-logo.png" alt="DNSC Logo" class="d-inline-block align-text-top">
                DNSC E-Request
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php#team">Our Team</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-success" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-outline-light" href="register.php">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container contact-container">
  <div class="row">
    <!-- Contact Info -->
    <div class="col-md-5 mb-4">
      <h2 class="fw-bold">Contact Us</h2>
      <p>For urgent concerns, please reach out via the contact number or email above. Otherwise, leave your message here and we will respond shortly.</p>
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
    </div>
    <!-- Contact Form -->
    <div class="col-md-7">
      <form action="contact.php" method="POST">
  <div class="row mb-3">
    <div class="col">
      <label class="form-label">Name</label>
      <input type="text" class="form-control" name="name" required>
    </div>
    <div class="col">
      <label class="form-label">Email</label>
      <input type="email" class="form-control" name="email" required>
    </div>
  </div>
  <div class="mb-3">
    <label class="form-label">Phone (optional)</label>
    <input type="text" class="form-control" name="phone">
  </div>
  <div class="mb-3">
    <label class="form-label">Subject</label>
    <input type="text" class="form-control" name="subject">
  </div>
  <div class="mb-3">
    <label class="form-label">Message</label>
    <textarea class="form-control" name="message" rows="5" required></textarea>
  </div>
  <button type="submit" class="submit-btn">Submit</button>
</form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Feedback Modal -->
<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header <?php echo ($feedbackType === 'success') ? 'bg-success' : 'bg-danger'; ?> text-white rounded-top">
        <h5 class="modal-title" id="feedbackModalLabel">
          <?php echo ($feedbackType === 'success') ? 'Success' : 'Error'; ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <?php echo htmlspecialchars($feedbackMessage); ?>
      </div>
    </div>
  </div>
</div>
<?php if ($showModal): ?>
<script>
    const feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
    feedbackModal.show();

    <?php if ($feedbackType === 'success'): ?>
    // Optional auto-close and redirect after success
    setTimeout(() => {
        window.location.href = 'contact.php';
    }, 3000);
    <?php endif; ?>
</script>
<?php endif; ?>

</body>
</html>
