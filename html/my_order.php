<?php
session_start();
include "config.php";

// 检查用户是否登录
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    // 假设未登录用户ID为 1 进行测试
    $user_id = 1; 
}

$username = $_SESSION['username'] ?? "User #".$user_id;
$message = $_GET['message'] ?? ''; 
$error_message = $_GET['error'] ?? '';
$highlight_id = $_GET['id'] ?? null;

// --- Helper Function ---
function getImagePath($path) {
    $default = "uploads/default.png";
    if (!$path) return $default;
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path ?: $default;
}
// --- END Helper Function ---


// --- 1. 查询该用户的订单 (包含 game_id) ---
$stmt = $conn->prepare("SELECT order_id, total, status, created_at, game_id FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
$orderIds = [];
$gameIdsForPackageCheck = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
    $orderIds[] = $row['order_id'];
    if ($row['game_id']) { 
        $gameIdsForPackageCheck[] = $row['game_id'];
    }
}
$stmt->close();

// --- 2. 批量查询所有订单的商品明细 (非套餐商品) ---
$orderDetails = [];
if (!empty($orderIds)) {
    $idList = implode(',', $orderIds); 

    // 使用 LEFT JOIN 来适应可能没有 game_items 记录的情况（例如套餐占位符）
    $item_query = "
        SELECT 
            oi.order_id, 
            oi.quantity, 
            oi.price, 
            gi.item_name, 
            gi.image 
        FROM order_items oi
        LEFT JOIN game_items gi ON oi.item_id = gi.item_id
        WHERE oi.order_id IN ($idList)
    ";
    
    $item_res = $conn->query($item_query);

    while ($item_row = $item_res->fetch_assoc()) {
        $orderId = $item_row['order_id'];
        if (!isset($orderDetails[$orderId])) {
            $orderDetails[$orderId] = [];
        }
        $orderDetails[$orderId][] = $item_row;
    }
}

// --- 3. 批量查询套餐详情 (Package Summary) ---
$packageDetails = [];
if (!empty($gameIdsForPackageCheck)) {
    $uniquePkgIds = array_unique($gameIdsForPackageCheck); 
    $pkgIdList = implode(',', $uniquePkgIds);
    
    $pkg_query = "
        SELECT package_id, package_name, image, discount 
        FROM topup_packages 
        WHERE package_id IN ($pkgIdList)
    ";
    
    $pkg_res = $conn->query($pkg_query);
    while ($pkg_row = $pkg_res->fetch_assoc()) {
        $packageDetails[$pkg_row['package_id']] = $pkg_row;
    }
}

// --- 4. 批量查询套餐内含商品详情 (Package Contents) ---
$packageContents = [];
if (!empty($packageDetails)) {
    $pkgIdList = implode(',', array_keys($packageDetails)); 

    $content_query = "
        SELECT 
            pi.package_id,
            gi.item_name, 
            gi.image, 
            gi.price AS unit_price
        FROM package_items pi
        JOIN game_items gi ON pi.item_id = gi.item_id
        WHERE pi.package_id IN ($pkgIdList)
    ";
    
    $content_res = $conn->query($content_query);

    while ($content_row = $content_res->fetch_assoc()) {
        $pkgId = $content_row['package_id'];
        if (!isset($packageContents[$pkgId])) {
            $packageContents[$pkgId] = [];
        }
        $packageContents[$pkgId][] = $content_row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track My Orders - DJS Game</title>
<style>
body {
    background-color: #000;
    color: #fff;
    font-family: 'Inter', Arial, sans-serif;
    margin: 0;
    padding: 0;
}
/* 导航栏样式 */
.navbar {
    background: #111;
    padding: 15px 20px;
    display: flex;
    justify-content: flex-start; /* 导航链接靠左 */
    box-shadow: 0 2px 5px rgba(0,0,0,0.5);
}
.navbar a {
    color: #fff;
    text-decoration: none;
    margin-right: 25px;
    font-weight: 500;
    transition: color 0.3s;
}
.navbar a:hover {
    color: #ff6600;
}

header {
    background: #111;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header .logo {
    font-size: 24px;
    font-weight: bold;
    color: #ff6600;
}
.welcome {
    color: #aaa;
    font-size: 14px;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    border-radius: 10px;
}
h1 {
    text-align: center;
    color: #ff6600;
    margin-bottom: 25px;
}
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
    font-weight: bold;
}
.alert-success {
    background-color: #28a745;
    color: #fff;
}
.alert-error {
    background-color: #dc3545;
    color: #fff;
}
/* 卡片列表容器 */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 20px; 
}
/* 单个订单卡片 */
.order-card-wrapper {
    text-decoration: none; 
    color: inherit; 
}
.order-card {
    background: #1a1a1a;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    transition: transform 0.2s;
    cursor: pointer; 
}
.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255,102,0,0.5); 
}

