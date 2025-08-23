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

    // ✅ 密码强度正则
    $passwordPattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if ($password !== $confirm) {
        $message = "❌ Passwords do not match!";
        echo "<script>alert('$message');</script>";
    } elseif (!preg_match($passwordPattern, $password)) {
        $message = "❌ Password must be at least 8 characters, include uppercase, lowercase, number, and special character (!@#￥ etc).";
        echo "<script>alert('$message');</script>";
    } else {
        $check = $conn->prepare("SELECT * FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "❌ Email already registered!";
            echo "<script>alert('$message');</script>";
        } else {
            $otp = substr(str_shuffle("0123456789"), 0, 6);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password, otp) VALUES (?,?,?,?)");
            $stmt->bind_param("ssss", $username, $email, $hash, $otp);

            if ($stmt->execute()) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'jesvin20050501@gmail.com';
                    $mail->Password   = 'fencfureagihgesd';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('jesvin20050501@gmail.com', 'DJS Game');
                    $mail->addAddress($email, $username);
                    $mail->Subject = 'Your OTP Code';
                    $mail->Body    = "Hello $username,\n\nYour OTP is: $otp";

                    $mail->send();

                    echo "<script>
                            alert('✅ Registration successful! OTP has been sent to your email.');
                            window.location.href='verify.php?email=".urlencode($email)."';
                          </script>";
                    exit();
                } catch (Exception $e) {
                    $message = "Mailer Error: " . $mail->ErrorInfo;
                    echo "<script>alert('$message');</script>";
                }
            } else {
                $message = "❌ Database insert failed!";
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
    <style>  body {
      font-family: Arial, sans-serif;
      background: linear-gradient(
          rgba(0, 0, 0, 0.5),
          rgba(0, 0, 0, 0.5)
      ), url("../image/bgsi.webp") no-repeat center center fixed;
      background-size: cover;
      color: white;
      margin: 0;
      padding: 0;
    }</style>
    <link rel="stylesheet" href="../css/register.css">
</head>
<body>
    <header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
           &copy; 2025 DJS Game. All Rights Reserved.
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
    <label for="password">Set Password</label>
    <input type="password" id="password" name="password" placeholder="Enter password" required>
    <!-- 密码强度提示 -->
    <div id="strengthMessage"></div>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter password" required>
            </div>

            <button type="submit" class="btn">Register</button>
        </form>
        <p class="login-link">Already have an account? <a href="signin.php">Login here</a></p>
    </section>

     <script>
document.getElementById("password").addEventListener("input", function() {
    let password = this.value;
    let strengthMessage = document.getElementById("strengthMessage");

    let strength = 0;

    if (password.length >= 6) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    if (password.length === 0) {
        strengthMessage.textContent = "";
    } else if (strength <= 1) {
        strengthMessage.textContent = "Weak password";
        strengthMessage.className = "strength-weak";
    } else if (strength === 2 || strength === 3) {
        strengthMessage.textContent = "Medium password";
        strengthMessage.className = "strength-medium";
    } else {
        strengthMessage.textContent = "Strong password";
        strengthMessage.className = "strength-strong";
    }
});
</script>

</body>

</html>
