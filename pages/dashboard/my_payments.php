<?php

/**
 * My Payments - Payment History
 * Shows user's payment history
 */

// Start output buffering to prevent headers already sent errors
ob_start();

// Include required files
include __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Get payment history
$payments = getPaymentHistory($userId, 20);

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary">
                    <i class="fas fa-credit-card me-2"></i>
                    My Payments
                </h2>
                <a href="index.php?page=dashboard" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Payment History</h5>
                </div>

                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Payments Found</h4>
                            <p class="text-muted">You haven't made any payments yet.</p>
                            <a href="index.php?page=properties" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>
                                Browse Properties
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Property</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                                            </td>
                                            <td>
                                                <?php if ($payment['property_title']): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($payment['property_title']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            <i class="fas fa-map-marker-alt me-1"></i>
                                                            <?php echo htmlspecialchars($payment['location']); ?>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo ucfirst($payment['payment_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    Rs. <?php echo number_format($payment['amount'], 2); ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                $statusIcon = '';
                                                switch ($payment['status']) {
                                                    case 'completed':
                                                        $statusClass = 'bg-success';
                                                        $statusIcon = 'fa-check-circle';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'bg-warning';
                                                        $statusIcon = 'fa-clock';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'bg-danger';
                                                        $statusIcon = 'fa-times-circle';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-secondary';
                                                        $statusIcon = 'fa-times';
                                                        break;
                                                    case 'refunded':
                                                        $statusClass = 'bg-info';
                                                        $statusIcon = 'fa-undo';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-light text-dark';
                                                        $statusIcon = 'fa-question';
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['payment_date']): ?>
                                                    <?php echo date('M j, Y g:i A', strtotime($payment['payment_date'])); ?>
                                                <?php else: ?>
                                                    <?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary"
                                                        onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($payment['status'] === 'completed'): ?>
                                                        <button type="button" class="btn btn-outline-success"
                                                            onclick="downloadReceipt(<?php echo $payment['id']; ?>)">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                    <?php endif; ?>
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

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Payment details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewPaymentDetails(paymentId) {
        // Load payment details via AJAX
        fetch(`index.php?page=payment_details&id=${paymentId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('paymentDetailsContent').innerHTML = data;
                new bootstrap.Modal(document.getElementById('paymentDetailsModal')).show();
            })
            .catch(error => {
                console.error('Error loading payment details:', error);
                alert('Error loading payment details');
            });
    }

    function downloadReceipt(paymentId) {
        // Open receipt in new window
        window.open(`index.php?page=payment_receipt&id=${paymentId}`, '_blank');
    }
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>