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
    $role     = $_POST['role']; // staff or admin

    // ✅ 密码强度检查
    $passwordPattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    if ($password !== $confirm) {
        echo "<script>alert('❌ Passwords do not match!');</script>";
    } elseif (!preg_match($passwordPattern, $password)) {
        echo "<script>
                alert('❌ Password must be at least 8 characters, include uppercase, lowercase, number, and special character (!@#￥ etc).');
              </script>";
    } else {
        // ✅ 检查邮箱是否存在
        $check = $conn->prepare("SELECT * FROM staff_admin WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('❌ Email already registered!');</script>";
        } else {
            $otp = substr(str_shuffle("0123456789"), 0, 6);
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // 生成 staffid，例如 A12345 或 S67890
            $prefix = ($role === "admin") ? "A" : "S";
            $staffid = $prefix . str_pad(rand(1, 99999), 5, "0", STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO staff_admin (username, email, password, role, staffid, otp) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $username, $email, $hash, $role, $staffid, $otp);

            if ($stmt->execute()) {
                // 📧 发邮件
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'jesvin20050501@gmail.com'; // 你的 Gmail
                    $mail->Password   = 'fencfureagihgesd';        // Gmail App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('jesvin20050501@gmail.com', 'DJS Game');
                    $mail->addAddress($email, $username);
                    $mail->Subject = 'Your Staff/Admin Registration Info';
                    $mail->Body    = "Hello $username,\n\nYour OTP is: $otp\nYour StaffID is: $staffid\n\nRole: $role";

                    $mail->send();

                    echo "<script>
                            alert('✅ Registration successful! StaffID & OTP sent to your email.');
                            window.location.href='verifyS.php?email=".urlencode($email)."';
                          </script>";
                    exit();

                } catch (Exception $e) {
                    echo "<script>alert('❌ Mailer Error: {$mail->ErrorInfo}');</script>";
                }
            } else {
                echo "<script>alert('❌ Database insert failed!');</script>";
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
    <title>Staff/Admin Register</title>
    <link rel="stylesheet" href="registerS.css">
</head>
<body>
    <header>
        Staff/Admin Registration
    </header>

    <section class="register-container">
        <h2>Create Staff/Admin Account</h2>
        <form action="registerS.php" method="POST">
            <div class="form-group">
                <label for="username">Name</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Work Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div class="form-group">
    <label for="password">Set Password</label>
    <input type="password" id="password" name="password" required>
    <!-- 密码强度提示 -->
    <div id="strengthMessage"></div>
</div>


            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
            </div>

            <button type="submit">Register</button>
        </form>
        <p class="login-link">Already have account? <a href="signinS.php">Login</a></p>
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
