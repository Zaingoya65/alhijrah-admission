<?php
session_start();
include '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $conn->real_escape_string($_POST['password']);
    $cpassword = $conn->real_escape_string($_POST['cpassword']);

    if ($password !== $cpassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO registered_admin (email, password) VALUES ('$email', '$hashed_password')";

        if ($conn->query($sql)) {
            $_SESSION['id'] = $conn->insert_id;
            $_SESSION['email'] = $email;
            header("Location: index.php");
            exit();
        } else {
            $error = "Registration failed: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Register | Alhijrah Trust</title>
    <!-- Bootstrap 5 CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Optional custom styles -->
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

</head>
<body>
    <?php //include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h4 class="mb-3 text-center fw-semibold">Admin Registration</h4>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger small"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3 position-relative">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                        </div>

                        <div class="mb-3 position-relative">
                            <label for="cpassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="cpassword" name="cpassword" minlength="8" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" disabled>Register</button>
                    </form>

                    <p class="text-center mt-3 small">
                        Already have an account? <a href="index.php">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php //include 'includes/footer.php'; ?>
</body>
</html>


