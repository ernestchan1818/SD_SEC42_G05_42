<?php
include "config.php";
session_start();

// 假设用户已登录
$user_id = $_SESSION['user_id'] ?? 1; 
$username = $_SESSION['username'] ?? "Demo User";

// 检查 session 是否有订单
if (!isset($_SESSION['order'])) {
    die("No order found. Please go back and select items first.");
}

$order = $_SESSION['order'];
$gameId = $order['game_id'];
$orderItems = $order['items'];

$order_id = uniqid("ORD_"); // 订单号
$items = [];
$total = 0;

// 遍历购物车，查数据库补全 item 信息
foreach ($orderItems as $itemId => $data) {
    $qty = (int)$data['qty'];
    $price = (float)$data['price'];

    // 查询数据库，拿 item 名称和图片
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

    <button class="pay-btn">Confirm Payment</button>
</div>

</body>
</html>
