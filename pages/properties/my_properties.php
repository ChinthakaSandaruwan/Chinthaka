<?php
include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in and is an owner
if (!isLoggedIn() || !isOwner()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];

// Handle property status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $status = sanitizeInput($_POST['status'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && in_array($status, ['available', 'unavailable'])) {
        try {
            // Check if property belongs to user
            $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND owner_id = ?");
            $stmt->execute([$propertyId, $userId]);
            
            if ($stmt->fetch()) {
                $isAvailable = $status === 'available' ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE properties SET is_available = ? WHERE id = ?");
                $stmt->execute([$isAvailable, $propertyId]);
                setFlashMessage('success', 'Property status updated successfully.');
            } else {
                setFlashMessage('error', 'You do not have permission to update this property.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update property status.');
            error_log("Property status update error: " . $e->getMessage());
        }
    }
    
    redirect('index.php?page=my_properties');
}

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        try {
            // Check if property belongs to user
            $stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND owner_id = ?");
            $stmt->execute([$propertyId, $userId]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
                $stmt->execute([$propertyId]);
                setFlashMessage('success', 'Property deleted successfully.');
            } else {
                setFlashMessage('error', 'You do not have permission to delete this property.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to delete property.');
            error_log("Property deletion error: " . $e->getMessage());
        }
    }
    
    redirect('index.php?page=my_properties');
}

// Get user's properties
$stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$properties = $stmt->fetchAll();

// Get property statistics
$stats = [
    'total' => count($properties),
    'available' => count(array_filter($properties, function($p) { return $p['is_available']; })),
    'verified' => count(array_filter($properties, function($p) { return $p['is_verified']; })),
    'total_rent' => array_sum(array_column($properties, 'price'))
];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 6;
$offset = ($page - 1) * $recordsPerPage;
$totalProperties = count($properties);
$paginatedProperties = array_slice($properties, $offset, $recordsPerPage);
$pagination = paginate($totalProperties, $recordsPerPage, $page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

    <div class="container py-5" style="margin-top: 76px;">
        <!-- Flash Messages -->
        <?php displayFlashMessage(); ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="display-6 fw-bold text-primary">My Properties</h1>
                        <p class="text-muted">Manage your rental properties and track performance</p>
                    </div>
                    <a href="index.php?page=add_property" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Add Property
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-primary"><?php echo $stats['total']; ?></div>
                    <div class="dashboard-label">Total Properties</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-success"><?php echo $stats['available']; ?></div>
                    <div class="dashboard-label">Available</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-warning"><?php echo $stats['verified']; ?></div>
                    <div class="dashboard-label">Verified</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-stat text-info"><?php echo formatCurrency($stats['total_rent']); ?></div>
                    <div class="dashboard-label">Total Monthly Rent</div>
                </div>
            </div>
        </div>

        <?php if (empty($paginatedProperties)): ?>
            <div class="text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No properties found</h4>
                <p class="text-muted">You haven't added any properties yet. Start by adding your first property.</p>
                <a href="index.php?page=add_property" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Your First Property
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($paginatedProperties as $property): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="property-card h-100">
                            <div class="property-image" 
                                 style="background-image: url('<?php echo !empty($property['images']) ? json_decode($property['images'])[0] : '../public/images/placeholder.jpg'; ?>');">
                                <div class="property-badge">
                                    <?php echo ucfirst($property['property_type']); ?>
                                </div>
                                <div class="property-status">
                                    <?php if ($property['is_verified']): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending Review</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold text-primary">
                                    <?php echo htmlspecialchars($property['title']); ?>
                                </h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars(ucfirst($property['location'])); ?>
                                </p>
                                <p class="property-price mb-3">
                                    <?php echo formatCurrency($property['price']); ?>/month
                                </p>
                                
                                <div class="property-amenities mb-3">
                                    <?php if ($property['bedrooms'] > 0): ?>
                                        <div class="amenity">
                                            <i class="fas fa-bed"></i>
                                            <span><?php echo $property['bedrooms']; ?> Bed</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($property['bathrooms'] > 0): ?>
                                        <div class="amenity">
                                            <i class="fas fa-bath"></i>
                                            <span><?php echo $property['bathrooms']; ?> Bath</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($property['area_sqft'] > 0): ?>
                                        <div class="amenity">
                                            <i class="fas fa-ruler-combined"></i>
                                            <span><?php echo $property['area_sqft']; ?> sq ft</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge <?php echo $property['is_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $property['is_available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                    <small class="text-muted">
                                        Added <?php echo formatDate($property['created_at']); ?>
                                    </small>
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="index.php?page=property_details&id=<?php echo $property['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                    
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#statusModal<?php echo $property['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteModal<?php echo $property['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Update Modal -->
                    <div class="modal fade" id="statusModal<?php echo $property['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Update Property Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                        
                                        <p class="mb-3">Current status: 
                                            <span class="badge <?php echo $property['is_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $property['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </p>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">New Status</label>
                                            <select class="form-select" name="status" required>
                                                <option value="available" <?php echo $property['is_available'] ? 'selected' : ''; ?>>Available</option>
                                                <option value="unavailable" <?php echo !$property['is_available'] ? 'selected' : ''; ?>>Unavailable</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Confirmation Modal -->
                    <div class="modal fade" id="deleteModal<?php echo $property['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Delete Property</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                        
                                        <p>Are you sure you want to delete this property?</p>
                                        <div class="alert alert-warning">
                                            <strong>Warning:</strong> This action cannot be undone. All associated data will be permanently deleted.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="delete_property" class="btn btn-danger">Delete Property</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-5">
                    <?php echo generatePagination($pagination, 'my_properties.php'); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>
</html>
