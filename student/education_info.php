<?php
session_start();
include '../includes/config.php';
include '../includes/session_auth.php';

$user_id = $_SESSION['id'];
$current_year = date('Y');
$editing_locked = false;

// Check submission status using prepared statement
$stmt = $conn->prepare("SELECT is_submitted FROM student_profiles WHERE user_id = ? AND application_year = ?");
$stmt->bind_param("ii", $user_id, $current_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $editing_locked = $result->fetch_assoc()['is_submitted'];
}

// Check if previous steps are completed
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

// Get existing education info if available
$education = [];
$stmt = $conn->prepare("SELECT * FROM student_profiles WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$education = $stmt->get_result()->fetch_assoc() ?? [];

// Process form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$editing_locked) {
    // Define fields with validation rules
    $fields = [
        'last_school_name' => [
            'value' => trim($_POST['last_school_name']),
            'required' => true,
            'error' => 'School name is required'
        ],
        'last_school_address' => [
            'value' => trim($_POST['last_school_address']),
            'required' => true,
            'error' => 'School address is required'
        ],
        'last_school_class' => [
            'value' => trim($_POST['last_school_class']),
            'required' => true,
            'error' => 'Last class attended is required'
        ],
        'last_school_result' => [
            'value' => trim($_POST['last_school_result']),
            'required' => true,
            'numeric' => true,
            'min' => 75,
            'max' => 100,
            'error' => 'Result must be between 75 and 100'
        ],
        'last_school_medium' => [
            'value' => trim($_POST['last_school_medium']),
            'required' => true,
            'error' => 'Medium of instruction is required'
        ],
        'last_school_type' => [
            'value' => trim($_POST['last_school_type']),
            'required' => true,
            'error' => 'School type is required'
        ]
    ];

    // Validate all fields
    foreach ($fields as $field => $rules) {
        if ($rules['required'] && empty($rules['value'])) {
            $errors[$field] = $rules['error'];
        } elseif (isset($rules['numeric']) && !is_numeric($rules['value'])) {
            $errors[$field] = "Please enter a valid number";
        } elseif (isset($rules['min']) && $rules['value'] < $rules['min']) {
            $errors[$field] = "Minimum {$rules['min']}% required";
        } elseif (isset($rules['max']) && $rules['value'] > $rules['max']) {
            $errors[$field] = "Maximum percentage is {$rules['max']}%";
        }
    }

    // If no errors, save to database
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE student_profiles SET 
            last_school_name = ?,
            last_school_address = ?,
            last_school_class = ?,
            last_school_result = ?,
            last_school_medium = ?,
            last_school_type = ?
            WHERE id = ?");
        
        $stmt->bind_param("ssssssi",
            $fields['last_school_name']['value'],
            $fields['last_school_address']['value'],
            $fields['last_school_class']['value'],
            $fields['last_school_result']['value'],
            $fields['last_school_medium']['value'],
            $fields['last_school_type']['value'],
            $profile_id
        );

        if ($stmt->execute()) {
            $success = "Education information saved successfully!";
            // Refresh education data
            $stmt = $conn->prepare("SELECT * FROM student_profiles WHERE id = ?");
            $stmt->bind_param("i", $profile_id);
            $stmt->execute();
            $education = $stmt->get_result()->fetch_assoc();
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
                            <h4 class="mb-1"><i class="fas fa-graduation-cap me-2"></i>Education Information</h4>
                            <p class="mb-0 text-muted">Provide your academic background details</p>
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
                            <!-- School Information Section -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-school me-2 text-primary"></i> School Details
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="last_school_name" class="form-label">School Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control rounded-4 <?= isset($errors['last_school_name']) ? 'is-invalid' : '' ?>" 
                                                       id="last_school_name" name="last_school_name" 
                                                       value="<?= htmlspecialchars($education['last_school_name'] ?? '') ?>" 
                                                       <?= $editing_locked ? 'readonly' : '' ?> required>
                                                <?php if (isset($errors['last_school_name'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['last_school_name'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="last_school_type" class="form-label">School Type <span class="text-danger">*</span></label>
                                                <select class="form-select rounded-4 <?= isset($errors['last_school_type']) ? 'is-invalid' : '' ?>" 
                                                        id="last_school_type" name="last_school_type" 
                                                        <?= $editing_locked ? 'disabled' : '' ?> required>
                                                    <option value="">Select School Type</option>
                                                    <option value="Government" <?= ($education['last_school_type'] ?? '') == 'Government' ? 'selected' : '' ?>>Government</option>
                                                    <option value="Madrassa" <?= ($education['last_school_type'] ?? '') == 'Madrassa' ? 'selected' : '' ?>>Madrassa</option>
                                                    <option value="Private" <?= ($education['last_school_type'] ?? '') == 'Private' ? 'selected' : '' ?>>Private</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                                <?php if (isset($errors['last_school_type'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['last_school_type'] ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="form-text">Preference given to government school students</div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <label for="last_school_address" class="form-label">School Address <span class="text-danger">*</span></label>
                                                <textarea class="form-control rounded-4 <?= isset($errors['last_school_address']) ? 'is-invalid' : '' ?>" 
                                                          id="last_school_address" name="last_school_address" rows="3"
                                                          <?= $editing_locked ? 'readonly' : '' ?> required><?= htmlspecialchars($education['last_school_address'] ?? '') ?></textarea>
                                                <?php if (isset($errors['last_school_address'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['last_school_address'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Academic Details Section -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-award me-2 text-primary"></i>Academic Details
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label for="last_school_class" class="form-label">Last Class Attended <span class="text-danger">*</span></label>
                                                <select class="form-select rounded-4 <?= isset($errors['last_school_class']) ? 'is-invalid' : '' ?>" 
                                                        id="last_school_class" name="last_school_class" 
                                                        <?= $editing_locked ? 'disabled' : '' ?> required>
                                                    <option value="">Select Class</option>
                                                    <option value="7th" <?= ($education['last_school_class'] ?? '') == '7th' ? 'selected' : '' ?>>7th Class</option>
                                                    <option value="6th" <?= ($education['last_school_class'] ?? '') == '6th' ? 'selected' : '' ?>>6th Class</option>
                                                </select>
                                                <?php if (isset($errors['last_school_class'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['last_school_class'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label for="last_school_result" class="form-label">Result (%) <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" min="0" max="100" 
                                                           class="form-control rounded-4 <?= isset($errors['last_school_result']) ? 'is-invalid' : '' ?>" 
                                                           id="last_school_result" name="last_school_result" 
                                                           value="<?= htmlspecialchars($education['last_school_result'] ?? '') ?>" 
                                                           <?= $editing_locked ? 'readonly' : '' ?> required>
                                                    <span class="input-group-text bg-primary bg-opacity-10 border-primary">%</span>
                                                </div>
                                                <?php if (isset($errors['last_school_result'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['last_school_result'] ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="form-text">Minimum 75% required for eligibility</div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <label for="last_school_medium" class="form-label">Medium of Instruction <span class="text-danger">*</span></label>
                                                <select class="form-select rounded-4 <?= isset($errors['last_school_medium']) ? 'is-invalid' : '' ?>" 
                                                        id="last_school_medium" name="last_school_medium" 
                                                        <?= $editing_locked ? 'disabled' : '' ?> required>
                                                    <option value="">Select Medium</option>
                                                    <option value="Urdu" <?= ($education['last_school_medium'] ?? '') == 'Urdu' ? 'selected' : '' ?>>Urdu</option>
                                                    <option value="English" <?= ($education['last_school_medium'] ?? '') == 'English' ? 'selected' : '' ?>>English</option>
                                                    <option value="Sindhi" <?= ($education['last_school_medium'] ?? '') == 'Sindhi' ? 'selected' : '' ?>>Sindhi</option>
                                                    <option value="Pashto" <?= ($education['last_school_medium'] ?? '') == 'Pashto' ? 'selected' : '' ?>>Pashto</option>
                                                    <option value="Balochi" <?= ($education['last_school_medium'] ?? '') == 'Balochi' ? 'selected' : '' ?>>Balochi</option>
                                                </select>
                                                <?php if (isset($errors['last_school_medium'])): ?>
                                                    <div class="invalid-feedback d-flex align-items-center">
                                                        <i class="fas fa-exclamation-circle me-2"></i>
                                                        <?= $errors['last_school_medium'] ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Important Note -->
                            <div class="col-12">
                                <div class="alert alert-info border-0 rounded-4">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-info-circle me-3 fs-4"></i>
                                        <div>
                                            <h5 class="alert-heading mb-1">Important Note</h5>
                                            <p class="mb-0">You must upload your last school result in the Documents section after completing this form.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Navigation -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 gap-3">
    <a href="address.php" class="btn btn-outline-primary rounded-4 w-100 w-sm-auto order-1 order-sm-1">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
    
    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto order-3 order-sm-2 mt-2 mt-sm-0">
        <button type="submit" class="btn btn-primary px-sm-4 rounded-4 flex-grow-1" <?= $editing_locked ? 'disabled' : '' ?>>
            <i class="fas fa-save me-2"></i>Save Information
        </button>
        <a href="payment.php" class="btn btn-success px-sm-4 rounded-4 flex-grow-1">
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

    // Result validation
    const resultInput = document.getElementById('last_school_result');
    if (resultInput) {
        resultInput.addEventListener('change', function() {
            const result = parseFloat(this.value);
            if (result < 75) {
                const alertMsg = 'Minimum 75% required in last class for eligibility';
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback d-block';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${alertMsg}`;
                
                // Remove existing feedback if any
                const existingFeedback = this.parentNode.querySelector('.invalid-feedback');
                if (existingFeedback) {
                    existingFeedback.remove();
                }
                
                this.parentNode.appendChild(errorDiv);
                this.classList.add('is-invalid');
            }
        });
    }
    
    // School type validation for government school requirement
    const schoolTypeSelect = document.getElementById('last_school_type');
    if (schoolTypeSelect) {
        schoolTypeSelect.addEventListener('change', function() {
            if (this.value !== 'Government') {
                // Create a Bootstrap toast notification
                const toastHTML = `
                    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="toast-header bg-warning text-dark">
                                <strong class="me-auto"><i class="fas fa-exclamation-triangle me-2"></i>Note</strong>
                                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                            <div class="toast-body">
                                Preference is given to students from government schools
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove any existing toasts
                document.querySelectorAll('.toast').forEach(toast => toast.remove());
                
                // Add the new toast
                document.body.insertAdjacentHTML('beforeend', toastHTML);
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    document.querySelectorAll('.toast').forEach(toast => toast.remove());
                }, 5000);
            }
        });
    }
});
</script>
