<?php
include "config.php";
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

// 从 URL 获取 email
if (!isset($_GET['email'])) {
    die("Invalid access.");
}
$email = $_GET['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);

    // 查找用户
    $stmt = $conn->prepare("SELECT * FROM staff_admin WHERE email=? AND otp=?");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // 验证成功
        $user = $result->fetch_assoc();

        // 清空 OTP，代表已验证
        $update = $conn->prepare("UPDATE staff_admin SET otp='' WHERE email=?");
        $update->bind_param("s", $email);
        $update->execute();

        echo "<script>
                alert('Verification successful! Welcome, {$user['role']} [{$user['staffid']}].');
                window.location.href='signinS.php';
              </script>";
        exit();
    } else {
        $message = "Invalid OTP!";
        echo "<script>alert('$message');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Staff/Admin</title>
    <link rel="stylesheet" href="verifyS.css">
</head>
<body>
    <header>
        Staff/Admin Verification
    </header>

    <section class="verify-container">
        <h2>Email Verification</h2>
        <p>Please enter the OTP sent to <b><?php echo htmlspecialchars($email); ?></b></p>

        <form action="verifyS.php?email=<?php echo urlencode($email); ?>" method="POST">
            <div class="form-group">
                <label for="otp">Enter OTP</label>
                <input type="text" id="otp" name="otp" placeholder="6-digit OTP" required>
            </div>

            <button type="submit">Verify</button>
        </form>
    </section>
</body>
</html>
