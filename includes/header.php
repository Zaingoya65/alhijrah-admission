<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Al-Hijrah Trust | Admission System</title>
 <link rel="icon"  href="../assets/images/trust-logo.jpg" type="image/x-icon">



     <link rel="stylesheet" href="/../assets/css/style.css">
     
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', 'Tajawal', sans-serif;
        }
        
        .top-bar {
            background-color: var(--primary-color);
            font-size: 0.9rem;
        }
        
        .navbar {
            padding: 0.5rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(44, 62, 80, 0.1);
            color: var(--primary-color);
        }
        
        .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 767.98px) {
            .navbar-brand img {
                height: 50px;
            }
            
            .contact-info {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar py-2 bg-primary bg-opacity-90 text-white">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                <!-- Contact Info -->
                <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-3 mb-2 mb-md-0">
                    <span>
                        <i class="fas fa-phone-alt me-1"></i> +92 123 4567890
                    </span>
                    <span>
                        <i class="fas fa-envelope me-1"></i> info@alhijrahtrust.edu.pk
                    </span>
                </div>

                <!-- User Links -->
                <div class="d-flex gap-3">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="text-white text-decoration-none">
                            <i class="fas fa-user-circle me-1"></i> Dashboard
                        </a>
                        <a href="logout.php" class="text-white text-decoration-none">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-white text-decoration-none">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                        <a href="register.php" class="text-white text-decoration-none">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/trust-logo.png" alt="Al-Hijrah Trust" height="60" class="d-inline-block align-top">
                <span class="ms-3 fs-4 fw-bold d-none d-md-inline">AL-HIJRAH TRUST</span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 py-2 rounded-3 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active fw-bold' : '' ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 py-2 rounded-3" href="#admission">Admission</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 py-2 rounded-3" href="#campuses">Campuses</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 py-2 rounded-3" href="#vision">Our Vision</a>
                    </li>
                    <li class="nav-item mx-1">
                        <a class="nav-link px-3 py-2 rounded-3" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <main>
        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>