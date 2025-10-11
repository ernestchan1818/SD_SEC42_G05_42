<?php
session_start();
include "config.php";

// 检查用户是否登录
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    $user_id = 1; // 假设未登录用户ID为 1 进行测试
    // die("You must log in first to view your orders."); // 生产环境请启用这行
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


// --- 1. 查询该用户的订单 ---
$stmt = $conn->prepare("SELECT order_id, total, status, created_at FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
$orderIds = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
    $orderIds[] = $row['order_id'];
}
$stmt->close();

// --- 2. 批量查询所有订单的商品详情 ---
$orderDetails = [];
if (!empty($orderIds)) {
    // 将 order ID 数组转换为逗号分隔的字符串
    $idList = implode(',', $orderIds); 

    // 使用 JOIN 语句一次性获取所有订单的商品明细、名称和图片
    $item_query = "
        SELECT 
            oi.order_id, 
            oi.quantity, 
            oi.price, 
            gi.item_name, 
            gi.image 
        FROM order_items oi
        JOIN game_items gi ON oi.item_id = gi.item_id
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
header {
    background: #111;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 5px rgba(0,0,0,0.5);
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
/* 新增：卡片列表容器 */
.order-list {
    display: flex;
    flex-direction: column;
    gap: 20px; /* 卡片之间的间距 */
}
/* 新增：单个订单卡片 */
.order-card-wrapper {
    text-decoration: none; /* 移除链接下划线 */
    color: inherit; /* 继承颜色 */
}
.order-card {
    background: #1a1a1a;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    transition: transform 0.2s;
    cursor: pointer; /* 添加指针以提示可点击 */
}
.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255,102,0,0.5); /* 悬停时更明显的高亮 */
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
.status.complete_payment, .status.paid {
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
    border: 2px solid #00ff99; /* 成功绿色边框 */
    box-shadow: 0 0 20px rgba(0,255,153,0.5);
}

/* 响应式调整 */
@media (max-width: 600px) {
    .order-summary {
        flex-direction: column;
        align-items: stretch;
    }
    .order-summary > div {
        margin-bottom: 10px;
    }
    .order-summary > div:last-child {
        margin-bottom: 0;
    }
    .order-total {
        text-align: right;
    }
    .item-detail {
        flex-wrap: wrap;
    }
    .item-info {
        flex-basis: 100%;
    }
}
</style>
</head>
<body>

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
                    <?php if (isset($orderDetails[$row['order_id']]) && !empty($orderDetails[$row['order_id']])): ?>
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
                    <?php else: ?>
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
