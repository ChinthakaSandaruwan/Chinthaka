<?php

/**
 * Process Payment - PayHere Integration
 * Handles rental payment processing
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

// Get user info
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Validate required parameters
$propertyId = (int)($_GET['property_id'] ?? 0);
$rentalId = (int)($_GET['rental_id'] ?? 0);
$paymentType = sanitizeInput($_GET['type'] ?? 'rent'); // rent, deposit, commission

if (!$propertyId && !$rentalId) {
    setFlashMessage('error', 'Invalid payment request.');
    redirect('index.php?page=dashboard');
}

try {
    // Get property details
    if ($propertyId) {
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.id = ? AND p.is_verified = 1
        ");
        $stmt->execute([$propertyId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$property) {
            setFlashMessage('error', 'Property not found or not verified.');
            redirect('index.php?page=properties');
        }
    }

    // Get rental agreement details if rental ID provided
    if ($rentalId) {
        $stmt = $pdo->prepare("
            SELECT ra.*, p.title as property_title, p.location, 
                   u1.name as tenant_name, u1.email as tenant_email, u1.phone as tenant_phone,
                   u2.name as owner_name, u2.email as owner_email, u2.phone as owner_phone
            FROM rental_agreements ra
            JOIN properties p ON ra.property_id = p.id
            JOIN users u1 ON ra.tenant_id = u1.id
            JOIN users u2 ON ra.owner_id = u2.id
            WHERE ra.id = ? AND ra.status = 'active'
        ");
        $stmt->execute([$rentalId]);
        $rental = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rental) {
            setFlashMessage('error', 'Rental agreement not found or not active.');
            redirect('index.php?page=dashboard');
        }
    }

    // Calculate payment amount based on type
    $amount = 0;
    $description = '';
    $orderId = '';

    switch ($paymentType) {
        case 'rent':
            if ($rentalId && $rental) {
                $amount = $rental['monthly_rent'];
                $description = "Monthly Rent - " . $rental['property_title'];
                $orderId = "RENT_" . $rentalId . "_" . time();
            } else {
                setFlashMessage('error', 'Invalid rental agreement.');
                redirect('index.php?page=dashboard');
            }
            break;

        case 'deposit':
            if ($rentalId && $rental) {
                $amount = $rental['security_deposit'];
                $description = "Security Deposit - " . $rental['property_title'];
                $orderId = "DEP_" . $rentalId . "_" . time();
            } else {
                setFlashMessage('error', 'Invalid rental agreement.');
                redirect('index.php?page=dashboard');
            }
            break;

        case 'commission':
            if ($rentalId && $rental) {
                $commission = $rental['monthly_rent'] * COMMISSION_RATE;
                $commission = max(MINIMUM_COMMISSION, min($commission, MAXIMUM_COMMISSION));
                $amount = $commission;
                $description = "Platform Commission - " . $rental['property_title'];
                $orderId = "COMM_" . $rentalId . "_" . time();
            } else {
                setFlashMessage('error', 'Invalid rental agreement.');
                redirect('index.php?page=dashboard');
            }
            break;

        default:
            setFlashMessage('error', 'Invalid payment type.');
            redirect('index.php?page=dashboard');
    }

    // Validate amount
    if (!validatePaymentAmount($amount)) {
        setFlashMessage('error', 'Invalid payment amount.');
        redirect('index.php?page=dashboard');
    }

    // Generate payment hash
    $hash = generatePayHereHash($merchant_id, $orderId, $amount, $currency, $merchant_secret);

    // Get customer details
    $customer = getUserById($userId);
    if (!$customer) {
        setFlashMessage('error', 'Customer information not found.');
        redirect('index.php?page=dashboard');
    }

    // Log payment initiation
    logPayment("Payment initiated - Order: $orderId, Amount: $amount, Type: $paymentType, User: $userId");

    // Store payment record in database
    $stmt = $pdo->prepare("
        INSERT INTO payments (rental_agreement_id, amount, payment_type, payment_method, transaction_id, status, payment_date, created_at) 
        VALUES (?, ?, ?, 'payhere', ?, 'pending', NULL, NOW())
    ");
    $stmt->execute([
        $rentalId ?: null,
        $amount,
        $paymentType,
        $orderId
    ]);

    $paymentId = $pdo->lastInsertId();

    // Store payment session data
    $_SESSION['payment_data'] = [
        'payment_id' => $paymentId,
        'order_id' => $orderId,
        'amount' => $amount,
        'type' => $paymentType,
        'property_id' => $propertyId,
        'rental_id' => $rentalId
    ];
} catch (Exception $e) {
    logPayment("Payment processing error: " . $e->getMessage(), 'ERROR');
    setFlashMessage('error', 'Payment processing failed. Please try again.');
    redirect('index.php?page=dashboard');
}

// Include header
include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Processing
                    </h4>
                </div>

                <div class="card-body">
                    <!-- Payment Summary -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary">Payment Details</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Order ID:</strong></td>
                                    <td><?php echo htmlspecialchars($orderId); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Description:</strong></td>
                                    <td><?php echo htmlspecialchars($description); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td class="h5 text-success">Rs. <?php echo number_format($amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Currency:</strong></td>
                                    <td><?php echo $currency; ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5 class="text-primary">Customer Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- PayHere Payment Form -->
                    <div class="text-center">
                        <h5 class="mb-3">Secure Payment via PayHere</h5>
                        <p class="text-muted mb-4">
                            You will be redirected to PayHere's secure payment gateway to complete your payment.
                        </p>

                        <form method="post" action="<?php echo $payhere_checkout_url; ?>" id="payhereForm">
                            <!-- Required PayHere Fields -->
                            <input type="hidden" name="merchant_id" value="<?php echo $merchant_id; ?>">
                            <input type="hidden" name="return_url" value="<?php echo $return_url; ?>">
                            <input type="hidden" name="cancel_url" value="<?php echo $cancel_url; ?>">
                            <input type="hidden" name="notify_url" value="<?php echo $notify_url; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                            <input type="hidden" name="items" value="<?php echo htmlspecialchars($description); ?>">
                            <input type="hidden" name="currency" value="<?php echo $currency; ?>">
                            <input type="hidden" name="amount" value="<?php echo number_format($amount, 2, '.', ''); ?>">
                            <input type="hidden" name="hash" value="<?php echo $hash; ?>">

                            <!-- Customer Information -->
                            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars(explode(' ', $customer['name'])[0]); ?>">
                            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars(substr($customer['name'], strpos($customer['name'], ' ') + 1)); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>">
                            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>">
                            <input type="hidden" name="address" value="<?php echo htmlspecialchars($customer['address'] ?? 'Not provided'); ?>">
                            <input type="hidden" name="city" value="<?php echo htmlspecialchars($customer['city'] ?? 'Colombo'); ?>">
                            <input type="hidden" name="country" value="Sri Lanka">

                            <!-- Additional Fields -->
                            <input type="hidden" name="custom_1" value="<?php echo $paymentId; ?>">
                            <input type="hidden" name="custom_2" value="<?php echo $paymentType; ?>">

                            <button type="submit" class="btn btn-success btn-lg me-3">
                                <i class="fas fa-credit-card me-2"></i>
                                Pay Rs. <?php echo number_format($amount, 2); ?>
                            </button>

                            <a href="index.php?page=dashboard" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>
                                Cancel Payment
                            </a>
                        </form>
                    </div>

                    <!-- Security Notice -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Secure Payment:</strong> Your payment is processed securely through PayHere.
                        We do not store your payment card information.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-submit form after 3 seconds
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            document.getElementById('payhereForm').submit();
        }, 3000);

        // Show countdown
        let countdown = 3;
        const button = document.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;

        const timer = setInterval(function() {
            countdown--;
            button.innerHTML = `<i class="fas fa-credit-card me-2"></i>Redirecting in ${countdown}s...`;

            if (countdown <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>