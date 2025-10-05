<?php
// Session already started in index.php
include 'config/database.php';
include 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php?page=dashboard');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $phoneNormalized = preg_replace('/[^0-9]/', '', $phone);
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    // Validate phone
    if (empty($phone)) {
        $errors[] = 'Phone number is required.';
    } elseif (!validatePhone($phoneNormalized)) {
        $errors[] = 'Please enter a valid Sri Lankan phone number.';
    }

    // Validate password
    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            // Check user credentials
            $stmt = $pdo->prepare("SELECT id, name, email, phone, password, user_type, is_verified FROM users WHERE phone = ?");
            $stmt->execute([$phoneNormalized]);
            $user = $stmt->fetch();

            if ($user && verifyPassword($password, $user['password'])) {
                if (!$user['is_verified']) {
                    $errors[] = 'Your account is not verified. Please check your email or contact support.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_type'] = $user['user_type'];

                    // Set success message
                    setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');

                    // Redirect to dashboard
                    redirect('index.php?page=dashboard');
                }
            } else {
                $errors[] = 'Invalid phone number or password.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle forgot password
if (isset($_POST['forgot_password'])) {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $phoneNormalized = preg_replace('/[^0-9]/', '', $phone);

    if (empty($phone)) {
        $errors[] = 'Please enter your phone number.';
    } elseif (!validatePhone($phoneNormalized)) {
        $errors[] = 'Please enter a valid phone number.';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE phone = ?");
            $stmt->execute([$phoneNormalized]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate OTP for password reset
                $otp = generateOTP();
                // Store OTP using DB time and normalized phone
                $stmt = $pdo->prepare("INSERT INTO otp_verification (phone, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
                $stmt->execute([$phoneNormalized, $otp]);

                // Send SMS
                $message = "Your RentFinder SL password reset code is: $otp. Valid for 10 minutes.";
                sendSMS($phoneNormalized, $message);

                // Store phone in session for password reset
                $_SESSION['reset_phone'] = $phoneNormalized;

                $success = 'Password reset code sent to your phone number.';
            } else {
                $errors[] = 'No account found with this phone number.';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to send reset code. Please try again.';
            error_log("Forgot password error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RentFinder SL</title>
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
                            <h2 class="fw-bold text-primary">Welcome Back</h2>
                            <p class="text-muted">Sign in to your RentFinder SL account</p>
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
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                    placeholder="07XXXXXXXX" required>
                                <div class="form-text">Enter your registered phone number</div>
                                <div class="invalid-feedback">
                                    Please provide a valid phone number.
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
                                <div class="invalid-feedback">
                                    Please provide your password.
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </form>

                        <div class="text-center">
                            <button type="button" class="btn btn-link text-decoration-none"
                                data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                Forgot your password?
                            </button>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <p class="text-muted">
                                Don't have an account?
                                <a href="index.php?page=register" class="text-primary fw-semibold">Create one here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <p class="text-muted">Enter your phone number and we'll send you a reset code.</p>
                        <div class="mb-3">
                            <label for="reset_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="reset_phone" name="phone"
                                placeholder="07XXXXXXXX" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="forgot_password" class="btn btn-primary">
                            Send Reset Code
                        </button>
                    </div>
                </form>
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

        // Auto-focus on phone input
        document.getElementById('phone').focus();
    </script>
</body>

</html>