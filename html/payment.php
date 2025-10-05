<?php
include "config.php";
session_start();

// ✅ 确保每个用户独立订单
$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['username'] ?? "Demo User";

// ✅ 检查是否存在该用户的订单
if (!isset($_SESSION['orders'][$user_id])) {
    die("No order found. Please go back and select items first.");
}

$order = $_SESSION['orders'][$user_id];
$gameId = $order['game_id'];
$orderItems = $order['items'];

// ===== 防止重复生成订单 =====
if (!isset($_SESSION['current_order_id'])) {
    // 第一次生成订单
    $order_id = uniqid("ORD_");
    $_SESSION['current_order_id'] = $order_id;

    $items = [];
    $total = 0;

    // 遍历购物车计算总价
    foreach ($orderItems as $itemId => $data) {
        $qty = (int)$data['qty'];
        $price = (float)$data['price'];

        $stmt = $conn->prepare("SELECT item_name, image FROM game_items WHERE item_id = ?");
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $stmt->bind_result($name, $image);
        if ($stmt->fetch()) {
            $subtotal = $qty * $price;
            $items[] = [
                "name" => $name,
                "img" => $image,
                "qty" => $qty,
                "price" => $price,
                "subtotal" => $subtotal
            ];
            $total += $subtotal;
        }
        $stmt->close();
    }

    // 保存订单信息到 session
    $_SESSION['items'] = $items;
    $_SESSION['total'] = $total;

    // 插入数据库（只执行一次）
    $paymentType = "TouchNGo";
    $status = "WAIT_FOR_PAYMENT";

    $stmt = $conn->prepare("
        INSERT INTO orders (order_id, user_id, total, payment_type, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("sidss", $order_id, $user_id, $total, $paymentType, $status);
    $stmt->execute();
    $stmt->close();

    // ✅ 保存每个商品到 order_items 表
    foreach ($items as $it) {
        $itemName = $it['name'];
        $qty = $it['qty'];
        $price = $it['price'];

        // 从 game_items 表获取 item_id
        $stmt = $conn->prepare("SELECT item_id FROM game_items WHERE item_name = ?");
        $stmt->bind_param("s", $itemName);
        $stmt->execute();
        $stmt->bind_result($item_id);
        if ($stmt->fetch()) {
            $stmt->close();

            // 插入到 order_items 表
            $insertItem = $conn->prepare("
                INSERT INTO order_items (order_id, item_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            $insertItem->bind_param("siid", $order_id, $item_id, $qty, $price);
            $insertItem->close();
        } else {
            $stmt->close();
        }
    }

} else {
    // 如果订单已存在，直接使用 session 数据
    $order_id = $_SESSION['current_order_id'];
    $items = $_SESSION['items'];
    $total = $_SESSION['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
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
    align-items:center;
    margin:10px 0;
    background:#222;
    padding:10px;
    border-radius:8px;
}
.order-item img {
    width:60px; height:60px;
    border-radius:6px;
    margin-right:15px;
}
.order-item div { flex-grow:1; }
.order-item p { margin:2px 0; }
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
.payment-method label {
    margin-right:20px;
    font-size:16px;
}
.status {
    margin:15px 0;
    font-weight:bold;
    color:#f39c12;
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
    <h1>Payment</h1>
    <div class="order-info">
        <p><strong>Order ID:</strong> #<?= $order_id ?></p>
        <p><strong>User:</strong> <?= htmlspecialchars($username) ?></p>
    </div>

    <h2>Order Details</h2>
    <?php if (!empty($items)): ?>
        <?php foreach($items as $it): ?>
        <div class="order-item">
            <img src="<?= htmlspecialchars($it['img']) ?>" alt="">
            <div>
                <p><strong><?= htmlspecialchars($it['name']) ?></strong></p>
                <p>Qty: <?= $it['qty'] ?> × RM <?= number_format($it['price'],2) ?></p>
            </div>
            <p><strong>RM <?= number_format($it['subtotal'],2) ?></strong></p>
        </div>
        <?php endforeach; ?>
        <div class="total-box">Total: RM <?= number_format($total,2) ?></div>
    <?php else: ?>
        <p>No items found in your order.</p>
    <?php endif; ?>

    <div class="payment-method">
        <p><strong>Choose Payment Method:</strong></p>
        <label><input type="radio" name="payment" value="TouchNGo" checked> Touch 'n Go</label>
        <label><input type="radio" name="payment" value="FPX"> FPX Bank</label>
    </div>

    <div class="status">Status: Waiting for Payment</div>

    <!-- 付款按钮 -->
    <form action="confirm_payment.php" method="POST">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
        <input type="hidden" name="total" value="<?= $total ?>">
        <input type="hidden" name="action" value="pay">
        <button type="submit" class="pay-btn">Pay with Touch 'n Go</button>
    </form>

    <!-- 已付款按钮 -->
    <form action="confirm_payment.php" method="POST" style="margin-top:20px;">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
        <input type="hidden" name="action" value="confirm">
        <button type="submit" style="padding:10px 20px; background:#28a745; color:#fff; border:none; border-radius:6px; font-size:16px;">
            ✅ I have paid
        </button>
    </form>

</div>
</body>
</html>
