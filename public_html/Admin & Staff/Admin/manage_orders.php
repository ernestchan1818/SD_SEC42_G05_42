<?php
session_start();
include "config.php"; // 确保 $conn 是 mysqli 连接

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

// --- 1. 查询所有订单主信息 (包含 game_id) ---
// 使用 CASE WHEN 语句将 'DELIVERED' 状态的订单推到列表最后
$query = "
    SELECT 
        o.order_id, 
        o.total, 
        o.status, 
        o.created_at, 
        o.user_id,
        o.game_id, 
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
$gameIdsForPackageCheck = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
    $orderIds[] = $row['order_id'];
    if ($row['game_id']) { 
        $gameIdsForPackageCheck[] = $row['game_id'];
    }
}

// --- 2. 批量查询所有订单的商品详情 (非套餐商品) ---
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

// --- 3. 批量查询套餐详情 (仅查询实际存在的 package_id) ---
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

// --- 4. 批量查询套餐内含商品详情 (新步骤) ---
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
    background: #FFFFFF; /* 修正：白色背景 */
    color: #e8ecf1; /* 确保：亮色文本 */
    margin: 0; 
    padding: 0; 
}
/* 头部主标题 */
header { 
    background: #007BFF; /* 蓝色头部背景 */
    padding: 15px 30px;
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.logo {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}
/* 导航栏样式 */
nav {
    display: flex;
    gap: 15px;
}
nav a {
    color: #fff;
    text-decoration: none;
    padding: 5px 10px;
    border-radius: 4px;
    transition: background 0.3s;
    font-weight: 500;
}
nav a:hover {
    background: #0056B3; /* 深蓝色悬停 */
}

.container { 
    max-width: 1100px; 
    margin: 40px auto; 
    padding: 20px; 
}
h2 { 
    color: #00BFFF; /* 亮蓝色标题 */
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
    background: #1a2a40; /* 修正：深蓝卡片背景 */
    border-radius: 12px; 
    margin-bottom: 25px; 
    /* ✅ 修正：更强的蓝色阴影 */
    box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4); 
    transition: transform 0.3s, box-shadow 0.3s;
}
.order-card:hover {
    transform: translateY(-5px); /* 悬停浮动更多 */
    box-shadow: 0 15px 35px rgba(0, 191, 255, 0.6); /* 悬停时更亮 */
}

