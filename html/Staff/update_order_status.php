<?php
session_start();
include "config.php";

// ⚠️ 实际应用中：在这里添加员工权限检查
$is_staff = true; // 假设用户已通过员工验证
if (!$is_staff) {
    header("Location: manage_orders.php?error=Access denied.");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = $_POST['order_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;

    // 验证数据
    if (!$order_id || !$new_status) {
        header("Location: manage_orders.php?error=Missing order ID or new status.");
        exit;
    }
    
    // 强制类型转换，以匹配数据库 (假设 order_id 是 INT)
    $order_id = (int)$order_id;
    $new_status = strtoupper(trim($new_status));

    // 更新数据库
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    
    if (!$stmt) {
        header("Location: manage_orders.php?error=Database Prepare Error: " . urlencode($conn->error));
        exit;
    }

    // s = string, i = integer
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
