<?php
include "config.php";
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm-password'];

    if ($password !== $confirm) {
        $message = "Passwords do not match!";
        echo "<script>alert('$message');</script>";
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already registered!";
            echo "<script>alert('$message');</script>";
        } else {
            $otp = substr(str_shuffle("0123456789"), 0, 6);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, otp) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $username, $email, $hash, $otp);

            if ($stmt->execute()) {
                // PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'jesvin20050501@gmail.com'; // 你的Gmail
                    $mail->Password   = 'fencfureagihgesd';        // 你的App password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('jesvin20050501@gmail.com', 'DJS Game');
                    $mail->addAddress($email, $username);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body    = "Hello $username,\n\nYour OTP is: $otp";

                    $mail->send();

                    // JS 跳转
                    echo "<script>
                            alert('Registration successful! OTP has been sent to your email.');
                            window.location.href='verify.php?email=".urlencode($email)."';
                          </script>";
                    exit();

                } catch (Exception $e) {
                    $message = "Mailer Error: " . $mail->ErrorInfo;
                    echo "<script>alert('$message');</script>";
                }
            } else {
                $message = "Database insert failed!";
                echo "<script>alert('$message');</script>";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
            <a href="home.html">Home</a>
            <a href="register.php" class="active">Register</a>
            <a href="signin.html">Login</a>
        </nav>
    </header>

    <section class="register-container">
        <h2>Create an Account</h2>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>
        <p class="login-link">Already have an account? <a href="signin.html">Login here</a></p>
    </section>

    <footer>
        &copy; 2025 DJS Game. All rights reserved.
    </footer>
</body>
</html>
