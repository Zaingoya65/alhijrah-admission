<?php
session_start();

// Check if the user is not logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.php"); // Redirect to login page
    exit();
}
include '../includes/config.php';

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    header("Location: applications.php");
    exit();
}

$application_id = (int)$_GET['id'];
$action = $_GET['action'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    header("Location: view_application.php?id=$application_id");
    exit();
}

// Get application details
$sql = "SELECT * FROM student_profiles WHERE user_id = $application_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    header("Location: applications.php");
    exit();
}

$application = $result->fetch_assoc();

// Check if already processed
if ($application['application_status'] != 'Pending') {
    header("Location: view_application.php?id=$application_id");
    exit();
}

// Process application
$status = $action == 'approve' ? 'Approved' : 'Rejected';
$sql = "UPDATE student_profiles SET application_status = '$status' WHERE user_id = $application_id";

if ($conn->query($sql)) {
    // Log this action
    $admin_id = $_SESSION['user_id'];
    $log_sql = "INSERT INTO application_logs (admin_id, application_id, action) 
                VALUES ($admin_id, $application_id, '$status')";
    $conn->query($log_sql);
    
    $_SESSION['success'] = "Application has been $status successfully";
} else {
    $_SESSION['error'] = "Error processing application: " . $conn->error;
}

header("Location: view_application.php?id=$application_id");
exit();
?>