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
    
    if (!$stmt->execute()) {
         die("❌ Failed to execute order insertion: " . $stmt->error);
    }
    
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
        // ✅ 修正逻辑：检测负数ID（套餐ID）并跳过外键约束检查
        $intItemId = (int)$itemId;
        if ($intItemId < 0) {
            // 这是套餐ID，我们已经将总价记录在 orders 表，
            // 故跳过插入 order_items，避免外键约束失败。
            continue; 
        }
        
        $qty = (int)($details["quantity"] ?? 1);
        $price = (float)($details["price"] ?? 0);
        
        // *你的原逻辑*：查询 item_name
        $itemName = $details["name"] ?? "Unknown Item"; // 建议直接从 POST 数据中获取 name，减少数据库查询
        
        // 假设 order_id, game_id, item_id, quantity 是 INT, price 是 DOUBLE
        $stmt_item->bind_param("iiisid", $order_id, $game_id, $intItemId, $itemName, $qty, $price);
        
        if (!$stmt_item->execute()) {
            // 如果单个商品插入失败，我们记录错误，但继续循环（通常是外键问题）
            error_log("Failed to insert item {$intItemId} for order {$order_id}: " . $stmt_item->error);
        }
    }
    $stmt_item->close();

    // --- 5. 存储 ID 到 Session 并重定向 ---
    // 💡 增强：将 order_id 存入 Session，供 payment.php 页面作为回退机制使用。
    $_SESSION['current_order_id'] = $order_id; 
    
    // ✅ 关键增强：将用于创建订单的 user_id 也存入 session，防止 payment.php 回退到 User ID 1
    $_SESSION['order_creator_id'] = $user_id;

    header("Location: payment.php?order_id=" . $order_id);
    exit;
} else {
    die("Invalid request method.");
}
?>
