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

<div class="container py-4">
   

    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <div class="col-md-9">
            <!-- Status Summary Card -->
            <div class="card shadow-sm mb-4 border-<?= 
                $application['application_status'] == 'Approved' ? 'success' : 
                ($application['application_status'] == 'Rejected' ? 'danger' : 'warning') 
            ?>">
                <div class="card-body text-center py-4">
                    <div class="status-icon mb-3">
                        <?php if ($application['application_status'] == 'Approved'): ?>
                            <div class="icon-circle bg-success bg-opacity-10 text-success">
                                <i class="fas fa-check-circle fa-3x"></i>
                            </div>
                        <?php elseif ($application['application_status'] == 'Rejected'): ?>
                            <div class="icon-circle bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-times-circle fa-3x"></i>
                            </div>
                        <?php else: ?>
                            <div class="icon-circle bg-warning bg-opacity-10 text-warning">
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
                        <div class="text-center px-3 py-2 bg-light rounded">
                            <small class="text-muted d-block">Application ID</small>
                            <strong class="text-primary"><?= $application['user_id'] ?></strong>
                        </div>
                        <div class="text-center px-3 py-2 bg-light rounded">
                            <small class="text-muted d-block">Application Year</small>
                            <strong><?= $application['application_year'] ?></strong>
                        </div>
                        <div class="text-center px-3 py-2 bg-light rounded">
                            <small class="text-muted d-block">Submitted On</small>
                            <strong><?= date('M j, Y', strtotime($application['submitted_at'])) ?></strong>
                        </div>
                        <?php if ($application['application_status']): ?>
                        <div class="text-center px-3 py-2 bg-light rounded">
                            <small class="text-muted d-block">Status Updated</small>
                            <strong><?= date('M j, Y', strtotime($application['reviewed_at'] ?? date('Y-m-d'))) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Documents</h5>
                    <small class="text-muted">Download your application documents</small>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0"><i class="fas fa-file-pdf text-danger me-2"></i>Application Form</h6>
                                        <small class="text-muted">PDF Document</small>
                                    </div>
                                    <span class="badge bg-success">Available</span>
                                </div>
                                <div class="mt-auto">
                                    <a href="download_application_form.php?user_id=<?= $user_id ?>" 
                                       class="btn btn-outline-primary w-100">
                                        <i class="fas fa-download me-2"></i>Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="p-3 border rounded h-100 d-flex flex-column">
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
                                        <button class="btn btn-primary w-100" disabled>
                                            <i class="fas fa-clock me-2"></i>Will be available soon
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary w-100" disabled>
                                            <i class="fas fa-clock me-2"></i>Will be available 7 days before test
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Details Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
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
                        <div class="alert alert-success bg-opacity-10 border border-success border-start-0 border-end-0 border-5">
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
                        <div class="alert alert-danger bg-opacity-10 border border-danger border-start-0 border-end-0 border-5">
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
                        <div class="alert alert-info bg-opacity-10 border border-info border-start-0 border-end-0 border-5">
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
        </div>
    </div>
</div>

<?php include'../includes/stud_footer.php'; ?>
<style>
/* Timeline Styles */
.timeline-steps {
    display: flex;
    justify-content: space-between;
    padding: 0 2rem;
}

.timeline-step {
    flex: 1;
    position: relative;
    text-align: center;
}

.timeline-step:not(:last-child):after {
    content: '';
    position: absolute;
    top: 1.5rem;
    left: 60%;
    right: -40%;
    height: 2px;
    background-color: #e9ecef;
    z-index: 1;
}

.timeline-step.completed:after {
    background-color: var(--bs-success);
}

.timeline-step.active:after {
    background-color: var(--bs-primary);
}

.timeline-icon {
    width: 3rem;
    height: 3rem;
    margin: 0 auto 0.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    position: relative;
    z-index: 2;
}

.timeline-label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.timeline-date {
    font-size: 0.75rem;
    color: #6c757d;
}

.timeline-step.completed .timeline-label,
.timeline-step.completed .timeline-date {
    color: var(--bs-success);
}

.timeline-step.active .timeline-label {
    color: var(--bs-primary);
    font-weight: 600;
}

/* Status Icon */
.icon-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* Document Cards */
.document-card {
    transition: all 0.3s ease;
}

.document-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

/* Alert Customization */
.alert {
    border-left: none;
    border-right: none;
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