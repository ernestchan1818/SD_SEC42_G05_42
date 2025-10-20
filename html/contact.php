<?php
session_start();
include "config.php";

// --- 辅助函数：处理图片路径和占位符 ---
function getImagePath($path) {
    $default = "https://placehold.co/60x60/333/fff?text=N/A";
    if (!$path) return $default;
    // 假设图片路径是相对路径，如果需要调整，请在这里修改
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path ?: $default;
}

// --- 1. 获取订单 ID 和用户 ID ---
$order_id = $_GET['order_id'] ?? $_SESSION['current_order_id'] ?? null;
// 优先使用订单创建时的 user_id，作为最可靠的查询条件
$user_id = $_SESSION['order_creator_id'] ?? $_SESSION['user_id'] ?? 1;

if (empty($user_id) || !is_numeric($user_id)) {
    die("⚠️ Please log in first.");
}

if (empty($order_id)) {
    die("❌ No order ID found. Please go back to the top-up page and select items first.");
}

// --- 2. 查询订单主信息 (orders 表) ---
$stmt_main = $conn->prepare("
    SELECT o.total, o.status, o.game_id, g.game_name
    FROM orders o
    LEFT JOIN games g ON o.game_id = g.game_id
    WHERE o.order_id = ? AND o.user_id = ?
");
if (!$stmt_main) die("Order Main Prepare Error: " . $conn->error);

$stmt_main->bind_param("ii", $order_id, $user_id);
$stmt_main->execute();
$order_data = $stmt_main->get_result()->fetch_assoc();
$stmt_main->close();

if (!$order_data) {
    die("❌ Order #{$order_id} not found for this user, or items are missing. (Attempted lookup with User ID: {$user_id})");
}

$total = $order_data['total'];
$status = $order_data['status'];
$game_name = $order_data['game_name'] ?? "Unknown Game";
$order_game_id = $order_data['game_id'];

$items = [];
$package_summary = null; 

// --- 3. 尝试查询订单明细 (order_items) ---
// ... (单品查询逻辑) ...
$stmt_items = $conn->prepare("
    SELECT 
        oi.item_name AS order_item_name, oi.quantity, oi.price, 
        gi.image, gi.item_name AS real_item_name 
    FROM order_items oi
    LEFT JOIN game_items gi ON oi.item_id = gi.item_id 
    WHERE oi.order_id = ?
");

if (!$stmt_items) die("Item Detail Prepare Error: " . $conn->error);

$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();

while ($row = $result_items->fetch_assoc()) {
    if ($row['order_item_name'] !== null || $row['real_item_name'] !== null) {
        $subtotal = $row['quantity'] * $row['price'];
        $display_name = $row['real_item_name'] ?? $row['order_item_name'];
        
        $items[] = [
            "name" => $display_name,
            "qty" => $row['quantity'],
            "price" => $row['price'],
            "subtotal" => $subtotal,
            "image" => getImagePath($row['image']),
            "is_package_item" => false 
        ];
    }
}
$stmt_items->close();


// --- 4. 如果没有找到 item (购买套餐)，则查询套餐详情及内含商品 ---
if (empty($items)) {
    // 1. 查询套餐主信息
    $pkg_stmt = $conn->prepare("
        SELECT package_name, image, discount, price AS list_price
        FROM topup_packages 
        WHERE package_id = ?
    ");
    
    if ($pkg_stmt) {
        $pkg_stmt->bind_param("i", $order_game_id);
        $pkg_stmt->execute();
        $pkg_data = $pkg_stmt->get_result()->fetch_assoc();
        $pkg_stmt->close();
        
        if ($pkg_data) {
            $package_summary = [
                "name" => $pkg_data['package_name'],
                "image" => getImagePath($pkg_data['image']),
                "discount" => $pkg_data['discount'],
                "list_price" => $pkg_data['list_price'], 
                "final_price" => $total 
            ];

            // 2. 查询套餐内的所有商品明细
            $items_in_pkg_query = "
                SELECT 
                    gi.item_name, 
                    gi.image, 
                    gi.price AS unit_price
                FROM package_items pi
                JOIN game_items gi ON pi.item_id = gi.item_id
                WHERE pi.package_id = ?
            ";

            $pkg_item_stmt = $conn->prepare($items_in_pkg_query);
            if ($pkg_item_stmt) {
                $pkg_item_stmt->bind_param("i", $pkg_data['package_id']);
                $pkg_item_stmt->execute();
                $pkg_items_result = $pkg_item_stmt->get_result();

                while ($item_row = $pkg_items_result->fetch_assoc()) {
                    // 3. 添加到 $items 数组作为子项目
                    $items[] = [
                        "name" => $item_row['item_name'],
                        "qty" => 1, 
                        "price" => $item_row['unit_price'],
                        "subtotal" => $item_row['unit_price'],
                        "image" => getImagePath($item_row['image']),
                        "is_package_item" => true 
                    ];
                }
                $pkg_item_stmt->close();
            }
        }
    }
}

$username = $_SESSION['username'] ?? "Demo User";
$total_formatted = number_format($total, 2);
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

<section class="hero">
    <div class="hero-text container">
        <h1>Contact Us</h1>
        <p>We’d love to hear from you. Reach us via phone or email below.</p>
        <h2>Our Contact Information</h2>
        <div class="contact-info-box" style="max-width:500px; margin:0 auto; text-align:left; font-size:1.2em; line-height:1.8;">
            <p><strong>📞 Phone:</strong> +60 12-345 6789</p>
            <p><strong>✉️ Email:</strong> djssupport@gmail.com</p>
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
