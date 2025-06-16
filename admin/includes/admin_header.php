<?php
// session_start();
include '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= $page_title ?? 'Dashboard' ?> | Al-Hijrah Trust</title>

    <link rel="icon" href="../../assets/images/trust-logo.png" type="image/png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">


    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f3f5;
            margin: 0;
        }

        .navbar-brand img {
            height: 36px;
        }

        .nav-link {
            color: #333 !important;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #198754 !important;
        }

        .dropdown-menu a:hover {
            background-color: #f8f9fa;
        }

        .content-wrapper {
            padding: 90px 20px 30px;
        }
    </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="../assets/images/trust-logo.png" alt="Logo" class="me-2">
            <strong>Admin Panel</strong>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
            <!-- Left nav items -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $page_title == 'Dashboard' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fas fa-home me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $page_title == 'Applications' ? 'active' : '' ?>" href="#" id="appDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-file-alt me-1"></i> Applications
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="applications.php">All Applications</a></li>
                        <li><a class="dropdown-item" href="applications.php?status=Pending">Pending</a></li>
                        <li><a class="dropdown-item" href="applications.php?status=Approved">Approved</a></li>
                        <li><a class="dropdown-item" href="applications.php?status=Rejected">Rejected</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page_title == 'Users' ? 'active' : '' ?>" href="users.php">
                        <i class="fas fa-users me-1"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $page_title == 'Settings' ? 'active' : '' ?>" href="settings.php">
                        <i class="fas fa-cog me-1"></i> Settings
                    </a>
                </li>
            </ul>

            <!-- User profile dropdown -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-1 fs-5"></i>
                        <?= $_SESSION['email'] ?? 'Admin' ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider" /></li>
                        <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Area -->
<main class="content-wrapper" id="mainContent">
    <!-- Your dynamic page content starts here -->
