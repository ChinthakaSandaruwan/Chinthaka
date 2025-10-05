<?php

/**
 * Payment Success Handler
 * Handles successful payment returns from PayHere
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
$success = false;
$message = '';
$paymentData = null;

try {
    // Get payment data from session
    $paymentData = $_SESSION['payment_data'] ?? null;

    if (!$paymentData) {
        $message = 'No payment data found. Please try again.';
    } else {
        // Get payment details from database
        $stmt = $pdo->prepare("
            SELECT p.*, ra.property_id, ra.tenant_id, ra.owner_id, ra.monthly_rent,
                   prop.title as property_title, prop.location,
                   u1.name as tenant_name, u1.email as tenant_email,
                   u2.name as owner_name, u2.email as owner_email
            FROM payments p
            LEFT JOIN rental_agreements ra ON p.rental_agreement_id = ra.id
            LEFT JOIN properties prop ON ra.property_id = prop.id
            LEFT JOIN users u1 ON ra.tenant_id = u1.id
            LEFT JOIN users u2 ON ra.owner_id = u2.id
            WHERE p.id = ? AND p.status = 'pending'
        ");
        $stmt->execute([$paymentData['payment_id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            // Update payment status to completed
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'completed', 
                    payment_date = NOW(),
                    transaction_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $paymentData['order_id'],
                $paymentData['payment_id']
            ]);

            // Log successful payment
            logPayment("Payment completed successfully - Order: {$paymentData['order_id']}, Amount: {$payment['amount']}, Payment ID: {$paymentData['payment_id']}", 'SUCCESS');

            // Send confirmation email to tenant
            if ($payment['tenant_email']) {
                $subject = "Payment Confirmation - RentFinder SL";
                $body = "
                    <h2>Payment Confirmation</h2>
                    <p>Dear {$payment['tenant_name']},</p>
                    <p>Your payment has been processed successfully.</p>
                    
                    <h3>Payment Details:</h3>
                    <ul>
                        <li><strong>Order ID:</strong> {$paymentData['order_id']}</li>
                        <li><strong>Amount:</strong> Rs. " . number_format($payment['amount'], 2) . "</li>
                        <li><strong>Property:</strong> {$payment['property_title']}, {$payment['location']}</li>
                        <li><strong>Payment Type:</strong> " . ucfirst($payment['payment_type']) . "</li>
                        <li><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                    </ul>
                    
                    <p>Thank you for using RentFinder SL!</p>
                ";

                sendEmail($payment['tenant_email'], $subject, $body);
            }

            // Send notification to owner
            if ($payment['owner_email'] && $payment['payment_type'] === 'rent') {
                $subject = "Rent Payment Received - RentFinder SL";
                $body = "
                    <h2>Rent Payment Received</h2>
                    <p>Dear {$payment['owner_name']},</p>
                    <p>You have received a rent payment for your property.</p>
                    
                    <h3>Payment Details:</h3>
                    <ul>
                        <li><strong>Property:</strong> {$payment['property_title']}, {$payment['location']}</li>
                        <li><strong>Tenant:</strong> {$payment['tenant_name']}</li>
                        <li><strong>Amount:</strong> Rs. " . number_format($payment['amount'], 2) . "</li>
                        <li><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                    </ul>
                    
                    <p>You can view your rental settlements in your dashboard.</p>
                ";

                sendEmail($payment['owner_email'], $subject, $body);
            }

            $success = true;
            $message = 'Payment completed successfully!';

            // Clear payment session data
            unset($_SESSION['payment_data']);
        } else {
            $message = 'Payment not found or already processed.';
        }
    }
} catch (Exception $e) {
    logPayment("Payment success handler error: " . $e->getMessage(), 'ERROR');
    $message = 'An error occurred while processing your payment. Please contact support.';
}

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header <?php echo $success ? 'bg-success' : 'bg-danger'; ?> text-white">
                    <h4 class="mb-0">
                        <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                        <?php echo $success ? 'Payment Successful' : 'Payment Error'; ?>
                    </h4>
                </div>

                <div class="card-body text-center">
                    <?php if ($success): ?>
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                            <h3 class="text-success">Payment Completed Successfully!</h3>
                            <p class="lead">Your payment has been processed and confirmed.</p>
                        </div>

                        <?php if ($payment): ?>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h5 class="text-primary">Payment Details</h5>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Order ID:</strong></td>
                                            <td><?php echo htmlspecialchars($paymentData['order_id']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amount:</strong></td>
                                            <td class="h5 text-success">Rs. <?php echo number_format($payment['amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td><?php echo ucfirst($payment['payment_type']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date:</strong></td>
                                            <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <?php if ($payment['property_title']): ?>
                                    <div class="col-md-6">
                                        <h5 class="text-primary">Property Details</h5>
                                        <table class="table table-borderless">
                                            <tr>
                                                <td><strong>Property:</strong></td>
                                                <td><?php echo htmlspecialchars($payment['property_title']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Location:</strong></td>
                                                <td><?php echo htmlspecialchars($payment['location']); ?></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Tenant:</strong></td>
                                                <td><?php echo htmlspecialchars($payment['tenant_name']); ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="alert alert-success">
                            <i class="fas fa-envelope me-2"></i>
                            <strong>Confirmation Email Sent!</strong>
                            A payment confirmation has been sent to your email address.
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php?page=dashboard" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Go to Dashboard
                            </a>
                            <a href="index.php?page=my_payments" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-credit-card me-2"></i>
                                View Payment History
                            </a>
                        </div>

                    <?php else: ?>
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle fa-5x text-danger mb-3"></i>
                            <h3 class="text-danger">Payment Error</h3>
                            <p class="lead"><?php echo htmlspecialchars($message); ?></p>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php?page=dashboard" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Go to Dashboard
                            </a>
                            <a href="index.php?page=properties" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-home me-2"></i>
                                Browse Properties
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>