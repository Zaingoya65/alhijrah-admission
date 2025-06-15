<?php
session_start();
include '../includes/config.php';
include '../includes/session_auth.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Initialize variables
$id = (int)$_SESSION['id'];
$b_form = htmlspecialchars($_SESSION['b_form']);
$current_year = date('Y');
$errors = [];
$success = '';
$profile_exists = false;
$profile = [];
$editing_locked = false;
$application_open = false;

// Fetch application deadlines
$deadlines = [];
$stmt = $conn->prepare("SELECT ziarat_start, ziarat_end, dgkhan_start, dgkhan_end FROM settings LIMIT 1");
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $deadlines = $result->fetch_assoc();
    }
}
$stmt->close();

// Check if application window is open
$current_date = date('Y-m-d');
if (!empty($deadlines)) {
    $application_open = ($current_date >= $deadlines['ziarat_start'] && $current_date <= $deadlines['ziarat_end']) || 
                       ($current_date >= $deadlines['dgkhan_start'] && $current_date <= $deadlines['dgkhan_end']);
}

// Fetch profile data
$stmt = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ? AND application_year = ?");
$stmt->bind_param("is", $id, $current_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile_exists = true;
    $profile = $result->fetch_assoc();
    $editing_locked = ($profile['is_submitted'] == 1);
}
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$editing_locked && $application_open) {
    // Validate and sanitize inputs
    $full_name = trim($conn->real_escape_string($_POST['full_name'] ?? ''));
    $date_of_birth = $conn->real_escape_string($_POST['date_of_birth'] ?? '');
    $domicile_province = $conn->real_escape_string($_POST['domicile_province'] ?? '');
    $domicile_district = $conn->real_escape_string($_POST['domicile_district'] ?? '');
    $guardian_name = trim($conn->real_escape_string($_POST['guardian_name'] ?? ''));
    $guardian_cnic = $conn->real_escape_string($_POST['guardian_cnic'] ?? '');
    $guardian_occupation = trim($conn->real_escape_string($_POST['guardian_occupation'] ?? ''));
    $applied_campus = $conn->real_escape_string($_POST['applied_campus'] ?? '');

    // Validate inputs
    if (empty($full_name)) $errors['full_name'] = "Full name is required";
    if (empty($date_of_birth)) $errors['date_of_birth'] = "Date of birth is required";
    if (empty($domicile_province)) $errors['domicile_province'] = "Province is required";
    if (empty($domicile_district)) $errors['domicile_district'] = "District is required";
    if (empty($guardian_name)) $errors['guardian_name'] = "Guardian name is required";
    if (empty($guardian_cnic)) $errors['guardian_cnic'] = "Guardian CNIC is required";
    if (empty($guardian_occupation)) $errors['guardian_occupation'] = "Guardian occupation is required";
    if (empty($applied_campus)) $errors['applied_campus'] = "Campus selection is required";

    // Validate campus-specific deadline
    if ($applied_campus == 'Ziarat' && ($current_date < $deadlines['ziarat_start'] || $current_date > $deadlines['ziarat_end'])) {
        $errors['campus_period'] = "Application closed for Ziarat Campus";
    } elseif ($applied_campus == 'Dera Ghazi Khan' && ($current_date < $deadlines['dgkhan_start'] || $current_date > $deadlines['dgkhan_end'])) {
        $errors['campus_period'] = "Application closed for Dera Ghazi Khan Campus";
    }

    // Handle file upload
    $upload_dir = "../uploads/$id/";
    $profile_image = $profile['profile_image'] ?? null;
    $remove_image = isset($_POST['remove_image']);

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $max_size = 500 * 1024; // 500KB
        
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['profile_image'] = "Only JPG, JPEG, PNG, and WEBP files are allowed";
        } elseif ($file_size > $max_size) {
            $errors['profile_image'] = "File size must be less than 500KB";
        } else {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $profile_image = "profile_" . uniqid() . '.' . $ext;
            $upload_path = $upload_dir . $profile_image;
            
            // Delete old image if exists
            if ($profile_exists && !empty($profile['profile_image'])) {
                $old_image_path = $upload_dir . $profile['profile_image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $errors['profile_image'] = "Failed to upload image";
                $profile_image = $profile['profile_image'] ?? null;
            }
        }
    } elseif ($remove_image && $profile_exists && !empty($profile['profile_image'])) {
        $old_image_path = $upload_dir . $profile['profile_image'];
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
        $profile_image = null;
    }

    // Save to database if no errors
    if (empty($errors)) {
        if ($profile_exists) {
            $stmt = $conn->prepare("UPDATE student_profiles SET 
                full_name = ?, date_of_birth = ?, domicile_province = ?, domicile_district = ?, 
                guardian_name = ?, guardian_cnic = ?, guardian_occupation = ?, applied_campus = ?,
                profile_image = ?
                WHERE user_id = ? AND application_year = ?");
            $stmt->bind_param("sssssssssis", $full_name, $date_of_birth, $domicile_province, $domicile_district,
                              $guardian_name, $guardian_cnic, $guardian_occupation, $applied_campus,
                              $profile_image, $id, $current_year);
        } else {
            $stmt = $conn->prepare("INSERT INTO student_profiles 
                (user_id, full_name, date_of_birth, domicile_province, domicile_district, 
                 guardian_name, guardian_cnic, guardian_occupation, applied_campus, profile_image, application_year)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssss", $id, $full_name, $date_of_birth, $domicile_province, $domicile_district,
                              $guardian_name, $guardian_cnic, $guardian_occupation, $applied_campus, 
                              $profile_image, $current_year);
        }

        if ($stmt->execute()) {
            $success = "Personal information saved successfully!";
            $profile_exists = true;
            // Refresh profile data
            $stmt = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ? AND application_year = ?");
            $stmt->bind_param("is", $id, $current_year);
            $stmt->execute();
            $result = $stmt->get_result();
            $profile = $result->fetch_assoc();
        } else {
            $errors['database'] = "Error saving information: " . $conn->error;
        }
        $stmt->close();
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
                <!-- Card Header -->
                <div class="card-header bg-primary bg-opacity-10 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Personal Information</h4>
                            <p class="mb-0 text-muted">Complete your personal details</p>
                        </div>
                        <?php if ($profile_exists): ?>
                            <span class="badge bg-<?= $editing_locked ? 'success' : 'warning' ?>">
                                <?= $editing_locked ? 'Submitted' : 'In Progress' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Application Status Alerts -->
                    <?php if ($editing_locked): ?>
                        <div class="alert alert-warning border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lock me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Editing Locked</h5>
                                    <p class="mb-0">You have submitted your application. Editing is now disabled.</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!$application_open): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-times me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Application Period Closed</h5>
                                    <p class="mb-0">The admission application period is currently closed.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Deadline Information -->
                    <?php if (!empty($deadlines)): ?>
                        <div class="alert alert-info border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-2">Application Deadlines</h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="bg-white p-3 rounded-3">
                                                <h6 class="mb-1">Ziarat Campus</h6>
                                                <p class="mb-0 small">
                                                    <?= date('M j, Y', strtotime($deadlines['ziarat_start'])) ?> - 
                                                    <?= date('M j, Y', strtotime($deadlines['ziarat_end'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="bg-white p-3 rounded-3">
                                                <h6 class="mb-1">Dera Ghazi Khan Campus</h6>
                                                <p class="mb-0 small">
                                                    <?= date('M j, Y', strtotime($deadlines['dgkhan_start'])) ?> - 
                                                    <?= date('M j, Y', strtotime($deadlines['dgkhan_end'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Messages -->
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Success!</h5>
                                    <p class="mb-0"><?= $success ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors['database'])): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Error</h5>
                                    <p class="mb-0"><?= $errors['database'] ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Main Form -->
                    <form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row g-4">
                            <!-- Left Column -->
                            <div class="col-lg-6">
                                <!-- Profile Photo Section -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">Profile Photo</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="position-relative me-4">
                                                <!-- Profile Image Container -->
                                                <div class="profile-image-container rounded-circle border shadow-sm cursor-pointer"
                                                     style="width: 120px; height: 120px; overflow: hidden;"
                                                     onclick="document.getElementById('profile_image').click()">
                                                    <?php if ($profile_exists && !empty($profile['profile_image']) && file_exists($upload_dir . $profile['profile_image'])): ?>
                                                        <img src="<?= $upload_dir . $profile['profile_image'] ?>" 
                                                             class="img-fluid h-100 w-100" 
                                                             style="object-fit: cover;"
                                                             alt="Profile Image"
                                                             id="currentProfileImage">
                                                    <?php else: ?>
                                                        <div class="h-100 w-100 bg-light d-flex flex-column align-items-center justify-content-center">
                                                            <i class="fas fa-user text-muted mb-2 fs-4"></i>
                                                            <small class="text-muted">No photo</small>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Upload Indicator -->
                                                    <div class="upload-indicator position-absolute top-0 start-0 w-100 h-100 d-none" 
                                                         style="background: rgba(0,0,0,0.5);">
                                                        <div class="h-100 d-flex align-items-center justify-content-center">
                                                            <div class="spinner-border text-light" role="status">
                                                                <span class="visually-hidden">Uploading...</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Remove Image Button -->
                                                <?php if ($profile_exists && !empty($profile['profile_image']) && !$editing_locked): ?>
                                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle shadow-sm"
                                                            style="width: 28px; height: 28px; transform: translate(30%, -30%);"
                                                            onclick="event.stopPropagation(); document.getElementById('remove_image').click()">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="flex-grow-1">
                                                <div class="alert alert-info border-0 rounded-3 py-2 mb-3">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        <strong>Photo Requirements:</strong> JPG/PNG/WEBP, max 500KB, plain background
                                                    </small>
                                                </div>
                                                
                                                <?php if (!$editing_locked): ?>
                                                    <div class="d-flex">
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-2"
                                                                onclick="document.getElementById('profile_image').click()">
                                                            <i class="fas fa-upload me-1"></i> Upload
                                                        </button>
                                                        <input type="file" class="d-none" id="profile_image" name="profile_image" accept="image/*">
                                                        <input type="checkbox" class="d-none" id="remove_image" name="remove_image" value="1">
                                                        
                                                        <?php if ($profile_exists && !empty($profile['profile_image'])): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    onclick="document.getElementById('remove_image').click()">
                                                                <i class="fas fa-trash-alt me-1"></i> Remove
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (isset($errors['profile_image'])): ?>
                                                    <div class="text-danger small mt-2"><?= $errors['profile_image'] ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Personal Information Section -->
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">Personal Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Full Name -->
                                        <div class="mb-3">
                                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" 
                                                   id="full_name" name="full_name" 
                                                   value="<?= $profile_exists ? htmlspecialchars($profile['full_name']) : '' ?>" 
                                                   <?= $editing_locked ? 'readonly' : '' ?> required>
                                            <?php if (isset($errors['full_name'])): ?>
                                                <div class="invalid-feedback"><?= $errors['full_name'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- B-Form Number -->
                                        <div class="mb-3">
                                            <label class="form-label">B-Form Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" value="<?= $b_form ?>" readonly>
                                        </div>

                                        <!-- Date of Birth -->
                                        <div class="mb-3">
                                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control <?= isset($errors['date_of_birth']) ? 'is-invalid' : '' ?>" 
                                                   id="date_of_birth" name="date_of_birth" 
                                                   value="<?= $profile_exists ? $profile['date_of_birth'] : '' ?>" 
                                                   <?= $editing_locked ? 'readonly' : '' ?> required>
                                            <?php if (isset($errors['date_of_birth'])): ?>
                                                <div class="invalid-feedback"><?= $errors['date_of_birth'] ?></div>
                                            <?php endif; ?>
                                            <small class="text-muted">Student must be 12-14 years old</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-lg-6">
                                <!-- Domicile Information -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">Domicile Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Province -->
                                        <div class="mb-3">
                                            <label for="domicile_province" class="form-label">Province <span class="text-danger">*</span></label>
                                            <select class="form-select <?= isset($errors['domicile_province']) ? 'is-invalid' : '' ?>" 
                                                    id="domicile_province" name="domicile_province" 
                                                    <?= $editing_locked ? 'disabled' : '' ?> required>
                                                <option value="">Select Province</option>
                                                <option value="Balochistan" <?= ($profile_exists && $profile['domicile_province'] == 'Balochistan') ? 'selected' : '' ?>>Balochistan</option>
                                                <option value="Sindh" <?= ($profile_exists && $profile['domicile_province'] == 'Sindh') ? 'selected' : '' ?>>Sindh</option>
                                                <option value="Punjab" <?= ($profile_exists && $profile['domicile_province'] == 'Punjab') ? 'selected' : '' ?>>Punjab</option>
                                            </select>
                                            <?php if (isset($errors['domicile_province'])): ?>
                                                <div class="invalid-feedback"><?= $errors['domicile_province'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- District -->
                                        <div class="mb-3">
                                            <label for="domicile_district" class="form-label">District <span class="text-danger">*</span></label>
                                            <select class="form-select <?= isset($errors['domicile_district']) ? 'is-invalid' : '' ?>" 
                                                    id="domicile_district" name="domicile_district" 
                                                    <?= $editing_locked ? 'disabled' : '' ?> required>
                                                <option value="">Select District</option>
                                                <?php if ($profile_exists && !empty($profile['domicile_district'])): ?>
                                                    <option value="<?= $profile['domicile_district'] ?>" selected>
                                                        <?= $profile['domicile_district'] ?>
                                                    </option>
                                                <?php endif; ?>
                                            </select>
                                            <?php if (isset($errors['domicile_district'])): ?>
                                                <div class="invalid-feedback"><?= $errors['domicile_district'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Guardian Information -->
                                <div class="card border-0 shadow-sm rounded-4 mb-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">Guardian Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Guardian Name -->
                                        <div class="mb-3">
                                            <label for="guardian_name" class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?= isset($errors['guardian_name']) ? 'is-invalid' : '' ?>" 
                                                   id="guardian_name" name="guardian_name" 
                                                   value="<?= $profile_exists ? htmlspecialchars($profile['guardian_name']) : '' ?>" 
                                                   <?= $editing_locked ? 'readonly' : '' ?> required>
                                            <?php if (isset($errors['guardian_name'])): ?>
                                                <div class="invalid-feedback"><?= $errors['guardian_name'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Guardian CNIC -->
                                        <div class="mb-3">
                                            <label for="guardian_cnic" class="form-label">CNIC <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?= isset($errors['guardian_cnic']) ? 'is-invalid' : '' ?>" 
                                                   id="guardian_cnic" name="guardian_cnic" 
                                                   value="<?= $profile_exists ? $profile['guardian_cnic'] : '' ?>" 
                                                   placeholder="XXXXX-XXXXXXX-X" 
                                                   <?= $editing_locked ? 'readonly' : '' ?> required>
                                            <?php if (isset($errors['guardian_cnic'])): ?>
                                                <div class="invalid-feedback"><?= $errors['guardian_cnic'] ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Guardian Occupation -->
                                        <div class="mb-3">
                                            <label for="guardian_occupation" class="form-label">Occupation <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?= isset($errors['guardian_occupation']) ? 'is-invalid' : '' ?>" 
                                                   id="guardian_occupation" name="guardian_occupation" 
                                                   value="<?= $profile_exists ? htmlspecialchars($profile['guardian_occupation']) : '' ?>" 
                                                   <?= $editing_locked ? 'readonly' : '' ?> required>
                                            <?php if (isset($errors['guardian_occupation'])): ?>
                                                <div class="invalid-feedback"><?= $errors['guardian_occupation'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Campus Selection -->
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0">Campus Selection</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="applied_campus" class="form-label">Preferred Campus <span class="text-danger">*</span></label>
                                            <select class="form-select <?= isset($errors['applied_campus']) ? 'is-invalid' : '' ?>" 
                                                    id="applied_campus" name="applied_campus" 
                                                    <?= $editing_locked ? 'disabled' : '' ?> required>
                                                <option value="">Select Campus</option>
                                                <option value="Ziarat" <?= ($profile_exists && $profile['applied_campus'] == 'Ziarat') ? 'selected' : '' ?>>Ziarat Campus (Balochistan)</option>
                                                <option value="Dera Ghazi Khan" <?= ($profile_exists && $profile['applied_campus'] == 'Dera Ghazi Khan') ? 'selected' : '' ?>>Dera Ghazi Khan Campus (Sindh & South Punjab)</option>
                                            </select>
                                            <?php if (isset($errors['applied_campus'])): ?>
                                                <div class="invalid-feedback"><?= $errors['applied_campus'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (isset($errors['campus_period'])): ?>
                                            <div class="alert alert-danger mb-0 mt-3">
                                                <?= $errors['campus_period'] ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                       <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-4 gap-3">
    <div class="w-100 w-sm-auto order-1">
        <?php if (!empty($success)): ?>
            <a href="address.php" class="btn btn-success px-3 px-sm-4 rounded-4 w-100">
                <i class="fas fa-arrow-right me-2"></i> Next
            </a>
        <?php else: ?>
            <button type="submit" class="btn btn-primary px-3 px-sm-4 rounded-4 w-100" 
                    <?= ($editing_locked || !$application_open) ? 'disabled' : '' ?>>
                <i class="fas fa-save me-2"></i> Save Information
            </button>
        <?php endif; ?>
    </div>
</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include'../includes/stud_footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // District data based on province selection
    const districtData = {
        'Balochistan': ['Awaran',
    'Barkhan',
    'Chagai',
    'Chaman',
    'Dera Bugti',
    'Duki',
    'Gwadar',
    'Harnai',
    'Jafarabad',
    'Jhal Magsi',
    'Kachhi (Bolan)',
    'Kalat',
    'Kech (Turbat)',
    'Kharan',
    'Khuzdar',
    'Killa Abdullah',
    'Killa Saifullah',
    'Kohlu',
    'Lasbela',
    'Loralai',
    'Mastung',
    'Musakhel',
    'Nasirabad',
    'Nushki',
    'Panjgur',
    'Pishin',
    'Quetta',
    'Sherani',
    'Sibi',
    'Sohbatpur',
    'Washuk',
    'Zhob',
    'Ziarat'],
    
        'Sindh': [  'Badin',
    'Dadu',
    'Ghotki',
    'Hyderabad',
    'Jacobabad',
    'Jamshoro',
    'Karachi Central',
    'Karachi East',
    'Karachi South',
    'Karachi West',
    'Kashmore',
    'Khairpur',
    'Korangi',
    'Larkana',
    'Malir',
    'Matiari',
    'Mirpur Khas',
    'Naushahro Feroze',
    'Nawabshah (Shaheed Benazirabad)',
    'Sanghar',
    'Shikarpur',
    'Sujawal',
    'Sukkur',
    'Tando Allahyar',
    'Tando Muhammad Khan',
    'Tharparkar',
    'Thatta',
    'Umerkot'],

    'Punjab': ['Dera Ghazi Khan', 
    'Bahawalpur', 
    'Bhakkar',
    'Khanewal', 
    'Kot Addu',
    'Taunsa Sharif', 
    'Rajanpur', 
    'Bahawalnagar', 
    'Vehari', 
    'Layyah',
    'Lodhran', 
    'Multan', 
    'Muzaffargarh', 
    'Rahim Yar Khan', 
    'Jhang']
    };
    
    // Province change event for districts
    const provinceSelect = document.getElementById('domicile_province');
    const districtSelect = document.getElementById('domicile_district');
    
    if (provinceSelect) {
        provinceSelect.addEventListener('change', function() {
            const selectedProvince = this.value;
            districtSelect.innerHTML = '<option value="">Select District</option>';
            
            if (selectedProvince && districtData[selectedProvince]) {
                districtData[selectedProvince].forEach(district => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    districtSelect.appendChild(option);
                });
            }
        });
    }
    
    // Campus validation based on domicile
    const campusSelect = document.getElementById('applied_campus');
    if (campusSelect && provinceSelect) {
        provinceSelect.addEventListener('change', validateCampus);
        campusSelect.addEventListener('change', validateCampus);
        
        function validateCampus() {
            const province = provinceSelect.value;
            const campus = campusSelect.value;
            
            if (province && campus) {
                if ((province === 'Balochistan' && campus !== 'Ziarat') || 
                    (province !== 'Balochistan' && campus === 'Ziarat')) {
                    alert('Error: Ziarat Campus is only for Balochistan students. Dera Ghazi Khan Campus is for Sindh and South Punjab students.');
                    campusSelect.value = province === 'Balochistan' ? 'Ziarat' : 'Dera Ghazi Khan';
                }
            }
        }
    }
    
  
    
    // CNIC format validation
    const cnicInput = document.getElementById('guardian_cnic');
    if (cnicInput) {
        cnicInput.addEventListener('input', function() {
            // Remove any non-digit characters
            let value = this.value.replace(/\D/g, '');
            
            // Format as XXXXX-XXXXXXX-X
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5);
            }
            if (value.length > 13) {
                value = value.substring(0, 13) + '-' + value.substring(13);
            }
            if (value.length > 15) {
                value = value.substring(0, 15);
            }
            
            this.value = value;
        });
    }

    // Profile image upload preview
    const profileImageInput = document.getElementById('profile_image');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            const uploadIndicator = document.querySelector('.upload-indicator');
            const currentImage = document.getElementById('currentProfileImage');
            
            if (this.files && this.files[0]) {
                // Show uploading indicator
                if (uploadIndicator) uploadIndicator.classList.remove('d-none');
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (currentImage) {
                        currentImage.src = e.target.result;
                        currentImage.classList.remove('d-none');
                    }
                    // Hide uploading indicator when done
                    if (uploadIndicator) uploadIndicator.classList.add('d-none');
                }
                
                reader.onerror = function() {
                    // Hide uploading indicator if error
                    if (uploadIndicator) uploadIndicator.classList.add('d-none');
                    alert('Error loading image. Please try another file.');
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});

// Form validation
(function() {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<style>
    .profile-image-container {
        transition: all 0.3s ease;
    }
    
    .profile-image-container:hover {
        transform: scale(1.03);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .cursor-pointer {
        cursor: pointer;
    }
    
    .rounded-4 {
        border-radius: 1rem !important;
    }
</style>

