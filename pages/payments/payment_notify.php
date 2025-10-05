<?php

/**
 * PayHere Payment Notification Handler
 * Receives server-to-server notifications from PayHere
 * This file should be accessible via HTTPS in production
 */

// Include required files
include __DIR__ . '/../../config/database.php';
include __DIR__ . '/../../config/payhere_config.php';
include __DIR__ . '/../../includes/functions.php';

// Set content type to plain text for PayHere
header('Content-Type: text/plain');

// Log all incoming data for debugging
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'get_data' => $_GET,
    'headers' => getallheaders()
];

logPayment("PayHere notification received: " . json_encode($logData), 'INFO');

try {
    // Validate that this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        logPayment("Invalid request method: " . $_SERVER['REQUEST_METHOD'], 'ERROR');
        http_response_code(405);
        echo "Method not allowed";
        exit;
    }

    // Get required parameters from PayHere
    $merchant_id_received = $_POST['merchant_id'] ?? '';
    $order_id = $_POST['order_id'] ?? '';
    $payhere_amount = $_POST['payhere_amount'] ?? '';
    $payhere_currency = $_POST['payhere_currency'] ?? '';
    $status_code = $_POST['status_code'] ?? '';
    $md5sig = $_POST['md5sig'] ?? '';

    // Validate required parameters
    if (
        empty($merchant_id_received) || empty($order_id) || empty($payhere_amount) ||
        empty($payhere_currency) || empty($status_code) || empty($md5sig)
    ) {
        logPayment("Missing required parameters in PayHere notification", 'ERROR');
        http_response_code(400);
        echo "Missing required parameters";
        exit;
    }

    // Verify merchant ID
    if ($merchant_id_received !== $merchant_id) {
        logPayment("Invalid merchant ID: $merchant_id_received", 'ERROR');
        http_response_code(400);
        echo "Invalid merchant ID";
        exit;
    }

    // Generate hash to verify PayHere signature
    $local_md5sig = generatePayHereNotificationHash(
        $merchant_id_received,
        $order_id,
        $payhere_amount,
        $payhere_currency,
        $status_code,
        $merchant_secret
    );

    // Verify hash
    if ($local_md5sig !== $md5sig) {
        logPayment("Hash verification failed for order: $order_id", 'ERROR');
        http_response_code(400);
        echo "Hash verification failed";
        exit;
    }

    // Get payment record from database
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
        WHERE p.transaction_id = ? AND p.status = 'pending'
    ");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        logPayment("Payment not found for order: $order_id", 'ERROR');
        http_response_code(404);
        echo "Payment not found";
        exit;
    }

    // Verify amount matches
    $expected_amount = number_format($payment['amount'], 2, '.', '');
    if ($payhere_amount !== $expected_amount) {
        logPayment("Amount mismatch for order $order_id: expected $expected_amount, received $payhere_amount", 'ERROR');
        http_response_code(400);
        echo "Amount mismatch";
        exit;
    }

    // Process payment based on status code
    switch ($status_code) {
        case PAYHERE_STATUS_SUCCESS: // Payment successful
            // Update payment status
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'completed', 
                    payment_date = NOW(),
                    transaction_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$order_id, $payment['id']]);

            // Log successful payment
            logPayment("Payment completed via notification - Order: $order_id, Amount: $payhere_amount, Payment ID: {$payment['id']}", 'SUCCESS');

            // Send confirmation emails
            if ($payment['tenant_email']) {
                $subject = "Payment Confirmation - RentFinder SL";
                $body = "
                    <h2>Payment Confirmation</h2>
                    <p>Dear {$payment['tenant_name']},</p>
                    <p>Your payment has been processed successfully.</p>
                    
                    <h3>Payment Details:</h3>
                    <ul>
                        <li><strong>Order ID:</strong> $order_id</li>
                        <li><strong>Amount:</strong> Rs. " . number_format($payment['amount'], 2) . "</li>
                        <li><strong>Property:</strong> {$payment['property_title']}, {$payment['location']}</li>
                        <li><strong>Payment Type:</strong> " . ucfirst($payment['payment_type']) . "</li>
                        <li><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</li>
                    </ul>
                    
                    <p>Thank you for using RentFinder SL!</p>
                ";

                sendEmail($payment['tenant_email'], $subject, $body);
            }

            // Send notification to owner for rent payments
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

            echo "Payment processed successfully";
            break;

        case PAYHERE_STATUS_CANCELLED: // Payment cancelled
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'cancelled' 
                WHERE id = ?
            ");
            $stmt->execute([$payment['id']]);

            logPayment("Payment cancelled via notification - Order: $order_id, Payment ID: {$payment['id']}", 'INFO');
            echo "Payment cancelled";
            break;

        case PAYHERE_STATUS_FAILED: // Payment failed
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = 'failed' 
                WHERE id = ?
            ");
            $stmt->execute([$payment['id']]);

            logPayment("Payment failed via notification - Order: $order_id, Payment ID: {$payment['id']}", 'ERROR');
            echo "Payment failed";
            break;

        default:
            logPayment("Unknown status code received: $status_code for order: $order_id", 'ERROR');
            http_response_code(400);
            echo "Unknown status code";
            break;
    }
} catch (Exception $e) {
    logPayment("PayHere notification processing error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo "Internal server error";
}

// Always return 200 to PayHere to acknowledge receipt
http_response_code(200);
echo "OK";
