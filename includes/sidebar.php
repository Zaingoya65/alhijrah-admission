<?php
include '../includes/config.php';
include '../includes/session_auth.php';

// Get student ID from session
$id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

// Default values
$user_name = 'Guest';
$user_image = '../uploads/default.png';

//

// Check if application is submitted
$is_submitted = isset($_SESSION['submitted']) && $_SESSION['submitted'] == 1;
$campus = isset($_SESSION['campus']) ? $_SESSION['campus'] : null;

// Check if deadline has passed
$deadline_passed = false;
if ($campus && isset($DEADLINES[$campus])) {
    $deadline = new DateTime($DEADLINES[$campus]);
    $today = new DateTime();
    $deadline_passed = $today > $deadline;
}

// Determine if editing is allowed
$editing_allowed = !$is_submitted && !$deadline_passed;




if ($id) {
    // Query to get full name and profile image from student_profiles
    $stmt = $conn->prepare("SELECT full_name, profile_image FROM student_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $user_name = $row['full_name'] ? htmlspecialchars($row['full_name']) : 'Student';
        
        // Check if profile image exists and build correct path
        if (!empty($row['profile_image'])) {
            $upload_dir = "../uploads/$id/";
            $user_image = $upload_dir . $row['profile_image'];
            
            // Verify the file exists
            if (!file_exists($user_image)) {
                $user_image = '../uploads/default.png';
            }
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
function navActive($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active text-primary fw-bold' : 'text-dark';
}
?>

<div class="col-12 col-md-3 mb-4">
    <!-- Mobile Toggle Button (Hidden on Desktop) -->
    <button class="btn btn-primary w-100 d-md-none mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse">
        <i class="fas fa-bars me-2"></i> Menu
    </button>

    <!-- Sidebar Content (Collapsible on Mobile) -->
    <div class="card border-0 shadow-sm rounded-3 collapse d-md-block" id="sidebarCollapse">
        <div class="card-body text-center p-3 p-md-4">
            <!-- Profile Image -->
            <div class="position-relative d-inline-block mb-3">
                <img src="<?php echo $user_image; ?>" 
                     class="rounded-circle border border-3 border-primary shadow-sm" 
                     width="80" height="80" 
                     alt="Profile Image"
                     style="object-fit: cover;"
                     onerror="this.src='../uploads/default.png'">
            </div>

            <!-- Name -->
            <h5 class="fw-semibold text-dark mb-3"><?php echo $user_name; ?></h5>
            <hr class="my-3">

            <!-- Sidebar Navigation -->
            <ul class="nav flex-column text-start small">
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('dashboard.php') ?>" href="dashboard.php">
                        <i class="fas fa-home me-2"></i> Overview
                    </a>
                </li>
                
                <?php if ($editing_allowed || $currentPage === 'personal_info.php'): ?>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('personal_info.php') ?>" href="personal_info.php">
                        <i class="fas fa-user me-2"></i> Profile Info
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($editing_allowed || $currentPage === 'address.php'): ?>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('address.php') ?>" href="address.php">
                        <i class="fas fa-map-marker-alt me-2"></i> Contact Details
                    </a>
                </li>
                <?php endif; ?>
                
                <?php if ($editing_allowed || $currentPage === 'education_info.php'): ?>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('education_info.php') ?>" href="education_info.php">
                        <i class="fas fa-graduation-cap me-2"></i> Academic Info
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('payment.php') ?>" href="payment.php">
                        <i class="fas fa-credit-card me-2"></i> Payment
                    </a>
                </li>
                
                <?php if ($editing_allowed || $currentPage === 'documents.php'): ?>
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('documents.php') ?>" href="documents.php">
                        <i class="fas fa-file-upload me-2"></i> Upload Documents
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('review.php') ?>" href="review.php">
                        <i class="fas fa-file-upload me-2"></i> Review & Submit
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <a class="nav-link d-flex align-items-center <?= navActive('status.php') ?>" href="status.php">
                        <i class="fas fa-info-circle me-2"></i> Application Status
                    </a>
                </li>
                
                <li class="nav-item mt-3 pt-2 border-top">
                    <a class="nav-link d-flex align-items-center text-danger" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<style>
    /* Custom styles for better mobile appearance */
    @media (max-width: 767.98px) {
        #sidebarCollapse {
            background-color: white;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            margin-bottom: 1rem;
        }
        .card-body {
            padding: 1.25rem;
        }
        .nav-link {
            padding: 0.5rem 1rem;
        }
    }
</style>