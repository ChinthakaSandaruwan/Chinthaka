<?php
include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in and is an owner
if (!isLoggedIn() || !isOwner()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];

// Handle settlement processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_settlement'])) {
    $rentalId = (int)($_POST['rental_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && $rentalId > 0 && $amount > 0) {
        try {
            // Verify rental agreement belongs to owner
            $stmt = $pdo->prepare("SELECT * FROM rental_agreements WHERE id = ? AND owner_id = ?");
            $stmt->execute([$rentalId, $userId]);
            $rental = $stmt->fetch();

            if ($rental) {
                // Process settlement payment
                $transactionId = 'SETTLE_' . time() . '_' . rand(1000, 9999);

                $stmt = $pdo->prepare("INSERT INTO payments (rental_agreement_id, amount, payment_type, payment_method, transaction_id, status, payment_date) 
                                      VALUES (?, ?, 'rent', 'bank_transfer', ?, 'completed', NOW())");
                $stmt->execute([$rentalId, $amount, $transactionId]);

                setFlashMessage('success', 'Rental settlement processed successfully!');
            } else {
                setFlashMessage('error', 'Invalid rental agreement.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Settlement processing failed. Please try again.');
            error_log("Settlement processing error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=rental_settlements');
}

// Get rental agreements for the owner
$stmt = $pdo->prepare("SELECT ra.*, p.title as property_title, p.location, p.price,
                      u.name as tenant_name, u.email as tenant_email, u.phone as tenant_phone,
                      (SELECT SUM(amount) FROM payments WHERE rental_agreement_id = ra.id AND status = 'completed') as total_paid
                      FROM rental_agreements ra
                      JOIN properties p ON ra.property_id = p.id
                      JOIN users u ON ra.tenant_id = u.id
                      WHERE ra.owner_id = ? 
                      ORDER BY ra.created_at DESC");
$stmt->execute([$userId]);
$rentalAgreements = $stmt->fetchAll();

// Get payment history
$stmt = $pdo->prepare("SELECT p.*, ra.property_id, prop.title as property_title, u.name as tenant_name
                      FROM payments p 
                      JOIN rental_agreements ra ON p.rental_agreement_id = ra.id 
                      JOIN properties prop ON ra.property_id = prop.id
                      JOIN users u ON ra.tenant_id = u.id
                      WHERE ra.owner_id = ? 
                      ORDER BY p.created_at DESC");
$stmt->execute([$userId]);
$payments = $stmt->fetchAll();

// Calculate statistics
$stats = [
    'total_rentals' => count($rentalAgreements),
    'active_rentals' => count(array_filter($rentalAgreements, function ($r) {
        return $r['status'] === 'active';
    })),
    'total_monthly_rent' => array_sum(array_column($rentalAgreements, 'monthly_rent')),
    'total_received' => array_sum(array_column($payments, 'amount'))
];

// Pagination for payments
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;
$totalPayments = count($payments);
$paginatedPayments = array_slice($payments, $offset, $recordsPerPage);
$pagination = paginate($totalPayments, $recordsPerPage, $page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Settlements - RentFinder SL</title>
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
                <h1 class="display-6 fw-bold text-primary">Rental Settlements</h1>
                <p class="text-muted">Manage rental payments and settlements for your properties</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-primary"><?php echo $stats['total_rentals']; ?></div>
                    <div class="dashboard-label">Total Rentals</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo $stats['active_rentals']; ?></div>
                    <div class="dashboard-label">Active Rentals</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-info"><?php echo formatCurrency($stats['total_monthly_rent']); ?></div>
                    <div class="dashboard-label">Monthly Rent Due</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo formatCurrency($stats['total_received']); ?></div>
                    <div class="dashboard-label">Total Received</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Active Rentals -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-home me-2"></i>
                            Active Rental Agreements
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($rentalAgreements)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No rental agreements found</h6>
                                <p class="text-muted">Your rental agreements will appear here once tenants start renting your properties.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Property</th>
                                            <th>Tenant</th>
                                            <th>Monthly Rent</th>
                                            <th>Total Paid</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentalAgreements as $rental): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($rental['property_title']); ?></h6>
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars(ucfirst($rental['location'])); ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($rental['tenant_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($rental['tenant_phone']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?php echo formatCurrency($rental['monthly_rent']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-success"><?php echo formatCurrency($rental['total_paid'] ?? 0); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $rental['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($rental['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#settlementModal<?php echo $rental['id']; ?>">
                                                        <i class="fas fa-credit-card me-1"></i>Process Settlement
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Settlement Modal -->
                                            <div class="modal fade" id="settlementModal<?php echo $rental['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Process Rental Settlement</h5>
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
                                                                    <label class="form-label">Tenant</label>
                                                                    <input type="text" class="form-control"
                                                                        value="<?php echo htmlspecialchars($rental['tenant_name']); ?>" readonly>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Monthly Rent</label>
                                                                    <input type="text" class="form-control"
                                                                        value="<?php echo formatCurrency($rental['monthly_rent']); ?>" readonly>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Amount Received (LKR)</label>
                                                                    <input type="number" class="form-control" name="amount"
                                                                        value="<?php echo $rental['monthly_rent']; ?>"
                                                                        min="0" step="0.01" required>
                                                                    <div class="form-text">Enter the actual amount received from tenant</div>
                                                                </div>

                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle me-2"></i>
                                                                    This will record the rental payment and update your settlement records.
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="process_settlement" class="btn btn-primary">
                                                                    <i class="fas fa-credit-card me-2"></i>Process Settlement
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Recent Settlements
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($paginatedPayments)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No settlements found</h6>
                                <p class="text-muted">Your settlement history will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($paginatedPayments as $payment): ?>
                                    <div class="list-group-item border-0 px-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($payment['property_title']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($payment['tenant_name']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?php echo ucfirst($payment['payment_type']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold text-success">
                                                    <?php echo formatCurrency($payment['amount']); ?>
                                                </div>
                                                <span class="badge bg-success">
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
                        <?php echo generatePagination($pagination, 'rental_settlements.php'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>

</html>