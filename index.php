<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } else {
        header('Location: student/dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNSC E-Request Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Improved Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #2d5516 20%, #4caf50 100%);
            color: white;
            padding: 100px 0 80px;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/img/pattern.png');
            opacity: 0.1;
            z-index: 0;
        }
        
        .hero-section .container {
            position: relative;
            z-index: 1;
        }
        
        .hero-title {
            font-weight: 800;
            margin-bottom: 25px;
            font-size: 3rem;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Enhanced Feature Cards */
        .feature-card {
            border-radius: 15px;
            border: none;
            transition: transform 0.4s, box-shadow 0.4s;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #388e3c;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        
        /* Team Section Improvements */
        .team-section {
            padding: 80px 0;
            background-color: #f9fafb;
        }
        
        .team-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.4s, box-shadow 0.4s;
            height: 100%;
            overflow: hidden;
        }
        
        .team-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
        
        .team-card .rounded-circle {
            transition: transform 0.3s;
            border: 4px solid #f8f9fa;
        }
        
        .team-card:hover .rounded-circle {
            transform: scale(1.05);
            border-color: #e8f5e9;
        }
        
        /* How It Works Section */
        .step-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 24px;
            top: 50px;
            bottom: 0;
            width: 2px;
            background-color: #e0e0e0;
        }
        
        .step-circle {
            background-color: #388e3c;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(56, 142, 60, 0.3);
        }
        
        /* Navbar Improvements */
        .navbar {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            transition: all 0.3s;
        }
        
        .navbar.scrolled {
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        /* Button Styling */
        .btn-success {
            background-color: #388e3c;
            border-color: #388e3c;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(56, 142, 60, 0.25);
            transition: all 0.3s;
        }
        
        .btn-success:hover {
            background-color: #2e7d32;
            border-color: #2e7d32;
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.3);
            transform: translateY(-2px);
        }
        
        .btn-outline-light {
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            border-width: 2px;
            transition: all 0.3s;
        }
        
        .btn-outline-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 255, 255, 0.2);
        }
        
        /* Section Headers */
        .section-header {
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-weight: 800;
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .section-header h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: #388e3c;
            border-radius: 10px;
        }
        
        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 50px;
            height: 50px;
            background-color: #388e3c;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            background-color: #2e7d32;
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        /* Footer Improvements */
        .footer {
            background-color: #263238;
            color: rgba(255, 255, 255, 0.8);
            padding: 60px 0 30px;
            margin-top: 80px;
            border-top: 5px solid #388e3c;
        }
        
        .footer a.text-white-50:hover {
            color: white !important;
            text-decoration: none;
        }
        
        .social-icon {
            transition: transform 0.3s;
            display: inline-block;
        }
        
        .social-icon:hover {
            transform: translateY(-3px);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .hero-section {
                padding: 70px 0 50px;
            }
            
            .hero-title {
                font-size: 2.2rem;
            }
            
            .section-header {
                margin-bottom: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-file-alt me-2"></i>
                DNSC E-Request
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#team">Our Team</a>
                    </li>
                    <li class="nav-item ms-lg-3">
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right" data-aos-duration="1000">
                    <h1 class="hero-title">DNSC E-Request Management System</h1>
                    <p class="lead mb-4 fw-normal" style="font-size: 1.25rem;">A streamlined solution for processing registrar-related documents and certificates for students and graduates.</p>
                    <a href="#features" class="btn btn-light btn-lg rounded-pill px-4 me-2">
                        <i class="fas fa-arrow-down me-2"></i>Discover More
                    </a>
                </div>
                <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                    <img src="assets/img/DNSC_thumbnail.png" alt="E-Request System" class="img-fluid rounded-lg shadow-lg" style="transform: perspective(1000px) rotateY(-10deg); max-width: 90%;">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container py-4">
            <div class="text-center section-header" data-aos="fade-up">
                <h2 class="fw-bold">System Features</h2>
                <p class="text-muted lead">Efficient document processing and tracking at your fingertips</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-file-alt feature-icon"></i>
                            <h4>Online Request Submission</h4>
                            <p class="text-muted">Submit requests for certificates, transcripts, and other documents online without visiting the office.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-clock feature-icon"></i>
                            <h4>Real-time Status Tracking</h4>
                            <p class="text-muted">Track the status of your requests in real-time, from submission to approval and completion.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-bell feature-icon"></i>
                            <h4>Notifications</h4>
                            <p class="text-muted">Receive notifications about request status updates, approvals, or rejections.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-history feature-icon"></i>
                            <h4>Transaction History</h4>
                            <p class="text-muted">Access a complete history of all your past and ongoing document requests.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="500">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-calendar-check feature-icon"></i>
                            <h4>Scheduled Pickup</h4>
                            <p class="text-muted">Get assigned pickup dates and times for collecting your requested documents.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="600">
                    <div class="card feature-card p-4">
                        <div class="text-center">
                            <i class="fas fa-shield-alt feature-icon"></i>
                            <h4>Secure Access</h4>
                            <p class="text-muted">Role-based secure access for students, graduates, and registrar staff.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-5 bg-light" id="how-it-works">
        <div class="container py-4">
            <div class="text-center section-header" data-aos="fade-up">
                <h2 class="fw-bold">How It Works</h2>
                <p class="text-muted lead">Simple steps to request and receive your documents</p>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm" data-aos="fade-up">
                        <div class="card-body p-4 p-lg-5">
                            <!-- Step 1 -->
                            <div class="step-item d-flex" data-aos="fade-up" data-aos-delay="100">
                                <div class="step-circle text-white me-4">
                                    <h4 class="m-0">1</h4>
                                </div>
                                <div>
                                    <h4>Login to your account</h4>
                                    <p class="text-muted">Access the system using your student credentials. If you don't have an account, contact the registrar's office.</p>
                                </div>
                            </div>
                            
                            <!-- Step 2 -->
                            <div class="step-item d-flex" data-aos="fade-up" data-aos-delay="200">
                                <div class="step-circle text-white me-4">
                                    <h4 class="m-0">2</h4>
                                </div>
                                <div>
                                    <h4>Select the document type</h4>
                                    <p class="text-muted">Choose from various document types such as Certificate of Enrollment, Certificate of Ratings, Academic Transcripts, etc.</p>
                                </div>
                            </div>
                            
                            <!-- Step 3 -->
                            <div class="step-item d-flex" data-aos="fade-up" data-aos-delay="300">
                                <div class="step-circle text-white me-4">
                                    <h4 class="m-0">3</h4>
                                </div>
                                <div>
                                    <h4>Submit your request</h4>
                                    <p class="text-muted">Fill in the required information and submit your request for processing.</p>
                                </div>
                            </div>
                            
                            <!-- Step 4 -->
                            <div class="step-item d-flex" data-aos="fade-up" data-aos-delay="400">
                                <div class="step-circle text-white me-4">
                                    <h4 class="m-0">4</h4>
                                </div>
                                <div>
                                    <h4>Pay at the cashier</h4>
                                    <p class="text-muted">Visit the cashier to make the payment for your requested documents.</p>
                                </div>
                            </div>
                            
                            <!-- Step 5 -->
                            <div class="step-item d-flex" data-aos="fade-up" data-aos-delay="500">
                                <div class="step-circle text-white me-4">
                                    <h4 class="m-0">5</h4>
                                </div>
                                <div>
                                    <h4>Collect your documents</h4>
                                    <p class="text-muted">Once your request is processed, you'll receive a notification with a pickup date and time. Visit the registrar's office to collect your documents.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-5 team-section" id="team">
        <div class="container py-4">
            <div class="text-center section-header" data-aos="fade-up">
                <h2 class="fw-bold">Meet Our Team</h2>
                <p class="text-muted lead">The Team behind the DNSC E-Request System</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="card team-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle overflow-hidden mx-auto mb-4" style="width: 140px; height: 140px; background-color: #e9ecef;">
                                <img src="assets/img/Duran.jpg" alt="Christian Duran" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <h5 class="card-title fw-bold">Christian Duran</h5>
                            <p class="card-text text-muted mb-3">Project Leader</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="https://www.facebook.com/christian.duran.827309" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-facebook"></i></a>
                                <a href="https://github.com/ChristInsanity" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-github"></i></a>
                                <a href="" class="btn btn-sm btn-outline-success rounded-circle"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="card team-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle overflow-hidden mx-auto mb-4" style="width: 140px; height: 140px; background-color: #e9ecef;">
                                <img src="assets/img/ako.jpg" alt="John Lyold C. Lozada" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <h5 class="card-title fw-bold">John Lyold C. Lozada</h5>
                            <p class="card-text text-muted mb-3">Backend Developer</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="https://www.facebook.com/DeJure12705" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-facebook"></i></a>
                                <a href="https://github.com/DeJure12705" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-github"></i></a>
                                <a href="#" class="btn btn-sm btn-outline-success rounded-circle"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="card team-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle overflow-hidden mx-auto mb-4" style="width: 140px; height: 140px; background-color: #e9ecef;">
                                <img src="assets/img/arjean1.jpg" alt="Arjean G. Logrosa" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <h5 class="card-title fw-bold">Arjean G. Logrosa</h5>
                            <p class="card-text text-muted mb-3">Frontend Developer</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="https://www.facebook.com/arjean.logrosa" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-facebook"></i></a>
                                <a href="https://github.com/aj-u3u" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-github"></i></a>
                                <a href="#" class="btn btn-sm btn-outline-success rounded-circle"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                    <div class="card team-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle overflow-hidden mx-auto mb-4" style="width: 140px; height: 140px; background-color: #e9ecef;">
                                <img src="assets/img/stephanie.jpg" alt="Stephanie Kate O. Losabia" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <h5 class="card-title fw-bold">Stephanie Kate O. Losabia</h5>
                            <p class="card-text text-muted mb-3">UI/UX Designer</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="https://www.facebook.com/ezraheli" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-facebook"></i></a>
                                <a href="https://github.com/Ezraheli" class="btn btn-sm btn-outline-success rounded-circle"><i class="fab fa-github"></i></a>
                                <a href="#" class="btn btn-sm btn-outline-success rounded-circle"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h5 class="text-white fw-bold mb-4">DNSC E-Request Management System</h5>
                    <p class="mb-4">A web-based platform designed to streamline registrar-related transactions for students and graduates.</p>
                    <div class="d-flex gap-3 mb-4">
                        <a href="#" class="social-icon text-white-50"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="social-icon text-white-50"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="social-icon text-white-50"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="social-icon text-white-50"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6 mb-4 mb-md-0">
                    <h5 class="text-white fw-bold mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#features" class="text-white-50"><i class="fas fa-chevron-right me-2"></i>Features</a></li>
                        <li class="mb-2"><a href="#how-it-works" class="text-white-50"><i class="fas fa-chevron-right me-2"></i>How It Works</a></li>
                        <li class="mb-2"><a href="#team" class="text-white-50"><i class="fas fa-chevron-right me-2"></i>Our Team</a></li>
                        <li class="mb-2"><a href="login.php" class="text-white-50"><i class="fas fa-chevron-right me-2"></i>Login</a></li>
                        <li><a href="register.php" class="text-white-50"><i class="fas fa-chevron-right me-2"></i>Register</a></li>
                    </ul>
                </div>
                <div class="col-md-3 col-sm-6">
                    <h5 class="text-white fw-bold mb-4">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fas fa-map-marker-alt me-2"></i>Davao del Norte State College</li>
                        <li class="mb-3"><i class="fas fa-phone me-2"></i>+63 951 229 7022</li>
                        <li class="mb-3"><i class="fas fa-envelope me-2"></i>DNSC_E-Request@dnsc.edu.ph</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 bg-light opacity-25">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> DNSC E-Request Management System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <p class="mb-0">Designed with <i class="fas fa-heart text-danger"></i> by Chanchan and Arjean</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <a href="#" class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialize AOS (Animate on Scroll)
        AOS.init({
            once: true,
            duration: 800
        });
        
        // Navbar scroll effect and back to top button
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const backToTop = document.querySelector('.back-to-top');
            
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
                backToTop.classList.add('active');
            } else {
                navbar.classList.remove('scrolled');
                backToTop.classList.remove('active');
            }
        });
        
        // Back to top functionality
        document.getElementById('backToTop').addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]:not(#backToTop)').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 70,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>
