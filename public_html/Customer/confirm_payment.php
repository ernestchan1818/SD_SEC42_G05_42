<?php
// 必须在任何输出之前启动输出缓冲
ob_start(); 
session_start();
include "config.php"; // 确保包含数据库配置

// 设置时区，防止时间戳问题
date_default_timezone_set('Asia/Kuala_Lumpur');

// --- 1. 获取和验证输入 ---
$order_id = $_POST['order_id'] ?? null;
$action = $_POST['action'] ?? null;
$user_id = $_SESSION['user_id'] ?? 1; // 使用当前登录用户ID

// 用于重定向的函数
function safeRedirect($conn, $order_id, $is_success, $message) {
    if ($conn) $conn->close();
    
    // 构建目标 URL，跳转到订单追踪页面
    $base_url = "my_order.php";
    $query_params = "id=" . urlencode($order_id);
    
    if ($is_success) {
        $query_params .= "&message=" . urlencode($message);
    } else {
        $query_params .= "&error=" . urlencode($message);
    }
    
    $final_url = $base_url . "?" . $query_params;

    // 优先使用 PHP header 重定向
    if (!headers_sent()) {
        header("Location: " . $final_url);
        ob_end_flush();
        exit;
    } else {
        // PHP 重定向失败时，使用 JavaScript 强制跳转 (这是防止循环的关键)
        // 任何页面输出都会触发此回退
        echo "<script type='text/javascript'>";
        echo "window.location.href = '" . $final_url . "';";
        echo "</script>";
        echo "<p>Redirecting to <a href='{$final_url}'>Order Status</a>...</p>";
        ob_end_flush();
        exit;
    }
}

if (empty($order_id) || empty($action) || !is_numeric($order_id)) {
    safeRedirect($conn, 0, false, "❌ Invalid request parameters or missing order ID.");
}

// 强制将 ID 转换为整数
$order_id = (int)$order_id;
$user_id = (int)$user_id;

if ($action === 'confirm') {
    // ✅ 修正：将状态改为 COMPLETE_PAYMENT
    $new_status = 'COMPLETE_PAYMENT'; 
    $payment_time = date('Y-m-d H:i:s');

    // --- 2. 检查订单状态并更新 ---
    // 查询当前状态是为了提供更精确的错误/提示信息
    $check_stmt = $conn->prepare("SELECT status FROM orders WHERE order_id=? AND user_id=?");
    
    if (!$check_stmt) {
        safeRedirect($conn, $order_id, false, "Database Prepare Error: " . $conn->error);
    }

    $check_stmt->bind_param("ii", $order_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $current_order = $result->fetch_assoc();
    $check_stmt->close();

    if (!$current_order) {
        safeRedirect($conn, $order_id, false, "Order #{$order_id} not found or access denied.");
    }

    if ($current_order['status'] === "COMPLETE_PAYMENT" || $current_order['status'] === "DELIVERED" || $current_order['status'] === "PROCESSING") {
        safeRedirect($conn, $order_id, false, "⚠️ Order #{$order_id} status is already {$current_order['status']}. No update performed.");
    }

    // --- 3. 执行更新 (更新状态和支付时间) ---
    $update_stmt = $conn->prepare("UPDATE orders SET status=?, payment_time=? WHERE order_id=? AND user_id=?");
    
    if (!$update_stmt) {
        safeRedirect($conn, $order_id, false, "Update Prepare Error: " . $conn->error);
    }

    // 绑定时使用 "ssii" (string status, string payment_time, integer order_id, integer user_id)
    $update_stmt->bind_param("ssii", $new_status, $payment_time, $order_id, $user_id); 

    if ($update_stmt->execute()) {
        if ($update_stmt->affected_rows > 0) {
            // 清除 session 中的当前订单 ID
            unset($_SESSION['current_order_id']); 
            safeRedirect($conn, $order_id, true, "✅ Payment for order #{$order_id} confirmed successfully! Proceeding to tracking.");
        } else {
            // affected_rows 为 0，但未进入前一个 if (状态检查)，说明 WHERE 条件失败
            safeRedirect($conn, $order_id, false, "Update failed. Order #{$order_id} not found or status is unchanged.");
        }
    } else {
        safeRedirect($conn, $order_id, false, "Update failed: " . $update_stmt->error);
    }

    $update_stmt->close();
} else {
    safeRedirect($conn, $order_id, false, "Invalid action requested.");
}
?>
