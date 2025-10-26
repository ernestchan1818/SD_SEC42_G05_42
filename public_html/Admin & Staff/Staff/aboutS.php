<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - DJS Game</title>
    <style>
/* --- Global Styles & Background --- */
body {
    margin: 0;
    padding: 0;
    font-family: 'Inter', Arial, sans-serif;
    background-color: #0d1a2f; /* 深蓝色/暗黑背景 */
    color: #e0e0e0; /* 亮色文本 */
    min-height: 100vh;
}

/* --- Header & Navigation Bar (Staff Theme) --- */
header {
    background: #007BFF; /* 蓝色头部背景 */
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4); /* 蓝色发光阴影 */
    position: sticky;
    top: 0;
    z-index: 1000;
}
header .logo {
    font-size: 26px;
    font-weight: bold;
    color: #fff;
    text-shadow: 0 0 5px rgba(255, 255, 255, 0.5); /* 轻微白色发光 */
}
header nav {
    display: flex;
    gap: 20px;
}
header nav a {
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    padding: 5px 0;
    position: relative;
    transition: color 0.3s ease, text-shadow 0.3s ease;
}
header nav a:hover {
    color: #b0e0ff; /* 悬停时浅蓝色 */
    text-shadow: 0 0 8px #b0e0ff;
}

/* --- About Section --- */
.about {
    padding: 60px 20px;
    text-align: center;
    position: relative;
}
.container {
    max-width: 900px;
    margin: 0 auto;
    background: rgba(17, 34, 51, 0.8); /* 半透明深蓝背景 */
    padding: 40px;
    border-radius: 12px;
    border: 1px solid #007BFF; /* 边框高亮 */
    box-shadow: 0 0 25px rgba(0, 123, 255, 0.3);
    backdrop-filter: blur(5px); /* 增加科技感模糊效果 */
}
h1 {
    font-size: 3.5em;
    color: #00BFFF; /* 亮蓝色主标题 */
    margin-bottom: 25px;
    text-shadow: 0 0 10px rgba(0, 123, 255, 0.8); /* 霓虹发光效果 */
}
.about p {
    font-size: 1.1em;
    line-height: 1.8;
    margin-bottom: 20px;
    text-align: left;
}
.about strong {
    color: #b0e0ff; /* 强调词使用浅蓝色 */
    font-weight: bold;
}

/* --- Footer --- */
footer {
    background: #111;
    text-align: center;
    padding: 20px;
    color: #555;
    font-size: 0.9em;
    border-top: 1px solid #007BFF;
    margin-top: auto;
}
</style>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <?php
        // 确保 session 已经启动
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                echo '<a href="admin_home.php">Home</a>';
            } elseif ($_SESSION['role'] === 'staff') {
                echo '<a href="staff_home.php">Home</a>';
            } 
        } 
        ?>
        <a href="manage_orders.php">Manage Orders</a>
        <a href="sales_report.php">Sales Report</a>
        <a href="contactS.php">Contact</a>
        <a href="contactus.php">Feedback</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="logoutS.php">Sign Out</a>
    </nav>
</header>

<section class="about">
    <div class="container">
        <h1>About Us</h1>
        <p>
            Welcome to <strong>DJS Game</strong>, your trusted destination for fast, safe, and affordable
            in-game currency and item top-ups. Whether you’re buying battlepasses, or buying in-game currencies,
            we’ve got you covered.
        </p>
        <p>
            We partner directly with top game publishers to ensure all transactions are secure, instant,
            and hassle-free. Our platform supports a wide range of popular games and payment methods, so
            you can focus on what matters — winning!
        </p>
        <p>
            Founded in 2025, DJS Game is built by gamers, for gamers. We understand the excitement of
            getting your items instantly, and we’re committed to giving you the best top-up experience
            possible.
        </p>
    </div>
</section>

<footer>
    <p>&copy; 2025 DJS Game. All rights reserved.</p>
</footer>
</body>
</html>
