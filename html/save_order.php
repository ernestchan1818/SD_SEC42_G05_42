<?php
session_start();
include "config.php"; // ✅ 确认路径正确，例如 ../connection.php 也可以改成相对路径

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id = $_SESSION["user_id"] ?? 0;
    $game_id = $_POST["game_id"] ?? null;
    $orderData = $_POST["order_items"] ?? null;

    if (!$user_id || !$game_id || !$orderData) {
        die("❌ Missing user_id, game_id, or order_items");
    }

    // ✅ 转换 JSON 数据
    $items = json_decode($orderData, true);
    if (empty($items)) {
        die("❌ No items found in order_items");
    }

    // ✅ 计算总价
    $total = 0;
    foreach ($items as $itemId => $details) {
        $qty = (int)($details["quantity"] ?? 1);
        $price = (float)($details["price"] ?? 0);
        $total += $qty * $price;
    }

    // ✅ 插入到 orders 表
    $status = "Pending";
    $paymentType = "TouchNGo";
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, game_id, total, payment_type, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iidss", $user_id, $game_id, $total, $paymentType, $status);
    $stmt->execute();
    $order_id = $stmt->insert_id;
    $stmt->close();

    // ✅ 插入每个 item 到 order_items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, game_id, item_id, item_name, quantity, price)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $itemId => $details) {
        $qty = (int)($details["quantity"] ?? 1);
        $price = (float)($details["price"] ?? 0);

        // 从 game_items 表查找 item_name
        $findItem = $conn->prepare("SELECT item_name FROM game_items WHERE item_id = ?");
        $findItem->bind_param("i", $itemId);
        $findItem->execute();
        $findItem->bind_result($itemName);
        $findItem->fetch();
        $findItem->close();

        // 如果找不到 item_name，用“Unknown Item”
        if (empty($itemName)) $itemName = "Unknown Item";

        // 插入 order_items
        $stmt->bind_param("iiisid", $order_id, $game_id, $itemId, $itemName, $qty, $price);
        $stmt->execute();
    }
    $stmt->close();

    // ✅ 保存订单到 session
    $_SESSION["current_order_id"] = $order_id;

    // ✅ 跳转到付款页面
    header("Location: payment.php?order_id=" . $order_id);
    exit;
}
?>
