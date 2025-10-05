<?php

/**
 * Admin Navigation Bar Component
 * Specialized navigation for admin users
 */

// Get current page for active state
$currentPage = $_GET['page'] ?? 'admin_dashboard';
$isAdminPanel = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
$userName = $_SESSION['user_name'] ?? '';
?>

<!-- Admin Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger fixed-top shadow-sm">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?php echo $isAdminPanel ? '../index.php' : 'index.php'; ?>">
            <i class="fas fa-user-shield me-2"></i>
            RentFinder SL Admin
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <!-- Admin Navigation (Left Side) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin_dashboard') ? 'active' : ''; ?>" href="index.php?page=admin_dashboard">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin_properties') ? 'active' : ''; ?>" href="index.php?page=admin_properties">
                        <i class="fas fa-building me-1"></i>Properties
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin_users') ? 'active' : ''; ?>" href="index.php?page=admin_users">
                        <i class="fas fa-users me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin_payments') ? 'active' : ''; ?>" href="index.php?page=admin_payments">
                        <i class="fas fa-credit-card me-1"></i>Payments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin_commissions') ? 'active' : ''; ?>" href="index.php?page=admin_commissions">
                        <i class="fas fa-percentage me-1"></i>Commissions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($currentPage === 'admin_reports') ? 'active' : ''; ?>" href="index.php?page=admin_reports">
                        <i class="fas fa-chart-bar me-1"></i>Reports
                    </a>
                </li>
            </ul>

            <!-- Admin User Actions (Right Side) -->
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-shield me-1"></i>
                        <span><?php echo htmlspecialchars($userName); ?></span>
                        <span class="badge bg-warning ms-2">Admin</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="<?php echo $isAdminPanel ? '../index.php?page=dashboard' : 'index.php?page=dashboard'; ?>">
                                <i class="fas fa-home me-2"></i>User Dashboard
                            </a></li>
                        <li><a class="dropdown-item" href="<?php echo $isAdminPanel ? '../index.php?page=profile' : 'index.php?page=profile'; ?>">
                                <i class="fas fa-user me-2"></i>Profile
                            </a></li>
                        <li><a class="dropdown-item" href="<?php echo $isAdminPanel ? '../index.php?page=settings' : 'index.php?page=settings'; ?>">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?php echo $isAdminPanel ? '../index.php?page=logout' : 'index.php?page=logout'; ?>">
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