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

// Get report parameters
$reportType = sanitizeInput($_GET['report'] ?? 'overview');
$startDate = sanitizeInput($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitizeInput($_GET['end_date'] ?? date('Y-m-d'));
$format = sanitizeInput($_GET['format'] ?? 'html');

// Get commission rate
$commissionRate = $_SESSION['commission_rate'] ?? 5;

// Generate reports based on type
$reportData = [];

switch ($reportType) {
    case 'overview':
        // System Overview Report
        $stmt = $pdo->query("
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM users WHERE user_type = 'tenant') as total_tenants,
                (SELECT COUNT(*) FROM users WHERE user_type = 'owner') as total_owners,
                (SELECT COUNT(*) FROM properties) as total_properties,
                (SELECT COUNT(*) FROM properties WHERE is_verified = 1) as verified_properties,
                (SELECT COUNT(*) FROM rental_agreements) as total_rentals,
                (SELECT COUNT(*) FROM rental_agreements WHERE status = 'active') as active_rentals,
                (SELECT SUM(amount) FROM payments WHERE status = 'completed') as total_revenue,
                (SELECT SUM(amount * $commissionRate / 100) FROM payments WHERE status = 'completed') as total_commission
        ");
        $reportData = $stmt->fetch();
        break;

    case 'financial':
        // Financial Report
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as payment_count,
                SUM(amount) as total_revenue,
                SUM(amount * $commissionRate / 100) as commission_earned,
                AVG(amount) as avg_payment
            FROM payments 
            WHERE status = 'completed' 
            AND created_at BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        $reportData = $stmt->fetchAll();
        break;

    case 'properties':
        // Property Performance Report
        $stmt = $pdo->prepare("
            SELECT 
                p.title,
                p.location,
                p.property_type,
                p.price,
                u.name as owner_name,
                p.is_verified,
                p.is_available,
                COUNT(pv.id) as visit_count,
                COUNT(ra.id) as rental_count,
                COALESCE(SUM(pay.amount), 0) as total_revenue,
                COALESCE(SUM(pay.amount * $commissionRate / 100), 0) as commission_earned
            FROM properties p
            LEFT JOIN users u ON p.owner_id = u.id
            LEFT JOIN property_visits pv ON p.id = pv.property_id
            LEFT JOIN rental_agreements ra ON p.id = ra.property_id
            LEFT JOIN payments pay ON ra.id = pay.rental_agreement_id AND pay.status = 'completed'
            WHERE p.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY commission_earned DESC
        ");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        $reportData = $stmt->fetchAll();
        break;

    case 'users':
        // User Activity Report
        $stmt = $pdo->prepare("
            SELECT 
                u.name,
                u.email,
                u.phone,
                u.user_type,
                u.is_verified,
                u.created_at,
                (SELECT COUNT(*) FROM properties WHERE owner_id = u.id) as property_count,
                (SELECT COUNT(*) FROM property_visits WHERE tenant_id = u.id) as visit_count,
                (SELECT COUNT(*) FROM rental_agreements WHERE tenant_id = u.id OR owner_id = u.id) as rental_count
            FROM users u
            WHERE u.created_at BETWEEN ? AND ?
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        $reportData = $stmt->fetchAll();
        break;

    case 'payments':
        // Payment Analysis Report
        $stmt = $pdo->prepare("
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
            WHERE p.created_at BETWEEN ? AND ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        $reportData = $stmt->fetchAll();
        break;
}

// Handle CSV export
if ($format === 'csv' && !empty($reportData)) {
    $filename = $reportType . '_report_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    if ($reportType === 'overview') {
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Users', $reportData['total_users']]);
        fputcsv($output, ['Total Tenants', $reportData['total_tenants']]);
        fputcsv($output, ['Total Owners', $reportData['total_owners']]);
        fputcsv($output, ['Total Properties', $reportData['total_properties']]);
        fputcsv($output, ['Verified Properties', $reportData['verified_properties']]);
        fputcsv($output, ['Total Rentals', $reportData['total_rentals']]);
        fputcsv($output, ['Active Rentals', $reportData['active_rentals']]);
        fputcsv($output, ['Total Revenue', $reportData['total_revenue']]);
        fputcsv($output, ['Total Commission', $reportData['total_commission']]);
    } else {
        // For other report types, output the data
        if (!empty($reportData)) {
            fputcsv($output, array_keys($reportData[0]));
            foreach ($reportData as $row) {
                fputcsv($output, $row);
            }
        }
    }

    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5" style="margin-top: 76px;">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-6 fw-bold text-primary">System Reports</h1>
                <p class="text-muted">Generate comprehensive reports and analytics</p>
            </div>
        </div>

        <!-- Report Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    Report Filters
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" name="report">
                            <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>System Overview</option>
                            <option value="financial" <?php echo $reportType === 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                            <option value="properties" <?php echo $reportType === 'properties' ? 'selected' : ''; ?>>Property Performance</option>
                            <option value="users" <?php echo $reportType === 'users' ? 'selected' : ''; ?>>User Activity</option>
                            <option value="payments" <?php echo $reportType === 'payments' ? 'selected' : ''; ?>>Payment Analysis</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Format</label>
                        <select class="form-select" name="format">
                            <option value="html" <?php echo $format === 'html' ? 'selected' : ''; ?>>HTML</option>
                            <option value="csv" <?php echo $format === 'csv' ? 'selected' : ''; ?>>CSV Export</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-chart-bar me-2"></i>Generate Report
                        </button>
                        <a href="index.php?page=admin_reports" class="btn btn-outline-secondary">
                            <i class="fas fa-refresh"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Content -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $reportType)); ?> Report
                    <small class="text-muted">(<?php echo date('M d, Y', strtotime($startDate)); ?> - <?php echo date('M d, Y', strtotime($endDate)); ?>)</small>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($reportType === 'overview'): ?>
                    <!-- System Overview Report -->
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-primary"><?php echo $reportData['total_users']; ?></div>
                                <div class="dashboard-label">Total Users</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-info"><?php echo $reportData['total_tenants']; ?></div>
                                <div class="dashboard-label">Tenants</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-success"><?php echo $reportData['total_owners']; ?></div>
                                <div class="dashboard-label">Property Owners</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-warning"><?php echo $reportData['total_properties']; ?></div>
                                <div class="dashboard-label">Properties</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-4 mt-2">
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-success"><?php echo $reportData['verified_properties']; ?></div>
                                <div class="dashboard-label">Verified Properties</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-info"><?php echo $reportData['active_rentals']; ?></div>
                                <div class="dashboard-label">Active Rentals</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-primary"><?php echo formatCurrency($reportData['total_revenue']); ?></div>
                                <div class="dashboard-label">Total Revenue</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="dashboard-card text-center">
                                <div class="dashboard-stat text-warning"><?php echo formatCurrency($reportData['total_commission']); ?></div>
                                <div class="dashboard-label">Total Commission</div>
                            </div>
                        </div>
                    </div>

                <?php elseif ($reportType === 'financial'): ?>
                    <!-- Financial Report -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Payments</th>
                                    <th>Total Revenue</th>
                                    <th>Commission Earned</th>
                                    <th>Average Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No financial data found</h6>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $month): ?>
                                        <tr>
                                            <td><?php echo date('M Y', strtotime($month['month'] . '-01')); ?></td>
                                            <td><?php echo $month['payment_count']; ?></td>
                                            <td><?php echo formatCurrency($month['total_revenue']); ?></td>
                                            <td class="fw-semibold text-success"><?php echo formatCurrency($month['commission_earned']); ?></td>
                                            <td><?php echo formatCurrency($month['avg_payment']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reportType === 'properties'): ?>
                    <!-- Property Performance Report -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Owner</th>
                                    <th>Type</th>
                                    <th>Price</th>
                                    <th>Visits</th>
                                    <th>Rentals</th>
                                    <th>Revenue</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No property data found</h6>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $property): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars(ucfirst($property['location'])); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
                                            <td><?php echo ucfirst($property['property_type']); ?></td>
                                            <td><?php echo formatCurrency($property['price']); ?>/month</td>
                                            <td><?php echo $property['visit_count']; ?></td>
                                            <td><?php echo $property['rental_count']; ?></td>
                                            <td><?php echo formatCurrency($property['total_revenue']); ?></td>
                                            <td class="fw-semibold text-success"><?php echo formatCurrency($property['commission_earned']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reportType === 'users'): ?>
                    <!-- User Activity Report -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Properties</th>
                                    <th>Visits</th>
                                    <th>Rentals</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No user data found</h6>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $user): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['user_type'] === 'admin' ? 'danger' : ($user['user_type'] === 'owner' ? 'success' : 'info'); ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($user['is_verified']): ?>
                                                    <span class="badge bg-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Unverified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $user['property_count']; ?></td>
                                            <td><?php echo $user['visit_count']; ?></td>
                                            <td><?php echo $user['rental_count']; ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reportType === 'payments'): ?>
                    <!-- Payment Analysis Report -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Tenant → Owner</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                            <h6 class="text-muted">No payment data found</h6>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $payment): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($payment['property_title']); ?></h6>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($payment['tenant_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">→ <?php echo htmlspecialchars($payment['owner_name']); ?></small>
                                                </div>
                                            </td>
                                            <td><?php echo formatCurrency($payment['amount']); ?></td>
                                            <td><?php echo ucfirst($payment['payment_type']); ?></td>
                                            <td class="fw-semibold text-success"><?php echo formatCurrency($payment['commission_amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo strtolower($payment['status']); ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($payment['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>