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

// Handle payment guarantee processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_guarantee'])) {
    $rentalId = (int)($_POST['rental_id'] ?? 0);
    $guaranteeAmount = (float)($_POST['guarantee_amount'] ?? 0);
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && $rentalId > 0 && $guaranteeAmount > 0) {
        try {
            // Get rental agreement details
            $stmt = $pdo->prepare("SELECT * FROM rental_agreements WHERE id = ?");
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();

            if ($rental) {
                // Create guarantee payment record
                $transactionId = 'GUARANTEE_' . time() . '_' . rand(1000, 9999);

                $stmt = $pdo->prepare("INSERT INTO payments (rental_agreement_id, amount, payment_type, payment_method, transaction_id, status, payment_date, notes) 
                                      VALUES (?, ?, 'guarantee', 'bank_transfer', ?, 'completed', NOW(), ?)");
                $stmt->execute([$rentalId, $guaranteeAmount, $transactionId, $notes]);

                setFlashMessage('success', 'Payment guarantee processed successfully!');
            } else {
                setFlashMessage('error', 'Invalid rental agreement.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Guarantee processing failed. Please try again.');
            error_log("Guarantee processing error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=admin_payments');
}

// Get filter parameters
$filter = sanitizeInput($_GET['filter'] ?? 'all');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query for payments
$sql = "SELECT p.*, ra.property_id, ra.monthly_rent, ra.status as rental_status,
        prop.title as property_title, prop.location,
        u_tenant.name as tenant_name, u_owner.name as owner_name
        FROM payments p 
        JOIN rental_agreements ra ON p.rental_agreement_id = ra.id 
        JOIN properties prop ON ra.property_id = prop.id
        JOIN users u_tenant ON ra.tenant_id = u_tenant.id
        JOIN users u_owner ON ra.owner_id = u_owner.id
        WHERE 1=1";

$params = [];

if ($filter === 'completed') {
    $sql .= " AND p.status = 'completed'";
} elseif ($filter === 'pending') {
    $sql .= " AND p.status = 'pending'";
} elseif ($filter === 'failed') {
    $sql .= " AND p.status = 'failed'";
} elseif ($filter === 'guarantee') {
    $sql .= " AND p.payment_type = 'guarantee'";
}

if (!empty($search)) {
    $sql .= " AND (prop.title LIKE :search OR u_tenant.name LIKE :search OR u_owner.name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY p.created_at DESC";

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 15;
$offset = ($page - 1) * $recordsPerPage;

// Get total count
$countSql = str_replace("SELECT p.*, ra.property_id, ra.monthly_rent, ra.status as rental_status, prop.title as property_title, prop.location, u_tenant.name as tenant_name, u_owner.name as owner_name", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();

// Add pagination
$sql .= " LIMIT :offset, :limit";
$params['offset'] = $offset;
$params['limit'] = $recordsPerPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$pagination = paginate($totalRecords, $recordsPerPage, $page);

// Get payment statistics
$stats = [
    'total_payments' => count($payments),
    'completed_payments' => count(array_filter($payments, function ($p) {
        return $p['status'] === 'completed';
    })),
    'total_revenue' => array_sum(array_column($payments, 'amount')),
    'commission_earned' => array_sum(array_column($payments, 'amount')) * 0.05
];

// Get rental agreements that might need guarantees
$stmt = $pdo->query("
    SELECT ra.*, prop.title as property_title, prop.location,
           u_tenant.name as tenant_name, u_owner.name as owner_name,
           (SELECT SUM(amount) FROM payments WHERE rental_agreement_id = ra.id AND status = 'completed') as total_paid
    FROM rental_agreements ra
    JOIN properties prop ON ra.property_id = prop.id
    JOIN users u_tenant ON ra.tenant_id = u_tenant.id
    JOIN users u_owner ON ra.owner_id = u_owner.id
    WHERE ra.status = 'active'
    ORDER BY ra.created_at DESC
    LIMIT 10
");
$rentalAgreements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Admin - RentFinder SL</title>
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
                <h1 class="display-6 fw-bold text-primary">Payment Management</h1>
                <p class="text-muted">Manage payments, guarantees, and financial transactions</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-primary"><?php echo $stats['total_payments']; ?></div>
                    <div class="dashboard-label">Total Payments</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo $stats['completed_payments']; ?></div>
                    <div class="dashboard-label">Completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-info"><?php echo formatCurrency($stats['total_revenue']); ?></div>
                    <div class="dashboard-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo formatCurrency($stats['commission_earned']); ?></div>
                    <div class="dashboard-label">Commission (5%)</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Active Rentals -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>
                            Payment Guarantee Management
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($rentalAgreements)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-file-contract fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No active rental agreements</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($rentalAgreements as $rental): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($rental['property_title']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($rental['tenant_name']); ?> →
                                                    <?php echo htmlspecialchars($rental['owner_name']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars(ucfirst($rental['location'])); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold text-primary">
                                                    <?php echo formatCurrency($rental['monthly_rent']); ?>/month
                                                </div>
                                                <small class="text-muted">
                                                    Paid: <?php echo formatCurrency($rental['total_paid'] ?? 0); ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#guaranteeModal<?php echo $rental['id']; ?>">
                                                <i class="fas fa-shield-alt me-1"></i>Process Guarantee
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Guarantee Modal -->
                                    <div class="modal fade" id="guaranteeModal<?php echo $rental['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Process Payment Guarantee</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="rental_id" value="<?php echo $rental['id']; ?>">

                                                        <div class="mb-3">
                                                            <label class="form-label">Property</label>
                                                            <input type="text" class="form-control"
                                                                value="<?php echo htmlspecialchars($rental['property_title']); ?>" readonly>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Monthly Rent</label>
                                                            <input type="text" class="form-control"
                                                                value="<?php echo formatCurrency($rental['monthly_rent']); ?>" readonly>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Guarantee Amount (LKR)</label>
                                                            <input type="number" class="form-control" name="guarantee_amount"
                                                                value="<?php echo $rental['monthly_rent']; ?>"
                                                                min="0" step="0.01" required>
                                                            <div class="form-text">Amount to guarantee to property owner</div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Notes</label>
                                                            <textarea class="form-control" name="notes" rows="3"
                                                                placeholder="Reason for guarantee payment..."></textarea>
                                                        </div>

                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle me-2"></i>
                                                            This guarantee ensures property owners receive payment even if tenants default.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="process_guarantee" class="btn btn-warning">
                                                            <i class="fas fa-shield-alt me-2"></i>Process Guarantee
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Payment History
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <!-- Filters -->
                        <div class="p-3 border-bottom">
                            <form method="GET" class="row g-2">
                                <div class="col-md-6">
                                    <select class="form-select form-select-sm" name="filter">
                                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Payments</option>
                                        <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo $filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="guarantee" <?php echo $filter === 'guarantee' ? 'selected' : ''; ?>>Guarantees</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" name="search"
                                        value="<?php echo htmlspecialchars($search); ?>"
                                        placeholder="Search...">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php if (empty($payments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No payments found</h6>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($payments as $payment): ?>
                                    <div class="list-group-item border-0 px-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($payment['property_title']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($payment['tenant_name']); ?> →
                                                    <?php echo htmlspecialchars($payment['owner_name']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo ucfirst($payment['payment_type']); ?>
                                                    <?php if ($payment['payment_type'] === 'guarantee'): ?>
                                                        <span class="text-warning">(Guarantee)</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold text-success">
                                                    <?php echo formatCurrency($payment['amount']); ?>
                                                </div>
                                                <span class="badge bg-<?php echo strtolower($payment['status']); ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                                <div class="small text-muted">
                                                    <?php echo formatDate($payment['created_at']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="card-footer">
                        <?php echo generatePagination($pagination, 'admin_payments.php'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>