/* 订单总结部分 (卡片头部) */
.order-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #222;
    border-bottom: 2px solid #333;
}
.order-info-group {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.order-id {
    font-size: 1.1em;
    font-weight: bold;
    color: #fff;
}
.order-date {
    font-size: 0.85em;
    color: #aaa;
    margin-top: 3px;
}
.order-total {
    font-size: 1.4em;
    font-weight: bold;
    color: #ff6600;
}

.status {
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
    font-size: 13px;
}
.status.complete_payment, .status.paid, .status.delivered {
    background-color: #218838; /* Green */
    color: #fff;
}
.status.pending, .status.wait_for_payment {
    background-color: #ff6600; /* Orange */
    color: #fff;
}
.empty {
    text-align: center;
    padding: 30px;
    font-size: 18px;
    color: #ccc;
}
.back-btn {
    display: block;
    text-align: center;
    background: #ff6600;
    color: #fff;
    padding: 12px 0;
    border-radius: 8px;
    width: 220px;
    margin: 25px auto 0;
    text-decoration: none;
    font-weight: bold;
    transition: background 0.3s;
}
.back-btn:hover {
    background: #e65c00;
}
/* 商品详情样式 (卡片主体) */
.item-details-box {
    padding: 15px 20px;
    text-align: left;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.item-detail {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 8px 0;
    border-bottom: 1px solid #2a2a2a;
}
.item-detail:last-child {
    border-bottom: none;
}
.item-detail img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
    flex-shrink: 0;
}
.item-info {
    flex-grow: 1;
}
.item-info p {
    margin: 0;
    font-size: 14px;
}
.item-price {
    font-weight: bold;
    color: #ff6600;
}
.highlight-card {
    border: 2px solid #00ff99; 
    box-shadow: 0 0 20px rgba(0,255,153,0.5);
}
/* 套餐特定样式 */
.package-badge {
    background: #444;
    color: #fff;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.75em;
    font-weight: normal;
    margin-left: 10px;
}
/* 套餐 Summary 样式 */
.package-summary-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    background: #331100; /* 深橙色背景 */
    border: 1px solid #ff6600;
    border-radius: 6px;
    padding: 15px;
}
.package-summary-item img {
    width: 60px; 
    height: 60px;
    border-radius: 4px;
}
.package-summary-info {
    flex-grow: 1;
}
.package-summary-info p {
    margin: 2px 0;
    font-size: 14px;
}
.package-summary-price {
    font-weight: bold;
    color: #ff6600;
    font-size: 1.1em;
}
/* 套餐内含项目列表 */
.package-item-list-box {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #444;
}
.pkg-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85em;
    padding: 5px 0;
    color: #ccc;
}
.pkg-item-row img {
    width: 30px;
    height: 30px;
    border-radius: 4px;
}

/* 响应式调整 */
@media (max-width: 600px) {
    .order-summary {
        flex-direction: column;
        align-items: stretch;
    }
    .order-total {
        text-align: right;
    }
}
</style>
</head>
<body>

<!-- 导航栏 -->
<div class="navbar">
    <a href="home.php">Home</a>
    <a href="my_order.php" style="color:#ff6600;">Track Orders</a>
    <a href="about.html">About</a>
    <a href="Contact.php">Contact</a>
    <a href="Feedback.php">Feedback</a>
    <a href="view_games.php">Top-Up Games</a>
    <a href="view_packages.php">Top-Up Packages</a>
