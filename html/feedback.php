<?php
session_start();
include "config.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $msg = trim($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)");
    $stmt->bind_param("sss", $name, $email, $msg);

    if ($stmt->execute()) {
        $message = "Your message has been sent! We will get back to you soon.";
    } else {
        $message = "Failed to send your message. Try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - DJS Game</title>
    <style>
/* --- 全局样式和背景 --- */
body {
    font-family: 'Inter', Arial, sans-serif;
    margin: 0;
    padding: 0;
    color: #e0e0e0; /* 默认文字为亮灰色 */
    min-height: 100vh; /* 确保 body 至少占满视口高度 */
    display: flex;
    flex-direction: column; /* 让 header, content, footer 垂直排列 */
}

/* 页面背景图及其覆盖层 */
.background-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -2; /* 在内容下方 */
    background-image: url("../image/SendFeedback.jpg"); /* 假设您的图片路径 */
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    filter: brightness(0.7) blur(2px); /* 调暗并模糊背景 */
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1; /* 在背景图和内容之间 */
    background-color: rgba(0, 0, 0, 0.6); /* 额外一层深色覆盖，增加对比度 */
}

/* --- 导航栏样式 (保持一致性) --- */
header {
    background: rgba(17, 17, 17, 0.95); /* 深色半透明背景 */
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    position: sticky; 
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

/* 下划线效果 */
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

/* --- Hero Section --- */
.contact-hero {
    background: rgba(0, 0, 0, 0.5); /* 标题区域半透明背景 */
    padding: 60px 20px 30px;
    text-align: center;
    color: #fff;
    margin-bottom: 30px; /* 与表单区域保持距离 */
}
.contact-hero h1 {
    font-size: 3.5em; /* 标题更大 */
    color: #ff6600;
    margin-bottom: 10px;
    text-shadow: 0 0 15px rgba(255, 102, 0, 0.7); /* 标题发光 */
}
.contact-hero p {
    font-size: 1.2em;
    color: #cfcfcf;
    max-width: 600px;
    margin: 0 auto;
}

/* --- Form Section --- */
.main-content {
    flex-grow: 1; /* 让主内容区域占据剩余空间 */
    display: flex;
    justify-content: center;
    align-items: flex-start; /* 表单靠顶部对齐 */
    padding: 20px;
}

.contact-form {
    background: rgba(26, 26, 26, 0.9); /* 表单背景深色半透明 */
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 0 30px rgba(255, 102, 0, 0.4); /* 橙色发光阴影更强 */
    max-width: 500px;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 18px; /* 增加间距 */
    animation: fadeIn 1s ease-out; /* 添加淡入动画 */
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.contact-form label {
    font-weight: bold;
    color: #ff8800; /* 标签颜色更亮 */
    margin-top: 5px;
    font-size: 1.1em;
}

.contact-form input[type="text"],
.contact-form input[type="email"],
.contact-form textarea {
    padding: 14px; /* 输入框更大 */
    border: 1px solid #555;
    border-radius: 8px;
    background: #3a3a3a;
    color: #e0e0e0;
    font-size: 17px;
    transition: border-color 0.3s, box-shadow 0.3s;
    resize: vertical;
}
.contact-form input:focus,
.contact-form textarea:focus {
    border-color: #ff6600;
    outline: none;
    box-shadow: 0 0 10px rgba(255, 102, 0, 0.6); /* 焦点时发光更明显 */
}

.contact-form .btn {
    background: #ff6600;
    color: #fff;
    padding: 15px 25px;
    border: none;
    border-radius: 8px;
    font-size: 19px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
    margin-top: 25px;
    box-shadow: 0 4px 15px rgba(255, 102, 0, 0.4);
}
.contact-form .btn:hover {
    background: #e65c00;
    transform: translateY(-2px); /* 悬停时稍微上浮 */
}

/* Success/Error Message */
.message {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: bold;
    text-align: center;
    font-size: 1.1em;
    box-shadow: 0 2px 10px rgba(0,0,0,0.3);
}
.message.success {
    background: #28a745; /* 绿色 */
    color: #fff;
}
.message.error {
    background: #dc3545; /* 红色 */
    color: #fff;
}

/* --- Footer --- */
footer {
    background: rgba(17, 17, 17, 0.95); /* 半透明深色背景 */
    text-align: center;
    padding: 20px;
    color: #ccc;
    font-size: 0.9em;
    border-top: 1px solid #333;
    margin-top: auto; /* 将 footer 推到页面底部 */
}
</style>
</head>
<body>
    <div class="background-wrapper"></div>
    <div class="overlay"></div>

    <header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="about.html">About</a>
            <a href="Contact.php">Contact</a>
            <a href="Feedback.php">Feedback</a>
            <a href="view_games.php">Top-Up Games</a>
            <a href="view_packages.php">Top-Up Packages</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="signin.php">Sign In</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="contact-hero">
        <h1>Send Feedback</h1>
        <p>We’d love to hear from you! Fill out the form below and we’ll get back to you as soon as possible.</p>
    </section>

    <div class="main-content">
        <div class="content-wrapper">
            <?php if ($message): ?>
                <div class="message <?= strpos($message, 'Failed') !== false ? 'error' : 'success' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form action="#" method="post" class="contact-form">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Your Name" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Your Email" required>

                <label for="message">Message</label>
                <textarea id="message" name="message" rows="6" placeholder="Your Message" required></textarea>

                <button type="submit" class="btn">Send Message</button>
            </form>
        </div>
    </div>

    <footer>
        &copy; 2025 DJS Game. All rights reserved.
    </footer>
</body>
</html>
