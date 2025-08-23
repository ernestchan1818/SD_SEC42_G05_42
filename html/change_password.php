<?php
session_start();
include("config.php");

// 确保用户已登录
if (!isset($_SESSION['username'])) {
    echo "<script>alert('Please login first'); window.location.href='signin.php';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_SESSION['username'];
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 检查新密码是否一致
    if ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match'); window.location.href='profile.php';</script>";
        exit();
    }

    // 检查新密码是否符合复杂度（大小写 + 数字）
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/", $new_password)) {
        echo "<script>alert('Password must include uppercase, lowercase, number and at least 6 characters'); window.location.href='profile.php';</script>";
        exit();
    }

    // 查询旧密码
    $sql = "SELECT password FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        // 验证旧密码
        if (password_verify($old_password, $row['password'])) {
            // 更新新密码
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_sql = "UPDATE users SET password=? WHERE username=?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $hashed_password, $username);

            if ($update_stmt->execute()) {
                echo "<script>alert('Password updated successfully!'); window.location.href='profile.php';</script>";
            } else {
                echo "<script>alert('Error updating password'); window.location.href='profile.php';</script>";
            }
        } else {
            echo "<script>alert('Old password incorrect'); window.location.href='profile.php';</script>";
        }
    }
}
?>
