<?php
session_start();
include 'includes/config.php';
include 'includes/auth.php';
include 'includes/email_functions.php'; // Add this line

// Initialize variables
$error = '';
$success = '';
$formData = [
    'b_form' => '',
    'email' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $formData['b_form'] = trim($conn->real_escape_string($_POST['b_form'] ?? ''));
    $formData['email'] = trim($conn->real_escape_string($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    // Validation checks
    if (!preg_match('/^\d{13}$/', $formData['b_form'])) {
        $error = "B-form must be exactly 13 digits without dashes or spaces.";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $cpassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one uppercase letter and one number.";
    } else {
        // Check for existing user
        $checkSql = "SELECT id FROM registered_users WHERE b_form = ? OR email = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ss", $formData['b_form'], $formData['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "A user with this B-form number or email already exists.";
        } else {
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insertSql = "INSERT INTO registered_users (b_form, email, password, is_verified) VALUES (?, ?, ?, FALSE)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param("sss", $formData['b_form'], $formData['email'], $hashed_password);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                $verification_token = bin2hex(random_bytes(16)); // 32-character token
                $verification_token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $verification_link = "https://moccasin-tiger-993742.hostingersite.com/verify-account.php?token=$verification_token";
                send_otp_email($formData['email'], $name, $otp, $verification_link);


                
                $update_sql = "UPDATE registered_users 
                SET otp = ?, otp_expiry = ?, verification_token = ?, verification_token_expiry = ? 
                WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param('ssssi', $otp, $otp_expiry, $verification_token, $verification_token_expiry, $user_id);
                $update_stmt->execute();

                
                // Send OTP email
              
                if (send_otp_email($formData['email'], $otp)) {
                    $_SESSION['verify_user_id'] = $user_id;
                    header("Location: verify-account.php");
                    exit();
                } else {
                    $error = "Account created but failed to send verification email. Please contact support.";
                }
                
                $update_stmt->close();
            } else {
                $error = "Registration failed. Please try again later.";
            }
        }
        $stmt->close();
    }
}
?>

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
                        <p class="text-muted">Create your account to get started</p>
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

                    <form id="registrationForm" method="POST" action="" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="b_form" name="b_form" 
                                           value="<?php echo htmlspecialchars($formData['b_form']); ?>" 
                                           maxlength="13" pattern="\d{13}" placeholder="1234567890123" required>
                                    <label for="b_form">B-form Number</label>
                                    <small class="form-text text-muted">13 digits without any dashes or spaces</small>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($formData['email']); ?>" 
                                           placeholder="name@example.com" required>
                                    <label for="email">Email Address</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="password" name="password" 
                                           minlength="8" placeholder="Password" required>
                                    <label for="password">Password</label>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted toggle-btn" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted">Password strength: <span id="strength-text">Weak</span></small>
                                </div>
                                <div class="password-requirements small text-muted mt-2">
                                    <ul class="list-unstyled">
                                        <li class="req-length"><i class="fas fa-circle-notch fa-xs"></i> At least 8 characters</li>
                                        <li class="req-upper"><i class="fas fa-circle-notch fa-xs"></i> At least 1 uppercase letter</li>
                                        <li class="req-number"><i class="fas fa-circle-notch fa-xs"></i> At least 1 number</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-floating position-relative">
                                    <input type="password" class="form-control" id="cpassword" name="cpassword" 
                                           minlength="8" placeholder="Confirm Password" required>
                                    <label for="cpassword">Confirm Password</label>
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted toggle-btn" id="toggleCPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-match small mt-2">
                                    <span id="match-text" class="text-muted">Passwords must match</span>
                                </div>
                            </div>

                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100 py-3" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm me-2 d-none" id="spinner" role="status" aria-hidden="true"></span>
                                    Create Account
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="login.php" class="fw-semibold">Sign in</a></p>
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

    // Password visibility toggles
    function setupToggle(buttonId, inputId) {
        const toggleBtn = document.getElementById(buttonId);
        const input = document.getElementById(inputId);
        
        toggleBtn.addEventListener('click', () => {
            const type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            const icon = toggleBtn.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
            toggleBtn.classList.toggle('text-muted');
            toggleBtn.classList.toggle('text-primary');
        });
    }

    setupToggle('togglePassword', 'password');
    setupToggle('toggleCPassword', 'cpassword');

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthBar = document.querySelector('.password-strength .progress-bar');
    const strengthText = document.getElementById('strength-text');
    const reqItems = document.querySelectorAll('.password-requirements li');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Length check
        if (password.length >= 8) {
            strength += 25;
            reqItems[0].innerHTML = '<i class="fas fa-check text-success"></i> At least 8 characters';
        } else {
            reqItems[0].innerHTML = '<i class="fas fa-circle-notch fa-xs"></i> At least 8 characters';
        }
        
        // Uppercase check
        if (/[A-Z]/.test(password)) {
            strength += 25;
            reqItems[1].innerHTML = '<i class="fas fa-check text-success"></i> At least 1 uppercase letter';
        } else {
            reqItems[1].innerHTML = '<i class="fas fa-circle-notch fa-xs"></i> At least 1 uppercase letter';
        }
        
        // Number check
        if (/[0-9]/.test(password)) {
            strength += 25;
            reqItems[2].innerHTML = '<i class="fas fa-check text-success"></i> At least 1 number';
        } else {
            reqItems[2].innerHTML = '<i class="fas fa-circle-notch fa-xs"></i> At least 1 number';
        }
        
        // Special character check (optional)
        if (/[^A-Za-z0-9]/.test(password)) {
            strength += 25;
        }
        
        // Update strength meter
        strengthBar.style.width = strength + '%';
        
        // Update strength text
        if (strength < 50) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Weak';
            strengthText.className = 'text-danger';
        } else if (strength < 75) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Moderate';
            strengthText.className = 'text-warning';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Strong';
            strengthText.className = 'text-success';
        }
    });

    // Password match indicator
    const confirmInput = document.getElementById('cpassword');
    const matchText = document.getElementById('match-text');
    
    function checkPasswordMatch() {
        if (passwordInput.value && confirmInput.value) {
            if (passwordInput.value === confirmInput.value) {
                matchText.textContent = 'Passwords match!';
                matchText.className = 'text-success';
            } else {
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'text-danger';
            }
        } else {
            matchText.textContent = 'Passwords must match';
            matchText.className = 'text-muted';
        }
    }
    
    passwordInput.addEventListener('input', checkPasswordMatch);
    confirmInput.addEventListener('input', checkPasswordMatch);

    // Form submission loading state
    const form = document.getElementById('registrationForm');
    const submitBtn = document.getElementById('submitBtn');
    const spinner = document.getElementById('spinner');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitBtn.innerHTML = 'Creating Account...';
    });
});
</script>

<?php include 'includes/footer.php'; ?>