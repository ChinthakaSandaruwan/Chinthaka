<?php
include 'config/database.php';
include 'includes/functions.php';

$propertyId = (int)($_GET['id'] ?? 0);

if (!$propertyId) {
    redirect('index.php?page=properties');
}

// Get property details
$property = getProperty($pdo, $propertyId);

if (!$property) {
    redirect('index.php?page=properties');
}

// Handle visit booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_visit'])) {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to book a visit.');
        redirect('index.php?page=login');
    }

    $visitDate = sanitizeInput($_POST['visit_date'] ?? '');
    $visitTime = sanitizeInput($_POST['visit_time'] ?? '');
    $notes = sanitizeInput($_POST['visit_notes'] ?? '');

    if (empty($visitDate) || empty($visitTime)) {
        setFlashMessage('error', 'Please select both date and time for your visit.');
    } else {
        try {
            // Check if user can book visit
            if (!canBookVisit($pdo, $propertyId, $_SESSION['user_id'], $visitDate)) {
                setFlashMessage('error', 'You already have a visit scheduled for this property on this date.');
            } else {
                // Book the visit
                $stmt = $pdo->prepare("INSERT INTO property_visits (property_id, tenant_id, visit_date, visit_time, notes) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$propertyId, $_SESSION['user_id'], $visitDate, $visitTime, $notes]);

                setFlashMessage('success', 'Visit request submitted successfully! The property owner will contact you soon.');
            }
        } catch (Exception $e) {
            setFlashMessage('error', 'Failed to book visit. Please try again.');
            error_log("Visit booking error: " . $e->getMessage());
        }
    }
}

// Get property images
$images = !empty($property['images']) ? json_decode($property['images'], true) : [];
if (empty($images)) {
    $images = ['../public/images/placeholder.jpg'];
}

// Get amenities
$amenities = !empty($property['amenities']) ? json_decode($property['amenities'], true) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - RentFinder SL</title>
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

        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="index.php?page=properties">Properties</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($property['title']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Property Images -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <?php if (count($images) > 1): ?>
                            <!-- Image Carousel -->
                            <div id="propertyCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner">
                                    <?php foreach ($images as $index => $image): ?>
                                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                            <img src="<?php echo htmlspecialchars($image); ?>"
                                                class="d-block w-100" style="height: 400px; object-fit: cover;"
                                                alt="Property Image <?php echo $index + 1; ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#propertyCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#propertyCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Single Image -->
                            <img src="<?php echo htmlspecialchars($images[0]); ?>"
                                class="img-fluid w-100" style="height: 400px; object-fit: cover;"
                                alt="Property Image">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Property Info & Booking -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 100px;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h2 class="h4 fw-bold text-primary mb-0"><?php echo htmlspecialchars($property['title']); ?></h2>
                            <span class="badge bg-success">Available</span>
                        </div>

                        <p class="text-muted mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars(ucfirst($property['location'])); ?>
                        </p>

                        <div class="property-price mb-4">
                            <span class="h3 fw-bold text-primary"><?php echo formatCurrency($property['price']); ?></span>
                            <span class="text-muted">/month</span>
                        </div>

                        <!-- Property Details -->
                        <div class="row g-3 mb-4">
                            <?php if ($property['bedrooms'] > 0): ?>
                                <div class="col-4 text-center">
                                    <div class="amenity-icon">
                                        <i class="fas fa-bed fa-2x text-primary mb-2"></i>
                                        <div class="fw-semibold"><?php echo $property['bedrooms']; ?></div>
                                        <small class="text-muted">Bedrooms</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($property['bathrooms'] > 0): ?>
                                <div class="col-4 text-center">
                                    <div class="amenity-icon">
                                        <i class="fas fa-bath fa-2x text-primary mb-2"></i>
                                        <div class="fw-semibold"><?php echo $property['bathrooms']; ?></div>
                                        <small class="text-muted">Bathrooms</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($property['area_sqft'] > 0): ?>
                                <div class="col-4 text-center">
                                    <div class="amenity-icon">
                                        <i class="fas fa-ruler-combined fa-2x text-primary mb-2"></i>
                                        <div class="fw-semibold"><?php echo $property['area_sqft']; ?></div>
                                        <small class="text-muted">Sq Ft</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Owner Contact -->
                        <div class="border-top pt-3 mb-4">
                            <h6 class="fw-semibold mb-2">Property Owner</h6>
                            <p class="text-muted mb-1">
                                <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($property['owner_name']); ?>
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($property['owner_phone']); ?>
                            </p>
                        </div>

                        <!-- Book Visit Button -->
                        <?php if (isLoggedIn()): ?>
                            <button class="btn btn-primary w-100 btn-lg mb-3" data-bs-toggle="modal" data-bs-target="#visitModal">
                                <i class="fas fa-calendar-plus me-2"></i>Book a Visit
                            </button>

                            <!-- Payment Buttons -->
                            <div class="d-grid gap-2">
                                <a href="index.php?page=process_payment&property_id=<?php echo $property['id']; ?>&type=rent"
                                    class="btn btn-success btn-lg">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Pay Rent - Rs. <?php echo number_format($property['price'], 2); ?>
                                </a>

                                <a href="index.php?page=process_payment&property_id=<?php echo $property['id']; ?>&type=deposit"
                                    class="btn btn-outline-success">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Pay Security Deposit
                                </a>
                            </div>
                        <?php else: ?>
                            <a href="index.php?page=login" class="btn btn-primary w-100 btn-lg mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Login to Book Visit
                            </a>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Login Required:</strong> Please login to make payments or book visits.
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Verified Property
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Property Description -->
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Description</h4>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                    </div>
                </div>

                <!-- Amenities -->
                <?php if (!empty($amenities)): ?>
                    <div class="card shadow-sm mt-4">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Amenities</h4>
                            <div class="row g-3">
                                <?php foreach ($amenities as $amenity): ?>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <span><?php echo htmlspecialchars($amenity); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Location -->
                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h4 class="fw-bold mb-3">Location</h4>
                        <p class="text-muted mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($property['address']); ?>
                        </p>
                        <p class="text-muted">
                            <i class="fas fa-city me-2"></i>
                            <?php echo htmlspecialchars(ucfirst($property['location'])); ?>
                        </p>
                    </div>
                </div>
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
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="book_visit" value="1">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Set minimum date to today
        document.getElementById('visit_date').min = new Date().toISOString().split('T')[0];
    </script>
</body>

</html>