</div>

<header>
    <div class="logo">🎮 DJS Game</div>
    <div class="welcome">Welcome, <?= htmlspecialchars($username) ?></div>
</header>

<div class="container">
    <h1>Your Order History</h1>
    
    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-error">❌ <?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($orders)): ?>
    <div class="order-list">
        <?php foreach ($orders as $row): 
            $status = $row['status'] ?: 'Pending'; 
            $statusClass = strtolower(str_replace(' ', '_', $status)); 
            $is_highlighted = (string)$row['order_id'] === $highlight_id ? 'highlight-card' : '';
            $is_package = !isset($orderDetails[$row['order_id']]) && $row['game_id'] > 0;
            $package_id = $row['game_id'];
            $pkg_detail = $is_package ? ($packageDetails[$package_id] ?? null) : null;
            $package_contents = $is_package ? ($packageContents[$package_id] ?? []) : [];
        ?>
        
        <!-- 整个卡片现在是可点击的链接 -->
        <a href="order_status.php?id=<?= $row['order_id'] ?>" class="order-card-wrapper">
            <div class="order-card <?= $is_highlighted ?>">
                
                <!-- 订单总结 (Order Summary) -->
                <div class="order-summary">
                    <div class="order-info-group">
                        <span class="order-id">Order #<?= htmlspecialchars($row['order_id']) ?></span>
                        <span class="order-date">Date: <?= htmlspecialchars($row['created_at']) ?></span>
                    </div>
                    
                    <div style="text-align: right;">
                        <span class="order-total">RM <?= number_format($row['total'], 2) ?></span>
                        <span class="status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                    </div>
                </div>
                
                <!-- 商品详情 (Item Details) -->
                <div class="item-details-box">
                    <?php 
                    // --- 场景 1: 显示套餐详情 ---
                    if ($is_package && $pkg_detail): ?>
                        <div class="package-summary-item">
                            <img src="<?= htmlspecialchars(getImagePath($pkg_detail['image'])) ?>" alt="<?= htmlspecialchars($pkg_detail['package_name']) ?>">
                            <div class="package-summary-info">
                                <p><strong><?= htmlspecialchars($pkg_detail['package_name']) ?></strong> <span class="package-badge">Package</span></p>
                                <p style="color: #ccc;">Discount: <?= number_format($pkg_detail['discount'], 2) ?>%</p>
                            </div>
                            <p class="package-summary-price">RM <?= number_format($row['total'], 2) ?></p>
                        </div>
                        
                        <div class="package-item-list-box">
                            <p style="font-weight: bold; font-size: 0.9em; color: #aaa;">Items Contained:</p>
                            <?php if (!empty($package_contents)): ?>
                                <?php foreach ($package_contents as $item): ?>
                                    <div class="pkg-item-row">
                                        <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                                        <div style="flex-grow: 1;"><?= htmlspecialchars($item['item_name']) ?></div>
                                        <span style="color: #ff6600;">RM <?= number_format($item['unit_price'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #999; font-size: 0.9em;">No item details linked to this package.</p>
                            <?php endif; ?>
                        </div>
                    <?php 
                    // --- 场景 2: 显示单品详情 ---
                    elseif (isset($orderDetails[$row['order_id']]) && !empty($orderDetails[$row['order_id']])): ?>
                        <?php foreach ($orderDetails[$row['order_id']] as $item): ?>
                            <div class="item-detail">
                                <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                                <div class="item-info">
                                    <p><strong><?= htmlspecialchars($item['item_name']) ?></strong></p>
                                    <p style="color: #ccc;">Qty: <?= $item['quantity'] ?> x RM <?= number_format($item['price'], 2) ?></p>
                                </div>
                                <p class="item-price">RM <?= number_format($item['quantity'] * $item['price'], 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php 
                    // --- 场景 3: 没有任何详情 ---
                    else: ?>
                        <div style="color: #999; text-align: center;">No item details found for this order.</div>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="empty">You have no orders yet. Go top-up some games!</div>
    <?php endif; ?>

    <a href="home.php" class="back-btn">⬅ Back to Home/Shop</a>
</div>

</body>
</html>
