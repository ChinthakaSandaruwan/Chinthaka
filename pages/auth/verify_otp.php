<?php
include 'config/database.php';
include 'includes/functions.php';

// Check if user data exists in session
if (!isset($_SESSION['temp_user'])) {
    redirect('index.php?page=register');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitizeInput($_POST['otp'] ?? '');
    // Normalize phone from session to digits-only to match storage format
    if (isset($_SESSION['temp_user']['phone'])) {
        $_SESSION['temp_user']['phone'] = preg_replace('/[^0-9]/', '', $_SESSION['temp_user']['phone']);
    }
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $errors[] = 'Invalid request. Please try again.';
    }

    // Validate OTP
    if (empty($otp)) {
        $errors[] = 'Please enter the verification code.';
    } elseif (strlen($otp) !== 6 || !is_numeric($otp)) {
        $errors[] = 'Please enter a valid 6-digit verification code.';
    }

    if (empty($errors)) {
        try {
            // Verify OTP
            // Use database server time consistently and allow small clock skew
            $stmt = $pdo->prepare("SELECT * FROM otp_verification 
                                  WHERE phone = ? AND otp_code = ? 
                                  AND is_used = 0 AND expires_at > NOW() 
                                  ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$_SESSION['temp_user']['phone'], $otp]);
            $otpRecord = $stmt->fetch();

            if ($otpRecord) {
                // Mark OTP as used
                $stmt = $pdo->prepare("UPDATE otp_verification SET is_used = 1 WHERE id = ?");
                $stmt->execute([$otpRecord['id']]);

                // Create user account
                $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, user_type, is_verified) 
                                      VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $_SESSION['temp_user']['name'],
                    $_SESSION['temp_user']['email'],
                    $_SESSION['temp_user']['phone'],
                    $_SESSION['temp_user']['password'],
                    $_SESSION['temp_user']['user_type']
                ]);

                // Get user ID
                $userId = $pdo->lastInsertId();

                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $_SESSION['temp_user']['name'];
                $_SESSION['user_email'] = $_SESSION['temp_user']['email'];
                $_SESSION['user_phone'] = $_SESSION['temp_user']['phone'];
                $_SESSION['user_type'] = $_SESSION['temp_user']['user_type'];

                // Clear temporary data
                unset($_SESSION['temp_user']);

                // Set success message
                setFlashMessage('success', 'Account created successfully! Welcome to RentFinder SL.');

                // Redirect to dashboard
                redirect('index.php?page=dashboard');
            } else {
                $errors[] = 'Invalid or expired verification code. Please try again.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again.';
            error_log("OTP verification error: " . $e->getMessage());
        }
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    try {
        $otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Store new OTP
        $stmt = $pdo->prepare("INSERT INTO otp_verification (phone, otp_code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['temp_user']['phone'], $otp, $expires_at]);

        // Send SMS
        $message = "Your RentFinder SL verification code is: $otp. Valid for 10 minutes.";
        sendSMS($_SESSION['temp_user']['phone'], $message);

        $success = 'Verification code has been resent to your phone number.';
    } catch (Exception $e) {
        $errors[] = 'Failed to resend verification code. Please try again.';
        error_log("Resend OTP error: " . $e->getMessage());
    }
}

$phone = $_SESSION['temp_user']['phone'] ?? '';
$maskedPhone = substr($phone, 0, 3) . '****' . substr($phone, -3);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Phone Number - RentFinder SL</title>
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
                            <div class="verification-icon mb-3">
                                <i class="fas fa-mobile-alt fa-3x text-primary"></i>
                            </div>
                            <h2 class="fw-bold text-primary">Verify Phone Number</h2>
                            <p class="text-muted">
                                We've sent a 6-digit verification code to<br>
                                <strong><?php echo htmlspecialchars($maskedPhone); ?></strong>
                            </p>
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

                            <div class="mb-4">
                                <label for="otp" class="form-label">Verification Code</label>
                                <input type="text" class="form-control form-control-lg text-center"
                                    id="otp" name="otp" maxlength="6"
                                    placeholder="000000" required autocomplete="off">
                                <div class="form-text text-center">
                                    Enter the 6-digit code sent to your phone
                                </div>
                                <div class="invalid-feedback">
                                    Please enter the 6-digit verification code.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
                                <i class="fas fa-check me-2"></i>Verify & Create Account
                            </button>
                        </form>

                        <form method="POST" class="text-center">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <button type="submit" name="resend_otp" class="btn btn-outline-primary">
                                <i class="fas fa-redo me-2"></i>Resend Code
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Didn't receive the code? Check your SMS messages or
                                <a href="index.php?page=register" class="text-primary fw-semibold">try again</a>
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
        // Auto-focus on OTP input
        document.getElementById('otp').focus();

        // Auto-format OTP input (numbers only)
        document.getElementById('otp').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');

            // Auto-submit when 6 digits are entered
            if (this.value.length === 6) {
                this.form.submit();
            }
        });

        // Handle paste
        document.getElementById('otp').addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
            this.value = pastedData.substring(0, 6);

            if (this.value.length === 6) {
                this.form.submit();
            }
        });

        // Countdown timer for resend button
        let timeLeft = 60;
        const resendBtn = document.querySelector('button[name="resend_otp"]');
        const originalText = resendBtn.innerHTML;

        function updateTimer() {
            if (timeLeft > 0) {
                resendBtn.innerHTML = `Resend Code (${timeLeft}s)`;
                resendBtn.disabled = true;
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                resendBtn.innerHTML = originalText;
                resendBtn.disabled = false;
            }
        }

        updateTimer();
    </script>
</body>

</html>