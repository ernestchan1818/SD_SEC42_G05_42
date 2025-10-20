<?php
session_start();
include "config.php";

// 检查用户是否登录
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    // 假设未登录用户ID为 1 进行测试
    $user_id = 1; 
    // die("⚠️ Please log in first."); // 生产环境请启用这行
}

// --- Helper Function ---
function getImagePath($path) {
    // 默认图片路径
    $default = "uploads/default.png";
    if (!$path) return $default;
    // 检查路径是否包含 uploads/，如果包含则截取相对路径
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path ?: $default;
}
// --- END Helper Function ---

$order_id = $_GET['id'] ?? 0;
// 强制转换为整数，确保数据库查询安全
$order_id = (int)$order_id;
$error_message = '';
$order_game_id = 0; // 用于存储 orders.game_id, 可能是 package_id

// --- 1. 查询订单主信息 ---
if ($order_id === 0) {
    $error_message = "Invalid order ID provided. Please go back to the orders list.";
} else {
    $stmt = $conn->prepare("SELECT total, status, created_at, game_id FROM orders WHERE order_id = ? AND user_id = ?");
    
    if (!$stmt) {
        $error_message = "Database Prepare Error: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order_result = $stmt->get_result();

        if ($order_result->num_rows === 0) {
            $error_message = "Order #{$order_id} not found or you lack permission to view it.";
        } else {
            $order = $order_result->fetch_assoc();
            $current_status = strtoupper($order['status']) ?: 'PENDING';
            $order_game_id = $order['game_id']; // 获取 game_id
            $stmt->close();

            // --- 2. 尝试查询单品商品详情 (如果为空，则为套餐) ---
            $item_query = "
                SELECT 
                    oi.quantity, 
                    oi.price, 
                    gi.item_name, 
                    gi.image 
                FROM order_items oi
                LEFT JOIN game_items gi ON oi.item_id = gi.item_id
                WHERE oi.order_id = ?
            ";
            $item_stmt = $conn->prepare($item_query);
            $item_stmt->bind_param("i", $order_id);
            $item_stmt->execute();
            $items_result = $item_stmt->get_result();
            $items = [];
            while ($row = $items_result->fetch_assoc()) {
                 // 只保留有名称的行 (排除 LEFT JOIN 带来的 NULL 行)
                if ($row['item_name'] !== null) {
                    $items[] = $row;
                }
            }
            $item_stmt->close();
            
            // --- 3. 如果 items 为空，查询套餐详情 ---
            $package_details = null;
            $package_contents = [];
            if (empty($items) && $order_game_id > 0) {
                // 查询套餐主信息
                $pkg_stmt = $conn->prepare("SELECT package_name, image, discount FROM topup_packages WHERE package_id = ?");
                if ($pkg_stmt) {
                    $pkg_stmt->bind_param("i", $order_game_id);
                    $pkg_stmt->execute();
                    $package_details = $pkg_stmt->get_result()->fetch_assoc();
                    $pkg_stmt->close();

                    // 查询套餐内含商品
                    if ($package_details) {
                        $content_query = $conn->prepare("
                            SELECT gi.item_name, gi.image, gi.price AS unit_price
                            FROM package_items pi
                            JOIN game_items gi ON pi.item_id = gi.item_id
                            WHERE pi.package_id = ?
                        ");
                        if ($content_query) {
                            $content_query->bind_param("i", $order_game_id);
                            $content_query->execute();
                            $content_result = $content_query->get_result();
                            while ($row = $content_result->fetch_assoc()) {
                                $package_contents[] = $row;
                            }
                            $content_query->close();
                        }
                    }
                }
            }
        }
    }
}

// 状态流程定义 (用于可视化)
$status_steps = [
    "PENDING" => "Order Placed",
    "WAIT_FOR_PAYMENT" => "Payment Pending",
    "COMPLETE_PAYMENT" => "Payment Confirmed",
    "PROCESSING" => "Processing Order",
    "DELIVERED" => "Order Completed (Delivered)"
];

// 确定当前步骤的进度
if (empty($error_message)) {
    $current_step_index = array_search($current_status, array_keys($status_steps));
    if ($current_step_index === false) {
        $current_step_index = 0; // 默认回到开始
    }
} else {
    $current_step_index = -1;
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
.item-price-qty {
    font-weight: bold;
    color: #ff6600;
    text-align: right;
}
/* 套餐样式 */
.package-summary-box {
    background: #442200;
    border: 1px solid #ff6600;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 10px;
}
.package-summary-box p {
    margin: 5px 0;
}
.package-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
    border-bottom: 1px dashed #333;
    font-size: 0.9em;
}
.package-item-row:last-child {
    border-bottom: none;
}
.package-item-row img {
    width: 30px;
    height: 30px;
}
.package-title {
    font-size: 1.1em;
    font-weight: bold;
    color: #ffcc00;
}
.package-discount {
    color: #00ff99;
}


.action-btn {
    display: inline-block;
    background: #ff6600;
    color: #fff;
    padding: 12px 25px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 30px;
    transition: background 0.3s;
    margin-right: 15px;
}
.action-btn:hover {
    background: #e65c00;
}
.action-btn.green {
    background: #28a745;
}
.action-btn.green:hover {
    background: #218838;
}
.back-btn {
    display: inline-block;
    background: #444;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin-top: 30px;
    transition: background 0.3s;
}
.back-btn:hover {
    background: #555;
}
.error-box {
    text-align: center;
    padding: 50px;
    border: 2px solid #ff6600;
    border-radius: 10px;
    background: #2a0a00;
    margin-top: 50px;
}
.error-box h2 {
    color: #ff6600;
    border: none;
    padding-bottom: 0;
}
</style>
</head>
<body>

<div class="container">
    <?php if ($error_message): ?>
        <div class="error-box">
            <h2>Error</h2>
            <p><?= htmlspecialchars($error_message) ?></p>
            <a href="my_order.php" class="back-btn">← Back to Orders List</a>
        </div>
    <?php else: ?>
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
            <?php 
            // --- 场景 1: 显示套餐详情 ---
            if ($package_details): ?>
                <div class="package-summary-box">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <img src="<?= htmlspecialchars(getImagePath($package_details['image'])) ?>" alt="<?= htmlspecialchars($package_details['package_name']) ?>" style="width: 70px; height: 70px; border-radius: 8px; margin-right: 15px;">
                        <div>
                            <p class="package-title"><?= htmlspecialchars($package_details['package_name']) ?> (Package)</p>
                            <p style="color: #ccc;">Discount: <span class="package-discount"><?= number_format($package_details['discount'], 2) ?>%</span></p>
                        </div>
                        <p class="item-price-qty">RM <?= number_format($order['total'], 2) ?></p>
                    </div>

                    <?php if (!empty($package_contents)): ?>
                        <h3 style="font-size: 1em; color: #ccc; margin-top: 10px; border-top: 1px dashed #333; padding-top: 10px;">Items Contained:</h3>
                        <?php foreach ($package_contents as $item): ?>
                            <div class="package-item-row">
                                <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                                <div style="flex-grow: 1;"><?= htmlspecialchars($item['item_name']) ?></div>
                                <span style="color: #ccc;">(RM <?= number_format($item['unit_price'], 2) ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color:#888; text-align: center; margin-top: 10px;">No item details linked to this package.</p>
                    <?php endif; ?>
                </div>
            <?php 
            // --- 场景 2: 显示单品详情 ---
            elseif (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <div class="item-detail">
                        <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                        <div class="item-info">
                            <p><strong><?= htmlspecialchars($item['item_name']) ?></strong></p>
                            <p style="color: #ccc;">Qty: <?= $item['quantity'] ?> x RM <?= number_format($item['price'], 2) ?></p>
                        </div>
                        <p class="item-price-qty">RM <?= number_format($item['quantity'] * $item['price'], 2) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <p style="color:#ccc;">No items found for this order.</p>
            <?php endif; ?>
        </div>

        <!-- 订单操作按钮 (收据/支付) -->
        <div style="text-align: center;">
            <?php if ($current_status === 'DELIVERED'): ?>
                <a href="view_receipt.php?id=<?= $order_id ?>" class="action-btn green">📄 View Receipt</a>
            <?php endif; ?>

            <?php if ($current_status === 'WAIT_FOR_PAYMENT' || $current_status === 'PENDING'): ?>
                <a href="payment.php?order_id=<?= $order_id ?>" class="action-btn">💰 Complete Payment Now</a>
            <?php endif; ?>
            
            <a href="my_order.php" class="back-btn">← Back to Orders List</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
