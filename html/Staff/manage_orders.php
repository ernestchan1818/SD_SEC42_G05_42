<?php
session_start();
include "config.php";

// ⚠️ 实际应用中：在这里添加员工权限检查
$is_staff = true; // 假设用户已通过员工验证
if (!$is_staff) {
    die("Access denied. Staff access required.");
}

// --- Helper Function ---
function getImagePath($path) {
    $default = "../Staff/image/default.png"; // 假设员工文件夹下的默认图片
    if (!$path) return $default;
    // 假设路径已经是相对于 manage_orders.php 可访问的路径
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path ?: $default;
}
// --- END Helper Function ---

// --- 1. 查询所有订单主信息 ---
// 注意：这里没有 user_id 限制
// 使用 CASE WHEN 语句将 'DELIVERED' 状态的订单推到列表最后
$query = "
    SELECT 
        o.order_id, 
        o.total, 
        o.status, 
        o.created_at, 
        o.user_id,
        u.username
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY 
        CASE 
            WHEN o.status = 'DELIVERED' THEN 1 
            ELSE 0 
        END ASC, 
        o.created_at DESC
";
$result = $conn->query($query);

$orders = [];
$orderIds = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $orderIds[] = $row['order_id'];
}

// --- 2. 批量查询所有订单的商品详情 ---
$orderDetails = [];
if (!empty($orderIds)) {
    $idList = implode(',', $orderIds); 

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

// 状态选项
$status_options = [
    "Pending",
    "WAIT_FOR_PAYMENT",
    "COMPLETE_PAYMENT",
    "PROCESSING",
    "DELIVERED",
    "CANCELLED"
];

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff: Manage Orders</title>
<style>
/* --- Blue/White Palette --- */
body { 
    font-family: 'Inter', sans-serif; 
    background: #F4F7F9; /* 浅灰色背景 */
    color: #333; /* 深色文本 */
    margin: 0; 
    padding: 0; 
}
/* 头部主标题 */
header { 
    background: #007BFF; /* 蓝色头部背景 */
    padding: 15px 20px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
}
header h1 { 
    margin: 0; 
    font-size: 24px; 
    color: #fff; /* 白色标题 */
}
/* 导航栏样式 */
.navbar { 
    background: #111; /* 深色背景 */
    padding: 10px 20px; 
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); 
    display: flex;
    justify-content: center;
}
.navbar nav a {
    color: #fff;
    text-decoration: none;
    padding: 8px 15px;
    margin: 0 5px;
    border-radius: 4px;
    transition: background 0.3s;
    font-size: 0.95em;
    font-weight: 500;
}
.navbar nav a:hover {
    background: #0056B3; /* 深蓝色悬停 */
}

.container { 
    max-width: 1100px; 
    margin: 40px auto; 
    padding: 20px; 
}
h2 { 
    color: #007BFF; /* 蓝色标题 */
    border-bottom: 2px solid #007BFF; 
    padding-bottom: 10px; 
    margin-bottom: 30px; 
}
.message-box { 
    padding: 15px; 
    margin-bottom: 20px; 
    border-radius: 8px; 
    font-weight: bold; 
    color: #fff;
}
.message-success { background: #28a745; }
.message-error { background: #dc3545; }

/* Order Card Styling */
.order-card { 
    background: #FFFFFF; /* 白色卡片背景 */
    border-radius: 12px; 
    margin-bottom: 25px; 
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); /* 柔和阴影 */
    transition: transform 0.3s, box-shadow 0.3s;
}
.order-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 123, 255, 0.2); /* 蓝色高亮阴影 */
}

