<?php
session_start();

if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'staff' && $_SESSION['role'] != 'admin')) {
    header("Location: ../Admin & Staff/signinS.php");
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
        <h1>Admin Dashboard</h1>
        <div class="cards">
            <div class="card">
                <img src="../image/contact.png" alt="Contact">
                <h3>View Contact Us</h3>
                <p>Check and View Contact Us.</p>
                <a href="contactS.php" class="btn">Go</a>
            </div>

            <div class="card">
                <img src="../image/feedback.png" alt="Feedback">
                <h3>View Feedback</h3>
                <p>Read customer feedback and improve our service.</p>
                <a href="contactus.php" class="btn">Go</a>
            </div>

            <div class="card">
                <img src="../image/profile.png" alt="Profile">
                <h3>Profile</h3>
                <p>Manage your account and update your profile details.</p>
                <a href="profileS.php" class="btn">Go</a>
            </div>

            <div class="card">
        <img src="../image/delete.png" alt="Delete Staff">
        <h3>Delete Staff</h3>
        <p>Remove staff accounts from the system.</p>
        <a href="delete_staff.php" class="btn">Go</a>
        </div>

            <!-- ✅ 新增 Delete Customer -->
            <div class="card">
                <img src="../image/customer.png" alt="Delete Customer">
                <h3>Delete Customer</h3>
                <p>Remove customer accounts from the system.</p>
                <a href="delete_customer.php" class="btn">Go</a>
            </div>

            <!-- ✅ Manage Games -->
            <div class="card">
                <img src="../image/games.webp" alt="Manage Games">
                <h3>Manage Games</h3>
                <p>Add, edit, or delete game information.</p>
                <a href="manage_games.php" class="btn">Go</a>
            </div>

            <!-- ✅ Manage Top-Up Packages -->
            <div class="card">
               <img src="../image/packages.jpg" alt="Manage Top-Up Packages">
               <h3>Manage Top-Up Packages</h3>
               <p>Create, edit, or remove top-up packages for customers.</p>
               <a href="manage_packages.php" class="btn">Go</a>
            </div>

            <!-- ✅ View Customer Orders -->
<div class="card">
   <img src="../image/order.jpg" alt="View Orders">
   <h3>View Customer Orders</h3>
   <p>Check customer orders, view items, and update delivery status.</p>
   <a href="manage_orders.php" class="btn">Go</a>
</div>       

               <!-- New Card: View Sales Report --><div class="card">
            <img src="../image/sales.png" alt="View Sales Report"> <!-- 您可能需要创建这张图片 --><h3>View Sales Report</h3>
            <p>Access detailed sales analytics and revenue reports.</p>
            <a href="sales_report.php" class="btn">Go</a>
        </div>
        </div>
    </div>
</body>
</html>
