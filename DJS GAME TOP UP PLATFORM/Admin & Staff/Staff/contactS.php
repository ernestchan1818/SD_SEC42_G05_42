<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - DJS Game</title>
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
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
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
    transition: color 0.3s ease;
}
header nav a:hover {
    color: #b0e0ff; /* 悬停时浅蓝色 */
}

/* --- Hero/Content Section Styles --- */
.hero {
    padding: 80px 20px;
    text-align: center;
}

.hero-text h1 {
    font-size: 3.5em;
    color: #00BFFF; /* 亮蓝色主标题 */
    margin-bottom: 10px;
    text-shadow: 0 0 10px rgba(0, 123, 255, 0.8); /* 霓虹发光效果 */
}

.hero-text p {
    font-size: 1.1em;
    color: #aaa;
    margin-bottom: 40px;
}

.hero-text h2 {
    color: #b0e0ff; /* 浅蓝色副标题 */
    font-size: 1.8em;
    border-bottom: 2px solid #007BFF;
    display: inline-block;
    padding-bottom: 5px;
    margin-bottom: 20px;
    margin-top: 30px;
}

/* Contact Info Box */
.contact-info-box {
    background: rgba(17, 34, 51, 0.9); /* 半透明深蓝背景 */
    padding: 30px;
    border-radius: 12px;
    max-width: 500px;
    margin: 0 auto;
    text-align: left;
    box-shadow: 0 0 15px rgba(0, 123, 255, 0.3);
}
.contact-info-box strong {
    color: #00BFFF; /* 亮蓝色标签 */
    font-weight: 600;
    min-width: 100px; /* 保持对齐 */
    display: inline-block;
}
.contact-info-box p {
    margin: 15px 0;
    display: flex;
    gap: 10px;
}

/* Footer */
footer {
    background: #111;
    text-align: center;
    padding: 20px;
    border-top: 1px solid #007BFF;
    font-size: 0.9em;
    color: #777;
    margin-top: auto;
}
</style>

</head>
<body>

<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <?php
        session_start();
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


<section class="hero">
    <div class="hero-text container">
        <h1>Contact Us</h1>
        <p>We’d love to hear from you. Reach us via phone or email below.</p>
        <h2>Our Contact Information</h2>
        
        <div class="contact-info-box">
            <p><strong>📞 Phone:</strong> +60 12-345 6789</p>
            <p><strong>✉️ Email:</strong> djssupport@gmail.com</p>
        </div>
    </div>
</section>


<footer>
    <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
</footer>

<script>
    // 移除未使用的滚动脚本，因为它依赖外部CSS和ID，容易出错
    // document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
    //     anchor.addEventListener("click", function(e) {
    //         e.preventDefault();
    //         document.querySelector(this.getAttribute("href"))
    //             .scrollIntoView({ behavior: "smooth" });
    //     });
    // });
</script>

</body>
</html>
