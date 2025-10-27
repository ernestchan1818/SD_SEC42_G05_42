<?php
session_start();
require "config.php"; // 确保有数据库连接

// 默认访客
$username = "Guest";
$avatar = "";
$is_logged_in = false;

// 如果用户已登录
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $is_logged_in = true;

    // 查询用户名和头像 (假设 users 表有 id, username, avatar)
    $sql = "SELECT username, avatar FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $avatar = $user['avatar']; 
    }
}

// Helper function for image path (Fallback provided)
function getImagePath($path) {
    // 假设您有一个默认的头像图片
    $default_avatar = "https://placehold.co/40x40/555/fff?text=U"; 
    $default_logo = "https://placehold.co/80x40/111/ff6600?text=DJS+Game";
    
    if (empty($path)) return $default_avatar;
    
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us - GameStore</title>
<style>
/* Reset basic styles */
body {
    background: #0a0a0a;
    color: #fff;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
}

/* User Info */
.nav-user {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95em;
    color: #fff;
    font-weight: 500;
    margin-right: 20px;
}
.nav-user img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ff6600;
}

/* --- Navigation Bar Styles (New/Enhanced) --- */
header {
    background: #111; /* 深色背景 */
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    position: sticky; /* 粘性头部 */
    top: 0;
    z-index: 1000;
}

header .logo {
    font-size: 24px;
    font-weight: bold;
    color: #ff6600; /* 主题橙色 */
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
    color: #ff6600; /* 悬停时变橙色 */
}

/* 底部发光/下划线效果 */
header nav a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 100%;
    height: 2px;
    background: #ff6600;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

header nav a:hover::after {
    transform: scaleX(1);
}

/* --- Hero/Content Section Styles --- */
.hero {
    background-color: #000;
    padding: 80px 20px;
    text-align: center;
}

.hero-text h1 {
    font-size: 3em;
    color: #fff;
    margin-bottom: 10px;
}

.hero-text p {
    font-size: 1.1em;
    color: #aaa;
    margin-bottom: 40px;
}

.hero-text h2 {
    color: #ff6600;
    font-size: 1.8em;
    border-bottom: 2px solid #333;
    display: inline-block;
    padding-bottom: 5px;
    margin-bottom: 20px;
}

/* Contact Info Box */
.contact-info-box {
    background: #1a1a1a;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(255, 102, 0, 0.2);
}
.contact-info-box strong {
    color: #ff6600;
    font-weight: 600;
}
.contact-info-box p {
    margin: 15px 0;
}

/* Footer */
footer {
    background: #111;
    text-align: center;
    padding: 20px;
    border-top: 1px solid #333;
    font-size: 0.9em;
    color: #777;
    margin-top: 50px;
}
</style>
</head>
<body>

<header>
    <div style="display:flex; align-items:center;">
            <div class="nav-user">
                <?php if ($is_logged_in): ?>
                    <img src="<?php echo htmlspecialchars(getImagePath($avatar)); ?>" alt="Avatar">
                    <span>Welcome！！<?php echo htmlspecialchars($username); ?></span>
                <?php endif; ?>
            </div>

            <nav>
                <a href="profile.php">Profile</a>
                <a href="index.php">Home</a>
                <a href="about.html">About</a>
                <a href="Contact.php">Contact</a>
                <a href="Feedback.php">Feedback</a>
                <a href="view_games.php">Top-Up Games</a>
                <a href="view_packages.php">Top-Up Packages</a>
                <a href="my_order.php">Track</a>

                <?php if ($is_logged_in): ?>
                    <a href="logout.php" style="color: #ff6600;">Logout</a>
                <?php else: ?>
                    <a href="signin.php">Sign In</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
</header>

<section class="hero">
    <div class="hero-text container">
        <h1>Contact Us</h1>
        <p>We’d love to hear from you. Reach us via phone or email below.</p>
        <h2>Our Contact Information</h2>
        <div class="contact-info-box" style="max-width:500px; margin:0 auto; text-align:left; font-size:1.2em; line-height:1.8;">
            <p><strong>📞 Phone:</strong> +60 12-345 6789</p>
            <p><strong>✉ Email:</strong> djssupport@gmail.com</p>
        </div>
    </div>
</section>


<footer>
    <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
</footer>

<script>
    // Simplified smooth scrolling script 
    document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute("href"))
                .scrollIntoView({ behavior: "smooth" });
        });
    });
</script>

</body>
</html>
