<?php
// Redirect direct access to router-based OTP page
header('Location: index.php?page=verify_otp', true, 302);
exit();
