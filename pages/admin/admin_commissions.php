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

// Handle commission rate updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_commission_rate'])) {
    $newRate = (float)($_POST['commission_rate'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && $newRate >= 0 && $newRate <= 100) {
        try {
            // Store commission rate in a settings table or session
            $_SESSION['commission_rate'] = $newRate;
            setFlashMessage('success', 'Commission rate updated successfully!');
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update commission rate.');
            error_log("Commission rate update error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=admin_commissions');
}

// Get current commission rate (default 5%)
$commissionRate = $_SESSION['commission_rate'] ?? 5;

// Get commission statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_payments,
        SUM(amount) as total_revenue,
        SUM(amount * $commissionRate / 100) as total_commission,
        AVG(amount) as avg_payment,
        MAX(amount) as max_payment,
        MIN(amount) as min_payment
    FROM payments 
    WHERE status = 'completed'
");
$commissionStats = $stmt->fetch();

// Get monthly commission breakdown
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as payment_count,
        SUM(amount) as total_amount,
        SUM(amount * $commissionRate / 100) as commission_earned
    FROM payments 
    WHERE status = 'completed' 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");
$monthlyBreakdown = $stmt->fetchAll();

// Get top earning properties
$stmt = $pdo->query("
    SELECT 
        p.title as property_title,
        p.location,
        u.name as owner_name,
        COUNT(pay.id) as payment_count,
        SUM(pay.amount) as total_revenue,
        SUM(pay.amount * $commissionRate / 100) as commission_earned
    FROM properties p
    JOIN rental_agreements ra ON p.id = ra.property_id
    JOIN payments pay ON ra.id = pay.rental_agreement_id
    JOIN users u ON p.owner_id = u.id
    WHERE pay.status = 'completed'
    GROUP BY p.id, p.title, p.location, u.name
    ORDER BY commission_earned DESC
    LIMIT 10
");
$topProperties = $stmt->fetchAll();

// Get recent commission transactions
$stmt = $pdo->query("
    SELECT 
        p.*,
        ra.property_id,
        prop.title as property_title,
        u_tenant.name as tenant_name,
        u_owner.name as owner_name,
        (p.amount * $commissionRate / 100) as commission_amount
    FROM payments p 
    JOIN rental_agreements ra ON p.rental_agreement_id = ra.id 
    JOIN properties prop ON ra.property_id = prop.id
    JOIN users u_tenant ON ra.tenant_id = u_tenant.id
    JOIN users u_owner ON ra.owner_id = u_owner.id
    WHERE p.status = 'completed'
    ORDER BY p.created_at DESC
    LIMIT 20
");
$recentTransactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Management - Admin - RentFinder SL</title>
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
                <h1 class="display-6 fw-bold text-primary">Commission Management</h1>
                <p class="text-muted">Manage commission rates and track earnings</p>
            </div>
        </div>

        <!-- Commission Rate Settings -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    Commission Rate Settings
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Current Commission Rate</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="commission_rate"
                                value="<?php echo $commissionRate; ?>"
                                min="0" max="100" step="0.01" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Set the commission rate (0-100%)</div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" name="update_commission_rate" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Rate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Commission Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-primary"><?php echo $commissionStats['total_payments'] ?? 0; ?></div>
                    <div class="dashboard-label">Total Payments</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-info"><?php echo formatCurrency($commissionStats['total_revenue'] ?? 0); ?></div>
                    <div class="dashboard-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo formatCurrency($commissionStats['total_commission'] ?? 0); ?></div>
                    <div class="dashboard-label">Total Commission</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo $commissionRate; ?>%</div>
                    <div class="dashboard-label">Commission Rate</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Monthly Breakdown -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Monthly Commission Breakdown
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($monthlyBreakdown)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No commission data available</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th>Payments</th>
                                            <th>Revenue</th>
                                            <th>Commission</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($monthlyBreakdown as $month): ?>
                                            <tr>
                                                <td><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></td>
                                                <td><?php echo $month['payment_count']; ?></td>
                                                <td><?php echo formatCurrency($month['total_amount']); ?></td>
                                                <td class="fw-semibold text-success"><?php echo formatCurrency($month['commission_earned']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Top Earning Properties -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Top Earning Properties
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($topProperties)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-home fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No property data available</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topProperties as $property): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($property['property_title']); ?></h6>
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
                                                <div class="fw-semibold text-success">
                                                    <?php echo formatCurrency($property['commission_earned']); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo $property['payment_count']; ?> payments
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

        <!-- Recent Commission Transactions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Recent Commission Transactions
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentTransactions)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No transactions found</h6>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Tenant → Owner</th>
                                            <th>Payment Amount</th>
                                            <th>Commission (<?php echo $commissionRate; ?>%)</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($transaction['property_title']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo ucfirst($transaction['payment_type']); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($transaction['tenant_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">→ <?php echo htmlspecialchars($transaction['owner_name']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo formatCurrency($transaction['amount']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-success"><?php echo formatCurrency($transaction['commission_amount']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="small text-muted">
                                                        <?php echo formatDate($transaction['created_at']); ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>