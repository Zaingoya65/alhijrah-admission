<?php
session_start();


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'includes/config.php';
include 'includes/email_functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $b_form = trim($conn->real_escape_string($_POST['b_form'] ?? ''));
    
    if (!preg_match('/^\d{13}$/', $b_form)) {
        $error = "Invalid B-form format. Must be 13 digits.";
    } else {
      $sql = "SELECT ru.id, ru.b_form, ru.email, sp.full_name 
        FROM registered_users ru 
        LEFT JOIN student_profiles sp ON ru.id = sp.user_id 
        WHERE ru.b_form = ?";


        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $b_form);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $update_sql = "UPDATE registered_users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('ssi', $token, $token_expiry, $user['id']);
            $update_stmt->execute();
            $name = $user['full_name'] ?? 'User';
            if (send_password_reset_email($user['email'], $user['full_name'], $token)) {
                $success = "Password reset link has been sent to your email.";
            } else {
                $error = "Failed to send reset email. Please try again.";
            }
            
            $update_stmt->close();
        } else {
            $error = "No account found with that B-form number.";
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="text-primary">Forgot Password</h2>
                        <p class="text-muted">Enter your B-form number to reset your password</p>
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
                                <input type="text" class="form-control" id="b_form" name="b_form" 
                                       maxlength="13" pattern="\d{13}" placeholder="1234567890123" required>
                                <label for="b_form">B-form Number</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                Send Reset Link
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Remember your password? <a href="login.php" class="fw-semibold">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Restrict B-form input to digits only
    document.getElementById('b_form').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').slice(0, 13);
    });
});
</script>

<?php include 'includes/footer.php'; ?>