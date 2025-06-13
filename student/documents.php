<?php
session_start();
require "../includes/config.php";
require "../includes/session_auth.php";

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$user_id = (int)$_SESSION["id"];
$max_file_size = 500 * 1024; // 500KB
$allowed_types = ["application/pdf"];
$upload_dir = __DIR__ . "/../uploads/$user_id/";
$upload_url = "../uploads/$user_id/";

$current_year = date("Y");
$editing_locked = false;

// Check if profile exists and submission status
$stmt = $conn->prepare("SELECT is_submitted FROM student_profiles WHERE user_id = ? AND application_year = ?");
$stmt->bind_param("is", $user_id, $current_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    $editing_locked = (bool)$profile["is_submitted"];
}
$stmt->close();

// Ensure secure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    file_put_contents($upload_dir . "index.php", "<?php // Silence is golden");
}

// Document configuration
$documents = [
    "b_form" => [
        "label" => "Student B-form",
        "description" => "Scanned copy of student's B-form or CNIC",
        "required" => true
    ],
    "school_result" => [
        "label" => "Last School Result",
        "description" => "Most recent academic transcript or certificate",
        "required" => true
    ],
    "guardian_cnic" => [
        "label" => "Guardian CNIC",
        "description" => "Scanned copy of guardian's CNIC/NICOP",
        "required" => true
    ],
    "income_certificate" => [
        "label" => "Income Certificate",
        "description" => "Proof of family income (if applicable)",
        "required" => false
    ],
    "domicile_certificate" => [
        "label" => "Domicile Certificate",
        "description" => "Current domicile certificate",
        "required" => true
    ],
    "challan_copy" => [
        "label" => "Paid Challan Copy",
        "description" => "Proof of application fee payment",
        "required" => true
    ]
];

$success = "";
$errors = [];

