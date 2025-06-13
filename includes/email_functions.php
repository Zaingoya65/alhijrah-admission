<?php
function send_otp_email($email, $name, $otp) {
    $subject = "Your Verification Code";
    $message = "Hello $name,\n\nYour OTP is: $otp\n\nThis code expires in 15 minutes.";
 
  $headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/plain; charset=UTF-8\r\n";
$headers .= "From: no-reply@admissions.alhjrah.pk\r\n";




    
   if (!fsockopen('localhost', 25, $errno, $errstr, 10)) {
    error_log("Mail server not running: $errstr ($errno)");
    $_SESSION['debug_otp'] = $otp;
    return true;
}

    return mail($email, $subject, $message, $headers);
}

// function send_password_reset_email($email, $name, $token) {
//     $reset_link = "http://localhost/reset-password.php?token=$token";
//     $subject = "Password Reset Request";
//     $message = "Hello $name,\n\nReset your password here: $reset_link\n\nLink expires in 1 hour.";
//     $headers = "From: no-reply@localhost\r\n";
    
//     return mail($email, $subject, $message, $headers);
// }

function send_password_reset_email($email, $name, $token) {
    $reset_link = "https://moccasin-tiger-993742.hostingersite.com/forgot-password.php?token=$token";
    $subject = "Password Reset Request";
    
    $message = "
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <h2>Hello $name,</h2>
        <p>We received a request to reset your password.</p>
        <p>Click here to reset: <a href='$reset_link'>$reset_link</a></p>
        <p>This link expires in 1 hour.</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers = "From: no-reply@admissions.alhjrah.pk\r\n";

    
    return mail($email, $subject, $message, $headers);
}
?>