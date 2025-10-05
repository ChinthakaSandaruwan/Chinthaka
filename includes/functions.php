<?php
// Utility functions for the RentFinder application

// Prevent multiple inclusions
if (!function_exists('generateOTP')) {

    // Generate OTP code
    function generateOTP($length = 6)
    {
        return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    // Send SMS OTP (mock function - replace with actual SMS service)
    function sendSMS($phone, $message)
    {
        // In a real application, integrate with SMS service like Twilio, AWS SNS, etc.
        // For now, we'll just log the OTP for testing
        error_log("SMS to $phone: $message");
        return true;
    }

    // Validate phone number (Sri Lankan format)
    function validatePhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^0[0-9]{9}$/', $phone);
    }

    // Validate email
    function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Hash password
    function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // Verify password
    function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    // Generate CSRF token
    function generateCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verify CSRF token
    function verifyCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Sanitize input
    function sanitizeInput($input)
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    // Format currency (Sri Lankan Rupees)
    function formatCurrency($amount)
    {
        return 'LKR ' . number_format($amount, 2);
    }

    // Format date
    function formatDate($date, $format = 'M d, Y')
    {
        return date($format, strtotime($date));
    }

    // Check if user is logged in
    function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    // Check if user is owner
    function isOwner()
    {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'owner';
    }

    // Check if user is admin
    function isAdmin()
    {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }

    // Redirect function
    function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    // Flash message functions
    function setFlashMessage($type, $message)
    {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    function getFlashMessage()
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }

    // Display flash message
    function displayFlashMessage()
    {
        $flash = getFlashMessage();
        if ($flash) {
            $alertClass = $flash['type'] === 'error' ? 'danger' : $flash['type'];
            echo "<div class='alert alert-{$alertClass} alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($flash['message']);
            echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
            echo "</div>";
        }
    }

    // Upload image
    function uploadImage($file, $uploadDir = 'uploads/properties/')
    {
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File too large'];
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
        }

        return ['success' => false, 'message' => 'Upload failed'];
    }

    // Pagination function
    function paginate($totalRecords, $recordsPerPage, $currentPage)
    {
        $totalPages = ceil($totalRecords / $recordsPerPage);
        $offset = ($currentPage - 1) * $recordsPerPage;

        return [
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
        ];
    }

    // Generate pagination HTML
    function generatePagination($pagination, $baseUrl)
    {
        $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        // Previous button
        if ($pagination['has_prev']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['prev_page'] . '">Previous</a></li>';
        }

        // Page numbers
        for ($i = 1; $i <= $pagination['total_pages']; $i++) {
            $active = $i == $pagination['current_page'] ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }

        // Next button
        if ($pagination['has_next']) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pagination['next_page'] . '">Next</a></li>';
        }

        $html .= '</ul></nav>';
        return $html;
    }

    // Search properties
    function searchProperties($pdo, $filters = [])
    {
        $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.is_available = 1 AND p.is_verified = 1";

        $params = [];

        if (!empty($filters['location'])) {
            $sql .= " AND p.location = :location";
            $params['location'] = $filters['location'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND p.property_type = :type";
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $params['min_price'] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $params['max_price'] = $filters['max_price'];
        }

        if (!empty($filters['bedrooms'])) {
            $sql .= " AND p.bedrooms >= :bedrooms";
            $params['bedrooms'] = $filters['bedrooms'];
        }

        $sql .= " ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // Get property by ID
    function getProperty($pdo, $id)
    {
        $sql = "SELECT p.*, u.name as owner_name, u.phone as owner_phone, u.email as owner_email 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    // Check if user can book visit
    function canBookVisit($pdo, $propertyId, $userId, $visitDate)
    {
        // Check if user already has a pending or confirmed visit for this property
        $sql = "SELECT COUNT(*) FROM property_visits 
            WHERE property_id = :property_id 
            AND tenant_id = :tenant_id 
            AND visit_date = :visit_date 
            AND status IN ('pending', 'confirmed')";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'property_id' => $propertyId,
            'tenant_id' => $userId,
            'visit_date' => $visitDate
        ]);

        return $stmt->fetchColumn() == 0;
    }

    // Get user's visits
    function getUserVisits($pdo, $userId)
    {
        $sql = "SELECT pv.*, p.title, p.location, p.price, u.name as owner_name 
            FROM property_visits pv 
            JOIN properties p ON pv.property_id = p.id 
            JOIN users u ON p.owner_id = u.id 
            WHERE pv.tenant_id = :user_id 
            ORDER BY pv.visit_date DESC, pv.visit_time DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    // Get owner's property visits
    function getOwnerVisits($pdo, $ownerId)
    {
        $sql = "SELECT pv.*, p.title, p.location, p.price, u.name as tenant_name, u.phone as tenant_phone 
            FROM property_visits pv 
            JOIN properties p ON pv.property_id = p.id 
            JOIN users u ON pv.tenant_id = u.id 
            WHERE p.owner_id = :owner_id 
            ORDER BY pv.visit_date DESC, pv.visit_time DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['owner_id' => $ownerId]);

        return $stmt->fetchAll();
    }
    // Error logging function
    function logError($message, $level = 'ERROR', $file = '', $line = '')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message";

        if ($file && $line) {
            $logMessage .= " [$file:$line]";
        }

        $logMessage .= PHP_EOL;

        // Ensure logs directory exists
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }

        // Write to error log file
        error_log($logMessage, 3, 'logs/error.log');
    }

    // Performance logging function
    function logPerformance($message, $startTime = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);

        $logMessage = "[$timestamp] [PERFORMANCE] $message";

        if ($startTime) {
            $executionTime = microtime(true) - $startTime;
            $logMessage .= " - Execution time: " . round($executionTime, 4) . "s";
        }

        $logMessage .= " - Memory: " . round($memoryUsage / 1024 / 1024, 2) . "MB";
        $logMessage .= " - Peak: " . round($memoryPeak / 1024 / 1024, 2) . "MB";
        $logMessage .= PHP_EOL;

        // Ensure logs directory exists
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }

        // Write to performance log file
        error_log($logMessage, 3, 'logs/performance.log');
    }

    // Database query logging
    function logQuery($query, $params = [], $executionTime = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [QUERY] $query";

        if (!empty($params)) {
            $logMessage .= " - Params: " . json_encode($params);
        }

        if ($executionTime) {
            $logMessage .= " - Time: " . round($executionTime, 4) . "s";
        }

        $logMessage .= PHP_EOL;

        // Ensure logs directory exists
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }

        // Write to query log file
        error_log($logMessage, 3, 'logs/queries.log');
    }

    /**
     * Send email notification
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool Success status
     */
    function sendEmail($to, $subject, $body) {
        // Simple mail function - in production, use PHPMailer or similar
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: RentFinder SL <noreply@rentfinder.lk>" . "\r\n";
        
        return mail($to, $subject, $body, $headers);
    }

    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|false User data or false
     */
    function getUserById($userId) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment history for user
     * 
     * @param int $userId User ID
     * @param int $limit Limit results
     * @return array Payment history
     */
    function getPaymentHistory($userId, $limit = 10) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, ra.property_id, prop.title as property_title, prop.location
                FROM payments p
                LEFT JOIN rental_agreements ra ON p.rental_agreement_id = ra.id
                LEFT JOIN properties prop ON ra.property_id = prop.id
                WHERE ra.tenant_id = ? OR ra.owner_id = ?
                ORDER BY p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting payment history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate commission for rental
     * 
     * @param float $rentalAmount Monthly rent amount
     * @return float Commission amount
     */
    function calculateCommission($rentalAmount) {
        $commission = $rentalAmount * COMMISSION_RATE;
        return max(MINIMUM_COMMISSION, min($commission, MAXIMUM_COMMISSION));
    }

    /**
     * Create rental agreement
     * 
     * @param int $propertyId Property ID
     * @param int $tenantId Tenant ID
     * @param int $ownerId Owner ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param float $monthlyRent Monthly rent
     * @param float $securityDeposit Security deposit
     * @return int|false Rental agreement ID or false
     */
    function createRentalAgreement($propertyId, $tenantId, $ownerId, $startDate, $endDate, $monthlyRent, $securityDeposit = 0) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO rental_agreements (property_id, tenant_id, owner_id, start_date, end_date, monthly_rent, security_deposit, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$propertyId, $tenantId, $ownerId, $startDate, $endDate, $monthlyRent, $securityDeposit]);
            return $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Error creating rental agreement: " . $e->getMessage());
            return false;
        }
    }
} // End of function_exists check
