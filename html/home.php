<?php
session_start();
require "config.php"; // 确保有数据库连接

// 默认访客
$username = "Guest";
$avatar = "";

// 如果用户已登录
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // 查询用户名和头像
    $sql = "SELECT username, avatar FROM users WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $username = $user['username'];
        $avatar = $user['avatar']; // 可以是上传过的路径
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DJS Game</title>
    <style>
header { 
    background: linear-gradient(90deg, #0b0b0b, #2a2a2a 40%, #0b0b0b); /* 黑灰渐变 */
    padding: 14px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #c75c2b; /* 底部橙色线条 */
    box-shadow: 0 3px 12px rgba(199,92,43,0.2); /* 橙色柔光 */
}

/* logo */
header .logo { 
    font-size: 22px; 
    font-weight: bold; 
    color: #ff6600;  /* 亮橙 logo */
    letter-spacing: 1px;
}

/* 导航链接 */
header nav a { 
    color: #eee; 
    margin: 0 18px; 
    text-decoration: none; 
    font-weight: 500; 
    position: relative;
    transition: color 0.3s ease; 
}

/* 下划线 hover 动画 */
header nav a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -6px;
    width: 100%;
    height: 2px;
    background: #c75c2b;
    transform: scaleX(0);
    transition: transform 0.3s ease;
    transform-origin: right;
}

header nav a:hover {
    color: #c75c2b; 
}

header nav a:hover::after {
    transform: scaleX(1);
    transform-origin: left;
}</style>
    <link rel="stylesheet" href="../css/home.css">
     <style>
        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 20px;
        }
        .nav-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        </style>
</head>
<body>

    <header>
        <div class="logo">🎮 DJS Game</div>
         <div class="nav-user">
                <?php if (!empty($avatar)): ?>
                    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                <?php endif; ?>
                <span>Welcome！！<?php echo htmlspecialchars($username); ?></span>
            </div>
        <nav>
    <a href="profile.php">Profile</a>
            <a href="home.php">Home</a>
            <a href="about.html">About</a>
            <a href="Contact.php">Contact</a>
            <a href="Feedback.php">Feedback</a>
            <a href="view_games.php">Top-Up Games</a>
            <a href="view_packages.php">Top-Up Packages</a>
            <a href="my_order.php">Track</a>

    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- 登录状态 -->
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <!-- 访客状态 -->
        <a href="signin.php">Sign In</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</nav>
    </header>
    
    <section id="home" class="hero">
        <img src="../image/backgroundhome.png" alt="Game Background" class="bg-image">
        <div class="hero-text">
            <h1>Welcome to DJS Game</h1>
            <p>Your ultimate gaming experience awaits!</p>
        </div>
    </section>

    <section id="topup" class="topup-games">
        <h2>🔥 Popular Top-Up Games</h2>
        <div class="game-list">
            <div class="game-card">
                <img src="../image/genshin.webp" alt="Genshin Impact">
                <h3>Genshin Impact</h3>
            </div>
            <div class="game-card">
                <img src="../image/hsr.png" alt="Honkai Star Rail">
                <h3>Honkai: Star Rail</h3>
            </div>
            <div class="game-card">
                <img src="../image/wuwa.png" alt="Wuthering Waves">
                <h3>Wuthering Waves</h3>
            </div>
        </div>
    </section>

    <section class="payment">
        <h2>We Accept</h2>
        <div class="payment-logos">
            <img src="../image/mastercard.webp" alt="Mastercard">
            <img src="../image/visa.png" alt="Visa">
            <img src="../image/tng.jpg" alt="TnG">
        </div>
    </section>

    <footer>
        <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
    </footer>

    <script>
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
