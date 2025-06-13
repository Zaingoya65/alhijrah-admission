<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Admin Settings";
include 'includes/admin_header.php';

// Form handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_ziarat_dates'])) {
        $ziarat_start = $conn->real_escape_string($_POST['ziarat_start']);
        $ziarat_end = $conn->real_escape_string($_POST['ziarat_end']);

        $sql = "UPDATE settings SET ziarat_start = '$ziarat_start', ziarat_end = '$ziarat_end' WHERE id = 1";
        $success = $conn->query($sql) ? "Ziarat dates updated." : "Error: " . $conn->error;
    }

    if (isset($_POST['update_dgkhan_dates'])) {
        $dgkhan_start = $conn->real_escape_string($_POST['dgkhan_start']);
        $dgkhan_end = $conn->real_escape_string($_POST['dgkhan_end']);

        $sql = "UPDATE settings SET dgkhan_start = '$dgkhan_start', dgkhan_end = '$dgkhan_end' WHERE id = 1";
        $success = $conn->query($sql) ? "D.G. Khan dates updated." : "Error: " . $conn->error;
    }

    if (isset($_POST['update_general_settings'])) {
        $site_name = $conn->real_escape_string($_POST['site_name']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

        $sql = "UPDATE settings SET site_name = '$site_name', maintenance_mode = '$maintenance_mode' WHERE id = 1";
        $success = $conn->query($sql) ? "General settings updated." : "Error: " . $conn->error;
    }

    if (isset($_POST['update_contact_info'])) {
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $address = $conn->real_escape_string($_POST['address']);

        $sql = "UPDATE settings SET contact_email = '$email', contact_phone = '$phone', contact_address = '$address' WHERE id = 1";
        $success = $conn->query($sql) ? "Contact info updated." : "Error: " . $conn->error;
    }
}

// Get settings
$settings = [];
$result = $conn->query("SELECT * FROM settings WHERE id = 1");
if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
} else {
    $conn->query("INSERT INTO settings (id) VALUES (1)");
    $settings = [];
}
?>

<div class="container-fluid px-4 py-4">
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row gy-4">
        <!-- Ziarat Campus -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light fw-bold">Ziarat Campus Dates</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="ziarat_start" class="form-control" value="<?= $settings['ziarat_start'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="ziarat_end" class="form-control" value="<?= $settings['ziarat_end'] ?? ''; ?>" required>
                        </div>
                        <button type="submit" name="update_ziarat_dates" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- DG Khan Campus -->
        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light fw-bold">D.G. Khan Campus Dates</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="dgkhan_start" class="form-control" value="<?= $settings['dgkhan_start'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="dgkhan_end" class="form-control" value="<?= $settings['dgkhan_end'] ?? ''; ?>" required>
                        </div>
                        <button type="submit" name="update_dgkhan_dates" class="btn btn-primary">Save</button>
                    </form>
                </div>
            </div>
        </div>

        
    </div>
</div>


<?php include 'includes/admin_footer.php'; ?>
