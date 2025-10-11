<?php
session_start();
include "config.php";

// 检查用户是否登录
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    $user_id = 1; // 假设未登录用户ID为 1 进行测试
    // die("⚠️ Please log in first."); // 生产环境请启用这行
}

// --- Helper Function ---
function getImagePath($path) {
    $default = "uploads/default.png";
    if (!$path) return $default;
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path ?: $default;
}
// --- END Helper Function ---

$order_id = $_GET['id'] ?? 0;
$order_id = (int)$order_id;

if ($order_id === 0) {
    die("❌ Invalid order ID provided.");
}

// --- 1. 查询订单主信息 ---
$stmt = $conn->prepare("SELECT total, status, created_at FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order_result = $stmt->get_result();

if ($order_result->num_rows === 0) {
    die("❌ Order #{$order_id} not found or you lack permission to view it.");
}

$order = $order_result->fetch_assoc();
$current_status = $order['status'] ?: 'Pending';
$stmt->close();

// --- 2. 查询商品详情 ---
$item_query = "
    SELECT 
        oi.quantity, 
        oi.price, 
        gi.item_name, 
        gi.image 
    FROM order_items oi
    JOIN game_items gi ON oi.item_id = gi.item_id
    WHERE oi.order_id = ?
";
$item_stmt = $conn->prepare($item_query);
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items_result = $item_stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$item_stmt->close();

// 状态流程定义 (用于可视化)
$status_steps = [
    "Pending" => "Order Placed",
    "WAIT_FOR_PAYMENT" => "Payment Pending",
    "COMPLETE_PAYMENT" => "Payment Confirmed",
    "PROCESSING" => "Processing Order",
    "DELIVERED" => "Order Completed (Delivered)"
];

// 确定当前步骤的进度
$current_step_index = array_search(strtoupper($current_status), array_keys($status_steps));
if ($current_step_index === false) {
    $current_step_index = 0; // 默认回到开始
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order #<?= $order_id ?> Status</title>
<style>
body { 
    font-family: 'Inter', Arial, sans-serif; 
    background:#0a0a0a; 
    color:#fff; 
    margin:0; 
    padding:0;
}
.container {
    max-width: 900px;
    margin: 40px auto;
    background: #1c1c1c;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(255,102,0,0.3);
}
h1 { color:#ff6600; text-align: center; margin-bottom: 20px; }
h2 { color:#fff; border-bottom: 1px solid #333; padding-bottom: 10px; margin-top: 25px; }

/* 状态流程 CSS */
.timeline {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    position: relative;
    padding-bottom: 50px;
}
.timeline::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 10%;
    right: 10%;
    height: 3px;
    background: #333;
}
.step {
    text-align: center;
    position: relative;
    width: 20%;
    color: #888;
}
.step-icon {
    width: 30px;
    height: 30px;
    background: #333;
    border-radius: 50%;
    margin: 0 auto 10px;
    line-height: 30px;
    font-size: 16px;
    color: #ccc;
    z-index: 10;
    position: relative;
    transition: background 0.4s;
}
.step-text {
    font-size: 0.9em;
    font-weight: bold;
    margin-top: 15px;
}

/* 激活状态 */
.step.active .step-icon {
    background: #ff6600;
    color: #fff;
    box-shadow: 0 0 10px #ff6600;
}
.step.active .step-text {
    color: #fff;
}
.step.complete .step-icon {
    background: #28a745;
    color: #fff;
    box-shadow: 0 0 10px #28a745;
}
.step.complete .step-text {
    color: #28a745;
}

/* 订单详情 */
.summary-info {
    display: flex;
    justify-content: space-between;
    padding: 15px 0;
    border-bottom: 1px solid #333;
}
.summary-info span {
    font-weight: bold;
}
.item-list {
    margin-top: 20px;
}
.item-detail {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
    border-bottom: 1px solid #2a2a2a;
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
.item-price {
    font-weight: bold;
    color: #ff6600;
}
.back-btn {
    display: inline-block;
    background: #ff6600;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 30px;
    transition: background 0.3s;
}
.back-btn:hover {
    background: #e65c00;
}

</style>
</head>
<body>

<div class="container">
    <h1>Order Status: #<?= $order_id ?></h1>

    <!-- 状态追踪时间轴 -->
    <h2>Tracking Timeline</h2>
    <div class="timeline">
        <?php $step_counter = 0; ?>
        <?php foreach ($status_steps as $key => $label): ?>
            <?php 
                $class = '';
                if ($step_counter < $current_step_index) {
                    $class = 'complete'; // 已完成
                } elseif ($step_counter === $current_step_index) {
                    $class = 'active'; // 当前状态
                }
            ?>
            <div class="step <?= $class ?>">
                <div class="step-icon">
                    <?php 
                        if ($class === 'complete') {
                            echo '✔'; // Checkmark
                        } elseif ($class === 'active') {
                            echo '●'; // Dot or other indicator
                        } else {
                            echo ($step_counter + 1);
                        }
                    ?>
                </div>
                <div class="step-text"><?= $label ?></div>
            </div>
            <?php $step_counter++; ?>
        <?php endforeach; ?>
    </div>
    <p style="text-align: center; font-size: 1.2em; color: #00ff99;">
        Current Status: <b><?= htmlspecialchars($current_status) ?></b>
    </p>

    <!-- 订单摘要 -->
    <h2>Order Summary</h2>
    <div class="summary-info">
        <p>Order Date:</p>
        <span><?= htmlspecialchars($order['created_at']) ?></span>
    </div>
    <div class="summary-info">
        <p>Total Amount:</p>
        <span style="color: #ff6600;">RM <?= number_format($order['total'], 2) ?></span>
    </div>

    <!-- 商品列表 -->
    <h2>Items Purchased</h2>
    <div class="item-list">
        <?php foreach ($items as $item): ?>
            <div class="item-detail">
                <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                <div class="item-info">
                    <p><strong><?= htmlspecialchars($item['item_name']) ?></strong></p>
                    <p style="color: #ccc;">Qty: <?= $item['quantity'] ?> x RM <?= number_format($item['price'], 2) ?></p>
                </div>
                <p class="item-price">RM <?= number_format($item['quantity'] * $item['price'], 2) ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="my_order.php" class="back-btn">← Back to Orders List</a>
</div>

</body>
</html>
