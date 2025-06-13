<?php

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Hijrah Trust | Admission System</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/trust-logo.png" type="image/png">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar py-2 bg-primary text-white">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
            <!-- Contact Info Left -->
            <div class="contact-info mb-2 mb-md-0">
                <span class="me-3">
                    <i class="fas fa-phone me-1"></i> +92 123 4567890
                </span>
                &nbsp; &nbsp;
                <span>
                    <i class="fas fa-envelope me-1"></i> info@alhijrahtrust.edu.pk
                </span>
            </div>

            <!-- User Links Right -->
            <div class="user-links">
                <?php if (isset($_SESSION['user_id'])): ?>
    <a href="" class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i>
    </a>
    <a href="logout.php" class="text-white">
        <i class="fas fa-sign-out-alt me-1"></i> Logout
    </a>
<?php else: ?>
    <a href="login.php" class="login-btn">
        <i class="fas fa-sign-in-alt me-1"></i> Login
    </a>
    <a href="register.php" class="register-btn">
        <i class="fas fa-user-plus me-1"></i> Register
    </a>
<?php endif; ?>

            </div>
        </div>
    </div>
</div>


    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="assets/images/trust-logo.png" alt="Al-Hijrah Trust" height="65">
                <span class="ms-2 fw-bold">AL-HIJRAH TRUST</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#admission">Admission</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#campuses">Campuses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#vision">Our Vision</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main>