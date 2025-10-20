<?php
session_start();
include "config.php"; 

// --- 1. 获取订单数据 ---
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$total = isset($_GET['total']) ? floatval($_GET['total']) : 0;
$user_id = $_SESSION['user_id'] ?? 0; // 从 session 获取当前用户ID

if ($order_id === 0 || $total <= 0) {
    die("Error: Invalid Order ID or Total Amount.");
}

if ($user_id === 0) {
    // 阻止未登录用户创建账单
    die("Error: User session not found.");
}

// ToyyibPay expects amounts in cents (RM 1.00 = 100)
$billAmount = round($total * 100); 

// --- 2. 修正：更新数据库中的 payment_type 为 FPX ---
$update_stmt = $conn->prepare("UPDATE orders SET payment_type = 'FPX' WHERE order_id = ?");
if ($update_stmt) {
    $update_stmt->bind_param("i", $order_id);
    if (!$update_stmt->execute()) {
        error_log("DB Error: Failed to update payment_type for Order #{$order_id}");
    }
    $update_stmt->close();
}

// --- 3. 修正：从数据库获取当前登录用户的联系信息 ---
// ✅ 关键修正：移除 phone 字段查询，以解决 "Unknown column" 错误
$user_stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$user_data = null;

if ($user_stmt) {
    $user_stmt->bind_param("i", $user_id);
    // 确保 prepare/execute 成功
    if ($user_stmt->execute()) {
        $user_result = $user_stmt->get_result();
        $user_data = $user_result->fetch_assoc();
    } else {
         error_log("DB Error fetching user data: " . $user_stmt->error);
    }
    $user_stmt->close();
}

// 设置账单信息，如果数据库查询失败，则使用默认值
$customer_name = $user_data['username'] ?? 'Customer ' . $user_id; 
$customer_email = $user_data['email'] ?? 'default@example.com'; 
// 既然数据库没有 phone 字段，这里必须提供一个默认值给 ToyyibPay
$customer_phone = '0000000000'; 

$billDescription = "Top-up Order #{$order_id} via DJS Game Platform";

$data = array(
    'userSecretKey'=>'kv5qqn3j-8e2w-s4u9-mn23-wg4ykvulqe0q', // 替换为您自己的 Secret Key
    'categoryCode'=>'dvqe44uj', // 替换为您自己的 Category Code
    'billName'=>'DJS Game Top-Up',
    'billDescription'=>$billDescription,
    'billPriceSetting'=>1, 
    'billPayorInfo'=>1, // 确保这个设置为1
    'billAmount'=>$billAmount,
    
    // IMPORTANT: Return URL should handle the payment success logic
    'billReturnUrl'=>'https://overexuberant-solomon-overthinly.ngrok-free.dev/projectLucky/payment_success.php?order_id=' . $order_id,
    'billCallbackUrl'=>'https://overexuberant-solomon-overthinly.ngrok-free.dev/projectLucky/payment_callback.php',
    
    'billExternalReferenceNo'=> $order_id, 
    'billTo'=>$customer_name, // 客户姓名 (从数据库获取)
    'billEmail'=>$customer_email, // 客户邮箱 (从数据库获取)
    'billPhone'=>$customer_phone // 客户电话 (使用默认值或您知道存在的字段)
);

// --- 4. Send Request to ToyyibPay ---
$curl = curl_init();
curl_setopt($curl, CURLOPT_POST, 1);
curl_setopt($curl, CURLOPT_URL, 'https://toyyibpay.com/index.php/api/createBill');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
$result = curl_exec($curl);
curl_close($curl);

// --- 5. Process Response and Redirect ---
$obj = json_decode($result, true);

if (isset($obj[0]['BillCode'])) {
    // 成功创建账单，重定向用户到 ToyyibPay 支付页面
    header("Location: https://toyyibpay.com/" . $obj[0]['BillCode']);
    exit();
} else {
    // 创建账单失败
    error_log("ToyyibPay Error for Order #{$order_id}: " . $result);
    die("Error creating FPX bill. Please try Touch 'n Go or confirm payment manually.");
}
?>