.order-summary { 
    padding: 20px; 
    border-bottom: 1px solid #E0E0E0; /* 浅灰色分割线 */
    display: flex; 
    flex-wrap: wrap; 
    justify-content: space-between; 
    align-items: center; 
}
.summary-item { margin-bottom: 10px; }
.summary-item strong { 
    color: #007BFF; /* 蓝色标签 */
    display: block; 
    font-size: 0.8em; 
    margin-bottom: 2px; 
}
.summary-item span { font-size: 1.1em; color: #333; }

.status-display { 
    padding: 5px 12px; 
    border-radius: 6px; 
    font-weight: bold; 
    font-size: 0.9em; 
    color: #fff;
}
.status-display.PENDING, .status-display.WAIT_FOR_PAYMENT { background: #FFC107; color: #333; } /* 黄色/警告色 */
.status-display.COMPLETE_PAYMENT, .status-display.PROCESSING { background: #007BFF; } /* 蓝色/处理中 */
.status-display.DELIVERED { background: #28A745; } /* 绿色/成功 */
.status-display.CANCELLED { background: #6C757D; } /* 灰色/取消 */

/* Item Details */
.item-details-section { 
    padding: 20px; 
    background: #F8F9FA; /* 极浅灰色商品背景 */
    border-radius: 0 0 12px 12px;
}
.item-header { font-size: 1em; font-weight: bold; color: #6C757D; margin-bottom: 10px; }
.item-detail { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #E0E0E0; }
.item-detail:last-child { border-bottom: none; }
.item-detail img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 15px; border: 1px solid #ccc;}
.item-info { flex-grow: 1; }
.item-info p { margin: 0; font-size: 0.9em; color: #333; }
.item-price-qty { font-weight: bold; color: #007BFF; }

/* Status Update Form */
.update-form { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #E0E0E0;
}
.update-form select { 
    padding: 8px 12px; 
    border-radius: 6px; 
    background: #fff; 
    color: #333; 
    border: 1px solid #CED4DA;
    transition: border-color 0.3s;
}
.update-form select:focus {
    border-color: #007BFF;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
.update-form button { 
    background: #007BFF; /* 蓝色按钮 */
    color: #fff; 
    padding: 10px 20px; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer; 
    font-weight: bold;
    transition: background 0.3s;
}
.update-form button:hover { background: #0056B3; } /* 深蓝色悬停 */

@media (max-width: 768px) {
    .order-summary { flex-direction: column; align-items: flex-start; }
    .update-form { flex-wrap: wrap; justify-content: space-between; }
    .update-form label { flex-basis: 100%; margin-bottom: 5px; }
}
</style>
</head>
<body>

<header>
    <h1>Staff Portal: Orders</h1>
</header>
<!-- 新增的导航栏 -->
<div class="navbar">
    <nav>
    <?php
    // 假设您在登录时设置了 $_SESSION['role']
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            echo '<a href="admin_home.php">Home</a>';
        } elseif ($_SESSION['role'] === 'staff') {
            echo '<a href="staff_home.php">Home</a>';
        } 
    } else {
        // 如果未设置角色，默认显示 Staff Home
        echo '<a href="staff_home.php">Home</a>';
    }
    ?>
        <a href="sales_report.php">Sales Report</a>
        <a href="Contact.php">Contact</a>
        <a href="contactus.php">Feedback</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="signout.php">Sign Out</a>
    </nav>
</div>
<!-- 导航栏结束 -->

<div class="container">
    <h2>Manage Customer Orders</h2>

    <?php if ($message): ?>
        <div class="message-box message-success">✅ Status updated: <?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="message-box message-error">❌ Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p style="text-align: center; color: #6c757d;">No customer orders found.</p>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): 
                $status_clean = strtoupper(str_replace(' ', '_', $order['status']));
                $customer_name = htmlspecialchars($order['username'] ?: "User #{$order['user_id']}");
            ?>
            <div class="order-card">
                <div class="order-summary">
                    <!-- Column 1: Order & Customer ID -->
                    <div class="summary-item">
                        <strong>ORDER ID</strong>
                        <span>#<?= htmlspecialchars($order['order_id']) ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>CUSTOMER</strong>
                        <span><?= $customer_name ?></span>
                    </div>
                    
                    <!-- Column 2: Total & Date -->
                    <div class="summary-item">
                        <strong>DATE</strong>
                        <span><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['created_at']))) ?></span>
                    </div>
                    <div class="summary-item">
                        <strong>TOTAL</strong>
                        <span style="color: #007BFF;">RM <?= number_format($order['total'], 2) ?></span>
                    </div>
                    
                    <!-- Column 3: Current Status -->
                    <div class="summary-item">
                        <strong>STATUS</strong>
                        <span class="status-display <?= $status_clean ?>"><?= htmlspecialchars($order['status']) ?></span>
                    </div>
                </div>

                <!-- Item Details & Status Update -->
                <div class="item-details-section">
                    <div class="item-header">Order Items (<?= count($orderDetails[$order['order_id']] ?? []) ?>)</div>
                    
                    <?php if (isset($orderDetails[$order['order_id']])): ?>
                        <?php foreach ($orderDetails[$order['order_id']] as $item): ?>
                        <div class="item-detail">
                            <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                            <div class="item-info">
                                <p><strong><?= htmlspecialchars($item['item_name']) ?></strong></p>
                                <p style="color: #6c757d;">Price: RM <?= number_format($item['price'], 2) ?></p>
                            </div>
                            <div class="item-price-qty">
                                Qty: <?= $item['quantity'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Status Update Form -->
                    <form action="update_order_status.php" method="POST" class="update-form">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <label for="status-<?= $order['order_id'] ?>">Update Status:</label>
                        <select name="new_status" id="status-<?= $order['order_id'] ?>">
                            <?php foreach ($status_options as $option): 
                                $selected = (strtoupper($option) === $status_clean) ? 'selected' : '';
                            ?>
                                <option value="<?= htmlspecialchars($option) ?>" <?= $selected ?>><?= htmlspecialchars($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
