<?php
// review.php
session_start();
require '../includes/config.php';
require '../includes/session_auth.php';

$id = $_SESSION['id'];
$current_year = date('Y');

// Define required fields for each table
$requiredProfileFields = [
    'full_name', 'date_of_birth', 'domicile_province', 'domicile_district',
    'phone_number', 'guardian_name', 'guardian_cnic', 'guardian_occupation',
    'emergency_contact', 'emergency_relation', 'emergency_phone',
    'last_school_name', 'last_school_result', 'last_school_class',
    'last_school_address', 'current_address', 'permanent_address', 'applied_campus'
];

$requiredDocumentFields = [
    'b_form_path',
    'school_result_path',
    'guardian_cnic_path',
    'domicile_certificate_path',
    'challan_copy_path'
];

// Fetch profile
$sql = "SELECT * FROM student_profiles WHERE user_id = ? AND application_year = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $id, $current_year);
$stmt->execute();
$result = $stmt->get_result();

// Fetch documents
$sql_docs = "SELECT * FROM documents WHERE user_id = ?";
$stmt_docs = $conn->prepare($sql_docs);
$stmt_docs->bind_param("i", $id);
$stmt_docs->execute();
$result_docs = $stmt_docs->get_result();
$documents = $result_docs->fetch_assoc();

// Fetch user data from registered_users
$sql_user = "SELECT b_form FROM registered_users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
if (!$stmt_user) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$stmt_user->bind_param("i", $id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

if ($result->num_rows === 0) {
    header('Location: personal_info.php');
    exit();
}

$profile = $result->fetch_assoc();

// Check for missing fields when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$profile['is_submitted']) {
    $missingFields = [];
    
    // Create a mapping between database fields and user-friendly names
    $fieldNameMap = [
        'b_form_path' => 'B-Form File',
        'school_result_path' => 'School Result Card',
        'guardian_cnic_path' => 'Guardian CNIC File',
        'domicile_certificate_path' => 'Local/Domicile Certificate',
        'challan_copy_path' => 'Fee Challan File',
        'B-form (in registration)' => 'B-Form Number (in registration)',
        // Add mappings for profile fields
        'full_name' => 'Full Name',
        'date_of_birth' => 'Date of Birth',
        'domicile_province' => 'Domicile Province',
        'domicile_district' => 'Domicile District',
        'phone_number' => 'Phone Number',
        'guardian_name' => 'Guardian Name',
        'guardian_cnic' => 'Guardian CNIC',
        'guardian_occupation' => 'Guardian Occupation',
        'emergency_contact' => 'Emergency Contact',
        'emergency_relation' => 'Relationship with emergency person',
        'emergency_phone' => 'Emergency Phone Number',
        'last_school_name' => 'School Name',
        'last_school_result' => 'School Result',
        'last_school_class' => 'School Class',
        'last_school_address' => 'School Address',
        'current_address' => 'Current Address',
        'permanent_address' => 'Permanent Address',
        'applied_campus' => 'Applied Campus'
    ];

    // Check profile fields - store original field names
    foreach ($requiredProfileFields as $field) {
        if (empty($profile[$field])) {
            $missingFields[] = $field; // Store original field name
        }
    }
    
    // Check document fields - store original field names
    if ($result_docs->num_rows === 0) {
        $missingFields = array_merge($missingFields, $requiredDocumentFields);
    } else {
        foreach ($requiredDocumentFields as $field) {
            if (empty($documents[$field])) {
                $missingFields[] = $field; // Store original field name
            }
        }
    }
    
    // Check B-form in registered_users
    if (empty($user['b_form'])) {
        $missingFields[] = 'B-form (in registration)';
    }
    
    if (!empty($missingFields)) {
        $errorMessage = "<div class='d-flex align-items-start'>";
        $errorMessage .= "<i class='fas fa-exclamation-triangle fs-3 text-danger me-3 mt-1'></i>";
        $errorMessage .= "<div>";
        $errorMessage .= "<h5 class='mb-2'>Submission Failed! Missing Required Information</h5>";
        $errorMessage .= "<p class='mb-3'>Please complete the following required information:</p>";
        $errorMessage .= "<div class='row'>";
        
        // Split missing fields into two columns for better readability
        $half = ceil(count($missingFields) / 2);
        $firstHalf = array_slice($missingFields, 0, $half);
        $secondHalf = array_slice($missingFields, $half);
        
        $errorMessage .= "<div class='col-md-6'>";
        $errorMessage .= "<ul class='mb-3'>";
        foreach ($firstHalf as $field) {
            $displayName = $fieldNameMap[$field] ?? str_replace('_', ' ', ucfirst($field));
            $errorMessage .= "<li>" . htmlspecialchars($displayName) . "</li>";
        }
        $errorMessage .= "</ul>";
        $errorMessage .= "</div>";
        
        $errorMessage .= "<div class='col-md-6'>";
        $errorMessage .= "<ul class='mb-3'>";
        foreach ($secondHalf as $field) {
            $displayName = $fieldNameMap[$field] ?? str_replace('_', ' ', ucfirst($field));
            $errorMessage .= "<li>" . htmlspecialchars($displayName) . "</li>";
        }
        $errorMessage .= "</ul>";
        $errorMessage .= "</div>";
        
        $errorMessage .= "</div>"; // Close row
        
        // Add quick action buttons
        $errorMessage .= "<div class='d-flex gap-2 mt-3'>";
        $errorMessage .= "<a href='personal_info.php' class='btn btn-sm btn-outline-primary'>";
        $errorMessage .= "<i class='fas fa-user-edit me-1'></i> Edit Personal Info";
        $errorMessage .= "</a>";
        $errorMessage .= "<a href='documents.php' class='btn btn-sm btn-outline-primary'>";
        $errorMessage .= "<i class='fas fa-file-upload me-1'></i> Upload Documents";
        $errorMessage .= "</a>";
        $errorMessage .= "</div>";
        
        $errorMessage .= "</div>"; // Close flex div
        $errorMessage .= "</div>"; // Close main div
    } else {
        // All required fields are present, proceed with submission
        $stmt = $conn->prepare("UPDATE student_profiles SET is_submitted = 1, submitted_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header('Location: review.php?submitted=1');
            exit();
        } else {
            $errorMessage = "<div class='alert alert-danger'>";
            $errorMessage .= "<i class='fas fa-times-circle me-2'></i> Error submitting application: " . $conn->error;
            $errorMessage .= "</div>";
        }
    }
}

