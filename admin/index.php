<?php
session_start();
?>
 <!DOCTYPE html>
 <html lang="en">
 <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Al-Hijrah Trust</title>
 </head>
 <body>
    <?php

include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = trim($conn->real_escape_string($_POST['email']));
$password = trim($_POST['password']);


    $sql = "SELECT admin_id, password FROM registered_admin WHERE email = '$email'";
    $result = $conn->query($sql);


    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['admin_id'];
            $_SESSION['email'] = $email;
            header("Location: dashboard.php"); // Change this to your actual destination
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!-- Bootstrap & styles -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: #f5f6fa;
        font-family: 'Segoe UI', sans-serif;
    }

    .card {
        border-radius: 10px;
    }

    .card-body h4 {
        color: #2f3640;
    }

    .form-label {
        font-weight: 500;
    }

    .btn-primary {
        background-color: #0056b3;
        border-color: #004a99;
    }

    .btn-primary:hover {
        background-color: #004a99;
    }

    .small a {
        text-decoration: none;
    }

    .small a:hover {
        text-decoration: underline;
    }

    .alert {
        margin-top: 10px;
    }
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h4 class="mb-3 text-center fw-semibold">Admin Login</h4>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger small"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <p class="text-center mt-3 small" disabled>
                        Don't have an account? <a href="register.php" >Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

 </body>
 </html>