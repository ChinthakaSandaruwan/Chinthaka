<?php

/**
 * Customer (Tenant) Navigation Bar Component
 * Specialized navigation for customer/tenant users
 */

// Get current page for active state
$currentPage = $_GET['page'] ?? 'home';
$userName = $_SESSION['user_name'] ?? '';
?>

<!-- Customer Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="fas fa-home me-2"></i>
            RentFinder SL
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#customerNavbar" aria-controls="customerNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="customerNavbar">
            <!-- Customer Navigation (Left Side) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'home' || !isset($_GET['page'])) ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'properties') ? 'active' : ''; ?>" href="index.php?page=properties">
                        <i class="fas fa-building me-1"></i>Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'about') ? 'active' : ''; ?>" href="index.php?page=about">
                        <i class="fas fa-info-circle me-1"></i>About
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'contact') ? 'active' : ''; ?>" href="index.php?page=contact">
                        <i class="fas fa-envelope me-1"></i>Contact
                    </a>
                </li>
            </ul>

            <!-- Customer User Actions (Right Side) -->
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="customerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                        <span class="badge bg-success ms-2">Customer</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="customerDropdown">
                        <li><a class="dropdown-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="index.php?page=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'my_bookings') ? 'active' : ''; ?>" href="index.php?page=my_bookings">
                                <i class="fas fa-calendar-check me-2"></i>My Bookings
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'my_payments') ? 'active' : ''; ?>" href="index.php?page=my_payments">
                                <i class="fas fa-credit-card me-2"></i>My Payments
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'favorites') ? 'active' : ''; ?>" href="index.php?page=favorites">
                                <i class="fas fa-heart me-2"></i>Favorites
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'profile') ? 'active' : ''; ?>" href="index.php?page=profile">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="index.php?page=logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Add top margin to account for fixed navbar -->
<div style="margin-top: 76px;"></div>