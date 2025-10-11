<?php
session_start();
include "config.php";

// 确保用户已登录，否则终止操作
$user_id = $_SESSION["user_id"] ?? 0;
if (!$user_id) {
    // 假设未登录用户ID为 1 进行测试，但在生产环境中应该强制登录
    $user_id = 1; 
    // die("⚠️ Please log in first."); // 生产环境请启用这行
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // --- 1. 获取和验证输入数据 ---
    $game_id = $_POST["game_id"] ?? null; // 假设 game_id 是 INT
    $orderData = $_POST["order_items"] ?? null; // 这是一个 JSON 字符串
    $paymentType = "TouchNGo";

    if (!$game_id || !$orderData) {
        die("❌ Missing game_id or order_items data.");
    }

    // 解析 JSON
    $items = json_decode($orderData, true);
    if (empty($items)) {
        die("❌ No items found in order_items.");
    }

    // --- 2. 计算总价 ---
    $total = 0.00;
    foreach ($items as $itemId => $details) {
        $qty = (int)($details["quantity"] ?? 1);
        $price = (float)($details["price"] ?? 0);
        $total += $qty * $price;
    }

    // --- 3. 插入 orders 表 (创建新订单) ---
    $status = "Pending"; // 订单初始状态
    
    // order_id 是自增的，不需要在参数中提供
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, game_id, total, payment_type, status, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) die("Order Insert Prepare Error: " . $conn->error);

    // 假设 user_id, game_id 是 INT, total 是 DOUBLE
    $stmt->bind_param("iidss", $user_id, $game_id, $total, $paymentType, $status);
    $stmt->execute();
    $order_id = $stmt->insert_id; // 获取新创建的订单 ID
    $stmt->close();

    if (!$order_id) {
        die("❌ Failed to create order in database.");
    }

    // --- 4. 插入 order_items 表 ---
    $stmt_item = $conn->prepare("
        INSERT INTO order_items (order_id, game_id, item_id, item_name, quantity, price)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt_item) die("Order Items Prepare Error: " . $conn->error);

    foreach ($items as $itemId => $details) {
        $qty = (int)($details["quantity"] ?? 1);
        $price = (float)($details["price"] ?? 0);
        
        // *你的原逻辑*：查询 item_name
        $itemName = $details["name"] ?? "Unknown Item"; // 建议直接从 POST 数据中获取 name，减少数据库查询
        
        // 假设 order_id, game_id, item_id, quantity 是 INT, price 是 DOUBLE
        $stmt_item->bind_param("iiisid", $order_id, $game_id, $itemId, $itemName, $qty, $price);
        $stmt_item->execute();
    }
    $stmt_item->close();

    // --- 5. 重定向到付款页面，通过 GET 传递 order_id ---
    header("Location: payment.php?order_id=" . $order_id);
    exit;
} else {
    die("Invalid request method.");
}
?>
