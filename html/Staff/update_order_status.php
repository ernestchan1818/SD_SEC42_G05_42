<?php
session_start();
// 假设 config.php 在 manage_orders.php 所在的目录，如果不在，请调整路径
include "config.php"; 

// --- 1. 员工权限检查 ---
// 检查用户是否登录，并且角色是否为 'staff' 或 'admin'
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../login.php?error=Access denied."); // 跳转到登录页面
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = $_POST['order_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;

    // --- 2. 验证数据 ---
    if (!$order_id || !$new_status) {
        header("Location: manage_orders.php?error=" . urlencode("Missing order ID or new status."));
        exit;
    }
    
    // 强制类型转换，以匹配数据库 (假设 order_id 是 INT)
    $order_id = (int)$order_id;
    $new_status = strtoupper(trim($new_status)); 

    // 状态值白名单检查
    $valid_statuses = ['PENDING', 'WAIT_FOR_PAYMENT', 'COMPLETE_PAYMENT', 'PROCESSING', 'DELIVERED'];
    if (!in_array($new_status, $valid_statuses)) {
        header("Location: manage_orders.php?error=" . urlencode("Invalid status value provided: {$new_status}"));
        exit;
    }

    // --- 3. 更新数据库 ---
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    
    if (!$stmt) {
        $error = "Database Prepare Error: " . $conn->error;
        header("Location: manage_orders.php?error=" . urlencode($error));
        exit;
    }

    // s = string (status), i = integer (order_id)
    $stmt->bind_param("si", $new_status, $order_id); 
    
    if ($stmt->execute()) {
        $message = "Order #{$order_id} status updated to {$new_status} successfully.";
        header("Location: manage_orders.php?message=" . urlencode($message));
    } else {
        $error = "Failed to update order status for #{$order_id}. DB Error: " . $stmt->error;
        header("Location: manage_orders.php?error=" . urlencode($error));
    }
    
    $stmt->close();
    exit; 

} else {
    // 非 POST 请求，重定向
    header("Location: manage_orders.php");
    exit;
}
?>
