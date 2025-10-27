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
    <title>DJS Game</title>
    <style>
/* --- Global & Base Styles --- */
body { 
    font-family: 'Inter', Arial, sans-serif; 
    background: #0a0a0a; /* 深黑背景 */
    color: #eee;
    margin: 0;
    padding: 0;
    min-height: 100vh;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* --- Header & Navigation --- */
header { 
    background: linear-gradient(90deg, #0b0b0b, #2a2a2a 40%, #0b0b0b); /* 黑灰渐变 */
    padding: 14px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid #ff6600; /* 底部橙色线条更突出 */
    box-shadow: 0 3px 15px rgba(255,102,0,0.5); /* 橙色柔光更强 */
    position: sticky;
    top: 0;
    z-index: 1000;
}

/* Logo and Header Left Content Container */
.header-left {
    display: flex;
    align-items: center;
    gap: 15px; /* Logo and image spacing */
}

/* Logo */
header .logo { 
    font-size: 26px; /* 字体更大 */
    font-weight: bold; 
    color: #ff6600; 
    letter-spacing: 2px;
    text-shadow: 0 0 5px rgba(255, 102, 0, 0.7);
}
/* New Image Style (用于 Hero Text 内) */
.hero-image-logo {
    height: 80px; /* 调整 Hero 区图片大小 */
    width: auto;
    filter: drop-shadow(0 0 10px rgba(255, 102, 0, 0.8)); /* 霓虹效果更强 */
    vertical-align: middle;
    margin-left: 20px;
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

/* Nav Links */
header nav {
    display: flex;
    align-items: center;
    gap: 18px;
}

header nav a { 
    color: #eee; 
    text-decoration: none; 
    font-weight: 500; 
    padding: 5px 0;
    position: relative;
    transition: color 0.3s ease; 
}

header nav a:hover {
    color: #ffaa00; 
    text-shadow: 0 0 8px #ffaa00;
}

/* --- Hero Section (Techno Look) --- */
.hero {
    position: relative;
    height: 60vh;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    border-bottom: 8px solid #ff6600;
}

.bg-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    /* 修正：移除模糊，降低暗化 */
    filter: brightness(0.8); 
    z-index: 1;
}

.hero-text {
    position: relative;
    z-index: 2;
    padding: 30px 40px;
    background: rgba(0, 0, 0, 0.7); /* 稍微降低透明度，让背景更可见 */
    border-radius: 15px;
    border: 3px solid #ff6600;
    box-shadow: 0 0 60px rgba(255, 102, 0, 1.0);
    animation: neonPulse 2.5s infinite alternate;
}
@keyframes neonPulse {
    from { box-shadow: 0 0 25px rgba(255, 102, 0, 0.9); }
    to { box-shadow: 0 0 50px rgba(255, 102, 0, 1.4); }
}

.hero-text h1 {
    font-family: 'Consolas', monospace; /* 修正：使用科技感字体 */
    font-size: 5.2em; /* 字体更大 */
    font-weight: 900; /* 加粗 */
    color: #fff;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 7px; /* 增加字母间距 */
    text-shadow: 0 0 25px #ff3300, 0 0 10px #ff6600, 0 0 2px #fff; /* 霓虹效果更明显 */
    display: inline; 
    vertical-align: middle;
}
.hero-text p {
    font-family: 'Consolas', monospace; /* 修正：使用科技感字体 */
    font-size: 1.8em;
    font-weight: 500;
    color: #ffaa00;
    text-shadow: 0 0 5px #ffaa00;
    letter-spacing: 1px;
}

/* --- Top-Up Games Section --- */
.topup-games {
    padding: 60px 20px;
    text-align: center;
}
.topup-games h2 {
    font-size: 2.5em;
    color: #ff6600;
    margin-bottom: 40px;
    border-bottom: 3px solid #444;
    display: inline-block;
    padding-bottom: 5px;
}

.game-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.game-card {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 12px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #333;
}
.game-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(255, 102, 0, 0.8);
    border-color: #ff6600;
}
.game-card img {
    width: 100%;
    height: auto;
    border-radius: 8px;
    margin-bottom: 15px;
}
.game-card h3 {
    color: #ff6600;
    font-size: 1.5em;
    margin: 0;
}

/* --- Payment Section --- */
.payment {
    padding: 60px 20px;
    text-align: center;
    background: #111;
    border-top: 3px solid #ff6600;
}
.payment h2 {
    color: #fff;
    margin-bottom: 30px;
    font-size: 2em;
}
.payment-logos {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 40px;
}
.payment-logos img {
    height: 60px;
    width: auto;
    filter: grayscale(10%); 
    opacity: 0.8;
    transition: filter 0.3s, opacity 0.3s;
}
.payment-logos img:hover {
    filter: none;
    opacity: 1;
}

/* --- Footer --- */
footer {
    background: #0b0b0b;
    padding: 25px;
    text-align: center;
    color: #555;
    font-size: 1em;
    border-top: 1px solid #333;
}
    </style>
</head>
<body>

    <header>
        <div class="header-left">
             <!-- Logo -->
            <div class="logo">🎮 DJS Game</div>
        </div>
        
        <div style="display:flex; align-items:center;">
            <div class="nav-user">
                <?php if ($is_logged_in): ?>
                    <img src="<?php echo htmlspecialchars(getImagePath($avatar)); ?>" alt="Avatar">
                    <span>Welcome！！<?php echo htmlspecialchars($username); ?></span>
                <?php endif; ?>
            </div>

            <nav>

                <a href="index.php">Home</a>
                <a href="about.html">About</a>
                <a href="contact.php">Contact</a>
                <a href="Feedback.php">Feedback</a>
                <a href="view_games.php">Top-Up Games</a>
                <a href="view_packages.php">Top-Up Packages</a>

                <?php if ($is_logged_in): ?>
                    <a href="my_order.php">Track</a>
                    <a href="logout.php" style="color: #ff6600;">Logout</a>
                <?php else: ?>
                    <a href="signin.php">Sign In</a>
                    <a href="register.php">Register</a>
                    <a href="../Admin & Staff/signinS.php">For Staff and Admin </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section id="home" class="hero">
        <img src="../Guest/image/backgroundhome.png" alt="Game Background" class="bg-image">
        <div class="hero-text">
            <!-- 修正后的 Hero H1 和 Image 结构 -->
            <div>
                <h1>Welcome to DJS Game</h1><img src="../Guest/image/logo.png" alt="DJS Logo Element" class="hero-image-logo">
            </div>
            <p>Your ultimate gaming experience awaits!</p>
        </div>
    </section>

    <section id="topup" class="topup-games container">
        <h2>🔥 Popular Top-Up Games</h2>
        <div class="game-list">
            <!-- Game Card Mockups (These should be dynamically loaded in a real app) -->
            <div class="game-card">
                <img src="../Guest/image/genshin.webp" alt="Genshin Impact">
                <h3>Genshin Impact</h3>
            </div>
            <div class="game-card">
                <img src="../Guest/image/hsr.png" alt="Honkai Star Rail">
                <h3>Honkai: Star Rail</h3>
            </div>
            <div class="game-card">
                <img src="../Guest/image/wuwa.png" alt="Wuthering Waves">
                <h3>Wuthering Waves</h3>
            </div>
        </div>
    </section>

    <section class="payment">
        <div class="container">
            <h2>We Accept</h2>
            <div class="payment-logos">
                <!-- Payment Logos Mockups -->
                <img src="../Guest/image/mastercard.webp" alt="Mastercard">
                <img src="../Guest/image/visa.png" alt="Visa">
                <img src="../Guest/image/tng.jpg" alt="TnG">
            </div>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
    </footer>

    <script>
        // Smooth scrolling is not functional with sticky headers and hash links without extra JS.
        // I've kept the general structure but the link behavior may need a full library like GSAP to be truly smooth.
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
