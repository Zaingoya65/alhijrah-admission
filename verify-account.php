<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'includes/config.php';
include 'includes/email_functions.php';

// Redirect if already verified or not logged in
if (!isset($_SESSION['verify_user_id']) || isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$email = ''; // Initialize email variable

// Get user email for display
if (isset($_SESSION['verify_user_id'])) {
    $user_id = $_SESSION['verify_user_id'];
    $sql = "SELECT email FROM registered_users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $email = $user['email'];
        // Mask email for display (e.g., te**@example.com)
        $masked_email = substr($user['email'], 0, 2) . str_repeat('*', strpos($user['email'], '@') - 2) . substr($user['email'], strpos($user['email'], '@'));
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = trim($conn->real_escape_string($_POST['otp'] ?? ''));
    
    if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
        $error = "Invalid OTP format. Must be 6 digits.";
    } else {
        $user_id = $_SESSION['verify_user_id'];
        
        $sql = "SELECT id, otp, otp_expiry FROM registered_users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if ($user['otp'] === $otp && strtotime($user['otp_expiry']) > time()) {
                // OTP is valid
                $update_sql = "UPDATE registered_users SET is_verified = TRUE, otp = NULL, otp_expiry = NULL WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('i', $user_id);
                $update_stmt->execute();
                
                // Set session and redirect
                $_SESSION['id'] = $user_id;
                unset($_SESSION['verify_user_id']);
                
                header("Location: login.php");
                exit();
            } else {
                $error = "Invalid or expired OTP.";
            }
        } else {
            $error = "User not found.";
        }
        $stmt->close();
    }
}

// Resend OTP functionality
if (isset($_GET['resend'])) {
    $user_id = $_SESSION['verify_user_id'];
    $new_otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    $sql = "UPDATE registered_users SET otp = ?, otp_expiry = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssi', $new_otp, $otp_expiry, $user_id);
    
    if ($stmt->execute()) {
        // Get user email to resend OTP
        $email_sql = "SELECT email FROM registered_users WHERE id = ?";
        $email_stmt = $conn->prepare($email_sql);
        $email_stmt->bind_param('i', $user_id);
        $email_stmt->execute();
        $email_result = $email_stmt->get_result();
        $user = $email_result->fetch_assoc();
        
        if (send_otp_email($user['email'], $new_otp)) {
            $success = "A new verification code has been sent to your email address.";
        } else {
            $error = "Failed to send verification email. Please try again.";
        }
        
        $email_stmt->close();
    } else {
        $error = "Failed to generate new OTP. Please try again.";
    }
    
    $stmt->close();
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="text-primary">Verify Your Account</h2>
                        <div class="alert alert-info text-start">
                            <h5><i class="fas fa-info-circle me-2"></i>Verification Instructions</h5>
                            <ol class="mb-0">
                                <li>Check your email <strong><?= htmlspecialchars($masked_email ?? '') ?></strong> for the 6-digit verification code</li>
                                <li>Enter the code below to verify your account</li>
                                <li>The code expires in 15 minutes</li>
                                <li>Can't find the email? Check your spam folder</li>
                            </ol>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="otp" name="otp" 
                                       maxlength="6" pattern="\d{6}" placeholder="123456" required
                                       inputmode="numeric" autocomplete="one-time-code">
                                <label for="otp">6-Digit Verification Code</label>
                            </div>
                            <small class="text-muted">Enter exactly 6 digits you received</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                <i class="fas fa-check-circle me-2"></i> Verify Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                            <p class="mb-0">Didn't receive the code?</p>
                            <div>
                                <a href="verify-account.php?resend=1" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-redo me-1"></i> Resend Code
                                </a>
                                <span class="ms-2 text-muted small">(Valid for 15 minutes)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>