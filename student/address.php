<?php
session_start();
include '../includes/config.php';
include '../includes/session_auth.php';

$user_id = $_SESSION['id'];
$current_year = date('Y');

// Check if personal info is completed first
$profile_id = null;
$stmt = $conn->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: personal_info.php");
    exit();
}

$profile_id = $result->fetch_assoc()['id'];

// Get existing address data
$address = [];
$stmt = $conn->prepare("SELECT * FROM student_profiles WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
$address = $result->fetch_assoc() ?? [];

// Check submission status
$editing_locked = false;
$stmt = $conn->prepare("SELECT is_submitted FROM student_profiles WHERE user_id = ? AND application_year = ?");
$stmt->bind_param("ii", $user_id, $current_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $editing_locked = $result->fetch_assoc()['is_submitted'];
}

// Process form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$editing_locked) {
    // Validate and sanitize inputs
    $fields = [
        'current_address' => [
            'value' => trim($_POST['current_address']),
            'required' => true,
            'min_length' => 10,
            'error' => 'Current address must be at least 10 characters'
        ],
        'permanent_address' => [
            'value' => trim($_POST['permanent_address']),
            'required' => true,
            'min_length' => 10,
            'error' => 'Permanent address must be at least 10 characters'
        ],
        'city' => [
            'value' => trim($_POST['city']),
            'required' => true,
            'error' => 'City is required'
        ],
        'postal_code' => [
            'value' => trim($_POST['postal_code']),
            'required' => false,
            'pattern' => '/^\d{5}$/',
            'error' => 'Postal code must be 5 digits'
        ],
        'phone_number' => [
            'value' => trim($_POST['phone_number']),
            'required' => true,
            'pattern' => '/^03\d{9}$/',
            'error' => 'Phone must be 11 digits starting with 03'
        ],
        'emergency_contact' => [
            'value' => trim($_POST['emergency_contact']),
            'required' => true,
            'error' => 'Emergency contact name is required'
        ],
        'emergency_relation' => [
            'value' => trim($_POST['emergency_relation']),
            'required' => true,
            'error' => 'Emergency contact relation is required'
        ],
        'emergency_phone' => [
            'value' => trim($_POST['emergency_phone']),
            'required' => true,
            'pattern' => '/^03\d{9}$/',
            'error' => 'Emergency phone must be 11 digits starting with 03'
        ]
    ];

    foreach ($fields as $field => $rules) {
        if ($rules['required'] && empty($rules['value'])) {
            $errors[$field] = $rules['error'];
        } elseif (isset($rules['min_length']) && strlen($rules['value']) < $rules['min_length']) {
            $errors[$field] = $rules['error'];
        } elseif (isset($rules['pattern']) && !preg_match($rules['pattern'], $rules['value'])) {
            $errors[$field] = $rules['error'];
        }
    }

    // If no errors, save to database
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE student_profiles SET 
            current_address = ?,
            permanent_address = ?,
            city = ?,
            postal_code = ?,
            phone_number = ?,
            emergency_contact = ?,
            emergency_relation = ?,
            emergency_phone = ?
            WHERE id = ?");
        
        $stmt->bind_param("ssssssssi", 
            $fields['current_address']['value'],
            $fields['permanent_address']['value'],
            $fields['city']['value'],
            $fields['postal_code']['value'],
            $fields['phone_number']['value'],
            $fields['emergency_contact']['value'],
            $fields['emergency_relation']['value'],
            $fields['emergency_phone']['value'],
            $profile_id
        );

        if ($stmt->execute()) {
            $success = "Address information saved successfully!";
            // Refresh address data
            $stmt = $conn->prepare("SELECT * FROM student_profiles WHERE id = ?");
            $stmt->bind_param("i", $profile_id);
            $stmt->execute();
            $address = $stmt->get_result()->fetch_assoc();
        } else {
            $errors['database'] = "Error saving information: " . $conn->error;
        }
    }
}
?>

