<?php

/**
 * Owner Navigation Bar Component
 * Specialized navigation for property owner users
 */

// Get current page for active state
$currentPage = $_GET['page'] ?? 'home';
$userName = $_SESSION['user_name'] ?? '';
?>

<!-- Owner Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success fixed-top shadow-sm">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <i class="fas fa-home me-2"></i>
            RentFinder SL
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ownerNavbar" aria-controls="ownerNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="ownerNavbar">
            <!-- Owner Navigation (Left Side) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'home' || !isset($_GET['page'])) ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'properties') ? 'active' : ''; ?>" href="index.php?page=properties">
                        <i class="fas fa-building me-1"></i>Browse Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'my_properties') ? 'active' : ''; ?>" href="index.php?page=my_properties">
                        <i class="fas fa-building me-1"></i>My Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'add_property') ? 'active' : ''; ?>" href="index.php?page=add_property">
                        <i class="fas fa-plus me-1"></i>Add Property
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'rental_settlements') ? 'active' : ''; ?>" href="index.php?page=rental_settlements">
                        <i class="fas fa-file-invoice-dollar me-1"></i>Settlements
                    </a>
                </li>
            </ul>

            <!-- Owner User Actions (Right Side) -->
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="ownerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user me-1"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                        <span class="badge bg-warning ms-2">Owner</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ownerDropdown">
                        <li><a class="dropdown-item <?php echo ($currentPage === 'dashboard') ? 'active' : ''; ?>" href="index.php?page=dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'my_properties') ? 'active' : ''; ?>" href="index.php?page=my_properties">
                                <i class="fas fa-building me-2"></i>My Properties
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'add_property') ? 'active' : ''; ?>" href="index.php?page=add_property">
                                <i class="fas fa-plus me-2"></i>Add Property
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'rental_settlements') ? 'active' : ''; ?>" href="index.php?page=rental_settlements">
                                <i class="fas fa-file-invoice-dollar me-2"></i>Rental Settlements
                            </a></li>
                        <li><a class="dropdown-item <?php echo ($currentPage === 'visit_requests') ? 'active' : ''; ?>" href="index.php?page=visit_requests">
                                <i class="fas fa-calendar-check me-2"></i>Visit Requests
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