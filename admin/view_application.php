<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}
if (!isset($_GET['id'])) {
    header("Location: applications.php");
    exit();
}
$application_id = (int)$_GET['id'];
$page_title = "Application Details";
include 'includes/admin_header.php';

$sql = "SELECT sp.*, u.b_form, u.email
        FROM student_profiles sp
        JOIN registered_users u ON sp.user_id = u.id
        WHERE sp.user_id = $application_id";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Application not found.</div>';
    include 'includes/admin_footer.php';
    exit();
}
$application = $result->fetch_assoc();

$documents = [];
$doc_query = $conn->query("SELECT * FROM documents WHERE user_id = $application_id");
if ($doc_query->num_rows > 0) {
    $documents = $doc_query->fetch_assoc();
}

function renderDocument($title, $file, $user_id) {
    echo '<div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 border-light shadow-sm">
                <div class="card-body text-center">
                    <h6 class="mb-3">' . $title . '</h6>';
    if (!empty($file)) {
        echo '<a href="../uploads/' . $user_id . '/' . $file . '" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye me-1"></i> View Document
              </a>';
    } else {
        echo '<span class="badge bg-danger">Not Submitted</span>';
    }
    echo '  </div>
            </div>
        </div>';
}
?>

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Application #<?= $application['user_id'] ?></h5>
            <span class="badge bg-<?= 
                $application['application_status'] === 'Approved' ? 'success' : 
                ($application['application_status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                <?= $application['application_status'] ?>
            </span>
        </div>

        <div class="card-body">
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs mb-3" id="sectionTabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#personal">Personal Info</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#guardian">Guardian Info</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#contact">Contact Info</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#education">Education</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#documents">Documents</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#actions">Actions</a></li>
            </ul>

            <div class="tab-content">
                <!-- Personal Info -->
                <div class="tab-pane fade show active" id="personal">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <?php if (!empty($application['profile_image'])): ?>
                                <img src="../uploads/<?= $application['user_id'] ?>/<?= $application['profile_image'] ?>" class="img-thumbnail" style="max-height: 250px;">
                            <?php else: ?>
                                <div class="alert alert-warning">No photo uploaded.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <table class="table table-sm table-bordered">
                                <tr><th>Full Name</th><td><?= htmlspecialchars($application['full_name']) ?></td></tr>
                                <tr><th>B-form</th><td><?= $application['b_form'] ?></td></tr>
                                <tr><th>Date of Birth</th><td><?= date('d M Y', strtotime($application['date_of_birth'])) ?></td></tr>
                                <tr><th>Domicile</th><td><?= $application['domicile_province'] ?> (<?= $application['domicile_district'] ?>)</td></tr>
                                <tr><th>Applied Campus</th><td><?= $application['applied_campus'] ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Guardian Info -->
                <div class="tab-pane fade" id="guardian">
                    <table class="table table-sm table-bordered">
                        <tr><th>Guardian Name</th><td><?= htmlspecialchars($application['guardian_name']) ?></td></tr>
                        <tr><th>Guardian CNIC</th><td><?= $application['guardian_cnic'] ?></td></tr>
                        <tr><th>Occupation</th><td><?= htmlspecialchars($application['guardian_occupation']) ?></td></tr>
                    </table>
                </div>

                <!-- Contact Info -->
                <div class="tab-pane fade" id="contact">
                    <table class="table table-sm table-bordered">
                        <tr><th>Email</th><td><?= $application['email'] ?></td></tr>
                        <tr><th>Phone Number</th><td><?= $application['phone_number'] ?></td></tr>
                        <tr><th>Current Address</th><td><?= nl2br(htmlspecialchars($application['current_address'])) ?></td></tr>
                        <tr><th>Permanent Address</th><td><?= nl2br(htmlspecialchars($application['permanent_address'])) ?></td></tr>
                        <tr><th>Emergency Contact</th>
                            <td><?= $application['emergency_contact'] ?> (<?= $application['emergency_relation'] ?>)<br><?= $application['emergency_phone'] ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Education -->
                <div class="tab-pane fade" id="education">
                    <table class="table table-sm table-bordered">
                        <tr><th>Last School Name</th><td><?= htmlspecialchars($application['last_school_name']) ?></td></tr>
                        <tr><th>School Type</th><td><?= $application['last_school_type'] ?></td></tr>
                        <tr><th>Last Class Attended</th><td><?= $application['last_school_class'] ?></td></tr>
                        <tr><th>Last Exam Result</th><td><?= $application['last_school_result'] ?>%</td></tr>
                        <tr><th>Application Submitted</th><td><?= date('d M Y H:i', strtotime($application['submitted_at'])) ?></td></tr>
                        <tr><th>Application Year</th><td><?= $application['application_year'] ?></td></tr>
                    </table>
                </div>

                <!-- Documents -->
                <div class="tab-pane fade" id="documents">
                    <div class="row mt-3">
                        <?php
                        if (!empty($documents)) {
                            renderDocument("Student B-form", $documents['b_form_path'], $application['user_id']);
                            renderDocument("School Result", $documents['school_result_path'], $application['user_id']);
                            renderDocument("Guardian CNIC", $documents['guardian_cnic_path'], $application['user_id']);
                            renderDocument("Income Certificate", $documents['income_certificate_path'], $application['user_id']);
                            renderDocument("Domicile Certificate", $documents['domicile_certificate_path'], $application['user_id']);
                            renderDocument("Paid Challan Copy", $documents['challan_copy_path'], $application['user_id']);
                        } else {
                            echo '<div class="alert alert-warning">No documents submitted.</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="tab-pane fade" id="actions">
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="applications.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <div>
                            <?php if ($application['application_status'] === 'Pending'): ?>
                                <a href="process_application.php?id=<?= $application['user_id'] ?>&action=approve" class="btn btn-success me-2">
                                    <i class="fas fa-check me-1"></i> Approve
                                </a>
                                <a href="process_application.php?id=<?= $application['user_id'] ?>&action=reject" class="btn btn-danger me-2">
                                    <i class="fas fa-times me-1"></i> Reject
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div> <!-- .tab-content -->
        </div>
    </div>
</div>


<?php include 'includes/admin_footer.php'; ?>
