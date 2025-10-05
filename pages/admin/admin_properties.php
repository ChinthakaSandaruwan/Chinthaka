<?php
// Start output buffering to prevent headers already sent errors
ob_start();

include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php?page=login');
}

// Include header
include 'includes/header.php';

// Handle property verification
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_property'])) {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $action = sanitizeInput($_POST['action'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token) && in_array($action, ['approve', 'reject'])) {
        try {
            $isVerified = $action === 'approve' ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE properties SET is_verified = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$isVerified, $propertyId]);

            if ($action === 'approve') {
                setFlashMessage('success', 'Property approved successfully!');
            } else {
                setFlashMessage('success', 'Property rejected.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to update property status.');
            error_log("Property verification error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=admin_properties');
}

// Handle property deletion
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $propertyId = (int)($_POST['property_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (verifyCSRFToken($csrf_token)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->execute([$propertyId]);
            setFlashMessage('success', 'Property deleted successfully.');
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to delete property.');
            error_log("Property deletion error: " . $e->getMessage());
        }
    }

    redirect('index.php?page=admin_properties');
}

// Get filter parameters
$filter = sanitizeInput($_GET['filter'] ?? 'all');
$search = sanitizeInput($_GET['search'] ?? '');

// Build query
$sql = "SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone 
        FROM properties p 
        JOIN users u ON p.owner_id = u.id 
        WHERE 1=1";

$params = [];

if ($filter === 'pending') {
    $sql .= " AND p.is_verified = 0";
} elseif ($filter === 'verified') {
    $sql .= " AND p.is_verified = 1";
} elseif ($filter === 'available') {
    $sql .= " AND p.is_available = 1";
} elseif ($filter === 'unavailable') {
    $sql .= " AND p.is_available = 0";
}

