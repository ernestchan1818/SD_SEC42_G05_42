<?php
session_start();
include "config.php";
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 路径改成你安装 Composer 后的路径

$mail = new PHPMailer(true);


$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Step 1: User enters email, system sends OTP
    if (isset($_POST["send_otp"])) {
        $email = $_POST["email"];
        $sql = "SELECT * FROM users WHERE email='$email'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['reset_email'] = $email;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; 
                $mail->SMTPAuth = true;
                $mail->Username = 'jesvin20050501@gmail.com'; 
                $mail->Password = 'fencfureagihgesd'; 
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('jesvin20050501@gmail.com', 'Password Reset');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Your OTP Code';
                $mail->Body = "Your OTP is: <b>$otp</b>";

                $mail->send();
                $message = "OTP sent to your email.";
            } catch (Exception $e) {
                $message = "Mailer Error: " . $mail->ErrorInfo;
            }
        } else {
            $message = "Email not found!";
        }
    }

    // Step 2: Verify OTP + reset password
    if (isset($_POST["reset_password"])) {
        $enteredOtp = $_POST["otp"];
        $newPassword = $_POST["new_password"];
        $confirmPassword = $_POST["confirm_password"];

        // Password validation (Uppercase, Lowercase, Number, Min 8 chars)
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $newPassword)) {
            $message = "Password must include Uppercase, Lowercase, Number and be at least 8 characters.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Passwords do not match.";
        } elseif ($_SESSION['otp'] == $enteredOtp) {
            $email = $_SESSION['reset_email'];
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $sql = "UPDATE staff_admin SET password='$hashedPassword' WHERE email='$email'";



            if ($conn->query($sql) === TRUE) {
                $message = "Password reset successful!";
                unset($_SESSION['otp']);
                unset($_SESSION['reset_email']);
            } else {
                $message = "Error updating password.";
            }
        } else {
            $message = "Invalid OTP.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - DJS Game</title>
    <link rel="stylesheet" href="forgetpasswordS.css">
</head>
<body>
    <header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
            <a href="home.html">Home</a>
            <a href="registerS.php">Register</a>
            <a href="signinS.php">Login</a>
        </nav>
    </header>

    <section class="forgot-password-container">
        <h2>Forgot Your Password?</h2>

        <p style="color:red;"><?php echo $message; ?></p>

        <!-- 步骤 1: 发送 OTP -->
        <form action="" method="post">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
            <button type="submit" name="send_otp" class="btn">Send OTP</button>
        </form>

        <!-- 步骤 2: 重置密码 -->
        <form action="" method="post" style="margin-top:20px;">
            <label for="otp">Enter OTP</label>
            <input type="text" id="otp" name="otp" placeholder="Enter OTP" required>

            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>

            <button type="submit" name="reset_password" class="btn">Reset Password</button>
        </form>

        <p class="back-link">
            Remembered your password? <a href="signinS.php">Back to Login</a>
        </p>
    </section>
</body>
</html>