.order-summary { 
    padding: 20px; 
    border-bottom: 1px solid #374151; /* 深色分割线 */
    display: flex; 
    flex-wrap: wrap; 
    justify-content: space-between; 
    align-items: center; 
}
.summary-item { margin-bottom: 10px; }
.summary-item strong { 
    color: #00BFFF; /* 亮蓝色标签 */
    display: block; 
    font-size: 0.8em; 
    margin-bottom: 2px; 
}
.summary-item span { font-size: 1.1em; color: #e8ecf1; }

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
    background: #111827; /* 极深色商品背景 */
    border-radius: 0 0 12px 12px;
}
.item-header { font-size: 1em; font-weight: bold; color: #9ca3af; margin-bottom: 10px; }
.item-detail { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #1f2937; }
.item-detail:last-child { border-bottom: none; }
.item-detail img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; margin-right: 15px; border: 1px solid #374151;}
.item-info { flex-grow: 1; }
.item-info p { margin: 0; font-size: 0.9em; color: #e8ecf1; }
.item-price-qty { font-weight: bold; color: #00BFFF; }

/* 套餐特定样式 */
.package-detail {
    border-left: 5px solid #00BFFF;
    padding: 15px;
    background: #142236;
    margin-bottom: 10px;
    border-radius: 4px;
}
.package-detail p {
    margin: 3px 0;
}
.package-discount {
    color: #ef4444; /* 红色表示折扣 */
    font-weight: bold;
}
.package-item-list {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px dashed #374151;
}
.pkg-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.85em;
    padding: 5px 0;
}
.pkg-item-row img {
    width: 30px;
    height: 30px;
    border-radius: 4px;
}


/* Status Update Form */
.update-form { 
    display: flex; 
    align-items: center; 
    gap: 15px; 
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #374151;
}
.update-form select { 
    padding: 8px 12px; 
    border-radius: 6px; 
    background: #111827; 
    color: #fff; 
    border: 1px solid #374151;
    transition: border-color 0.3s;
}
.update-form select:focus {
    border-color: #00BFFF;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 191, 255, 0.25);
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
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <?php
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                echo '<a href="admin_home.php">Home</a>';
            } elseif ($_SESSION['role'] === 'staff') {
                echo '<a href="staff_home.php">Home</a>';
            } 
        } 
        ?>
        <a href="manage_orders.php">Manage Orders</a>
        <a href="sales_report.php">Sales Report</a>
        <a href="contactS.php">Contact</a>
        <a href="contactus.php">Feedback</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="logoutS.php">Sign Out</a>
    </nav>
</header>

<div class="container">
    <h2>Manage Customer Orders</h2>

    <?php if ($message): ?>
        <div class="message-box message-success">✅ Status updated: <?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="message-box message-error">❌ Error: <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p style="text-align: center; color: #9ca3af;">No customer orders found.</p>
    <?php else: ?>
        <div class="order-list">
            <?php foreach ($orders as $order): 
                $status_clean = strtoupper(str_replace(' ', '_', $order['status']));
                $customer_name = htmlspecialchars($order['username'] ?: "User #{$order['user_id']}");
                $is_package_order = !isset($orderDetails[$order['order_id']]) && $order['game_id'] > 0;
                $package_data = $is_package_order ? ($packageDetails[$order['game_id']] ?? null) : null;
                $package_contents = $is_package_order ? ($packageContents[$order['game_id']] ?? []) : [];
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
                    <div class="item-header">Order Items (<?= $is_package_order ? '1 Package' : (count($orderDetails[$order['order_id']] ?? []) . ' Items') ?>)</div>
                    
                    <?php 
                    // --- 场景 1: 显示套餐详情 ---
                    if ($is_package_order && $package_data): ?>
                        <div class="package-detail">
                            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                <img src="<?= htmlspecialchars(getImagePath($package_data['image'])) ?>" alt="<?= htmlspecialchars($package_data['package_name']) ?>" style="width: 60px; height: 60px; border-radius: 4px; margin-right: 15px;">
                                <div>
                                    <p style="margin:0;"><strong>Package Name:</strong> <?= htmlspecialchars($package_data['package_name']) ?></p>
                                    <p style="margin:0; font-size: 0.9em; color: #9ca3af;">Package ID: #<?= htmlspecialchars($package_data['package_id']) ?></p>
                                </div>
                            </div>
                            
                            <p><strong>Total Paid:</strong> RM <?= number_format($order['total'], 2) ?></p>
                            <p class="package-discount">Discount Applied: <?= number_format($package_data['discount'], 2) ?>%</p>
                            
                            <div class="package-item-list">
                                <p style="font-weight: bold; margin-bottom: 5px;">Contained Items:</p>
                                <?php if (!empty($package_contents)): ?>
                                    <?php foreach ($package_contents as $item): ?>
                                        <div class="pkg-item-row">
                                            <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                                            <div style="flex-grow: 1;">&mdash; <?= htmlspecialchars($item['item_name']) ?></div>
                                            <span style="color: #9ca3af;">(RM <?= number_format($item['unit_price'], 2) ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="color: #9ca3af; font-size: 0.9em;">No items linked to this package.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php
                    // --- 场景 2: 显示单品详情 ---
                    elseif (isset($orderDetails[$order['order_id']])): ?>
                        <?php foreach ($orderDetails[$order['order_id']] as $item): ?>
                        <div class="item-detail">
                            <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                            <div class="item-info">
                                <p><strong><?= htmlspecialchars($item['item_name']) ?></strong></p>
                                <p style="color: #9ca3af;">Price: RM <?= number_format($item['price'], 2) ?></p>
                            </div>
                            <div class="item-price-qty">
                                Qty: <?= $item['quantity'] ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php 
                    // --- 场景 3: 没有任何详情 ---
                    else: ?>
                        <div style="color: #999; text-align: center;">No item details found. (Possibly skipped due to package purchase or old record)</div>
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
