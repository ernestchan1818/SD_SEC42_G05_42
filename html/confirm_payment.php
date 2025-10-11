<?php
session_start();
include "config.php";

// --- 1. 获取和验证数据 ---
// 假设未登录用户 ID 为 1，生产环境中应强制登录
$user_id = $_SESSION['user_id'] ?? 1; 
// 优先从 POST 获取 order_id，如果缺失，则从 Session 获取 (以防 POST 丢失)
$order_id = $_POST['order_id'] ?? $_SESSION['current_order_id'] ?? '';
$action = $_POST['action'] ?? ''; // 期望是 'confirm'

// ✅ 修正：强制将 ID 转换为整数，确保与数据库的 INT 类型匹配
$order_id = (int)$order_id;
$user_id = (int)$user_id;

if ($order_id === 0) { // 如果强制转换后为 0，说明 order_id 无效
    // 订单 ID 缺失，重定向到追踪页面并报错
    header("Location: my_order.php?error=Missing order ID to confirm payment.");
    exit;
}

// 确认付款，设定新状态
if ($action === "confirm") {
    $newStatus = "COMPLETE_PAYMENT";
} else {
    // 如果没有明确的 'confirm' 动作，默认为等待付款
    $newStatus = "WAIT_FOR_PAYMENT"; 
}

// --- 2. 检查订单当前状态并更新 ---
$check_stmt = $conn->prepare("SELECT status FROM orders WHERE order_id=? AND user_id=?");
if (!$check_stmt) die("Prepare Error: " . $conn->error);

// 绑定时使用 "ii" (两个整数)
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$current_order = $result->fetch_assoc();
$check_stmt->close();

if (!$current_order) {
    // 找不到订单，可能是用户 ID 不匹配
    header("Location: my_order.php?error=Order #{$order_id} not found or access denied.&id=" . $order_id);
    exit;
}

if ($current_order['status'] === "COMPLETE_PAYMENT") {
    // 订单已经完成付款，不需要重复更新
    header("Location: my_order.php?message=Order #{$order_id} already confirmed as paid.&id=" . $order_id);
    exit;
}

// --- 3. 执行更新 ---
$update_stmt = $conn->prepare("UPDATE orders SET status=? WHERE order_id=? AND user_id=?");
if (!$update_stmt) die("Update Prepare Error: " . $conn->error);

// 绑定时使用 "sii" (字符串，整数，整数)
$update_stmt->bind_param("sii", $newStatus, $order_id, $user_id); 
$update_stmt->execute();

if ($update_stmt->affected_rows > 0) {
    // 更新成功，清除 session 中的当前订单 ID，防止再次加载旧订单
    unset($_SESSION['current_order_id']); 
    
    // 跳转到订单追踪页面并显示成功信息
    header("Location: my_order.php?message=Payment for order #{$order_id} confirmed successfully!&id=" . $order_id);
} else {
    // 更新失败 (如果 update_stmt->affected_rows 为 0，通常是因为 WHERE 条件不匹配)
    header("Location: my_order.php?error=Update failed. Order #{$order_id} may not exist for this user.&id=" . $order_id);
}

$update_stmt->close();
exit;

?>
