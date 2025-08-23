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
            <a href="contact.php">Contact</a>
            <a href="#topup">Top-Up Games</a>
            <a href="signin.php">Sign In</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <section id="home" class="hero">
        <img src="../image/homebg.webp" alt="Game Background" class="bg-image">
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
