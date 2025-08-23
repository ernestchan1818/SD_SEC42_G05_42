<?php
include "config.php";
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM staff_admin WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (!empty($user['otp'])) {
            // 还没验证
            echo "<script>
                    alert('Account not verified! Please check your email for OTP.');
                    window.location.href='verifyS.php?email=".urlencode($email)."';
                  </script>";
            exit();
        }

        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['staffid'] = $user['staffid'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];


            if ($user['role'] === 'admin') {
                header("Location: admin_home.php");

            } else {
                header("Location: staff_home.php");
            }
            exit();
        } else {
            $message = "Incorrect password!";
            echo "<script>alert('$message');</script>";
        }
    } else {
        $message = "No account found!";
        echo "<script>alert('$message');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff/Admin Login</title>
    <link rel="stylesheet" href="signinS.css">
</head>
<body>
    <header>
        Staff/Admin Login
    </header>

    <section class="login-container">
        <h2>Login</h2>

        <form action="signinS.php" method="POST">
            <div class="form-group">
                <label for="email">Work Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

             <!-- Forgot Password 链接 -->
            <p class="forgot-password">
                <a href="forgetpasswords.php">Forgot Password?</a>
            </p>

            <button type="submit">Sign In</button>
        </form>
        

        <!-- 新增提示 -->
        <p class="register-link">
            Don't have an account? <a href="registerS.php">Register here</a>
        </p>
    </section>
</body>
</html>
