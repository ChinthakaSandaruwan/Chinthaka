<?php

/**
 * Home Page
 * Main landing page for RentFinder SL
 */

// Page title is set in index.php
// Database connection is available through index.php includes
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Find Your Perfect Rental Property in Sri Lanka</h1>
                <p class="lead mb-4">Connect with verified property owners and find your ideal home with our secure rental platform.</p>
                <div class="d-flex gap-3">
                    <a href="?page=properties" class="btn btn-light btn-lg">Browse Properties</a>
                    <a href="?page=register" class="btn btn-outline-light btn-lg">Get Started</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="public/images/placeholder.jpg" alt="Rental Properties" class="img-fluid rounded">
            </div>
        </div>
    </div>
</section>

<!-- Featured Properties Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold text-primary">Featured Properties</h2>
                <p class="lead">Discover our latest verified rental properties</p>
            </div>
        </div>

        <?php
        // Get featured properties (verified properties)
        try {
            $stmt = $pdo->prepare("
                SELECT p.*, u.name as owner_name, u.phone as owner_phone
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.is_verified = 1 AND p.is_available = 1
                ORDER BY p.created_at DESC 
                LIMIT 6
            ");
            $stmt->execute();
            $featuredProperties = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Process images for each property
            foreach ($featuredProperties as &$property) {
                $images = json_decode($property['images'] ?? '[]', true);
                $property['main_image'] = !empty($images) ? $images[0] : null;
            }

            if (!empty($featuredProperties)):
        ?>
                <div class="row">
                    <?php foreach ($featuredProperties as $property): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card property-card h-100 shadow-sm">
                                <div class="position-relative">
                                    <?php if ($property['main_image']): ?>
                                        <img src="<?php echo htmlspecialchars($property['main_image']); ?>"
                                            class="card-img-top property-image"
                                            alt="<?php echo htmlspecialchars($property['title']); ?>"
                                            style="height: 250px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                            style="height: 250px;">
                                            <i class="fas fa-home fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-success">Verified</span>
                                    </div>

                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-primary"><?php echo ucfirst($property['property_type']); ?></span>
                                    </div>
                                </div>

                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-primary"><?php echo htmlspecialchars($property['title']); ?></h5>
                                    <p class="card-text text-muted small">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($property['location']); ?>
                                    </p>

                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <small class="text-muted">Bedrooms</small>
                                            <div class="fw-bold"><?php echo $property['bedrooms']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Bathrooms</small>
                                            <div class="fw-bold"><?php echo $property['bathrooms']; ?></div>
                                        </div>
                                        <div class="col-4">
                                            <small class="text-muted">Size</small>
                                            <div class="fw-bold"><?php echo $property['area_sqft']; ?> sq ft</div>
                                        </div>
                                    </div>

                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="price">
                                                <span class="h4 text-success fw-bold">Rs. <?php echo number_format($property['price']); ?></span>
                                                <small class="text-muted">/month</small>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($property['owner_name']); ?>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <a href="?page=property_details&id=<?php echo $property['id']; ?>"
                                                class="btn btn-primary">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-12 text-center">
                        <a href="?page=properties" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-search me-2"></i>View All Properties
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <div class="row">
                    <div class="col-12 text-center">
                        <div class="py-5">
                            <i class="fas fa-home fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Properties Available</h4>
                            <p class="text-muted">Check back soon for new rental properties!</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php
        } catch (Exception $e) {
            error_log("Error fetching featured properties: " . $e->getMessage());
            echo '<div class="alert alert-warning">Unable to load properties at this time. Please try again later.</div>';
        }
        ?>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="feature-card p-4">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h3>Verified Properties</h3>
                    <p>All properties are verified by our team to ensure quality and authenticity.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card p-4">
                    <i class="fas fa-credit-card fa-3x text-primary mb-3"></i>
                    <h3>Secure Payments</h3>
                    <p>Safe and secure payment processing with guaranteed payouts for property owners.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="feature-card p-4">
                    <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                    <h3>24/7 Support</h3>
                    <p>Round-the-clock customer support to help you with any questions or issues.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer is included by index.php -->