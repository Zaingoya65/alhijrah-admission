<?php
session_start();
include '../includes/config.php';
include '../includes/session_auth.php';

$user_id = $_SESSION['id'];

// Get application status
$sql = "SELECT * FROM student_profiles WHERE user_id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    header("Location: personal_info.php");
    exit();
}

$application = $result->fetch_assoc();
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
                            <h4 class="mb-1">Application Status</h4>
                            <p class="mb-0 text-muted">Track your admission application progress</p>
                        </div>
                        <span class="badge bg-<?= 
                            $application['application_status'] == 'Approved' ? 'success' : 
                            ($application['application_status'] == 'Rejected' ? 'danger' : 'warning') 
                        ?>">
                            <?= $application['application_status'] ?? 'Pending' ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Status Summary -->
                    <div class="card border-0 shadow-sm mb-4 rounded-4 border-start border-5 border-<?= 
                        $application['application_status'] == 'Approved' ? 'success' : 
                        ($application['application_status'] == 'Rejected' ? 'danger' : 'warning') 
                    ?>">
                        <div class="card-body text-center py-4">
                            <div class="status-icon mb-3">
                                <?php if ($application['application_status'] == 'Approved'): ?>
                                    <div class="icon-circle bg-success bg-opacity-10 text-success d-inline-flex p-3 rounded-circle">
                                        <i class="fas fa-check-circle fa-3x"></i>
                                    </div>
                                <?php elseif ($application['application_status'] == 'Rejected'): ?>
                                    <div class="icon-circle bg-danger bg-opacity-10 text-danger d-inline-flex p-3 rounded-circle">
                                        <i class="fas fa-times-circle fa-3x"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="icon-circle bg-warning bg-opacity-10 text-warning d-inline-flex p-3 rounded-circle">
                                        <i class="fas fa-clock fa-3x"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="mb-2 text-<?= 
                                $application['application_status'] == 'Approved' ? 'success' : 
                                ($application['application_status'] == 'Rejected' ? 'danger' : 'warning') 
                            ?>">
                                <?= 
                                    $application['application_status'] == 'Approved' ? 'Application Approved' : 
                                    ($application['application_status'] == 'Rejected' ? 'Application Not Approved' : 'Application Pending')
                                ?>
                            </h3>
                            
                            <p class="lead mb-4">
                                <span class="fw-bold"><?= htmlspecialchars($application['full_name'] ?? 'Applicant') ?></span> - 
                                <span class="text-primary"><?= htmlspecialchars($application['applied_campus'] ?? 'No Campus Selected') ?></span>
                            </p>
                            
                            <div class="d-flex justify-content-center gap-3 mb-3 flex-wrap">
                                <div class="text-center px-3 py-2 bg-light rounded-3">
                                    <small class="text-muted d-block">Application ID</small>
                                    <strong class="text-primary"><?= $application['user_id'] ?></strong>
                                </div>
                                <div class="text-center px-3 py-2 bg-light rounded-3">
                                    <small class="text-muted d-block">Application Year</small>
                                    <strong><?= $application['application_year'] ?></strong>
                                </div>
                                <div class="text-center px-3 py-2 bg-light rounded-3">
                                    <small class="text-muted d-block">Submitted On</small>
                                    <strong><?= date('M j, Y', strtotime($application['submitted_at'])) ?></strong>
                                </div>
                                <?php if ($application['application_status']): ?>
                                <div class="text-center px-3 py-2 bg-light rounded-3">
                                    <small class="text-muted d-block">Status Updated</small>
                                    <strong><?= date('M j, Y', strtotime($application['reviewed_at'] ?? date('Y-m-d'))) ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Section -->
                    <div class="card border-0 shadow-sm mb-4 rounded-4">
                        <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Documents</h5>
                            <small class="text-muted">Download your application documents</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 h-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0"><i class="fas fa-file-pdf text-danger me-2"></i>Application Form</h6>
                                                <small class="text-muted">PDF Document</small>
                                            </div>
                                            <span class="badge bg-success">Available</span>
                                        </div>
                                        <div class="mt-auto">
                                            <a href="download_application_form.php?user_id=<?= $user_id ?>" 
                                               class="btn btn-outline-primary w-100 rounded-3">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 h-100 d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-0"><i class="fas fa-ticket-alt text-info me-2"></i>Roll No Slip</h6>
                                                <small class="text-muted">Test Admission Ticket</small>
                                            </div>
                                            <span class="badge bg-<?= $application['application_status'] == 'Approved' ? 'info' : 'secondary' ?>">
                                                <?= $application['application_status'] == 'Approved' ? 'Available Soon' : 'Pending' ?>
                                            </span>
                                        </div>
                                        <div class="mt-auto">
                                            <?php if ($application['application_status'] == 'Approved'): ?>
                                                <button class="btn btn-primary w-100 rounded-3" disabled>
                                                    <i class="fas fa-clock me-2"></i>Will be available soon
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary w-100 rounded-3" disabled>
                                                    <i class="fas fa-clock me-2"></i>Will be available 7 days before test
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Details -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-primary bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status Information</h5>
                            <?php if ($application['application_status']): ?>
                                <span class="badge bg-<?= 
                                    $application['application_status'] == 'Approved' ? 'success' : 
                                    ($application['application_status'] == 'Rejected' ? 'danger' : 'info') 
                                ?>">
                                    <?= $application['application_status'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if ($application['application_status'] == 'Approved'): ?>
                                <div class="alert alert-success border-0 rounded-4">
                                    <div class="d-flex">
                                        <i class="fas fa-check-circle fa-2x text-success me-3 mt-1"></i>
                                        <div>
                                            <h5 class="alert-heading">Congratulations!</h5>
                                            <p>Your application has been approved for the admission process.</p>
                                            <hr>
                                            <p class="mb-0">You will receive your roll number slip via email and portal, when it becomes available.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-calendar-check text-success me-2"></i>
                                            <span>Next Steps:</span>
                                        </div>
                                        <span class="badge bg-success rounded-pill">Prepare for admission test</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-envelope text-primary me-2"></i>
                                            <span>Communication:</span>
                                        </div>
                                        <span>Check your email regularly</span>
                                    </div>
                                </div>
                                
                            <?php elseif ($application['application_status'] == 'Rejected'): ?>
                                <div class="alert alert-danger border-0 rounded-4">
                                    <div class="d-flex">
                                        <i class="fas fa-times-circle fa-2x text-danger me-3 mt-1"></i>
                                        <div>
                                            <h5 class="alert-heading">Application Not Approved</h5>
                                            <p>We regret to inform you that your application has not been approved for this admission cycle.</p>
                                            <?php if (!empty($application['rejection_reason'])): ?>
                                                <hr>
                                                <p class="mb-0"><strong>Reason:</strong> <?= htmlspecialchars($application['rejection_reason']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                            <?php else: ?>
                                <div class="alert alert-info border-0 rounded-4">
                                    <div class="d-flex">
                                        <i class="fas fa-clock fa-2x text-info me-3 mt-1"></i>
                                        <div>
                                            <h5 class="alert-heading">Application Under Review</h5>
                                            <p>Your application is currently being processed by our admissions team.</p>
                                            <hr>
                                            <p class="mb-0">The review typically takes 24-72 hours.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-hourglass-half text-warning me-2"></i>
                                            <span>Current Status:</span>
                                        </div>
                                        <span class="badge bg-warning rounded-pill">In Review</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-ticket-alt text-info me-2"></i>
                                            <span>Roll No Slip:</span>
                                        </div>
                                        <span>Will be available 7 days before test</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Help Section -->
                    <div class="card border-0 shadow-sm rounded-4 mt-4 bg-primary bg-opacity-5">
                        <div class="card-body p-4">
                            <div class="d-md-flex align-items-center">
                                <div class="mb-3 mb-md-0 me-md-4">
                                    <h5 class="mb-1"><i class="fas fa-headset me-2"></i>Need Assistance?</h5>
                                    <p class="mb-0 text-muted">Our support team is ready to help you with any questions.</p>
                                </div>
                                <div class="ms-md-auto">
                                    <a href="../support.php" class="btn btn-primary me-2 rounded-3">
                                        <i class="fas fa-envelope me-1"></i> Contact Support
                                    </a>
                                    <a href="../faq.php" class="btn btn-outline-primary rounded-3">
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

<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }
    
    .icon-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
    }
    
    .list-group-item {
        border-left: 0;
        border-right: 0;
        padding: 1rem 0;
    }
    
    .list-group-item:first-child {
        border-top: 0;
        padding-top: 0;
    }
    
    .list-group-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any necessary JavaScript here
    // For example, tooltips or additional interactivity
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include'../includes/stud_footer.php'; ?>