<?php include '../includes/stud_header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Header -->
                <div class="card-header bg-primary bg-opacity-10 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1"><i class="fas fa-address-card me-2"></i>Contact Information</h4>
                            <p class="mb-0 text-muted">Manage your contact details and addresses</p>
                        </div>
                        <?php if ($application_status): ?>
                        <span class="badge bg-<?= 
                            $application_status === 'Approved' ? 'success' : 
                            ($application_status === 'Rejected' ? 'danger' : 'warning'); ?>">
                            Application status: 
                            <?= htmlspecialchars($application_status) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <?php if ($editing_locked): ?>
                        <div class="alert alert-warning border-0 rounded-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lock me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Editing Locked</h5>
                                    <p class="mb-0">You have submitted your application. Editing is now disabled.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success border-0 rounded-4 alert-dismissible fade show">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="alert-heading mb-1">Success!</h5>
                                    <p class="mb-0"><?= $success ?></p>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-4">
                            <!-- Current Address Section -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-home me-2 text-primary"></i>Current Address
                                        </h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-4 copy-address-btn" 
                                                data-target="permanent_address" <?= $editing_locked ? 'disabled' : '' ?>>
                                            <i class="fas fa-copy me-1"></i>Copy from Permanent
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="current_address" class="form-label">Residential Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control rounded-4 <?= isset($errors['current_address']) ? 'is-invalid' : '' ?>" 
                                                      id="current_address" name="current_address" rows="3" required
                                                      <?= $editing_locked ? 'readonly' : '' ?>><?= htmlspecialchars($address['current_address'] ?? '') ?></textarea>
                                            <div class="d-flex justify-content-between mt-1">
                                                <div class="form-text">Include house number, street, and area</div>
                                                <small class="text-muted character-counter" data-target="current_address"><?= strlen($address['current_address'] ?? '') ?>/200</small>
                                            </div>
                                            <?php if (isset($errors['current_address'])): ?>
                                                <div class="invalid-feedback d-flex align-items-center">
                                                    <i class="fas fa-exclamation-circle me-2"></i>
                                                    <?= $errors['current_address'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Permanent Address Section -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-flag me-2 text-primary"></i>Permanent Address
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="permanent_address" class="form-label">Home Address <span class="text-danger">*</span></label>
                                            <textarea class="form-control rounded-4 <?= isset($errors['permanent_address']) ? 'is-invalid' : '' ?>" 
                                                      id="permanent_address" name="permanent_address" rows="3" required
                                                      <?= $editing_locked ? 'readonly' : '' ?>><?= htmlspecialchars($address['permanent_address'] ?? '') ?></textarea>
                                            <div class="d-flex justify-content-between mt-1">
                                                <div class="form-text">Your permanent home address</div>
                                                <small class="text-muted character-counter" data-target="permanent_address"><?= strlen($address['permanent_address'] ?? '') ?>/200</small>
                                            </div>
                                            <?php if (isset($errors['permanent_address'])): ?>
                                                <div class="invalid-feedback d-flex align-items-center">
                                                    <i class="fas fa-exclamation-circle me-2"></i>
                                                    <?= $errors['permanent_address'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control rounded-4 <?= isset($errors['city']) ? 'is-invalid' : '' ?>" 
                                                       id="city" name="city" value="<?= htmlspecialchars($address['city'] ?? '') ?>" 
                                                       <?= $editing_locked ? 'readonly' : '' ?> required>
                                                <?php if (isset($errors['city'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['city'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="postal_code" class="form-label">Postal Code</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary bg-opacity-10 border-primary"><i class="fas fa-mail-bulk text-primary"></i></span>
                                                    <input type="text" class="form-control rounded-4 <?= isset($errors['postal_code']) ? 'is-invalid' : '' ?>" 
                                                           id="postal_code" name="postal_code" 
                                                           value="<?= htmlspecialchars($address['postal_code'] ?? '') ?>"
                                                           <?= $editing_locked ? 'readonly' : '' ?>>
                                                </div>
                                                <?php if (isset($errors['postal_code'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['postal_code'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-phone-alt me-2 text-primary"></i>Contact Details
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="phone_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary bg-opacity-10 border-primary"><i class="fas fa-mobile-alt text-primary"></i></span>
                                                    <input type="tel" class="form-control rounded-4 <?= isset($errors['phone_number']) ? 'is-invalid' : '' ?>" 
                                                           id="phone_number" name="phone_number" 
                                                           value="<?= htmlspecialchars($address['phone_number'] ?? '') ?>" 
                                                           placeholder="03XXXXXXXXX" required
                                                           <?= $editing_locked ? 'readonly' : '' ?>>
                                                </div>
                                                <?php if (isset($errors['phone_number'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['phone_number'] ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="form-text">Format: 03XXXXXXXXX (11 digits)</div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-check form-switch mt-4 pt-2">
                                                    <input class="form-check-input" type="checkbox" id="whatsapp_available" 
                                                           name="whatsapp_available" <?= isset($address['whatsapp_available']) && $address['whatsapp_available'] ? 'checked' : '' ?>
                                                           <?= $editing_locked ? 'disabled' : '' ?>>
                                                    <label class="form-check-label" for="whatsapp_available">
                                                        This number is available on WhatsApp
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Emergency Contact -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-user-md me-2 text-primary"></i>Emergency Contact Information
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="emergency_contact" class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary bg-opacity-10 border-primary"><i class="fas fa-user text-primary"></i></span>
                                                    <input type="text" class="form-control rounded-4 <?= isset($errors['emergency_contact']) ? 'is-invalid' : '' ?>" 
                                                           id="emergency_contact" name="emergency_contact" 
                                                           value="<?= htmlspecialchars($address['emergency_contact'] ?? '') ?>" required
                                                           <?= $editing_locked ? 'readonly' : '' ?>>
                                                </div>
                                                <?php if (isset($errors['emergency_contact'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['emergency_contact'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label for="emergency_relation" class="form-label">Relationship <span class="text-danger">*</span></label>
                                                <select class="form-select rounded-4 <?= isset($errors['emergency_relation']) ? 'is-invalid' : '' ?>" 
                                                        id="emergency_relation" name="emergency_relation" required
                                                        <?= $editing_locked ? 'disabled' : '' ?>>
                                                    <option value="">Select Relationship</option>
                                                    <option value="Parent" <?= ($address['emergency_relation'] ?? '') == 'Parent' ? 'selected' : '' ?>>Parent</option>
                                                    <option value="Sibling" <?= ($address['emergency_relation'] ?? '') == 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                                                    <option value="Spouse" <?= ($address['emergency_relation'] ?? '') == 'Spouse' ? 'selected' : '' ?>>Spouse</option>
                                                    <option value="Relative" <?= ($address['emergency_relation'] ?? '') == 'Relative' ? 'selected' : '' ?>>Relative</option>
                                                    <option value="Friend" <?= ($address['emergency_relation'] ?? '') == 'Friend' ? 'selected' : '' ?>>Friend</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <?php if (isset($errors['emergency_relation'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['emergency_relation'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label for="emergency_phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-primary bg-opacity-10 border-primary"><i class="fas fa-phone text-primary"></i></span>
                                                    <input type="tel" class="form-control rounded-4 <?= isset($errors['emergency_phone']) ? 'is-invalid' : '' ?>" 
                                                           id="emergency_phone" name="emergency_phone" 
                                                           value="<?= htmlspecialchars($address['emergency_phone'] ?? '') ?>" 
                                                           placeholder="03XXXXXXXXX" required
                                                           <?= $editing_locked ? 'readonly' : '' ?>>
                                                </div>
                                                <?php if (isset($errors['emergency_phone'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['emergency_phone'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Navigation -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 gap-3">
    <!-- Previous Button - Full width on mobile, auto on larger screens -->
    <a href="personal_info.php" class="btn btn-outline-primary rounded-4 w-100 w-sm-auto order-1 order-sm-1">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
    
    <!-- Action Buttons - Stack vertically on mobile, horizontal on larger screens -->
    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto order-3 order-sm-2 mt-2 mt-sm-0">
        <!-- Save Button - Full width on mobile -->
        <button type="submit" class="btn btn-primary rounded-4 flex-grow-1" <?= $editing_locked ? 'disabled' : '' ?>>
            <i class="fas fa-save me-2"></i>Save Information
        </button>
        
        <!-- Next Button - Full width on mobile -->
        <a href="education_info.php" class="btn btn-success rounded-4 flex-grow-1">
            Next<i class="fas fa-arrow-right ms-2"></i>
        </a>
    </div>
</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Progress Steps */
.progress-steps {
    display: flex;
    justify-content: space-between;
    margin: 0 auto;
    max-width: 800px;
}

.step {
    flex: 1;
    text-align: center;
    position: relative;
}

.step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 15px;
    left: 50%;
    right: -50%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 0;
}

.step.active:not(:last-child):after,
.step.completed:not(:last-child):after {
    background-color: #0d6efd;
}

.step-number {
    width: 30px;
    height: 30px;
    line-height: 30px;
    border-radius: 50%;
    background-color: #dee2e6;
    color: #6c757d;
    margin: 0 auto 5px;
    font-weight: bold;
    position: relative;
    z-index: 1;
}

.step.active .step-number {
    background-color: #0d6efd;
    color: white;
}

.step.completed .step-number {
    background-color: #198754;
    color: white;
}

.step-label {
    font-size: 0.85rem;
    color: #6c757d;
}

.step.active .step-label {
    color: #0d6efd;
    font-weight: bold;
}

.step.completed .step-label {
    color: #198754;
}

/* Character counters */
.character-counter {
    transition: color 0.3s;
}

/* Form validation icons */
.invalid-feedback i {
    font-size: 1.1em;
}

/* Card hover effects */
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>


<?php include'../includes/stud_footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
            if (this.value.length > 0 && !this.value.startsWith('03')) {
                this.setCustomValidity("Phone number must start with 03");
            } else {
                this.setCustomValidity("");
            }
        });
    });

    // Postal code validation
    const postalCodeInput = document.getElementById('postal_code');
    if (postalCodeInput) {
        postalCodeInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 5);
        });
    }

    // Character counters for textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        const counter = document.querySelector(`.character-counter[data-target="${textarea.id}"]`);
        
        textarea.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length}/200`;
            counter.className = length > 200 ? 'text-danger d-block text-end' : 'text-muted d-block text-end';
        });
    });
    
    // Copy address button
    const copyAddressBtn = document.querySelector('.copy-address-btn');
    if (copyAddressBtn) {
        copyAddressBtn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const sourceTextarea = document.getElementById(targetId);
            const targetTextarea = document.getElementById('current_address');
            
            if (sourceTextarea && targetTextarea) {
                targetTextarea.value = sourceTextarea.value;
                // Trigger input event to update counter
                const event = new Event('input');
                targetTextarea.dispatchEvent(event);
            }
        });
    }
    
    // Enhanced validation feedback
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
                const feedback = this.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.style.display = 'none';
                }
            }
        });
    });
});
</script>