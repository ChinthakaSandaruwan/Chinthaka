<?php
// Start output buffering to prevent headers already sent errors
ob_start();

// Start session first to avoid headers already sent error
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute paths to avoid working directory issues
$root = dirname(__DIR__, 2); // Go up 2 levels from pages/properties to root
include $root . '/config/database.php';
include $root . '/includes/functions.php';

// Get search filters
$filters = [
    'location' => sanitizeInput($_GET['location'] ?? ''),
    'type' => sanitizeInput($_GET['type'] ?? ''),
    'min_price' => sanitizeInput($_GET['min_price'] ?? ''),
    'max_price' => sanitizeInput($_GET['max_price'] ?? ''),
    'bedrooms' => sanitizeInput($_GET['bedrooms'] ?? ''),
    'search' => sanitizeInput($_GET['search'] ?? '')
];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$recordsPerPage = 12;
$offset = ($page - 1) * $recordsPerPage;

// Build search query
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

if (!empty($filters['search'])) {
    $sql .= " AND (p.title LIKE :search OR p.description LIKE :search OR p.location LIKE :search)";
    $params['search'] = '%' . $filters['search'] . '%';
}

// Get total count for pagination
$countSql = str_replace("SELECT p.*, u.name as owner_name, u.phone as owner_phone", "SELECT COUNT(*)", $sql);
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();

// Add pagination and ordering
$sql .= " ORDER BY p.created_at DESC LIMIT :offset, :limit";
$params['offset'] = $offset;
$params['limit'] = $recordsPerPage;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Calculate pagination
$pagination = paginate($totalRecords, $recordsPerPage, $page);

// Get locations for filter dropdown
$locationStmt = $pdo->query("SELECT DISTINCT location FROM properties WHERE is_available = 1 ORDER BY location");
$locations = $locationStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Unified Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="py-5 bg-light" style="margin-top: 76px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1 class="display-5 fw-bold text-primary">Find Your Perfect Property</h1>
                    <p class="lead text-muted">Discover amazing rental properties across Sri Lanka</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <p class="text-muted mb-0">
                        Showing <?php echo count($properties); ?> of <?php echo $totalRecords; ?> properties
                    </p>
                </div>
            </div>
        </div>
    </section>

    <div class="container py-5">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filter-form">
                            <div class="mb-3">
                                <label class="form-label">Search</label>
                                <input type="text" class="form-control" name="search"
                                    value="<?php echo htmlspecialchars($filters['search']); ?>"
                                    placeholder="Search properties...">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <select class="form-select" name="location">
                                    <option value="">All Locations</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo htmlspecialchars($location); ?>"
                                            <?php echo $filters['location'] === $location ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars(ucfirst($location)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <option value="apartment" <?php echo $filters['type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                    <option value="house" <?php echo $filters['type'] === 'house' ? 'selected' : ''; ?>>House</option>
                                    <option value="villa" <?php echo $filters['type'] === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                    <option value="room" <?php echo $filters['type'] === 'room' ? 'selected' : ''; ?>>Room</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bedrooms</label>
                                <select class="form-select" name="bedrooms">
                                    <option value="">Any</option>
                                    <option value="1" <?php echo $filters['bedrooms'] === '1' ? 'selected' : ''; ?>>1+ Bedroom</option>
                                    <option value="2" <?php echo $filters['bedrooms'] === '2' ? 'selected' : ''; ?>>2+ Bedrooms</option>
                                    <option value="3" <?php echo $filters['bedrooms'] === '3' ? 'selected' : ''; ?>>3+ Bedrooms</option>
                                    <option value="4" <?php echo $filters['bedrooms'] === '4' ? 'selected' : ''; ?>>4+ Bedrooms</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Min Price (LKR)</label>
                                <input type="number" class="form-control" name="min_price"
                                    value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                    placeholder="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Max Price (LKR)</label>
                                <input type="number" class="form-control" name="max_price"
                                    value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                    placeholder="100000">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Apply Filters
                                </button>
                                <a href="index.php?page=properties" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Properties Grid -->
            <div class="col-lg-9">
                <?php if (empty($properties)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No properties found</h4>
                        <p class="text-muted">Try adjusting your search criteria or browse all properties.</p>
                        <a href="index.php?page=properties" class="btn btn-primary">View All Properties</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($properties as $property): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="property-card h-100">
                                    <div class="property-image"
                                        style="background-image: url('<?php echo !empty($property['images']) ? json_decode($property['images'])[0] : '../public/images/placeholder.jpg'; ?>');">
                                        <div class="property-badge">
                                            <?php echo ucfirst($property['property_type']); ?>
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

                                        <div class="d-grid gap-2">
                                            <a href="index.php?page=property_details&id=<?php echo $property['id']; ?>"
                                                class="btn btn-primary">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a>
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-outline-primary"
                                                    onclick="bookVisit(<?php echo $property['id']; ?>)">
                                                    <i class="fas fa-calendar-plus me-2"></i>Book Visit
                                                </button>
                                            <?php else: ?>
                                                <a href="index.php?page=login" class="btn btn-outline-primary">
                                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Book Visit
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="mt-5">
                            <?php echo generatePagination($pagination, 'properties.php'); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Visit Booking Modal -->
    <div class="modal fade" id="visitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Property Visit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="visit-booking-form">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="visit_date" class="form-label">Visit Date</label>
                            <input type="date" class="form-control" id="visit_date" name="visit_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="visit_time" class="form-label">Visit Time</label>
                            <select class="form-select" id="visit_time" name="visit_time" required>
                                <option value="">Select time</option>
                                <option value="09:00">9:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="14:00">2:00 PM</option>
                                <option value="15:00">3:00 PM</option>
                                <option value="16:00">4:00 PM</option>
                                <option value="17:00">5:00 PM</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="visit_notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="visit_notes" name="visit_notes" rows="3"
                                placeholder="Any specific requirements or questions..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Book Visit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Initialize dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });

        // Set minimum date to today
        document.getElementById('visit_date').min = new Date().toISOString().split('T')[0];

        // Book visit function
        function bookVisit(propertyId) {
            document.getElementById('visit-booking-form').setAttribute('data-property-id', propertyId);
            new bootstrap.Modal(document.getElementById('visitModal')).show();
        }

        // Handle visit booking form submission
        document.getElementById('visit-booking-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const propertyId = this.getAttribute('data-property-id');
            const formData = new FormData(this);
            formData.append('property_id', propertyId);

            // Simulate API call
            setTimeout(() => {
                showAlert('Visit request submitted successfully!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('visitModal')).hide();
                this.reset();
            }, 1000);
        });
    </script>
</body>

</html>