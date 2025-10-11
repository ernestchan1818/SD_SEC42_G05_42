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
$user_id = $_SESSION['user_id'] ?? 1; // 假设未登录用户ID为 1 进行测试

if (empty($user_id) || !is_numeric($user_id)) {
    die("⚠️ Please log in first.");
}

if (empty($order_id)) {
    die("❌ No order ID found. Please go back to the top-up page and select items first.");
}

// --- 2. 从数据库查询该订单的详细信息（包含图片和真实商品名称） ---
$stmt = $conn->prepare("
    SELECT 
        o.total, o.status, g.game_name, 
        oi.item_name AS order_item_name, oi.quantity, oi.price, /* 保留 order_items 里的名称作为备用 */
        gi.image,  
        gi.item_name AS real_item_name /* ✅ 关键修正：从 game_items 获取商品名称 */
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN games g ON o.game_id = g.game_id
    LEFT JOIN game_items gi ON oi.item_id = gi.item_id 
    WHERE o.order_id = ? AND o.user_id = ?
");

if (!$stmt) die("Database Prepare Error: " . $conn->error);

// ⚠️ 关键点：假设 order_id 是 INT 类型，如果你的 order_id 是字符串，请将 "ii" 改为 "si"
$stmt->bind_param("ii", $order_id, $user_id); 
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("❌ Order #{$order_id} not found for this user, or items are missing.");
}

$items = [];
$total = 0;
$status = "Unknown";
$game_name = "N/A";

// 遍历结果集，构建订单详情
while ($row = $result->fetch_assoc()) {
    $game_name = $row['game_name'] ?? "Unknown Game";
    $status = $row['status'];
    
    $subtotal = $row['quantity'] * $row['price'];
    $total += $subtotal;
    
    // ✅ 修复 item name 逻辑：优先使用 game_items 表中的名称
    $display_name = $row['real_item_name'] ?? $row['order_item_name'];
    
    $items[] = [
        "name" => $display_name,
        "qty" => $row['quantity'],
        "price" => $row['price'],
        "subtotal" => $subtotal,
        "image" => getImagePath($row['image']) 
    ];
}

$username = $_SESSION['username'] ?? "Demo User";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - DJS Game</title>
<style>
body {
    background:#000;
    color:#fff;
    font-family: Arial, sans-serif;
    margin:0;
    padding:0;
}
header {
    background:#111;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
header .logo {
    font-size:22px;
    font-weight:bold;
    color:#ff6600;
}
.container {
    max-width:800px;
    margin:30px auto;
    background:#1a1a1a;
    padding:20px;
    border-radius:10px;
    box-shadow:0 6px 20px rgba(255,102,0,0.3);
}
h1 {
    text-align:center;
    color:#ff6600;
}
.order-info {
    margin-bottom:20px;
    padding:10px;
    border-bottom:1px solid #333;
}
.order-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin:10px 0;
    background:#222;
    padding:10px;
    border-radius:8px;
}
.order-item img {
    width:60px; 
    height:60px;
    border-radius:6px;
    margin-right:15px;
    object-fit: cover;
}
.item-details { flex-grow:1; margin-left: 10px; }
.item-details p { margin:2px 0; }
.total-box {
    font-size:20px;
    font-weight:bold;
    text-align:right;
    margin:15px 0;
    color:#ff6600;
}
.payment-method {
    margin:20px 0;
}
.status {
    margin:15px 0;
    font-weight:bold;
    text-align: center;
    padding: 10px;
    border-radius: 6px;
}
.status-Pending {
    color: #ffcc00; /* 黄色 */
    background: rgba(255, 204, 0, 0.1);
}
.status-WAIT_FOR_PAYMENT, .status-Unknown {
    color: #ff6600; /* 橙色 */
    background: rgba(255, 102, 0, 0.1);
}
.status-COMPLETE_PAYMENT {
    color: #00ff99; /* 绿色 */
    background: rgba(0, 255, 153, 0.1);
}

.pay-btn {
    background:#ff6600;
    border:none;
    padding:12px 20px;
    font-size:18px;
    border-radius:8px;
    color:white;
    cursor:pointer;
    width:100%;
}
.pay-btn:hover { background:#e65c00; }
</style>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <a href="home.php" style="color:white;">Home</a>
    </nav>
</header>

<div class="container">
    <h1>Order Summary & Payment</h1>
    <div class="order-info">
        <p><strong>Order ID:</strong> <span style="color: #00ff99;">#<?= htmlspecialchars($order_id) ?></span></p>
        <p><strong>User:</strong> <?= htmlspecialchars($username) ?></p>
        <p><strong>Game:</strong> <?= htmlspecialchars($game_name) ?></p>
    </div>

    <h2>Order Details</h2>
    <?php if (!empty($items)): ?>
        <?php foreach($items as $it): ?>
        <div class="order-item">
            <!-- ✅ 显示图片和商品名称 -->
            <img src="<?= htmlspecialchars($it['image']) ?>" alt="<?= htmlspecialchars($it['name']) ?>">
            <div class="item-details">
                <p><strong><?= htmlspecialchars($it['name']) ?></strong></p>
                <p style="font-size: 0.9em; color: #ccc;">Qty: <?= $it['qty'] ?> × RM <?= number_format($it['price'],2) ?></p>
            </div>
            <p><strong>RM <?= number_format($it['subtotal'],2) ?></strong></p>
        </div>
        <?php endforeach; ?>
        <div class="total-box">Total: RM <?= number_format($total,2) ?></div>
    <?php else: ?>
        <p style="text-align: center; color: yellow;">No items found in this order ID.</p>
    <?php endif; ?>

    <div class="payment-method">
        <p><strong>Choose Payment Method:</strong></p>
        <label><input type="radio" name="payment" value="TouchNGo" checked> Touch 'n Go</label>
        <label><input type="radio" name="payment" value="FPX"> FPX Bank</label>
    </div>

    <!-- ✅ 显示订单状态，并添加样式 -->
    <?php $status_class = "status-" . str_replace(" ", "_", $status); ?>
    <div class="status <?= htmlspecialchars($status_class) ?>">
        Current Status: **<?= htmlspecialchars($status) ?>**
    </div>

    <!-- 付款按钮 -->
    <a href="https://payment.tngdigital.com.my/sc/bDLoiwKBF4" target="_blank" class="pay-btn" style="text-decoration: none; display: block; text-align: center;">
        Pay with Touch 'n Go (RM <?= number_format($total,2) ?>)
    </a>

    <!-- 已付款按钮 (提交给 confirm_payment.php) -->
    <form action="confirm_payment.php" method="POST" style="margin-top:20px;">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
        <input type="hidden" name="action" value="confirm">
        <button type="submit" style="padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:6px; font-size:16px; width: 100%; cursor: pointer;">
            ✅ I have paid, Confirm Order
        </button>
    </form>

</div>
</body>
</html>
