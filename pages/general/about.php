<?php
// Start output buffering to prevent headers already sent errors
ob_start();

// Start session first to avoid headers already sent error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Unified Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="py-5 bg-primary text-white" style="margin-top: 76px;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">About RentFinder SL</h1>
                    <p class="lead mb-4">
                        We're revolutionizing the rental property market in Sri Lanka by connecting tenants with verified property owners through a secure, transparent, and user-friendly platform.
                    </p>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="fas fa-building fa-5x text-warning"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="py-5">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-bullseye fa-3x text-primary"></i>
                            </div>
                            <h3 class="fw-bold text-center mb-4">Our Mission</h3>
                            <p class="text-muted text-center">
                                To simplify the rental property search process in Sri Lanka by providing a trusted platform that connects tenants with verified property owners, ensuring secure transactions and transparent property listings.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-eye fa-3x text-success"></i>
                            </div>
                            <h3 class="fw-bold text-center mb-4">Our Vision</h3>
                            <p class="text-muted text-center">
                                To become Sri Lanka's leading rental property platform, making it easier for everyone to find their perfect home while providing property owners with a reliable way to manage their rental business.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Why Choose RentFinder SL?</h2>
                    <p class="lead text-muted">We provide comprehensive solutions for all your rental property needs</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h4>Verified Properties</h4>
                        <p class="text-muted">All properties are thoroughly verified by our team to ensure authenticity and quality standards.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-credit-card fa-3x text-success"></i>
                        </div>
                        <h4>Secure Payments</h4>
                        <p class="text-muted">Tokenized card transactions with recurring billing for hassle-free and secure payments.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-calendar-check fa-3x text-warning"></i>
                        </div>
                        <h4>Easy Scheduling</h4>
                        <p class="text-muted">Request and schedule property visits with just a few clicks, making the process convenient.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-search fa-3x text-info"></i>
                        </div>
                        <h4>Advanced Search</h4>
                        <p class="text-muted">Powerful search filters to help you find the perfect property based on your specific requirements.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-mobile-alt fa-3x text-danger"></i>
                        </div>
                        <h4>Mobile SMS OTP</h4>
                        <p class="text-muted">Secure registration and login using SMS OTP verification for enhanced security.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center p-4">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-headset fa-3x text-secondary"></i>
                        </div>
                        <h4>24/7 Support</h4>
                        <p class="text-muted">Round-the-clock customer support to assist you with any queries or concerns.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold mb-3">Our Team</h2>
                    <p class="lead text-muted">Meet the dedicated professionals behind RentFinder SL</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body p-4">
                            <div class="team-avatar mb-3">
                                <i class="fas fa-user-circle fa-4x text-primary"></i>
                            </div>
                            <h5 class="fw-bold">Chinthaka Perera</h5>
                            <p class="text-muted">Founder & CEO</p>
                            <p class="small text-muted">Visionary leader with 10+ years in real estate technology</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body p-4">
                            <div class="team-avatar mb-3">
                                <i class="fas fa-user-circle fa-4x text-success"></i>
                            </div>
                            <h5 class="fw-bold">Sarah Fernando</h5>
                            <p class="text-muted">CTO</p>
                            <p class="small text-muted">Technology expert specializing in secure payment systems</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm text-center">
                        <div class="card-body p-4">
                            <div class="team-avatar mb-3">
                                <i class="fas fa-user-circle fa-4x text-warning"></i>
                            </div>
                            <h5 class="fw-bold">Rajesh Kumar</h5>
                            <p class="text-muted">Head of Operations</p>
                            <p class="small text-muted">Operations specialist ensuring smooth user experience</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <h2 class="display-4 fw-bold">500+</h2>
                        <p class="lead">Properties Listed</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <h2 class="display-4 fw-bold">1000+</h2>
                        <p class="lead">Happy Customers</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <h2 class="display-4 fw-bold">50+</h2>
                        <p class="lead">Cities Covered</p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-item">
                        <h2 class="display-4 fw-bold">99%</h2>
                        <p class="lead">Customer Satisfaction</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Info -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold mb-4">Get in Touch</h2>
                    <p class="lead text-muted mb-5">
                        Have questions or need assistance? We're here to help you find your perfect rental property.
                    </p>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="contact-item">
                                <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                                <h5>Phone</h5>
                                <p class="text-muted">+94 11 234 5678</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="contact-item">
                                <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                                <h5>Email</h5>
                                <p class="text-muted">info@rentfinder.lk</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                                <h5>Address</h5>
                                <p class="text-muted">Colombo, Sri Lanka</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>RentFinder SL</h5>
                    <p class="text-muted">Your trusted partner for finding rental properties in Sri Lanka.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 mb-4">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php?page=properties" class="text-muted text-decoration-none">Properties</a></li>
                        <li><a href="index.php?page=about" class="text-muted text-decoration-none">About Us</a></li>
                        <li><a href="index.php?page=contact" class="text-muted text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php?page=help" class="text-muted text-decoration-none">Help Center</a></li>
                        <li><a href="index.php?page=privacy" class="text-muted text-decoration-none">Privacy Policy</a></li>
                        <li><a href="index.php?page=terms" class="text-muted text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h6>Contact Info</h6>
                    <p class="text-muted mb-1">
                        <i class="fas fa-phone me-2"></i>+94 11 234 5678
                    </p>
                    <p class="text-muted mb-1">
                        <i class="fas fa-envelope me-2"></i>info@rentfinder.lk
                    </p>
                    <p class="text-muted">
                        <i class="fas fa-map-marker-alt me-2"></i>Colombo, Sri Lanka
                    </p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 RentFinder SL. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">Made with <i class="fas fa-heart text-danger"></i> in Sri Lanka</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Initialize dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
</body>

</html>