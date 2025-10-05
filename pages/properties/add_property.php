<?php
include 'config/database.php';
include 'includes/functions.php';

// Check if user is logged in and is an owner
if (!isLoggedIn() || !isOwner()) {
    redirect('index.php?page=login');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $property_type = sanitizeInput($_POST['property_type'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $area_sqft = (int)($_POST['area_sqft'] ?? 0);
    $amenities = $_POST['amenities'] ?? [];
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    // Validate title
    if (empty($title)) {
        $errors[] = 'Property title is required.';
    } elseif (strlen($title) < 5) {
        $errors[] = 'Property title must be at least 5 characters long.';
    }

    // Validate description
    if (empty($description)) {
        $errors[] = 'Property description is required.';
    } elseif (strlen($description) < 20) {
        $errors[] = 'Property description must be at least 20 characters long.';
    }

    // Validate property type
    if (!in_array($property_type, ['apartment', 'house', 'villa', 'room'])) {
        $errors[] = 'Please select a valid property type.';
    }

    // Validate location
    if (empty($location)) {
        $errors[] = 'Location is required.';
    }

    // Validate address
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }

    // Validate price
    if ($price <= 0) {
        $errors[] = 'Please enter a valid price.';
    }

    // Validate bedrooms and bathrooms
    if ($bedrooms < 0) {
        $errors[] = 'Number of bedrooms cannot be negative.';
    }
    if ($bathrooms < 0) {
        $errors[] = 'Number of bathrooms cannot be negative.';
    }

    // Handle image uploads
    $uploadedImages = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $imageCount = count($_FILES['images']['name']);

        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'size' => $_FILES['images']['size'][$i]
                ];

                $uploadResult = uploadImage($file);
                if ($uploadResult['success']) {
                    $uploadedImages[] = $uploadResult['filepath'];
                } else {
                    $errors[] = 'Failed to upload image: ' . $uploadResult['message'];
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            // Insert property
            $stmt = $pdo->prepare("INSERT INTO properties (owner_id, title, description, property_type, location, address, price, bedrooms, bathrooms, area_sqft, amenities, images, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $amenitiesJson = json_encode($amenities);
            $imagesJson = json_encode($uploadedImages);

            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $description,
                $property_type,
                $location,
                $address,
                $price,
                $bedrooms,
                $bathrooms,
                $area_sqft,
                $amenitiesJson,
                $imagesJson,
                0 // Will be verified by admin (0 = false, 1 = true)
            ]);

            setFlashMessage('success', 'Property added successfully! It will be reviewed and verified before being published.');
            redirect('index.php?page=my_properties');
        } catch (Exception $e) {
            $errors[] = 'Failed to add property. Please try again.';
            error_log("Property creation error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - RentFinder SL</title>
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
                <h1 class="display-6 fw-bold text-primary">Add New Property</h1>
                <p class="text-muted">List your property and start earning rental income</p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                            <!-- Basic Information -->
                            <h5 class="fw-bold mb-3 text-primary">Basic Information</h5>

                            <div class="row g-3 mb-4">
                                <div class="col-md-8">
                                    <label for="title" class="form-label">Property Title</label>
                                    <input type="text" class="form-control" id="title" name="title"
                                        value="<?php echo htmlspecialchars($title ?? ''); ?>"
                                        placeholder="e.g., Modern 2BR Apartment in Colombo 7" required>
                                    <div class="invalid-feedback">
                                        Please provide a property title.
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="property_type" class="form-label">Property Type</label>
                                    <select class="form-select" id="property_type" name="property_type" required>
                                        <option value="">Select Type</option>
                                        <option value="apartment" <?php echo ($property_type ?? '') === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                        <option value="house" <?php echo ($property_type ?? '') === 'house' ? 'selected' : ''; ?>>House</option>
                                        <option value="villa" <?php echo ($property_type ?? '') === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                        <option value="room" <?php echo ($property_type ?? '') === 'room' ? 'selected' : ''; ?>>Room</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a property type.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                    placeholder="Describe your property in detail..." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                                <div class="form-text">Provide a detailed description of your property, including key features and benefits.</div>
                                <div class="invalid-feedback">
                                    Please provide a property description.
                                </div>
                            </div>

                            <!-- Location Information -->
                            <h5 class="fw-bold mb-3 text-primary">Location Information</h5>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="location" class="form-label">City</label>
                                    <select class="form-select" id="location" name="location" required>
                                        <option value="">Select City</option>
                                        <option value="colombo" <?php echo ($location ?? '') === 'colombo' ? 'selected' : ''; ?>>Colombo</option>
                                        <option value="kandy" <?php echo ($location ?? '') === 'kandy' ? 'selected' : ''; ?>>Kandy</option>
                                        <option value="galle" <?php echo ($location ?? '') === 'galle' ? 'selected' : ''; ?>>Galle</option>
                                        <option value="negombo" <?php echo ($location ?? '') === 'negombo' ? 'selected' : ''; ?>>Negombo</option>
                                        <option value="anuradhapura" <?php echo ($location ?? '') === 'anuradhapura' ? 'selected' : ''; ?>>Anuradhapura</option>
                                        <option value="jaffna" <?php echo ($location ?? '') === 'jaffna' ? 'selected' : ''; ?>>Jaffna</option>
                                        <option value="kurunegala" <?php echo ($location ?? '') === 'kurunegala' ? 'selected' : ''; ?>>Kurunegala</option>
                                        <option value="ratnapura" <?php echo ($location ?? '') === 'ratnapura' ? 'selected' : ''; ?>>Ratnapura</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a city.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Monthly Rent (LKR)</label>
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="<?php echo htmlspecialchars($price ?? ''); ?>"
                                        min="0" step="0.01" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid price.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="address" class="form-label">Full Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"
                                    placeholder="Enter the complete address..." required><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                                <div class="invalid-feedback">
                                    Please provide the property address.
                                </div>
                            </div>

                            <!-- Property Details -->
                            <h5 class="fw-bold mb-3 text-primary">Property Details</h5>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label for="bedrooms" class="form-label">Bedrooms</label>
                                    <input type="number" class="form-control" id="bedrooms" name="bedrooms"
                                        value="<?php echo htmlspecialchars($bedrooms ?? '0'); ?>"
                                        min="0" max="20">
                                </div>
                                <div class="col-md-3">
                                    <label for="bathrooms" class="form-label">Bathrooms</label>
                                    <input type="number" class="form-control" id="bathrooms" name="bathrooms"
                                        value="<?php echo htmlspecialchars($bathrooms ?? '0'); ?>"
                                        min="0" max="20">
                                </div>
                                <div class="col-md-6">
                                    <label for="area_sqft" class="form-label">Area (Square Feet)</label>
                                    <input type="number" class="form-control" id="area_sqft" name="area_sqft"
                                        value="<?php echo htmlspecialchars($area_sqft ?? ''); ?>"
                                        min="0">
                                </div>
                            </div>

                            <!-- Amenities -->
                            <h5 class="fw-bold mb-3 text-primary">Amenities</h5>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Air Conditioning" id="ac">
                                        <label class="form-check-label" for="ac">Air Conditioning</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Parking" id="parking">
                                        <label class="form-check-label" for="parking">Parking</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Security" id="security">
                                        <label class="form-check-label" for="security">Security</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Garden" id="garden">
                                        <label class="form-check-label" for="garden">Garden</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Swimming Pool" id="pool">
                                        <label class="form-check-label" for="pool">Swimming Pool</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Gym" id="gym">
                                        <label class="form-check-label" for="gym">Gym</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Water Tank" id="water">
                                        <label class="form-check-label" for="water">Water Tank</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Furnished" id="furnished">
                                        <label class="form-check-label" for="furnished">Furnished</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="Pet Friendly" id="pet">
                                        <label class="form-check-label" for="pet">Pet Friendly</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="amenities[]" value="WiFi" id="wifi">
                                        <label class="form-check-label" for="wifi">WiFi</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Images -->
                            <h5 class="fw-bold mb-3 text-primary">Property Images</h5>

                            <div class="mb-4">
                                <label for="images" class="form-label">Upload Images</label>
                                <input type="file" class="form-control" id="images" name="images[]"
                                    accept="image/*" multiple>
                                <div class="form-text">Upload multiple images of your property. First image will be used as the main image.</div>
                                <div id="image-preview" class="mt-3"></div>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Add Property
                                </button>
                                <a href="index.php?page=my_properties" class="btn btn-outline-secondary btn-lg">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Help Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips for Better Listings</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Clear Title:</strong> Use descriptive titles that highlight key features
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Detailed Description:</strong> Include all important details about the property
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>High-Quality Photos:</strong> Upload clear, well-lit images of all rooms
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Accurate Pricing:</strong> Research similar properties in your area
                            </li>
                            <li class="mb-3">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <strong>Complete Amenities:</strong> List all available amenities and features
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Verification Process</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">All properties go through a verification process before being published:</p>
                        <ol class="small">
                            <li>Property details review</li>
                            <li>Image quality check</li>
                            <li>Pricing validation</li>
                            <li>Owner verification</li>
                        </ol>
                        <p class="text-muted small">This usually takes 24-48 hours.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
</body>

</html>