// Format specific fields
function formatDate($date) {
    return $date ? date('F j, Y', strtotime($date)) : 'Not provided';
}

function formatCNIC($cnic) {
    return $cnic ? preg_replace('/(\d{5})(\d{7})(\d{1})/', '$1-$2-$3', $cnic) : 'Not provided';
}

// Check completeness for display
$profileComplete = true;
$docsComplete = true;
$missingProfileFields = [];
$missingDocFields = [];

foreach ($requiredProfileFields as $field) {
    if (empty($profile[$field])) {
        $profileComplete = false;
        $missingProfileFields[] = $field;
    }
}

if ($result_docs->num_rows === 0) {
    $docsComplete = false;
    $missingDocFields = $requiredDocumentFields;
} else {
    foreach ($requiredDocumentFields as $field) {
        if (empty($documents[$field])) {
            $docsComplete = false;
            $missingDocFields[] = $field;
        }
    }
}

$bFormComplete = !empty($user['b_form']);

include '../includes/stud_header.php';
?>

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
                            <h4 class="mb-1">Application Review</h4>
                            <p class="mb-0 text-muted">Review your application before submission</p>
                        </div>
                        <?php if (isset($_GET['submitted']) || $profile['is_submitted']): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i> Submitted
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-exclamation-circle me-1"></i> Pending Submission
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Error</h5>
                                    <p class="mb-0"><?php echo $errorMessage; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['submitted']) || $profile['is_submitted']): ?>
                        <!-- Submitted Application View -->
                        <div class="text-center mb-5 py-4">
                            <div class="mb-4">
                                <div class="bg-success bg-opacity-10 d-inline-flex p-4 rounded-circle">
                                    <i class="fas fa-check-circle text-success fa-4x"></i>
                                </div>
                            </div>
                            <h2 class="mb-3">Application Submitted Successfully!</h2>
                            <p class="lead text-muted mb-4">Your application has been received and is being processed.</p>
                            
                            <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mb-5">
                                <a href="status.php" class="btn btn-primary px-4">
                                    <i class="fas fa-search me-2"></i> Check Status
                                </a>
                                <a href="../support.php" class="btn btn-outline-secondary px-4">
                                    <i class="fas fa-question-circle me-2"></i> Get Help
                                </a>
                            </div>
                        </div>

                        <!-- Application Timeline -->
                        <div class="application-timeline mb-5">
                            <h4 class="mb-4 border-bottom pb-2">Next Steps</h4>
                            
                            <div class="steps">
                                <!-- Step 1 -->
                                <div class="step completed">
                                    <div class="step-icon">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Application Submitted</h5>
                                        <p class="text-muted mb-1"><?php echo date('F j, Y, g:i a', strtotime($profile['submitted_at'] ?? 'now')); ?></p>
                                        <p class="mb-0">Your application is now in the review queue.</p>
                                    </div>
                                </div>
                                
                                <!-- Step 2 -->
                                <div class="step active">
                                    <div class="step-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Under Review</h5>
                                        <p class="text-muted mb-1">Expected within 72 hours</p>
                                        <p class="mb-0">Our admissions team is reviewing your application.</p>
                                    </div>
                                </div>
                                
                                <!-- Step 3 -->
                                <div class="step">
                                    <div class="step-icon">
                                        <i class="fas fa-file-download"></i>
                                    </div>
                                    <div class="step-content">
                                        <h5>Roll Number Slip</h5>
                                        <p class="text-muted mb-1">Available 7 days before test</p>
                                        <p class="mb-0">Download your roll number slip when available.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info border-0 rounded-4 mt-4">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-info-circle fs-4 me-3"></i>
                                    <div>
                                        <strong>Important:</strong> The roll number slip will only be available for download 7 days before the test date. 
                                        Make sure to check back regularly.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Pre-Submission Review -->
                        <div class="alert alert-info border-0 rounded-4 d-flex align-items-center mb-4">
                            <i class="fas fa-info-circle fs-4 me-3"></i>
                            <div>
                                <h5 class="alert-heading mb-2">Before You Submit</h5>
                                <p class="mb-0">Please carefully review all information before submission. After submission, you won't be able to make changes to your application.</p>
                            </div>
                        </div>
                        
                        <!-- Profile Header -->
                        <div class="profile-header text-center mb-5">
                            <div class="profile-image-container mb-3">
                                <?php if (!empty($profile['profile_image'])): ?>
                                    <img src="../uploads/<?php echo $id . '/' . htmlspecialchars($profile['profile_image']); ?>" 
                                         class="rounded-circle border border-3 border-primary shadow-sm" 
                                         width="120" 
                                         height="120"
                                         alt="Profile Image"
                                         style="object-fit: cover;"
                                         onerror="this.onerror=null;this.src='../assets/default-profile.png';">
                                <?php else: ?>
                                    <div class="rounded-circle border border-3 border-primary d-flex align-items-center justify-content-center bg-light shadow-sm" style="width: 120px; height: 120px;">
                                        <i class="fas fa-user text-primary fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h2 class="mb-1"><?php echo htmlspecialchars($profile['full_name'] ?? 'Your Name'); ?></h2>
                            <p class="text-muted">Application for <?php echo $current_year; ?> Admission</p>
                            
                            <!-- Application Completeness Indicator -->
                            <div class="completeness-indicator mt-4 mx-auto" style="max-width: 500px;">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Application Completeness</span>
                                    <span><?php echo ($profileComplete && $docsComplete && $bFormComplete) ? '100%' : 'Incomplete'; ?></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar <?php echo ($profileComplete && $docsComplete && $bFormComplete) ? 'bg-success' : 'bg-warning'; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo ($profileComplete && $docsComplete && $bFormComplete) ? '100' : '75'; ?>%" 
                                         aria-valuenow="<?php echo ($profileComplete && $docsComplete && $bFormComplete) ? '100' : '75'; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <?php if (!$profileComplete || !$docsComplete || !$bFormComplete): ?>
                                    <small class="text-muted d-block mt-1">Some required information is missing</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Application Sections -->
                        <div class="row g-4">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                                        <a href="personal_info.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <dl class="mb-0">
                                            <dt>Full Name</dt>
                                            <dd><?php echo htmlspecialchars($profile['full_name'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>B-form Number</dt>
                                            <dd><?php echo htmlspecialchars($user['b_form'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Date of Birth</dt>
                                            <dd><?php echo formatDate($profile['date_of_birth'] ?? ''); ?></dd>
                                            
                                            <dt>Domicile Province</dt>
                                            <dd><?php echo htmlspecialchars($profile['domicile_province'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Domicile District</dt>
                                            <dd><?php echo htmlspecialchars($profile['domicile_district'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Phone Number</dt>
                                            <dd><?php echo htmlspecialchars($profile['phone_number'] ?? 'Not provided'); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Guardian Information -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Guardian Information</h6>
                                        <a href="personal_info.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <dl class="mb-0">
                                            <dt>Guardian Name</dt>
                                            <dd><?php echo htmlspecialchars($profile['guardian_name'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Guardian CNIC</dt>
                                            <dd><?php echo formatCNIC($profile['guardian_cnic'] ?? ''); ?></dd>
                                            
                                            <dt>Guardian Occupation</dt>
                                            <dd><?php echo htmlspecialchars($profile['guardian_occupation'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Emergency Contact</dt>
                                            <dd><?php echo htmlspecialchars($profile['emergency_contact'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Emergency Relation</dt>
                                            <dd><?php echo htmlspecialchars($profile['emergency_relation'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Emergency Phone</dt>
                                            <dd><?php echo htmlspecialchars($profile['emergency_phone'] ?? 'Not provided'); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Academic Information -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h6>
                                        <a href="education_info.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <dl class="mb-0">
                                            <dt>Last School Name</dt>
                                            <dd><?php echo htmlspecialchars($profile['last_school_name'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Last School Result</dt>
                                            <dd><?php echo htmlspecialchars($profile['last_school_result'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>Last School Class</dt>
                                            <dd><?php echo htmlspecialchars($profile['last_school_class'] ?? 'Not provided'); ?></dd>
                                            
                                            <dt>School Address</dt>
                                            <dd><?php echo nl2br(htmlspecialchars($profile['last_school_address'] ?? 'Not provided')); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Information -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-home me-2"></i>Address Information</h6>
                                        <a href="address.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <h6>Current Address</h6>
                                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($profile['current_address'] ?? 'Not provided')); ?></p>
                                        
                                        <h6>Permanent Address</h6>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($profile['permanent_address'] ?? 'Not provided')); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Documents Section -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Submitted Documents</h6>
                                        <a href="documents.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <?php 
                                            $documentItems = [
                                                'b_form_path' => ['title' => 'B-Form', 'icon' => 'fa-id-card'],
                                                'school_result_path' => ['title' => 'School Result', 'icon' => 'fa-graduation-cap'],
                                                'guardian_cnic_path' => ['title' => 'Guardian CNIC', 'icon' => 'fa-id-card'],
                                                'domicile_certificate_path' => ['title' => 'Domicile Certificate', 'icon' => 'fa-file-contract'],
                                                'challan_copy_path' => ['title' => 'Fee Challan', 'icon' => 'fa-receipt']
                                            ];
                                            
                                            foreach ($documentItems as $path => $doc): ?>
                                                <div class="col-md-4">
                                                    <div class="document-item border rounded-3 p-3 h-100 <?php echo empty($documents[$path]) ? 'border-danger' : 'border-success'; ?>">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <i class="fas <?php echo $doc['icon']; ?> me-2 text-muted"></i>
                                                            <h6 class="mb-0"><?php echo $doc['title']; ?></h6>
                                                        </div>
                                                        <?php if (!empty($documents[$path])): ?>
                                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                                <span class="badge bg-success">
                                                                    <i class="fas fa-check-circle me-1"></i> Uploaded
                                                                </span>
                                                                <a href="../uploads/<?php echo $id . '/' . htmlspecialchars($documents[$path]); ?>" 
                                                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                                                    <i class="fas fa-eye me-1"></i> View
                                                                </a>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                                <span class="badge bg-danger">
                                                                    <i class="fas fa-times-circle me-1"></i> Missing
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Application Details -->
                            <div class="col-12">
                                <div class="card border-0 shadow-sm rounded-4">
                                    <div class="card-header bg-primary bg-opacity-10 border-bottom">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Application Details</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <dl>
                                                    <dt>Applied Campus</dt>
                                                    <dd><?php echo htmlspecialchars($profile['applied_campus'] ?? 'Not provided'); ?></dd>
                                                </dl>
                                            </div>
                                            <div class="col-md-6">
                                                <dl>
                                                    <dt>Application Date</dt>
                                                    <dd><?php echo date('F j, Y'); ?></dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submission Section -->
                        <div class="submission-section mt-5 pt-4 border-top">
                            <div class="alert alert-warning border-0 rounded-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-exclamation-triangle fs-4 me-3 mt-1"></i>
                                    <div>
                                        <h5 class="mb-3">Final Submission Confirmation</h5>
                                        <ul class="mb-0">
                                            <li>Ensure all information is accurate and complete</li>
                                            <li>You won't be able to edit after submission</li>
                                            <li>Application will be processed within 72 hours</li>
                                            <li>Roll number slip will be available 7 days before test date</li>
                                            <li>Check your application status regularly after submission</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-4">
                                <form method="POST">
                                    <div class="d-flex flex-column flex-md-row gap-3 gap-md-4 justify-content-center">
                                        <button type="submit" class="btn btn-success btn-lg px-4 py-3">
                                            <i class="fas fa-paper-plane me-2"></i>Submit Application
                                        </button>
                                        <a href="personal_info.php" class="btn btn-outline-primary btn-lg px-4 py-3">
                                            <i class="fas fa-edit me-2"></i>Edit Information
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }
    
    .step {
        position: relative;
        padding-left: 2.5rem;
        margin-bottom: 2rem;
    }
    
    .step-icon {
        position: absolute;
        left: 0;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #e9ecef;
        color: #6c757d;
    }
    
    .step.completed .step-icon {
        background: #198754;
        color: white;
    }
    
    .step.active .step-icon {
        background: #0d6efd;
        color: white;
    }
    
    .step-content {
        padding-bottom: 1rem;
        border-left: 2px solid #e9ecef;
        padding-left: 1.5rem;
    }
    
    .step:last-child .step-content {
        border-left: none;
    }
    
    .document-item {
        transition: all 0.2s ease;
    }
    
    .document-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }
</style>