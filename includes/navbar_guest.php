<?php

/**
 * Guest Navigation Bar Component
 * Navigation for non-logged-in users
 */

// Get current page for active state
$currentPage = $_GET['page'] ?? 'home';
?>

<!-- Guest Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="fas fa-home me-2"></i>
            RentFinder SL
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#guestNavbar" aria-controls="guestNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="guestNavbar">
            <!-- Guest Navigation (Left Side) -->
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

            <!-- Guest User Actions (Right Side) -->
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'login') ? 'active' : ''; ?>" href="index.php?page=login">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'register') ? 'active' : ''; ?>" href="index.php?page=register">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Add top margin to account for fixed navbar -->
<div style="margin-top: 76px;"></div>