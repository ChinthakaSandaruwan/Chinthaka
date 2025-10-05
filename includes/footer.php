<?php

/**
 * Common Footer
 * Shared footer for all pages
 */
?>

<!-- Footer -->
<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-home me-2"></i>RentFinder SL
                </h5>
                <p class="text-muted">
                    Your trusted partner in finding the perfect rental property in Sri Lanka.
                    We connect tenants with verified property owners for secure and transparent transactions.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="text-light"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <div class="col-lg-2 mb-4">
                <h6 class="fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="index.php?page=properties" class="text-muted text-decoration-none">Properties</a></li>
                    <li class="mb-2"><a href="index.php?page=about" class="text-muted text-decoration-none">About Us</a></li>
                    <li class="mb-2"><a href="index.php?page=contact" class="text-muted text-decoration-none">Contact</a></li>
                </ul>
            </div>

            <div class="col-lg-3 mb-4">
                <h6 class="fw-bold mb-3">Support</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php?page=help" class="text-muted text-decoration-none">Help Center</a></li>
                    <li class="mb-2"><a href="index.php?page=privacy" class="text-muted text-decoration-none">Privacy Policy</a></li>
                    <li class="mb-2"><a href="index.php?page=terms" class="text-muted text-decoration-none">Terms of Service</a></li>
                    <li class="mb-2"><a href="index.php?page=faq" class="text-muted text-decoration-none">FAQ</a></li>
                </ul>
            </div>

            <div class="col-lg-3 mb-4">
                <h6 class="fw-bold mb-3">Contact Info</h6>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                    <span class="text-muted">Colombo, Sri Lanka</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-phone me-2 text-primary"></i>
                    <span class="text-muted">+94 11 234 5678</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-envelope me-2 text-primary"></i>
                    <span class="text-muted">info@rentfinder.lk</span>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-clock me-2 text-primary"></i>
                    <span class="text-muted">24/7 Support</span>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    &copy; <?php echo date('Y'); ?> RentFinder SL. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0">
                    Made with <i class="fas fa-heart text-danger"></i> in Sri Lanka
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="public/js/main.js"></script>

<!-- Initialize Dropdowns -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize dropdowns
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
</body>

</html>