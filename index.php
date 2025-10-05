<?php

// Enable output buffering early to avoid "headers already sent" issues
// This ensures redirects and header modifications can occur after includes
if (function_exists('ob_start')) {
    ob_start();
}

/**
 * RentFinder SL - Main Entry Point
 * Simple router for the application
 */

// Start performance tracking
$startTime = microtime(true);

session_start();

// Include database configuration and functions
include 'config/database.php';
include 'includes/functions.php';

// Get the requested page
$page = $_GET['page'] ?? 'home';

// Define allowed pages
$allowedPages = [
    'home' => 'pages/general/home.php',
    'login' => 'pages/auth/login.php',
    'register' => 'pages/auth/register.php',
    'verify_otp' => 'pages/auth/verify_otp.php',
    'logout' => 'pages/auth/logout.php',
    'dashboard' => 'pages/dashboard/dashboard.php',
    'properties' => 'pages/properties/properties.php',
    'property_details' => 'pages/properties/property_details.php',
    'add_property' => 'pages/properties/add_property.php',
    'my_properties' => 'pages/properties/my_properties.php',
    'my_bookings' => 'pages/dashboard/my_bookings.php',
    'my_payments' => 'pages/dashboard/my_payments.php',
    'rental_settlements' => 'pages/dashboard/rental_settlements.php',
    'process_payment' => 'pages/payments/process_payment.php',
    'payment_success' => 'pages/payments/payment_success.php',
    'payment_cancel' => 'pages/payments/payment_cancel.php',
    'payment_notify' => 'pages/payments/payment_notify.php',
    'admin_dashboard' => 'pages/admin/admin_dashboard.php',
    'admin_properties' => 'pages/admin/admin_properties.php',
    'admin_users' => 'pages/admin/admin_users.php',
    'admin_payments' => 'pages/admin/admin_payments.php',
    'admin_commissions' => 'pages/admin/admin_commissions.php',
    'admin_reports' => 'pages/admin/admin_reports.php',
    'about' => 'pages/general/about.php',
];

// Set page title
$pageTitle = "RentFinder SL - Find Your Perfect Rental Property";

// Check if page exists and is allowed
if (isset($allowedPages[$page]) && file_exists($allowedPages[$page])) {
    // Include header for all pages except auth pages and admin pages (they have their own headers)
    if (!in_array($page, ['login', 'register', 'verify_otp', 'logout', 'admin_dashboard', 'admin_properties', 'admin_users', 'admin_payments', 'admin_commissions', 'admin_reports'])) {
        include 'includes/header.php';
    }

    // Include the requested page
    include $allowedPages[$page];

    // Include footer for all pages except auth pages and admin pages (they have their own footers)
    if (!in_array($page, ['login', 'register', 'verify_otp', 'logout', 'admin_dashboard', 'admin_properties', 'admin_users', 'admin_payments', 'admin_commissions', 'admin_reports'])) {
        include 'includes/footer.php';
    }
} else {
    // 404 page
    http_response_code(404);
    include 'public/404.html';
}

// Log performance metrics
logPerformance("Page loaded: " . ($page ?? 'home'), $startTime);
