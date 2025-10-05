<?php

/**
 * Dynamic Navigation Bar Component
 * Automatically selects the appropriate navbar based on user type
 */

// Ensure session is started (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user info
$userType = $_SESSION['user_type'] ?? '';
$isLoggedIn = isLoggedIn();

// Include the appropriate navbar based on user type
if ($isLoggedIn) {
    switch ($userType) {
        case 'admin':
            include 'navbar_admin.php';
            break;
        case 'owner':
            include 'navbar_owner.php';
            break;
        case 'tenant':
        case 'customer':
        default:
            include 'navbar_customer.php';
            break;
    }
} else {
    // Guest user
    include 'navbar_guest.php';
}
