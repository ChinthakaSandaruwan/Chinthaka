<?php
include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $visitId = (int)($_POST['visit_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && in_array($status, ['confirmed', 'completed', 'cancelled'])) {
        try {
            // Check if user has permission to update this visit
            if ($userType === 'tenant') {
                $stmt = $pdo->prepare("SELECT id FROM property_visits WHERE id = ? AND tenant_id = ?");
            } else {
                $stmt = $pdo->prepare("SELECT pv.id FROM property_visits pv 
                                      JOIN properties p ON pv.property_id = p.id 
                                      WHERE pv.id = ? AND p.owner_id = ?");
            }
            $stmt->execute([$visitId, $userId]);

            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE property_visits SET status = ? WHERE id = ?");
                $stmt->execute([$status, $visitId]);
                setFlashMessage('success', 'Visit status updated successfully.');
            } else {
                setFlashMessage('error', 'You do not have permission to update this visit.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update visit status.');
            error_log("Visit status update error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=my_bookings');
}

// Get visits based on user type
if ($userType === 'tenant') {
    $visits = getUserVisits($pdo, $userId);
} else {
    $visits = getOwnerVisits($pdo, $userId);
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;
$totalVisits = count($visits);
$visits = array_slice($visits, $offset, $recordsPerPage);
$pagination = paginate($totalVisits, $recordsPerPage, $page);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - RentFinder SL</title>
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

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="display-6 fw-bold text-primary">
                    <?php echo $userType === 'tenant' ? 'My Bookings' : 'Visit Requests'; ?>
                </h1>
                <p class="text-muted">
                    <?php echo $userType === 'tenant'
                        ? 'Manage your property visits and bookings'
                        : 'Manage visit requests for your properties'; ?>
                </p>
            </div>
        </div>

        <?php if (empty($visits)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No bookings found</h4>
                <p class="text-muted">
                    <?php echo $userType === 'tenant'
                        ? 'You haven\'t booked any property visits yet.'
                        : 'No visit requests for your properties yet.'; ?>
                </p>
                <?php if ($userType === 'tenant'): ?>
                    <a href="index.php?page=properties" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Browse Properties
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Date & Time</th>
                                    <?php if ($userType === 'owner'): ?>
                                        <th>Tenant</th>
                                    <?php endif; ?>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visits as $visit): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo !empty($visit['images']) ? json_decode($visit['images'])[0] : '../public/images/placeholder.jpg'; ?>"
                                                    class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($visit['title']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars($visit['location']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo formatDate($visit['visit_date']); ?></strong>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('g:i A', strtotime($visit['visit_time'])); ?>
                                            </small>
                                        </td>
                                        <?php if ($userType === 'owner'): ?>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($visit['tenant_name']); ?></strong>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?php echo htmlspecialchars($visit['tenant_phone']); ?>
                                                </small>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($visit['status']); ?>">
                                                <?php echo ucfirst($visit['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#visitModal<?php echo $visit['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <?php if ($userType === 'owner' && $visit['status'] === 'pending'): ?>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-success dropdown-toggle"
                                                            data-bs-toggle="dropdown">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                    <input type="hidden" name="visit_id" value="<?php echo $visit['id']; ?>">
                                                                    <input type="hidden" name="status" value="confirmed">
                                                                    <button type="submit" name="update_status" class="dropdown-item">
                                                                        <i class="fas fa-check me-2"></i>Confirm
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                    <input type="hidden" name="visit_id" value="<?php echo $visit['id']; ?>">
                                                                    <input type="hidden" name="status" value="cancelled">
                                                                    <button type="submit" name="update_status" class="dropdown-item text-danger">
                                                                        <i class="fas fa-times me-2"></i>Cancel
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Visit Details Modal -->
                                    <div class="modal fade" id="visitModal<?php echo $visit['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Visit Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <h6>Property Information</h6>
                                                            <p><strong>Title:</strong> <?php echo htmlspecialchars($visit['title']); ?></p>
                                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($visit['location']); ?></p>
                                                            <p><strong>Price:</strong> <?php echo formatCurrency($visit['price']); ?>/month</p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <h6>Visit Information</h6>
                                                            <p><strong>Date:</strong> <?php echo formatDate($visit['visit_date']); ?></p>
                                                            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($visit['visit_time'])); ?></p>
                                                            <p><strong>Status:</strong>
                                                                <span class="badge badge-<?php echo strtolower($visit['status']); ?>">
                                                                    <?php echo ucfirst($visit['status']); ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <?php if ($userType === 'owner'): ?>
                                                        <hr>
                                                        <h6>Tenant Information</h6>
                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($visit['tenant_name']); ?></p>
                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($visit['tenant_phone']); ?></p>
                                                    <?php endif; ?>

                                                    <?php if (!empty($visit['notes'])): ?>
                                                        <hr>
                                                        <h6>Notes</h6>
                                                        <p><?php echo nl2br(htmlspecialchars($visit['notes'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <?php if ($userType === 'owner' && $visit['status'] === 'pending'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                            <input type="hidden" name="visit_id" value="<?php echo $visit['id']; ?>">
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" name="update_status" class="btn btn-success">
                                                                <i class="fas fa-check me-2"></i>Confirm Visit
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-4">
                    <?php echo generatePagination($pagination, 'my_bookings.php'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>

</html>