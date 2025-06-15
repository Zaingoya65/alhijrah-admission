<?php
session_start();
include 'includes/config.php';

// Initialize variables
$error = '';
$b_form = '';

// Check if user is already logged in
if (isset($_SESSION['id'])) {
    header("Location: student/dashboard.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $b_form = trim($conn->real_escape_string($_POST['b_form'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Validate B-form format
    if (!preg_match('/^\d{13}$/', $b_form)) {
        $error = "Invalid B-form format. Must be 13 digits.";
    } else {
        // Use prepared statement to prevent SQL injection
        $sql = "SELECT id, b_form, password, is_verified FROM registered_users WHERE b_form = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $b_form);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$stmt) {
            die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        }

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if (!$user['is_verified']) {
                    // User is not verified - redirect to verification page
                    $_SESSION['verify_user_id'] = $user['id'];
                    header("Location: verify-account.php");
                    exit();
                }
                
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['id'] = $user['id'];
                $_SESSION['b_form'] = $user['b_form'];
                
                header("Location: student/dashboard.php");
                exit();
            } else {
                // Delay response to prevent brute force attacks
                sleep(1);
                $error = "Invalid B-form or password.";
            }
        } else {
            // Delay response to prevent brute force attacks
            sleep(1);
            $error = "Invalid B-form or password.";
        }
        $stmt->close();
    }
}
?>

<!-- Rest of your login.php file remains the same -->

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <!-- Branding Section -->
                    <div class="text-center mb-4">
                        <img src="assets/images/trust-logo.png" alt="Al-Hijrah Trust Logo" class="mb-3" style="height: 80px;">
                        <h2 class="trust-name text-primary">Al-Hijrah Trust</h2>
                        <p class="text-muted">Sign in to your student account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" method="POST" action="" novalidate>
                        <div class="mb-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="b_form" name="b_form" 
                                       value="<?php echo htmlspecialchars($b_form); ?>" 
                                       maxlength="13" pattern="\d{13}" placeholder="1234567890123" required>
                                <label for="b_form">B-form Number</label>
                            </div>
                            <small class="form-text text-muted">13 digits without any dashes or spaces</small>
                        </div>

                        <div class="mb-4">
                            <div class="form-floating position-relative">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password">Password</label>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted toggle-btn" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="text-end mt-2">
                                <a href="forgot-password.php" class="small">Forgot password?</a>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg py-3" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="spinner" role="status" aria-hidden="true"></span>
                                Sign In
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Don't have an account? <a href="register.php" class="fw-semibold">Register here</a></p>
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

    // Password visibility toggle
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    togglePassword.addEventListener('click', () => {
        const type = password.type === 'password' ? 'text' : 'password';
        password.type = type;
        const icon = togglePassword.querySelector('i');
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
        togglePassword.classList.toggle('text-muted');
        togglePassword.classList.toggle('text-primary');
    });

    // Form submission loading state
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitBtn.innerHTML = 'Signing In...';
    });

    // Auto-focus the first field with error or the B-form field
    <?php if ($error): ?>
        document.getElementById('b_form').focus();
    <?php else: ?>
        document.getElementById('b_form').focus();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>