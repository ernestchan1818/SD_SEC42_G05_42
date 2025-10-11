<?php
session_start();
include "config.php";

// Set timezone for accurate receipt time
date_default_timezone_set('Asia/Kuala_Lumpur');

// 检查用户是否登录
$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    // 假设未登录用户ID为 1 进行测试
    $user_id = 1; 
}

// --- Helper Function ---
function getImagePath($path) {
    $default = "uploads/default.png";
    if (!$path) return $default;
    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);
    return $path ?: $default;
}
// --- END Helper Function ---

$order_id = $_GET['id'] ?? 0;
$order_id = (int)$order_id;
$error_message = '';
$package_details = null;
$package_contents = [];
$items = [];
$order_game_id = 0;
$order_data = null; // 确保在作用域内声明

// --- 1. 查询订单主信息 ---
if ($order_id === 0) {
    $error_message = "Invalid order ID provided. Please go back to the orders list.";
} else {
    $stmt = $conn->prepare("SELECT total, status, created_at, game_id, payment_type FROM orders WHERE order_id = ? AND user_id = ?");
    
    if (!$stmt) {
        $error_message = "Database Prepare Error: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $order_result = $stmt->get_result();

        if ($order_result->num_rows === 0) {
            $error_message = "Order #{$order_id} not found or you lack permission to view it.";
        } else {
            $order = $order_result->fetch_assoc();
            $current_status = strtoupper($order['status']) ?: 'PENDING';
            $order_game_id = $order['game_id'];
            $order_data = $order; // 将订单数据存储在 $order_data 以供 HTML 部分使用
            $stmt->close();

            // --- 2. 尝试查询单品商品详情 ---
            $item_query = "
                SELECT 
                    oi.quantity, 
                    oi.price, 
                    gi.item_name, 
                    gi.image 
                FROM order_items oi
                LEFT JOIN game_items gi ON oi.item_id = gi.item_id
                WHERE oi.order_id = ?
            ";
            $item_stmt = $conn->prepare($item_query);
            $item_stmt->bind_param("i", $order_id);
            $item_stmt->execute();
            $items_result = $item_stmt->get_result();
            while ($row = $items_result->fetch_assoc()) {
                if ($row['item_name'] !== null) {
                    $items[] = $row;
                }
            }
            $item_stmt->close();
            
            // --- 3. 如果 items 为空 (即套餐购买)，查询套餐详情和内容 ---
            if (empty($items) && $order_game_id > 0) {
                // a) 查询套餐主信息 (Package Summary)
                $pkg_stmt = $conn->prepare("
                    SELECT package_name, image, discount, price AS list_price
                    FROM topup_packages 
                    WHERE package_id = ?
                ");
                if ($pkg_stmt) {
                    $pkg_stmt->bind_param("i", $order_game_id);
                    $pkg_stmt->execute();
                    $package_details = $pkg_stmt->get_result()->fetch_assoc();
                    $pkg_stmt->close();
                }

                // b) 查询套餐内含商品 (Package Contents)
                if ($package_details) {
                    // ** 修正：这里是导致 'Commands out of sync' 的潜在原因。
                    // 确保使用新的 prepare/execute/close 流程。 **
                    $content_query = $conn->prepare("
                        SELECT gi.item_name, gi.image, gi.price AS unit_price
                        FROM package_items pi
                        JOIN game_items gi ON pi.item_id = gi.item_id
                        WHERE pi.package_id = ?
                    ");
                    if ($content_query) {
                        $content_query->bind_param("i", $order_game_id);
                        $content_query->execute();
                        $content_result = $content_query->get_result(); // 确保结果集被完全获取
                        while ($row = $content_result->fetch_assoc()) {
                            $package_contents[] = $row;
                        }
                        $content_query->close(); // 确保关闭
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $order_id ?></title>
    <style>
        body { 
            font-family: 'Inter', Arial, sans-serif; 
            background: #f4f4f4; 
            color: #333; 
            margin: 0; 
            padding: 20px;
        }
        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            color: #007BFF;
            text-align: center;
            border-bottom: 3px solid #007BFF;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }
        .info-block {
            margin-bottom: 20px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .info-block p {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            font-size: 0.95em;
        }
        .info-block span {
            font-weight: bold;
            color: #555;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .item-table th, .item-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .item-table th {
            background-color: #f7f7f7;
            color: #333;
            font-weight: bold;
        }
        .item-name-col {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .item-name-col img {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 4px;
        }
        .total-row {
            font-size: 1.2em;
            font-weight: bold;
            background-color: #e9f5ff;
            color: #007BFF;
        }
        .total-row td:last-child {
            text-align: right;
        }
        .package-row td {
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .package-item-list {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px dashed #eee;
            font-size: 0.9em;
            color: #666;
        }
        .print-btn {
            display: block;
            margin: 30px auto 0;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        @media print {
            .print-btn {
                display: none;
            }
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>

<?php if ($error_message): ?>
    <div style="text-align: center; color: red; margin-top: 50px;">
        <h2>Receipt Error</h2>
        <p><?= htmlspecialchars($error_message) ?></p>
    </div>
<?php else: ?>
<div class="receipt-container">
    <h1>DJS Game Top-Up Receipt</h1>

    <div class="info-block">
        <p>Receipt ID: <span>#<?= htmlspecialchars($order_id) ?></span></p>
        <p>Date: <span><?= date("Y-m-d H:i:s", strtotime($order['created_at'])) ?></span></p>
        <p>Customer: <span><?= htmlspecialchars($order_data['username'] ?? 'N/A') ?></span></p>
        <p>Payment Method: <span><?= htmlspecialchars($order['payment_type']) ?></span></p>
        <p>Status: <span style="color: <?= $order['status'] === 'DELIVERED' ? '#28a745' : '#ffc107' ?>;"><?= htmlspecialchars($order['status']) ?></span></p>
    </div>

    <table class="item-table">
        <thead>
            <tr>
                <th>Item / Package</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: right;">Price (RM)</th>
                <th style="text-align: right;">Subtotal (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($package_details): ?>
                <!-- 场景 1: 购买套餐 -->
                <tr class="package-row">
                    <td>
                        <div class="item-name-col">
                            <img src="<?= htmlspecialchars(getImagePath($package_details['image'])) ?>" alt="Package Image">
                            <div>
                                <strong><?= htmlspecialchars($package_details['package_name']) ?> (Package)</strong>
                                <br><small style="color: #dc3545;">Discount: <?= number_format($package_details['discount'], 2) ?>%</small>
                            </div>
                        </div>
                        <?php if (!empty($package_contents)): ?>
                            <div class="package-contents-list">
                                <p style="font-weight: bold; margin: 0 0 5px;">Contained Items:</p>
                                <?php foreach ($package_contents as $content): ?>
                                    <small>&mdash; <?= htmlspecialchars($content['item_name']) ?> (RM <?= number_format($content['unit_price'], 2) ?>)</small><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">1</td>
                    <td style="text-align: right;"><?= number_format($order['total'], 2) ?></td>
                    <td style="text-align: right;"><?= number_format($order['total'], 2) ?></td>
                </tr>
            <?php elseif (!empty($items)): ?>
                <!-- 场景 2: 购买单品 -->
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="item-name-col">
                                <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                                <span><?= htmlspecialchars($item['item_name']) ?></span>
                            </div>
                        </td>
                        <td style="text-align: center;"><?= htmlspecialchars($item['quantity']) ?></td>
                        <td style="text-align: right;"><?= number_format($item['price'], 2) ?></td>
                        <td style="text-align: right;"><?= number_format($item['quantity'] * $item['price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                 <tr><td colspan="4" style="text-align: center; color: #777;">No item details found.</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3">Total Paid</td>
                <td>RM <?= number_format($order['total'], 2) ?></td>
            </tr>
        </tfoot>
    </table>
    
    <p style="text-align: center; margin-top: 30px; font-size: 0.9em; color: #777;">Thank you for your business!</p>

    <button class="print-btn" onclick="window.print()">Print Receipt</button>
</div>
<?php endif; ?>

</body>
</html>
