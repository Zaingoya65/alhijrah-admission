<?php
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.php"); // Redirect to login page
    exit();
}


// Get filter values from GET
$status_filter   = $_GET['status']   ?? '';
$year_filter     = $_GET['year']     ?? '';
$domicile_filter = $_GET['domicile'] ?? '';
$campus_filter   = $_GET['campus']   ?? '';
$date_filter     = $_GET['date']     ?? '';



include 'includes/admin_header.php';

// Get distinct years from the database for the year filter dropdown
$years_sql = "SELECT DISTINCT YEAR(submitted_at) as year FROM student_profiles ORDER BY year DESC";
$years_result = $conn->query($years_sql);
$years = [];

while ($year_row = $years_result->fetch_assoc()) {
    $years[] = $year_row['year'];
}

// Build the SQL query based on filters
$where = [];
if (!empty($status_filter)) {
    $where[] = "sp.application_status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($year_filter)) {
    $where[] = "YEAR(sp.submitted_at) = '" . $conn->real_escape_string($year_filter) . "'";
}

$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT sp.user_id, sp.full_name, sp.applied_campus, sp.application_status, 
               sp.submitted_at, u.b_form, u.email, sp.domicile_province
        FROM student_profiles sp
        JOIN registered_users u ON sp.user_id = u.id
        $where_clause
        ORDER BY sp.submitted_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Query Error: " . $conn->error . "<br>SQL: " . $sql);
}
$years_result = $conn->query($years_sql);
if (!$years_result) {
    die("Error fetching years: " . $conn->error);
}
$years = [];
while ($year_row = $years_result->fetch_assoc()) {
    $years[] = $year_row['year'];
}


$domicile_filter = $_GET['domicile_district'] ?? '';
$campus_filter = $_GET['applied_campus'] ?? '';
$date_filter = $_GET['application_year'] ?? '';

// Fetch filter options
$years_res = $conn->query("SELECT DISTINCT YEAR(submitted_at) as year FROM student_profiles ORDER BY year DESC");
$campuses_res = $conn->query("SELECT DISTINCT applied_campus FROM student_profiles ORDER BY applied_campus");
$domiciles_res = $conn->query("SELECT DISTINCT domicile_province FROM student_profiles ORDER BY domicile_province");

$years = [];
while ($row = $years_res->fetch_assoc()) $years[] = $row['year'];

$campuses = [];
while ($row = $campuses_res->fetch_assoc()) $campuses[] = $row['applied_campus'];

$domiciles = [];
while ($row = $domiciles_res->fetch_assoc()) $domiciles[] = $row['domicile_province'];

// Build WHERE clause
$where = [];
if ($status_filter)   $where[] = "sp.application_status = '" . $conn->real_escape_string($status_filter) . "'";
if ($year_filter)     $where[] = "YEAR(sp.submitted_at) = '" . $conn->real_escape_string($year_filter) . "'";
if ($campus_filter)   $where[] = "sp.applied_campus = '" . $conn->real_escape_string($campus_filter) . "'";
if ($domicile_filter) $where[] = "sp.domicile_province = '" . $conn->real_escape_string($domicile_filter) . "'";
if ($date_filter)     $where[] = "DATE(sp.submitted_at) = '" . $conn->real_escape_string($date_filter) . "'";

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT sp.user_id, sp.full_name, sp.applied_campus, sp.application_status, 
               sp.submitted_at, u.b_form, u.email, sp.domicile_province
        FROM student_profiles sp
        JOIN registered_users u ON sp.user_id = u.id
        $where_clause
        ORDER BY sp.submitted_at DESC";
$result = $conn->query($sql);
if (!$result) die("Query Error: " . $conn->error);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
    
        <div>
            <div class="btn-group me-2">
                <a href="applications.php" class="btn btn-sm btn-outline-secondary <?php echo empty($status_filter) ? 'active' : ''; ?>">All</a>
                <a href="applications.php?status=Pending" class="btn btn-sm btn-outline-warning <?php echo $status_filter == 'Pending' ? 'active' : ''; ?>">Pending</a>
                <a href="applications.php?status=Approved" class="btn btn-sm btn-outline-success <?php echo $status_filter == 'Approved' ? 'active' : ''; ?>">Approved</a>
                <a href="applications.php?status=Rejected" class="btn btn-sm btn-outline-danger <?php echo $status_filter == 'Rejected' ? 'active' : ''; ?>">Rejected</a>
            </div>
        </div>
        <button class="btn btn-outline-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection" aria-expanded="false" aria-controls="filterSection">
    <i class="fas fa-filter"></i> Show Filters
</button>

    </div>
    <div class="card-body">
<div class="collapse <?= ($year_filter || $campus_filter || $domicile_filter || $date_filter) ? 'show' : '' ?>" id="filterSection">

    <form method="get" class="row g-3 mb-3 border p-3 rounded bg-light">
        <!-- Year Filter -->
        <div class="col-md-3">
            <label class="form-label">Filter by Year</label>
            <select name="year" class="form-select">
                <option value="">All Years</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year_filter == $year ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Domicile Filter -->
        <div class="col-md-3">
            <label class="form-label">Filter by Domicile</label>
            <select name="domicile" class="form-select">
                <option value="">All Domiciles</option>
                <?php foreach ($domiciles as $dom): ?>
                    <option value="<?= $dom ?>" <?= $domicile_filter == $dom ? 'selected' : '' ?>><?= $dom ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Campus Filter -->
        <div class="col-md-3">
            <label class="form-label">Filter by Campus</label>
            <select name="campus" class="form-select">
                <option value="">All Campuses</option>
                <?php foreach ($campuses as $campus): ?>
                    <option value="<?= $campus ?>" <?= $campus_filter == $campus ? 'selected' : '' ?>><?= $campus ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Date Filter -->
        <div class="col-md-3">
            <label class="form-label">Filter by Date</label>
            <input type="date" name="date" class="form-control" value="<?= $date_filter ?>">
        </div>

        <!-- Buttons -->
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="applications.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>


        <div class="table-responsive">
            <table id="applicationsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>B-form</th>
                        <th>Email</th>
                        <th>Campus</th>
                        <th>Domicile</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo $row['b_form']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['applied_campus']; ?></td>
                        <td><?php echo $row['domicile_province']; ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $row['application_status'] == 'Approved' ? 'success' : 
                                     ($row['application_status'] == 'Rejected' ? 'danger' : 'warning'); ?>">
                                <?php echo $row['application_status']; ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($row['submitted_at'])); ?></td>
                        <td>
                            <a href="view_application.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="View">
                                 <i class="fas fa-eye"></i> 
                            </a>
                           <a href="../student/download_application_form.php?user_id=<?php echo $row['user_id']; ?>"

                              <i class="fas fa-download"></i>
                            </a>
                            
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<style>
    .dt-buttons .btn {
    margin-right: 0.5rem;
}
  /* Add vertical spacing below the DataTable controls */
    div.dataTables_wrapper .dt-buttons {
        margin-bottom: 1rem;
    }

    div.dataTables_wrapper .dataTables_length {
        margin-bottom: 1rem;
    }

    div.dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
</style>




<?php include 'includes/admin_footer.php'; ?>