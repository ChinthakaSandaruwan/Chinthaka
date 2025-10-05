<?php

/**
 * Payment Cancel Handler
 * Handles cancelled payments from PayHere
 */

// Start output buffering to prevent headers already sent errors
ob_start();

// Include required files
include __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../config/payhere_config.php';
include __DIR__ . '/../../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];
$paymentData = null;

try {
    // Get payment data from session
    $paymentData = $_SESSION['payment_data'] ?? null;

    if ($paymentData) {
        // Update payment status to cancelled
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'cancelled' 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$paymentData['payment_id']]);

        // Log cancelled payment
        logPayment("Payment cancelled by user - Order: {$paymentData['order_id']}, Payment ID: {$paymentData['payment_id']}", 'INFO');

        // Clear payment session data
        unset($_SESSION['payment_data']);
    }
} catch (Exception $e) {
    logPayment("Payment cancel handler error: " . $e->getMessage(), 'ERROR');
}

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-times-circle me-2"></i>
                        Payment Cancelled
                    </h4>
                </div>

                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-times-circle fa-5x text-warning mb-3"></i>
                        <h3 class="text-warning">Payment Cancelled</h3>
                        <p class="lead">You have cancelled the payment process.</p>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>No charges were made</strong> to your account. You can try again anytime.
                    </div>

                    <?php if ($paymentData): ?>
                        <div class="row mb-4">
                            <div class="col-md-6 offset-md-3">
                                <h5 class="text-primary">Cancelled Payment Details</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Order ID:</strong></td>
                                        <td><?php echo htmlspecialchars($paymentData['order_id']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Amount:</strong></td>
                                        <td class="h5 text-muted">Rs. <?php echo number_format($paymentData['amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Type:</strong></td>
                                        <td><?php echo ucfirst($paymentData['type']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Time:</strong></td>
                                        <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="index.php?page=dashboard" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Go to Dashboard
                        </a>
                        <a href="index.php?page=properties" class="btn btn-outline-primary btn-lg me-2">
                            <i class="fas fa-home me-2"></i>
                            Browse Properties
                        </a>
                        <?php if ($paymentData && $paymentData['rental_id']): ?>
                            <a href="index.php?page=process_payment&rental_id=<?php echo $paymentData['rental_id']; ?>&type=<?php echo $paymentData['type']; ?>"
                                class="btn btn-success btn-lg">
                                <i class="fas fa-redo me-2"></i>
                                Try Again
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-primary">Need Help?</h5>
                        <p class="text-muted">
                            If you're experiencing issues with payments, please contact our support team.
                        </p>
                        <a href="mailto:support@rentfinder.lk" class="btn btn-outline-secondary">
                            <i class="fas fa-envelope me-2"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>