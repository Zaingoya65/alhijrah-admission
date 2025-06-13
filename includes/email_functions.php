<?php
function send_otp_email($email, $otp, $verification_link) {
    $subject = "Verify Your Account – OTP and Link Inside";
    $message = "
    <html>
    <head><title>Email Verification</title></head>
    <body>
        <h3>Assala Walekum!,</h3>
        <h4>Welcome Al-Hijrah trust portal </h4>
        <p>Your One-Time Password (OTP) is: <strong style='font-size: 18px;'>$otp</strong></p>
        <p><em>This code will expire in 15 minutes.</em></p>
        <hr>
        <p>If you prefer, click the link below to verify your account:</p>
        <p><a href='$verification_link'>$verification_link</a></p>
        <p><em>This link will expire in 1 hour.</em></p>
        <br>
        <p>If you didn't register on our site, please ignore this email.</p>
        <p>Thank You </p>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: no-reply@admissions.alhjrah.pk\r\n";

    $result = mail($email, $subject, $message, $headers);
    if (!$result) {
        error_log("Failed to send email to $email");
    }
    return $result;
}


function send_password_reset_email($email, $token) {
   $reset_link = "https://moccasin-tiger-993742.hostingersite.com/reset-password.php?token=$token";

    $subject = "Password Reset Request";
    
    $message = "
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <h2>Hello,</h2>
        <p>We received a request to reset your password.</p>
        <p>Click here to reset to reset password: <a href='$reset_link'>Click Here</a></p>
        <p> or paste this link in broswer</p>
         <p>Click here to reset to reset password: <a href='$reset_link'>$reset_link</a></p>
        <p>This link expires in 1 hour.</p>
    </body>
    </html>
    ";
    
 $headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: no-reply@admissions.alhjrah.pk\r\n";


    
    return mail($email, $subject, $message, $headers);
}
?>