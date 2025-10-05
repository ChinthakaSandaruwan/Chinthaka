<?php
// Start output buffering to prevent headers already sent errors
ob_start();

include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?page=login');
}

// Include header
include 'includes/header.php';

$userId = $_SESSION['user_id'];

// Get system statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$stats['total_users'] = $stmt->fetchColumn();

// Total properties
$stmt = $pdo->query("SELECT COUNT(*) FROM properties");
$stats['total_properties'] = $stmt->fetchColumn();

// Verified properties
$stmt = $pdo->query("SELECT COUNT(*) FROM properties WHERE is_verified = 1");
$stats['verified_properties'] = $stmt->fetchColumn();

// Pending properties
$stmt = $pdo->query("SELECT COUNT(*) FROM properties WHERE is_verified = 0");
$stats['pending_properties'] = $stmt->fetchColumn();

// Active rentals
$stmt = $pdo->query("SELECT COUNT(*) FROM rental_agreements WHERE status = 'active'");
$stats['active_rentals'] = $stmt->fetchColumn();

// Total revenue
$stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
$stats['total_revenue'] = $stmt->fetchColumn() ?: 0;

// Commission earned
$stmt = $pdo->query("SELECT SUM(amount * 0.05) FROM payments WHERE status = 'completed'");
$stats['commission_earned'] = $stmt->fetchColumn() ?: 0;

// Recent activities
$stmt = $pdo->query("
    SELECT 'property' as type, title as description, created_at, 'primary' as color
    FROM properties 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'user' as type, CONCAT(name, ' registered') as description, created_at, 'success' as color
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION ALL
    SELECT 'payment' as type, CONCAT('Payment of LKR ', FORMAT(amount, 0)) as description, created_at, 'warning' as color
    FROM payments 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status = 'completed'
    ORDER BY created_at DESC 
    LIMIT 10
");
$recentActivities = $stmt->fetchAll();

// Get pending properties for quick review
$stmt = $pdo->query("
    SELECT p.*, u.name as owner_name, u.email as owner_email
    FROM properties p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.is_verified = 0 
    ORDER BY p.created_at ASC 
    LIMIT 5
");
$pendingProperties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5" style="margin-top: 76px;">
        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h1 class="display-6 fw-bold mb-2">Admin Dashboard</h1>
                        <p class="mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Manage your RentFinder SL platform.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-primary"><?php echo $stats['total_users']; ?></div>
                    <div class="dashboard-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-info"><?php echo $stats['total_properties']; ?></div>
                    <div class="dashboard-label">Total Properties</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo $stats['pending_properties']; ?></div>
                    <div class="dashboard-label">Pending Verification</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo $stats['active_rentals']; ?></div>
                    <div class="dashboard-label">Active Rentals</div>
                </div>
            </div>
        </div>

        <!-- Revenue Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                    <div class="dashboard-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo formatCurrency($stats['commission_earned']); ?></div>
                    <div class="dashboard-label">Commission Earned (5%)</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Properties -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Pending Property Verifications
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pendingProperties)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                <p class="text-muted mb-0">No pending properties to review</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pendingProperties as $property): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($property['owner_name']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars(ucfirst($property['location'])); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold text-primary">
                                                    <?php echo formatCurrency($property['price']); ?>/month
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo formatDate($property['created_at']); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <a href="index.php?page=admin_properties&action=review&id=<?php echo $property['id']; ?>"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>Review
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php?page=admin_properties" class="btn btn-outline-primary">
                                    View All Properties
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-activity me-2"></i>
                            Recent Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent activities</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3">
                                                <i class="fas fa-<?php echo $activity['type'] === 'property' ? 'home' : ($activity['type'] === 'user' ? 'user' : 'credit-card'); ?> 
                                                   fa-lg text-<?php echo $activity['color']; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <p class="mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <small class="text-muted">
                                                    <?php echo formatDate($activity['created_at']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="index.php?page=admin_properties" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-home me-2"></i>Manage Properties
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="index.php?page=admin_users" class="btn btn-outline-success w-100">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="index.php?page=admin_payments" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-credit-card me-2"></i>Payment Management
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="index.php?page=admin_reports" class="btn btn-outline-info w-100">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>