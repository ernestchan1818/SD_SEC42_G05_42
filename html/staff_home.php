<?php
session_start();
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'staff' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff/Admin Home</title>
    <link rel="stylesheet" href="style_home.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo ucfirst($username); ?> 👋</h1>
        <p class="subtitle">This is your Staff/Admin Dashboard</p>

        <div class="card-container">

            <!-- View Contact -->
            <a href="view_contact.php" class="card">
                <img src="images/contact.png" alt="View Contact">
                <h2>View Contact</h2>
                <p>Check messages from customers and reply.</p>
            </a>

            <!-- View Feedback -->
            <a href="view_feedback.php" class="card">
                <img src="images/feedback.png" alt="View Feedback">
                <h2>View Feedback</h2>
                <p>Read customer feedback and reviews.</p>
            </a>

            <!-- Profile -->
            <a href="profile.php" class="card">
                <img src="images/profile.png" alt="Profile">
                <h2>Profile</h2>
                <p>Manage your profile and settings.</p>
            </a>

            <!-- Logout -->
            <a href="logout.php" class="card logout">
                <img src="images/logout.png" alt="Logout">
                <h2>Logout</h2>
                <p>Sign out from your account safely.</p>
            </a>

        </div>
    </div>
</body>
</html>
