<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'staff' && $_SESSION['role'] != 'admin')) {
    header("Location: ../Staff/signinS.php");
    exit();
}

require "config.php";

// 从 session 取用户信息
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "Staff";
$staffId  = isset($_SESSION['staffid']) ? $_SESSION['staffid'] : null;

// 查询头像
$avatar = "";
if ($staffId) {
    $sql = "SELECT avatar FROM staff_admin WHERE staffid=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $avatar = $row['avatar']; // 如果有头像路径
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff/Admin Home</title>
    <link rel="stylesheet" href="style_home.css">
    <style>
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
      <div class="navbar">
        <h2><a href="aboutS.php" style="color:white;">DJS Game Top-up Platform</a></h2>
        <div class="nav-right">
            <?php if (!empty($avatar)): ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" 
                     alt="Profile" class="profile-pic">
            <?php endif; ?>

            <span>Welcome, <?php echo htmlspecialchars($username); ?> </span>
            <a href="logoutS.php" class="btn-logout">Logout</a>

        </div>
    </div>

    <div class="container">
        <h1>Staff Dashboard</h1>

        <div class="cards">
            <div class="card">
                <img src="../Staff/image/contact.png" alt="Contact">
                <h3>View Contact Us</h3>
                <p>Check and View Contact Us.</p>
                <a href="contact.php" class="btn">Go</a>
            </div>

            <div class="card">
                <img src="../Staff/image/feedback.png" alt="Feedback">
                <h3>View Feedback</h3>
                <p>Read customer feedback and improve our service.</p>
                <a href="view_feedback.php" class="btn">Go</a>
            </div>

            <div class="card">
                <img src="../Staff/image/profile.png" alt="Profile">
                <h3>Profile</h3>
                <p>Manage your account and update your profile details.</p>
                <a href="profileS.php" class="btn">Go</a>
            </div>
            
             <!-- ✅ 新增 Delete Customer -->
        <div class="card">
            <img src="../Staff/image/customer.png" alt="Delete Customer">
            <h3>Delete Customer</h3>
            <p>Remove customer accounts from the system.</p>
            <a href="delete_customer.php" class="btn">Go</a>
        </div>

        </div>
    </div>
</body>
</html>
