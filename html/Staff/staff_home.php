<?php
session_start();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff/Admin Home</title>
    <link rel="stylesheet" href="style_home.css">
</head>
<body>
    <div class="navbar">
        <h2>DJS Game Top-up Platform</h2>
        <div class="nav-right">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <a href="logoutS.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Staff/Admin Dashboard</h1>
        <div class="cards">
            <div class="card">
                <img src="../Staff/image/contact.png" alt="Contact">
                <h3>View Customer Contact</h3>
                <p>Check and reply to messages from customers.</p>
                <a href="contactus.php" class="btn">Go</a>
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
        </div>
    </div>
</body>
</html>
