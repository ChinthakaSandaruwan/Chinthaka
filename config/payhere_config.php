<?php

/**
 * PayHere Payment Gateway Configuration
 * RentFinder SL - Rental Property Payment System
 */

// ⚠️ REPLACE WITH YOUR OWN PAYHERE DETAILS
$merchant_id = "1224197"; // Your PayHere Merchant ID
$merchant_secret = "Mzg1MDk0MTE4ODMyNDU4Mjc0OTMyNjI4MjI2Nzc3MzY5MTk2NzI4NQ=="; // Your PayHere Merchant Secret
$currency = "LKR"; // Sri Lankan Rupees

// Payment URLs - Update these to match your domain
$base_url = "http://localhost/chinthaka"; // Change to your actual domain

$return_url = $base_url . "/pages/payments/payment_success.php";
$cancel_url = $base_url . "/pages/payments/payment_cancel.php";
$notify_url = $base_url . "/pages/payments/payment_notify.php";

// PayHere URLs
$payhere_checkout_url = "https://www.payhere.lk/pay/checkout"; // Live URL
// $payhere_checkout_url = "https://sandbox.payhere.lk/pay/checkout"; // Sandbox URL for testing

/**
 * Generate PayHere payment hash for security
 * 
 * @param string $merchant_id PayHere Merchant ID
 * @param string $order_id Unique order identifier
 * @param float $amount Payment amount
 * @param string $currency Currency code
 * @param string $merchant_secret PayHere Merchant Secret
 * @return string Generated hash
 */
function generatePayHereHash($merchant_id, $order_id, $amount, $currency, $merchant_secret)
{
    return strtoupper(
        md5($merchant_id . $order_id . number_format($amount, 2, '.', '') . $currency . strtoupper(md5($merchant_secret)))
    );
}

/**
 * Generate PayHere notification hash for verification
 * 
 * @param string $merchant_id PayHere Merchant ID
 * @param string $order_id Order ID
 * @param string $amount Payment amount
 * @param string $currency Currency code
 * @param string $status_code Payment status code
 * @param string $merchant_secret PayHere Merchant Secret
 * @return string Generated hash
 */
function generatePayHereNotificationHash($merchant_id, $order_id, $amount, $currency, $status_code, $merchant_secret)
{
    return strtoupper(
        md5($merchant_id . $order_id . $amount . $currency . $status_code . strtoupper(md5($merchant_secret)))
    );
}

/**
 * Log payment activities for debugging
 * 
 * @param string $message Log message
 * @param string $level Log level (INFO, ERROR, SUCCESS)
 */
function logPayment($message, $level = 'INFO')
{
    $log_file = __DIR__ . '/../logs/payments.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Validate payment amount
 * 
 * @param float $amount Payment amount
 * @return bool True if valid
 */
function validatePaymentAmount($amount)
{
    return is_numeric($amount) && $amount > 0 && $amount <= 1000000; // Max 1M LKR
}

/**
 * Sanitize payment data
 * 
 * @param array $data Payment data
 * @return array Sanitized data
 */
function sanitizePaymentData($data)
{
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    return $sanitized;
}

// Payment status constants
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_COMPLETED', 'completed');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_CANCELLED', 'cancelled');
define('PAYMENT_STATUS_REFUNDED', 'refunded');

// PayHere status codes
define('PAYHERE_STATUS_SUCCESS', '2');
define('PAYHERE_STATUS_CANCELLED', '0');
define('PAYHERE_STATUS_FAILED', '-1');
define('PAYHERE_STATUS_AUTHORIZED', '1');

// Commission rates
define('COMMISSION_RATE', 0.05); // 5% commission
define('MINIMUM_COMMISSION', 100); // Minimum 100 LKR commission
define('MAXIMUM_COMMISSION', 5000); // Maximum 5000 LKR commission
