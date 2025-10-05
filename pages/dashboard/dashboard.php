<?php
include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Get user statistics
$stats = [
    'total_properties' => 0,
    'available_properties' => 0,
    'total_visits' => 0,
    'confirmed_visits' => 0,
    'rental_agreements' => 0
];

if ($userType === 'tenant') {
    // Tenant statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits WHERE tenant_id = ?");
    $stmt->execute([$userId]);
    $stats['total_visits'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits WHERE tenant_id = ? AND status = 'confirmed'");
    $stmt->execute([$userId]);
    $stats['confirmed_visits'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rental_agreements WHERE tenant_id = ?");
    $stmt->execute([$userId]);
    $stats['rental_agreements'] = $stmt->fetchColumn();

    // Get recent visits
    $recentVisits = getUserVisits($pdo, $userId);
    $recentVisits = array_slice($recentVisits, 0, 5);
} elseif ($userType === 'owner') {
    // Owner statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ?");
    $stmt->execute([$userId]);
    $stats['total_properties'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ? AND is_available = 1");
    $stmt->execute([$userId]);
    $stats['available_properties'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits pv JOIN properties p ON pv.property_id = p.id WHERE p.owner_id = ?");
    $stmt->execute([$userId]);
    $stats['total_visits'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rental_agreements WHERE owner_id = ?");
    $stmt->execute([$userId]);
    $stats['rental_agreements'] = $stmt->fetchColumn();

    // Get recent visits for owner's properties
    $recentVisits = getOwnerVisits($pdo, $userId);
    $recentVisits = array_slice($recentVisits, 0, 5);

    // Get owner's properties
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentProperties = $stmt->fetchAll();
} elseif ($userType === 'admin') {
    // Admin statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM properties");
    $stmt->execute();
    $stats['total_properties'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE is_available = 1");
    $stmt->execute();
    $stats['available_properties'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits");
    $stmt->execute();
    $stats['total_visits'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rental_agreements");
    $stmt->execute();
    $stats['rental_agreements'] = $stmt->fetchColumn();

    // Get recent visits
    $recentVisits = [];
    $recentProperties = [];
} else {
    // Default for any other user type
    $recentVisits = [];
    $recentProperties = [];
}

// Get recent payments (if any)
$recentPayments = [];
try {
    $stmt = $pdo->prepare("SELECT p.*, ra.property_id, prop.title 
                          FROM payments p 
                          JOIN rental_agreements ra ON p.rental_agreement_id = ra.id 
                          JOIN properties prop ON ra.property_id = prop.id 
                          WHERE (ra.tenant_id = ? OR ra.owner_id = ?) 
                          ORDER BY p.created_at DESC LIMIT 5");
    $stmt->execute([$userId, $userId]);
    $recentPayments = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching recent payments: " . $e->getMessage());
    $recentPayments = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RentFinder SL</title>
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

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h2 class="card-title">
                            Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                        </h2>
                        <p class="card-text">
                            <?php if ($userType === 'tenant'): ?>
                                Find your perfect rental property and manage your bookings.
                            <?php else: ?>
                                Manage your properties and track your rental business.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <?php if ($userType === 'tenant'): ?>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['total_visits']; ?></div>
                        <div class="dashboard-label">Total Visits</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['confirmed_visits']; ?></div>
                        <div class="dashboard-label">Confirmed Visits</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['rental_agreements']; ?></div>
                        <div class="dashboard-label">Rental Agreements</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat">0</div>
                        <div class="dashboard-label">Active Payments</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['total_properties']; ?></div>
                        <div class="dashboard-label">Total Properties</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['available_properties']; ?></div>
                        <div class="dashboard-label">Available Properties</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['total_visits']; ?></div>
                        <div class="dashboard-label">Visit Requests</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="dashboard-card text-center">
                        <div class="dashboard-stat"><?php echo $stats['rental_agreements']; ?></div>
                        <div class="dashboard-label">Active Rentals</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row">
            <!-- Recent Activity -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentVisits)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No recent activity</h6>
                                <p class="text-muted">Your recent visits and bookings will appear here.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentVisits as $visit): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($visit['title']); ?></h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($visit['location']); ?>
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo formatDate($visit['visit_date']); ?> at
                                                    <?php echo date('g:i A', strtotime($visit['visit_time'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge badge-<?php echo strtolower($visit['status']); ?>">
                                                <?php echo ucfirst($visit['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Properties (for owners) -->
                <?php if ($userType === 'owner' && !empty($recentProperties)): ?>
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-home me-2"></i>
                                Recent Properties
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($recentProperties as $property): ?>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($property['images']) ? json_decode($property['images'])[0] : '../public/images/placeholder.jpg'; ?>"
                                                class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                <p class="text-muted mb-1"><?php echo formatCurrency($property['price']); ?>/month</p>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($property['location']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($userType === 'tenant'): ?>
                                <a href="index.php?page=properties" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Browse Properties
                                </a>
                                <a href="index.php?page=my_bookings" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar me-2"></i>My Bookings
                                </a>
                                <a href="index.php?page=my_payments" class="btn btn-outline-primary">
                                    <i class="fas fa-credit-card me-2"></i>My Payments
                                </a>
                            <?php else: ?>
                                <a href="index.php?page=add_property" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Property
                                </a>
                                <a href="index.php?page=my_properties" class="btn btn-outline-primary">
                                    <i class="fas fa-home me-2"></i>My Properties
                                </a>
                                <a href="index.php?page=my_bookings" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar me-2"></i>Visit Requests
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments -->
                <?php if (!empty($recentPayments)): ?>
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                Recent Payments
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentPayments as $payment): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($payment['title']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo ucfirst($payment['payment_type']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-semibold"><?php echo formatCurrency($payment['amount']); ?></div>
                                                <small class="text-muted">
                                                    <?php echo formatDate($payment['created_at']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>

</html>