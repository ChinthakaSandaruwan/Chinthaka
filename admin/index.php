<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

session_start();

// Check if user is admin
if (!isLoggedIn() || getCurrentUser()['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();

// Get dashboard statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total properties
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM properties");
    $totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total payments
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE status = 'completed'");
    $totalPayments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total revenue
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Recent users
    $stmt = $pdo->query("
        SELECT first_name, last_name, email, role, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent properties
    $stmt = $pdo->query("
        SELECT p.title, p.location, p.monthly_rent, u.first_name, u.last_name, p.created_at
        FROM properties p
        JOIN users u ON p.owner_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $recentProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent payments
    $stmt = $pdo->query("
        SELECT p.amount, p.status, u.first_name, u.last_name, p.created_at
        FROM payments p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $recentPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i>
                            Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="properties.php">
                            <i class="fas fa-home"></i>
                            Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="payments.php">
                            <i class="fas fa-credit-card"></i>
                            Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="visits.php">
                            <i class="fas fa-calendar-check"></i>
                            Visits
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i>
                            Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Users
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($totalUsers); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Properties
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($totalProperties); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-home fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Payments
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo number_format($totalPayments); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-credit-card fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Revenue
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo formatCurrency($totalRevenue); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Users</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recentUsers as $user): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></div>
                                        <div class="text-muted small"><?php echo ucfirst($user['role']); ?></div>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo formatDate($user['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-success">Recent Properties</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recentProperties as $property): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-home text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold"><?php echo htmlspecialchars($property['title']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($property['location']); ?></div>
                                        <div class="text-muted small"><?php echo formatCurrency($property['monthly_rent']); ?></div>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo formatDate($property['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-info">Recent Payments</h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($recentPayments as $payment): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-credit-card text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold"><?php echo formatCurrency($payment['amount']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                                        <div class="text-muted small">
                                            <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-muted small">
                                        <?php echo formatDate($payment['created_at']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>