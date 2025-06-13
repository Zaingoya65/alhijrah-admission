<?php
session_start();
include '../includes/config.php';
include '../includes/session_auth.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$id = (int)$_SESSION['id'];

$application_status = 'Not Started';
$applied_campus = '';
$application_year = '';
$progress = 0;

// Get profile data with prepared statement
$stmt = $conn->prepare("SELECT 
    sp.*,
    sp.applied_campus as campus
FROM student_profiles sp
JOIN registered_users ru ON sp.user_id = ru.id
WHERE sp.user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $profile = $result->fetch_assoc();

    $application_status = strtolower($profile['application_status'] ?? 'pending');
    $applied_campus = htmlspecialchars($profile['applied_campus'] ?? $profile['registered_campus'] ?? '');
    $application_year = htmlspecialchars($profile['application_year'] ?? date('Y'));
    $progress = (int)($profile['progress'] ?? 0);
}

$stmt->close();

// Get deadlines from config

include '../includes/stud_header.php';
?>

<div class="container-fluid mt-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Welcome Header -->
                <div class="card-header bg-primary bg-opacity-10 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Welcome to Al-Hijrah Trust Admission Portal</h4>
                            <p class="mb-0 text-muted">Manage your admission application</p>
                        </div>
                        <?php if ($application_status ): ?>
                           
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
                   <?php if ($application_status === 'pending'): ?>
                        <!-- New Application Section -->
                        <div class="alert alert-info border-0 rounded-4">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h5><i class="fas fa-rocket me-2"></i>Start Your Application</h5>
                                    <p class="mb-3">Begin your admission process to Al-Hijrah Trust. Complete your profile to get started.</p>
                                    <a href="personal_info.php" class="btn btn-primary px-4">
                                        <i class="fas fa-play me-2"></i>Start Application
                                    </a>
                                </div>
                               
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Application Summary -->
                        <div class="row g-4">

                            <!-- Status Card -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-body">
                                        <h6 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Application Details</h6>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Campus</small>
                                                    <strong><?= $applied_campus ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Year</small>
                                                    <strong><?= $application_year ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Deadline</small>
                                                    <strong><?= isset($deadlines[$applied_campus]) ? date('M d, Y', strtotime($deadlines[$applied_campus])) : 'N/A' ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 bg-light rounded-3">
                                                    <small class="text-muted d-block">Status</small>
                                                    <strong class="text-<?= 
                                                        $application_status === 'Approved' ? 'success' : 
                                                        ($application_status === 'Rejected' ? 'danger' : 'warning'); ?>">
                                                        <?= $application_status ?>
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Resources Section -->
                        <div class="row mt-4 g-4">
                            <!-- Required Documents -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Required Documents</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex flex-column">
                                            <div class="mb-3 d-flex align-items-center">
                                               <div class="text-primary  p-2 me-3">
                                                    <i class="fas fa-id-card"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">Identification</small>
                                                    <strong>B-Form</strong>
                                                </div>   
                                            </div>

                                                 <div class="mb-3 d-flex align-items-center">
                                               <div class="text-primary  p-2 me-3">
                                                    <i class="fas fa-id-card"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">Residential</small>
                                                    <strong>Domicile/Local</strong>
                                                </div>   
                                            </div>


                                            <div class="mb-3 d-flex align-items-center">
                                               <div class="text-primary  p-2 me-3">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">Photo</small>
                                                    <strong>Passport Size</strong>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <div class="text-primary  p-2 me-3">
                                                    <i class="fas fa-certificate"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">Academic</small>
                                                    <strong>Previous School Result</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0"><i class="fas fa-bolt me-2 text-primary"></i>Quick Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <a href="documents.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 rounded-3">
                                                    <i class="fas fa-upload mb-2"></i>
                                                    <small>Upload Docs</small>
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="payment.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 rounded-3">
                                                    <i class="fas fa-credit-card mb-2"></i>
                                                    <small>Make Payment</small>
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="status.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 rounded-3">
                                                    <i class="fas fa-search mb-2"></i>
                                                    <small>Check Status</small>
                                                </a>
                                            </div>
                                            <div class="col-6">
                                                <a href="../support.php" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3 rounded-3">
                                                    <i class="fas fa-question mb-2"></i>
                                                    <small>Get Help</small>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>



                     <!-- Action Cards -->
                        <div class="row mt-4 g-4">
                            <!-- Next Steps -->
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100 rounded-4">
                                    <div class="card-header bg-white border-bottom">
                                        <h6 class="mb-0"><i class="fas fa-arrow-right me-2 text-primary"></i>Next Steps</h6>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-unstyled mb-0">
                                            <li class="mb-2 d-flex">
                                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-circle me-2" style="width: 24px; height: 24px;">1</span>
                                                <span>Track your application status regularly</span>
                                            </li>
                                            <li class="mb-2 d-flex">
                                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-circle me-2" style="width: 24px; height: 24px;">2</span>
                                                <span>Download admission slip once approved</span>
                                            </li>
                                            <li class="d-flex">
                                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-circle me-2" style="width: 24px; height: 24px;">3</span>
                                                <span>Complete registration at campus</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>       
                        </div>

                    <!-- Help Section (Visible for all users) -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-5">
                                <div class="card-body p-4">
                                    <div class="d-md-flex align-items-center">
                                        <div class="mb-3 mb-md-0 me-md-4">
                                            <h5 class="mb-1"><i class="fas fa-headset me-2"></i>Need Assistance?</h5>
                                            <p class="mb-0 text-muted">Our support team is ready to help you with any questions.</p>
                                        </div>
                                        <div class="ms-md-auto">
                                            <a href="../support.php" class="btn btn-primary me-2">
                                                <i class="fas fa-envelope me-1"></i> Contact Support
                                            </a>
                                            <a href="../faq.php" class="btn btn-outline-primary">
                                                <i class="fas fa-question-circle me-1"></i> View FAQs
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include'../includes/stud_footer.php'; ?>
<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #3498db;
        --accent-color: #e74c3c;
        --light-color: #f8f9fa;
        --dark-color: #343a40;
    }
    
    .rounded-4 {
        border-radius: 1rem !important;
    }
    
    .timeline {
        position: relative;
        padding-left: 1.5rem;
    }
    
    .timeline-item {
        position: relative;
        padding-bottom: 1.5rem;
    }
    
    .timeline-badge {
        position: absolute;
        left: -0.5rem;
        top: 0;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
    }
    
    .timeline-content {
        padding-left: 1rem;
    }
    
    .progress-bar-striped {
        background-image: linear-gradient(
            45deg,
            rgba(255, 255, 255, 0.15) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, 0.15) 50%,
            rgba(255, 255, 255, 0.15) 75%,
            transparent 75%,
            transparent
        );
        background-size: 1rem 1rem;
    }
</style>