if (!empty($search)) {
    $sql .= " AND (p.title LIKE :search OR p.location LIKE :search OR u.name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY p.created_at DESC";

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Get total count
$countSql = str_replace("SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();

// Add pagination
$sql .= " LIMIT :offset, :limit";
$params['offset'] = $offset;
$params['limit'] = $recordsPerPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$pagination = paginate($totalRecords, $recordsPerPage, $page);

// Get property for detailed review
$reviewProperty = null;
if (isset($_GET['action']) && $_GET['action'] === 'review' && isset($_GET['id'])) {
    $propertyId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT p.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone 
                          FROM properties p 
                          JOIN users u ON p.owner_id = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$propertyId]);
    $reviewProperty = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Admin - RentFinder SL</title>
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
                <h1 class="display-6 fw-bold text-primary">Property Management</h1>
                <p class="text-muted">Verify, approve, and manage all property listings</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter</label>
                        <select class="form-select" name="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Properties</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Verification</option>
                            <option value="verified" <?php echo $filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            <option value="available" <?php echo $filter === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="unavailable" <?php echo $filter === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search by title, location, or owner...">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Properties Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Owner</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Verification</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($properties)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">No properties found</h6>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo !empty($property['images']) ? json_decode($property['images'])[0] : '../public/images/placeholder.jpg'; ?>"
                                                    class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($property['title']); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?php echo htmlspecialchars(ucfirst($property['location'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($property['owner_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($property['owner_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo formatCurrency($property['price']); ?>/month</div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $property['is_available'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $property['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($property['is_verified']): ?>
                                                <span class="badge bg-success">Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="index.php?page=admin_properties&action=review&id=<?php echo $property['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (!$property['is_verified']): ?>
                                                    <button class="btn btn-sm btn-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#approveModal<?php echo $property['id']; ?>">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal<?php echo $property['id']; ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal<?php echo $property['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Approve Modal -->
                                    <div class="modal fade" id="approveModal<?php echo $property['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Approve Property</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                        <input type="hidden" name="action" value="approve">

                                                        <p>Are you sure you want to approve this property?</p>
                                                        <div class="alert alert-info">
                                                            <strong>Property:</strong> <?php echo htmlspecialchars($property['title']); ?><br>
                                                            <strong>Owner:</strong> <?php echo htmlspecialchars($property['owner_name']); ?><br>
                                                            <strong>Price:</strong> <?php echo formatCurrency($property['price']); ?>/month
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Notes (Optional)</label>
                                                            <textarea class="form-control" name="notes" rows="3"
                                                                placeholder="Add any notes about this approval..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="verify_property" class="btn btn-success">
                                                            <i class="fas fa-check me-2"></i>Approve Property
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade" id="rejectModal<?php echo $property['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reject Property</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                        <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                                        <input type="hidden" name="action" value="reject">

                                                        <p>Are you sure you want to reject this property?</p>
                                                        <div class="alert alert-warning">
                                                            <strong>Property:</strong> <?php echo htmlspecialchars($property['title']); ?><br>
                                                            <strong>Owner:</strong> <?php echo htmlspecialchars($property['owner_name']); ?>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                                            <textarea class="form-control" name="notes" rows="3"
                                                                placeholder="Please provide a reason for rejection..." required></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="verify_property" class="btn btn-danger">
                                                            <i class="fas fa-times me-2"></i>Reject Property
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
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

                                                        <p>Are you sure you want to permanently delete this property?</p>
                                                        <div class="alert alert-danger">
                                                            <strong>Warning:</strong> This action cannot be undone. All associated data will be permanently deleted.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="delete_property" class="btn btn-danger">
                                                            <i class="fas fa-trash me-2"></i>Delete Property
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="mt-4">
                <?php echo generatePagination($pagination, 'index.php?page=admin_properties'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Property Review Modal -->
    <?php if ($reviewProperty): ?>
        <div class="modal fade" id="reviewModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Property Review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Property Images Section -->
                        <?php if (!empty($reviewProperty['images'])): ?>
                            <div class="mb-4">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-images me-2"></i>Property Images
                                </h6>
                                <div class="row">
                                    <?php
                                    $images = json_decode($reviewProperty['images']);
                                    foreach ($images as $index => $image):
                                    ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card">
                                                <img src="<?php echo htmlspecialchars($image); ?>"
                                                    class="card-img-top"
                                                    alt="Property Image <?php echo $index + 1; ?>"
                                                    style="height: 200px; object-fit: cover; cursor: pointer;"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#imageModal<?php echo $index; ?>">
                                                <div class="card-body p-2">
                                                    <small class="text-muted">Image <?php echo $index + 1; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    No images available for this property.
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-home me-2"></i>Property Information
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Title:</strong></td>
                                            <td><?php echo htmlspecialchars($reviewProperty['title']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Type:</strong></td>
                                            <td><?php echo ucfirst($reviewProperty['property_type']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Location:</strong></td>
                                            <td><?php echo htmlspecialchars(ucfirst($reviewProperty['location'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Address:</strong></td>
                                            <td><?php echo htmlspecialchars($reviewProperty['address']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Price:</strong></td>
                                            <td><?php echo formatCurrency($reviewProperty['price']); ?>/month</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Bedrooms:</strong></td>
                                            <td><?php echo $reviewProperty['bedrooms']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Bathrooms:</strong></td>
                                            <td><?php echo $reviewProperty['bathrooms']; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Area:</strong></td>
                                            <td><?php echo $reviewProperty['area_sqft']; ?> sq ft</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <?php if ($reviewProperty['is_verified']): ?>
                                                    <span class="badge bg-success">Verified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending Review</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-user me-2"></i>Owner Information
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Name:</strong></td>
                                            <td><?php echo htmlspecialchars($reviewProperty['owner_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($reviewProperty['owner_email']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td><?php echo htmlspecialchars($reviewProperty['owner_phone']); ?></td>
                                        </tr>
                                    </table>
                                </div>

                                <h6 class="fw-bold mb-3 mt-4">
                                    <i class="fas fa-align-left me-2"></i>Description
                                </h6>
                                <div class="border rounded p-3 bg-light">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($reviewProperty['description'])); ?></p>
                                </div>

                                <?php if (!empty($reviewProperty['amenities'])): ?>
                                    <h6 class="fw-bold mb-3 mt-4">
                                        <i class="fas fa-star me-2"></i>Amenities
                                    </h6>
                                    <div class="border rounded p-3 bg-light">
                                        <ul class="mb-0">
                                            <?php foreach (json_decode($reviewProperty['amenities']) as $amenity): ?>
                                                <li><?php echo htmlspecialchars($amenity); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#approveModal<?php echo $reviewProperty['id']; ?>">
                            <i class="fas fa-check me-2"></i>Approve
                        </button>
                        <button type="button" class="btn btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#rejectModal<?php echo $reviewProperty['id']; ?>">
                            <i class="fas fa-times me-2"></i>Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Viewing Modals -->
        <?php if ($reviewProperty && !empty($reviewProperty['images'])): ?>
            <?php
            $images = json_decode($reviewProperty['images']);
            foreach ($images as $index => $image):
            ?>
                <div class="modal fade" id="imageModal<?php echo $index; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Property Image <?php echo $index + 1; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="<?php echo htmlspecialchars($image); ?>"
                                    class="img-fluid rounded"
                                    alt="Property Image <?php echo $index + 1; ?>"
                                    style="max-height: 70vh;">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <a href="<?php echo htmlspecialchars($image); ?>"
                                    class="btn btn-primary"
                                    target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>Open Full Size
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Auto-show review modal if property ID is in URL
        <?php if ($reviewProperty): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('reviewModal')).show();
            });
        <?php endif; ?>
        <?php include 'includes/footer.php'; ?>