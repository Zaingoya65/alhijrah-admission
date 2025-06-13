<?php
session_start();
include 'includes/config.php';
include 'includes/email_functions.php';

// Redirect if already verified or not logged in
if (!isset($_SESSION['verify_user_id']) || isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

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
                
                header("Location: student/dashboard.php");
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
    $stmt->execute();
    
    // Get user email to resend OTP (you'll need to add email to your users table)
    $email_sql = "SELECT email, name FROM registered_users WHERE id = ?";
    $email_stmt = $conn->prepare($email_sql);
    $email_stmt->bind_param('i', $user_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $user = $email_result->fetch_assoc();
    
    if (send_otp_email($user['email'], $user['name'], $new_otp)) {
        $success = "A new verification code has been sent to your email.";
    } else {
        $error = "Failed to send verification email. Please try again.";
    }
    
    $stmt->close();
    $email_stmt->close();
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
                        <p class="text-muted">Enter the 6-digit code sent to your email</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="otp" name="otp" 
                                       maxlength="6" pattern="\d{6}" placeholder="123456" required>
                                <label for="otp">Verification Code</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                Verify Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Didn't receive the code? <a href="verify-account.php?resend=1" class="fw-semibold">Resend Code</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>