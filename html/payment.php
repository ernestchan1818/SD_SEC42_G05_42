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
$package_summary = null; // 用于存储套餐主信息，方便在顶部显示

// --- 3. 尝试查询订单明细 (order_items) ---
// 针对购买单品的情况
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
            "is_package_item" => false // 标记为普通商品
        ];
    }
}
$stmt_items->close();


// --- 4. 如果没有找到 item (购买套餐)，则查询套餐详情及内含商品 ---
if (empty($items)) {
    // 1. 查询套餐主信息
    $pkg_stmt = $conn->prepare("
        SELECT package_id, package_name, image, discount, price AS list_price
        FROM topup_packages 
        WHERE package_id = ?
    ");
    
    if ($pkg_stmt) {
        // 使用 orders.game_id 作为 package_id (这是我们的假设)
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
                        "is_package_item" => true // 标记为套餐内含物
                    ];
                }
                $pkg_item_stmt->close();
            }
        }
    }
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
/* 套餐总结样式 */
.order-item.summary {
    background: #331100; /* 深橙色背景 */
    border: 1px solid #ff6600;
    font-size: 1.1em;
    padding: 15px 10px;
    margin-bottom: 5px; /* 靠近子项目 */
}
/* 套餐内含项目样式 */
.order-item.package-item {
    background: #2a2a2a;
    border-left: 5px solid #ff6600; /* 橙色边框 */
    padding-left: 20px;
    font-size: 0.9em;
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
    <?php if (!empty($items) || $package_summary): ?>
        <?php 
        // 1. 如果是套餐购买，先显示套餐总结行
        if ($package_summary): ?>
            <div class="order-item summary">
                <img src="<?= htmlspecialchars($package_summary['image']) ?>" alt="<?= htmlspecialchars($package_summary['name']) ?>">
                <div class="item-details">
                    <p>
                        <strong><?= htmlspecialchars($package_summary['name']) ?></strong> 
                        <span style="color: #ffcc00; margin-left: 10px;">(Package)</span>
                    </p>
                    <p style="font-size: 0.9em; color: #ccc;">Original Price: <del>RM <?= number_format($package_summary['list_price'] ?? $total, 2) ?></del></p>
                    <p style="font-size: 0.9em; color: #00ff99;">Discount: <?= number_format($package_summary['discount'] ?? 0, 2) ?>% Applied</p>
                </div>
                <p><strong>RM <?= number_format($package_summary['final_price'] ?? $total, 2) ?></strong></p>
            </div>
            <p style="color: #ccc; margin-top: -5px; margin-bottom: 15px; font-size: 0.9em;">Items Contained in Package:</p>
        <?php endif; ?>
        
        <?php 
        // 2. 循环显示所有商品或套餐内含商品
        foreach($items as $it): 
            $is_package_item = $it['is_package_item'] ?? false;
        ?>
        <div class="order-item <?= $is_package_item ? 'package-item' : '' ?>">
            <img src="<?= htmlspecialchars($it['image']) ?>" alt="<?= htmlspecialchars($it['name']) ?>">
            <div class="item-details">
                <p><strong><?= htmlspecialchars($it['name']) ?></strong></p>
                <?php if ($is_package_item): ?>
                    <p style="font-size: 0.9em; color: #ccc;">Contained Item (Individual Price)</p>
                <?php else: ?>
                    <p style="font-size: 0.9em; color: #ccc;">Qty: <?= $it['qty'] ?> × RM <?= number_format($it['price'],2) ?></p>
                <?php endif; ?>
            </div>
            <!-- 显示单个商品的单价或小计 -->
            <p><strong>RM <?= number_format($it['price'], 2) ?></strong></p>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; color: #ff6600;">No item details found for this order.</p>
    <?php endif; ?>

    <div class="total-box">Total: RM <?= number_format($total,2) ?></div>

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
