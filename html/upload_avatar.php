<?php
session_start();
include("config.php");

// 确保用户已登录
if (!isset($_SESSION['username'])) {
    header("Location: signin.php");
    exit();
}

$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $targetDir = "uploads/";  // 保存路径
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // 自动创建目录
    }

    $fileName = basename($_FILES['profile_pic']['name']);
    $targetFile = $targetDir . time() . "_" . $fileName; // 防止重名

    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // 允许的文件类型
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
            // 更新数据库
            $sql = "UPDATE users SET profile_pic=? WHERE username=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $targetFile, $username);
            $stmt->execute();

            echo "<script>alert('Profile picture updated!'); window.location.href='profile.php';</script>";
        } else {
            echo "<script>alert('Error uploading file'); window.location.href='profile.php';</script>";
        }
    } else {
        echo "<script>alert('Only JPG, JPEG, PNG, GIF allowed'); window.location.href='profile.php';</script>";
    }
}
?>
