<?php
include "config.php";
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = $_POST['order_id'] ?? '';
    $total = $_POST['total'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? 'TouchNGo';
    $user_id = $_SESSION['user_id'] ?? 1;
    $action = $_POST['action'] ?? ''; // pay 或 confirm

    if (empty($order_id)) {
        die("❌ Invalid order ID");
    }

    // 检查订单是否存在
    $check = $conn->prepare("SELECT status FROM orders WHERE order_id=? AND user_id=?");
    $check->bind_param("si", $order_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // 已存在订单 → 根据 action 更新状态
        if ($action === "pay") {
            $newStatus = "WAIT_FOR_PAYMENT";
        } elseif ($action === "confirm") {
            $newStatus = "COMPLETE_PAYMENT";
        } else {
            $newStatus = "UNKNOWN";
        }

        $update = $conn->prepare("UPDATE orders SET status=? WHERE order_id=? AND user_id=?");
        $update->bind_param("ssi", $newStatus, $order_id, $user_id);
        $update->execute();
        $update->close();

        echo "<p style='color:lime'>✅ Order updated to <b>$newStatus</b></p>";

    } else {
        // 没有订单则创建新记录（仅作为兜底，正常不会执行）
        $status = ($action === "confirm") ? "COMPLETE_PAYMENT" : "WAIT_FOR_PAYMENT";
        $insert = $conn->prepare("
            INSERT INTO orders (order_id, user_id, total, payment_type, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $insert->bind_param("sidss", $order_id, $user_id, $total, $payment_type, $status);
        $insert->execute();
        $insert->close();

        echo "<p style='color:orange'>🟠 New order created with status: $status</p>";
    }

    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Touch 'n Go Payment</title>
<style>
body { font-family: Arial, sans-serif; background:#000; color:#fff; text-align:center; padding:40px; }
h1 { color:#ff6600; }
a.pay-link { display:inline-block; margin-top:20px; padding:15px 25px; background:#ff6600; color:#fff; font-size:18px; font-weight:bold; border-radius:8px; text-decoration:none; }
a.pay-link:hover { background:#e65c00; }
button.track-btn {
    margin-top:30px;
    background:#28a745;
    color:#fff;
    border:none;
    padding:12px 25px;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
}
button.track-btn:hover { background:#218838; }
</style>
</head>
<body>

<h1>Touch 'n Go Payment</h1>
<p>Please make your payment to <b>DJS Game Topup Platform System</b></p>
<p>Order ID: <b><?= htmlspecialchars($order_id) ?></b></p>
<p>Total: <b>RM <?= number_format($total, 2) ?></b></p>

<!-- ✅ 保留你的 TNG 付款链接 -->
<a href="https://payment.tngdigital.com.my/sc/bDLoiwKBF4" target="_blank" class="pay-link">
    Pay with Touch 'n Go
</a>

<!-- ✅ 点击“我已付款”时更新状态为 COMPLETE_PAYMENT -->
<form action="confirm_payment.php" method="POST">
    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">
    <input type="hidden" name="action" value="confirm">
    <button type="submit" class="track-btn">✅ I have paid</button>
</form>

<!-- 跳转到订单追踪页面 -->
<form action="my_order.php" method="GET">
    <button type="submit" class="track-btn" style="background:#ff6600;">📦 Go to Track Order</button>
</form>

</body>
</html>
