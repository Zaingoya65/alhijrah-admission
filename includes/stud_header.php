<?php
// Start session and include config
include '../includes/config.php';
include '../includes/session_auth.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Get user info if logged in
$user_name = 'Guest';
$user_image = '../uploads/default.png'; // Local default image
$user_role = 'Student';

if (isset($_SESSION['id'])) {
    $id = $_SESSION['id'];
   $stmt = $conn->prepare("SELECT 
    sp.full_name, 
    sp.profile_image
FROM student_profiles sp
JOIN registered_users ru ON sp.user_id = ru.id
WHERE sp.user_id = ?");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$stmt) {
    die("Prepare failed: " . $conn->error);
    }

    if ($result && $row = $result->fetch_assoc()) {
        $user_name = $row['full_name'] ? htmlspecialchars($row['full_name']) : 'User';
        $user_campus = isset($row['campus']) ? htmlspecialchars($row['campus']) : '';
        
        if (!empty($row['profile_image'])) {
            $upload_dir = "../uploads/$id/";
            $user_image = $upload_dir . $row['profile_image'];
            
            // Verify the file exists and is an image
            if (!file_exists($user_image) || !getimagesize($user_image)) {
                $user_image = '../uploads/default.png';
            }
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Al-Hijrah Trust Admission System">
    <meta name="author" content="Al-Hijrah Trust">
    
    <title>Al-Hijrah Trust | Student Dashboard</title>
    
    
    <link rel="preload" href="../assets/css/style.css" as="style">
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../assets/images/trust-logo.png" type="image/x-icon">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/images/trust-logo.png">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        .navbar {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            color: var(--primary-color) !important;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand img {
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            object-fit: cover;
            transition: all 0.3s ease;
            border: 2px solid var(--secondary-color);
        }
        
        .user-avatar:hover {
            transform: scale(1.1);
            border-color: var(--accent-color);
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .dropdown-item {
            padding: 0.5rem 1.5rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--secondary-color);
            color: white !important;
        }
        
        .dropdown-divider {
            border-color: rgba(0, 0, 0, 0.05);
        }
        
        .dropdown-header {
            font-family: 'Tajawal', sans-serif;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        @media (max-width: 991.98px) {
            .navbar-collapse {
                padding: 1rem;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light ">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../assets/images/trust-logo.png" alt="Al-Hijrah Trust" height="50" class="d-inline-block align-top">
                <span class="ms-2 d-none d-sm-inline">AL-HIJRAH TRUST</span>
            </a>
            
            <!-- Mobile Toggler -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
                <ul class="navbar-nav align-items-center">
                   
                    
                    <!-- User Profile Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo htmlspecialchars($user_image); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="user-avatar rounded-circle shadow-sm" onerror="this.src='../assets/images/default-profile.png'">
                            <span class="ms-2 d-none d-lg-inline"><?php echo $user_name; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <div class="dropdown-header text-center">
                                    <img src="<?php echo htmlspecialchars($user_image); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="rounded-circle mb-2" width="80" height="80" onerror="this.src='../assets/images/default-profile.png'">
                                    <h6 class="mb-1"><?php echo $user_name; ?></h6>
                                    <small class="text-muted"><?php echo $user_role; ?></small>
                                    <?php if (!empty($user_campus)): ?>
                                        <div class="mt-1">
                                            <span class="badge bg-primary"><?php echo $user_campus; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../logout.php" onclick="return confirm('Are you sure you want to logout?');">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

   
    


    