// Fetch student's document records
$stmt = $conn->prepare("SELECT * FROM documents WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$doc_paths = $result->num_rows > 0 ? $result->fetch_assoc() : [];
$stmt->close();

// Handle document deletion
if (isset($_GET["delete"]) && isset($documents[$_GET["delete"]])) {
    if ($editing_locked) {
        $_SESSION['error'] = "Editing is locked as application has been submitted";
        header("Location: documents.php");
        exit();
    }

    $field = $_GET["delete"] . "_path";
    $stmt = $conn->prepare("SELECT $field FROM documents WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $filename = $row[$field];
        $filepath = $upload_dir . $filename;

        if ($filename && file_exists($filepath)) {
            unlink($filepath);
        }

        $stmt = $conn->prepare("UPDATE documents SET $field = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $_SESSION['success'] = "{$documents[$_GET["delete"]]['label']} deleted successfully";
        header("Location: documents.php");
        exit();
    }
}

// Handle file uploads
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$editing_locked) {
    foreach ($documents as $field => $doc) {
        if (!isset($_FILES[$field]) || $_FILES[$field]["error"] !== UPLOAD_ERR_OK) {
            continue;
        }

        $file = $_FILES[$field];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($file_info, $file["tmp_name"]);
        finfo_close($file_info);
        $size = $file["size"];

        // Validate file
        if (!in_array($mime, $allowed_types)) {
            $errors[$field] = "{$doc['label']} must be in PDF format";
            continue;
        }

        if ($size > $max_file_size) {
            $errors[$field] = "{$doc['label']} must not exceed 500KB";
            continue;
        }

        // Generate secure filename
        $safe_filename = "doc_" . bin2hex(random_bytes(8)) . ".pdf";
        $destination = $upload_dir . $safe_filename;

        if (move_uploaded_file($file["tmp_name"], $destination)) {
            // Delete old file if exists
            if (isset($doc_paths[$field . '_path']) && !empty($doc_paths[$field . '_path'])) {
                $old_file = $upload_dir . $doc_paths[$field . '_path'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }

            // Update database
            $check = $conn->prepare("SELECT id FROM documents WHERE user_id = ?");
            $check->bind_param("i", $user_id);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if ($exists) {
                $stmt = $conn->prepare("UPDATE documents SET {$field}_path = ? WHERE user_id = ?");
            } else {
                $stmt = $conn->prepare("INSERT INTO documents (user_id, {$field}_path) VALUES (?, ?)");
            }
            
            $stmt->bind_param("si", $safe_filename, $user_id);
            
            if ($stmt->execute()) {
                $success = "{$doc['label']} uploaded successfully";
                $doc_paths[$field . '_path'] = $safe_filename; // Update local paths
            } else {
                $errors[$field] = "Database error: " . $conn->error;
                unlink($destination); // Clean up failed upload
            }
            $stmt->close();
        } else {
            $errors[$field] = "Failed to upload {$doc['label']}";
        }
    }
}

include "../includes/stud_header.php";
?>

<div class="container-fluid mt-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <?php include "../includes/sidebar.php"; ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Card Header -->
                <div class="card-header bg-primary bg-opacity-10 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Document Upload</h4>
                            <p class="mb-0 text-muted">Submit required documents for your application</p>
                        </div>
                        <?php if ($editing_locked): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-lock me-1"></i> Submitted
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Status Alerts -->
                    <?php if ($editing_locked): ?>
                        <div class="alert alert-warning border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-lock me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Editing Locked</h5>
                                    <p class="mb-0">You have submitted your application. Document editing is now disabled.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Success!</h5>
                                    <p class="mb-0"><?= htmlspecialchars($_SESSION['success']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php elseif (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Error</h5>
                                    <p class="mb-0"><?= htmlspecialchars($_SESSION['error']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php elseif (!empty($success)): ?>
                        <div class="alert alert-success border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Success!</h5>
                                    <p class="mb-0"><?= htmlspecialchars($success) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!empty($errors)): ?>
                        <div class="alert alert-danger border-0 rounded-4 mb-4">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-3 fs-4"></i>
                                <div>
                                    <h5 class="mb-1">Error</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Document Upload Instructions -->
                    <div class="alert alert-info border-0 rounded-4 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading">Document Requirements</h5>
                                <ul class="mb-2">
                                    <li>All documents must be in <strong>PDF format</strong></li>
                                    <li>Maximum file size: <strong>500KB per document</strong></li>
                                    <li>Ensure documents are <strong>clear and readable</strong></li>
                                    <li>Files should not be password protected</li>
                                </ul>
                               
                            </div>
                        </div>
                    </div>

                    <!-- Document Upload Form -->
                     <h5 class="py-2">Please seclect all Required documents then upload</h5> 
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                       
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="25%">Document</th>
                                        <th width="15%">Status</th>
                                        <th width="35%">Upload</th>
                                        <th width="25%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $field => $doc): ?>
                                        <?php
                                            $filename = $doc_paths[$field . '_path'] ?? '';
                                            $file_exists = $filename && file_exists($upload_dir . $filename);
                                            $file_url = $file_exists ? ($upload_url . $filename) : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($doc['label']) ?></strong>
                                                <?php if ($doc['required']): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                                <div class="text-muted small mt-1">
                                                    <?= htmlspecialchars($doc['description']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($file_exists): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i> Uploaded
                                                    </span>
                                                    <div class="small text-muted mt-1">
                                                        <?= round(filesize($upload_dir . $filename) / 1024) ?>KB
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-<?= $doc['required'] ? 'danger' : 'warning' ?>">
                                                        <i class="fas fa-exclamation-circle me-1"></i>
                                                        <?= $doc['required'] ? 'Required' : 'Optional' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="input-group">
                                                    <input type="file" 
                                                           name="<?= htmlspecialchars($field) ?>" 
                                                           class="form-control form-control-sm" 
                                                           accept="application/pdf"
                                                           <?= $editing_locked ? 'disabled' : '' ?>
                                                           <?= $doc['required'] ? 'required' : '' ?>>
                                                </div>
                                                <div class="form-text small">PDF only, max 500KB</div>
                                            </td>
                                            <td>
                                                <?php if ($file_exists): ?>
                                                    <a href="<?= htmlspecialchars($file_url) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>
                                                    <a href="?delete=<?= htmlspecialchars($field) ?>" 
                                                       onclick="return confirm('Are you sure you want to delete this document?')" 
                                                       class="btn btn-sm btn-outline-danger ms-2"
                                                       <?= $editing_locked ? 'disabled' : '' ?>>
                                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">No file uploaded</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Form Actions -->
                       <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mt-4">
    <!-- Back Button - Full width on mobile, auto on desktop -->
    <a href="education_info.php" class="btn btn-outline-secondary px-3 px-md-4 rounded-4 w-100 w-md-auto order-1">
        <i class="fas fa-arrow-left me-2"></i> Back
    </a>
    
    <!-- Action Buttons - Stack on mobile, horizontal on desktop -->
    <div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto order-2">
        <!-- Upload Button -->
        <button type="submit" 
                class="btn btn-primary px-3 px-md-4 rounded-4 flex-grow-1"
                <?= $editing_locked ? 'disabled' : '' ?>>
            <i class="fas fa-upload me-2"></i> Upload Documents
        </button>
        
        <!-- Review Button -->
        <a href="review.php" class="btn btn-success px-3 px-md-4 rounded-4 flex-grow-1 mt-2 mt-md-0 ms-md-2">
            <i class="fas fa-check-circle me-2"></i> Review Application
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
    // Client-side validation
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            // Validate file type
            if (file.type !== 'application/pdf') {
                alert('Error: Only PDF files are allowed');
                this.value = '';
                return;
            }

            // Validate file size
            if (file.size > <?= $max_file_size ?>) {
                alert('Error: File size must be less than 500KB');
                this.value = '';
                return;
            }
        });
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
});
</script>

<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }
    
    .table th {
        white-space: nowrap;
    }
    
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
    
    .input-group-text {
        font-size: 0.875rem;
    }
</style>
