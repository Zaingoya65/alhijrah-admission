<?php
session_start();
include 'includes/config.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    header("Location: forgot-password.php");
    exit();
}

// Check token in database
$sql = "SELECT id, reset_token_expiry FROM registered_users WHERE reset_token = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1 || strtotime($result->fetch_assoc()['reset_token_expiry']) < time()) {
    $error = "Invalid or expired reset token.";
    $token_valid = false;
} else {
    $token_valid = true;
}
$stmt->close();

if ($token_valid && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) ){
        $error = "Password is required.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $update_sql = "UPDATE registered_users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('ss', $hashed_password, $token);
        $update_stmt->execute();
        
        $success = "Your password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
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
                        <h2 class="text-primary">Reset Password</h2>
                        <p class="text-muted">Enter your new password</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($token_valid): ?>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="New Password" required minlength="8">
                                <label for="password">New Password</label>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted toggle-btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                        </div>

                        <div class="mb-4">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm Password" required minlength="8">
                                <label for="confirm_password">Confirm Password</label>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted toggle-btn">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3">
                                Reset Password
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>

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
    // Password visibility toggle
    document.querySelectorAll('.toggle-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const input = e.currentTarget.parentElement.querySelector('input');
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            const icon = e.currentTarget.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
            e.currentTarget.classList.toggle('text-muted');
            e.currentTarget.classList.toggle('text-primary');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>