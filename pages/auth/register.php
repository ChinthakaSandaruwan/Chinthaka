<?php
include 'config/database.php';
include 'includes/functions.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $user_type = sanitizeInput($_POST['user_type'] ?? 'tenant');
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    // Validate name
    if (empty($name)) {
        $errors[] = 'Name is required.';
    } elseif (strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters long.';
    }

    // Validate email
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email address is already registered.';
        }
    }

    // Validate phone
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!validatePhone($phone)) {
        $errors[] = 'Please enter a valid Sri Lankan phone number (10 digits starting with 0).';
    } else {
        // Check if phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            $errors[] = 'Phone number is already registered.';
        }
    }

    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Validate user type
    if (!in_array($user_type, ['tenant', 'owner'])) {
        $errors[] = 'Invalid user type selected.';
    }

    if (empty($errors)) {
        try {
            // Normalize phone to digits-only to ensure consistent matching
            $phoneNormalized = preg_replace('/[^0-9]/', '', $phone);

            // Generate OTP
            $otp = generateOTP();

            // Store OTP in database using DB time to avoid timezone mismatches
            $stmt = $pdo->prepare("INSERT INTO otp_verification (phone, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
            $stmt->execute([$phoneNormalized, $otp]);

            // Send SMS OTP (mock function)
            $message = "Your RentFinder SL verification code is: $otp. Valid for 10 minutes.";
            sendSMS($phoneNormalized, $message);

            // Store user data in session for verification
            $_SESSION['temp_user'] = [
                'name' => $name,
                'email' => $email,
                'phone' => $phoneNormalized,
                'password' => hashPassword($password),
                'user_type' => $user_type
            ];

            // Redirect to OTP verification via router
            redirect('index.php?page=verify_otp');
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RentFinder SL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">Create Account</h2>
                            <p class="text-muted">Join RentFinder SL and find your perfect rental property</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide your full name.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                    placeholder="07XXXXXXXX" required>
                                <div class="form-text">Enter your Sri Lankan mobile number (10 digits starting with 0)</div>
                                <div class="invalid-feedback">
                                    Please provide a valid phone number.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="user_type" class="form-label">Account Type</label>
                                <select class="form-select" id="user_type" name="user_type" required>
                                    <option value="">Select account type</option>
                                    <option value="tenant" <?php echo ($user_type ?? '') === 'tenant' ? 'selected' : ''; ?>>
                                        Tenant - Looking for rental properties
                                    </option>
                                    <option value="owner" <?php echo ($user_type ?? '') === 'owner' ? 'selected' : ''; ?>>
                                        Property Owner - List your properties
                                    </option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select an account type.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Password must be at least 6 characters long</div>
                                <div class="invalid-feedback">
                                    Please provide a valid password.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div class="invalid-feedback">
                                    Passwords do not match.
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="index.php?page=terms" target="_blank">Terms of Service</a>
                                        and <a href="index.php?page=privacy" target="_blank">Privacy Policy</a>
                                    </label>
                                    <div class="invalid-feedback">
                                        You must agree to the terms and conditions.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Already have an account?
                                <a href="index.php?page=login" class="text-primary fw-semibold">Sign in here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/main.js"></script>
    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>

</html>