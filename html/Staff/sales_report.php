<?php
session_start();
include "config.php"; // 确保 $conn 是 mysqli 连接

// ⚠️ 实际应用中：在这里添加员工权限检查
$is_staff = true; // 假设用户已通过员工验证
if (!$is_staff) {
    die("Access denied. Staff access required.");
}

// 假设只有 'COMPLETE_PAYMENT' 和 'DELIVERED' 状态的订单计入销售额
$valid_statuses = "'COMPLETE_PAYMENT', 'DELIVERED'";

// --- 1. 获取汇总数据 (Total Revenue & Total Orders) ---
$summary_query = "
    SELECT 
        COUNT(order_id) as total_orders, 
        IFNULL(SUM(total), 0) as total_revenue
    FROM orders 
    WHERE status IN ($valid_statuses)
";
$summary_result = $conn->query($summary_query);
$summary_data = $summary_result->fetch_assoc();

// --- 2. 获取每月销售额 ---
$monthly_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as order_month, 
        SUM(total) as monthly_revenue
    FROM orders 
    WHERE status IN ($valid_statuses)
    GROUP BY order_month
    ORDER BY order_month DESC
";
$monthly_result = $conn->query($monthly_query);
$monthly_sales = $monthly_result->fetch_all(MYSQLI_ASSOC);

// --- 3. 获取畅销商品 (Top Selling Items) ---
$top_items_query = "
    SELECT 
        gi.item_name, 
        SUM(oi.quantity) as total_quantity_sold,
        SUM(oi.quantity * oi.price) as item_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN game_items gi ON oi.item_id = gi.item_id
    WHERE o.status IN ($valid_statuses)
    GROUP BY gi.item_name
    ORDER BY total_quantity_sold DESC
    LIMIT 10
";
$top_items_result = $conn->query($top_items_query);
$top_items = $top_items_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff: Sales Report</title>
<style>
/* --- Blue/White Palette --- */
body { 
    font-family: 'Inter', sans-serif; 
    background: #FFFFFF; /* 修正：白色背景 */
    color: #333; /* 字体颜色为深色 */
    margin: 0; 
    padding: 0; 
}
/* 头部主标题 */
header { 
    background: #007BFF; /* 蓝色头部背景 */
    padding: 15px 30px; /* 调整 padding */
    display: flex; 
    justify-content: space-between; /* 调整布局 */
    align-items: center; 
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}
header h1 { 
    margin: 0; 
    font-size: 24px; 
    color: #fff; /* 白色标题 */
    text-align: left;
}
/* 导航栏样式 */
nav {
    display: flex;
    gap: 15px;
}
nav a {
    color: #fff;
    text-decoration: none;
    padding: 5px 10px;
    border-radius: 4px;
    transition: background 0.3s;
    font-weight: 500;
}
nav a:hover {
    background: #0056B3; /* 深蓝色悬停 */
}

.container { 
    max-width: 1100px; 
    margin: 40px auto; 
    padding: 20px; 
}
h2 { 
    color: #007BFF; /* 蓝色标题 */
    border-bottom: 2px solid #007BFF; 
    padding-bottom: 10px; 
    margin-bottom: 30px; 
}
/* Summary Cards */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}
.summary-card {
    background: #FFFFFF;
    padding: 25px;
    border-radius: 12px;
    /* 修正：深蓝色阴影 */
    box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3); 
    transition: transform 0.2s;
}
.summary-card:hover {
    transform: translateY(-5px);
}
.summary-card h3 {
    margin: 0 0 10px;
    font-size: 1.1em;
    color: #6C757D;
}
.summary-card .value {
    font-size: 2.5em;
    font-weight: bold;
    color: #007BFF;
}
.summary-card .currency {
    font-size: 0.7em;
    font-weight: normal;
}
.summary-card.revenue .value {
    color: #28A745; /* Green for Revenue */
}

/* Report Tables */
table {
    width: 100%;
    border-collapse: collapse;
    background: #FFFFFF;
    border-radius: 8px;
    overflow: hidden;
    /* 修正：深蓝色阴影 */
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3); 
    margin-bottom: 40px;
}
th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #E0E0E0;
    font-size: 0.95em;
}
th {
    background: #007BFF;
    color: #fff;
    text-transform: uppercase;
}
tr:nth-child(even) {
    background-color: #F8F9FA;
}
tr:hover {
    background-color: #e9f5ff; /* 浅蓝色悬停 */
}
.revenue-column {
    font-weight: bold;
    color: #28A745;
}
</style>
</head>
<body>

<header>
    <h1>Staff Portal: Sales Report</h1>
    <nav>
    <?php
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            echo '<a href="admin_home.php">Home</a>';
        } elseif ($_SESSION['role'] === 'staff') {
            echo '<a href="staff_home.php">Home</a>';
        } 
    } else {
        echo '<a href="staff_home.php">Home</a>';
    }
    ?>
        <a href="manage_orders.php">Manage Orders</a>
        <a href="sales_report.php">Sales Report</a>
        <a href="Contact.php">Contact</a>
        <a href="contactus.php">Feedback</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="signout.php">Sign Out</a>
    </nav>
</header>

<div class="container">
    <h2>Sales Report & Analytics</h2>

    <!-- Summary Grid -->
    <div class="summary-grid">
        <div class="summary-card revenue">
            <h3>TOTAL REVENUE (Completed)</h3>
            <div class="value">
                <span class="currency">RM</span> <?= number_format($summary_data['total_revenue'], 2) ?>
            </div>
        </div>
        <div class="summary-card">
            <h3>TOTAL ORDERS (Completed)</h3>
            <div class="value">
                <?= number_format($summary_data['total_orders']) ?>
            </div>
        </div>
    </div>

    <!-- Monthly Sales Table -->
    <h2>Monthly Sales Breakdown</h2>
    <?php if (!empty($monthly_sales)): ?>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Revenue (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly_sales as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['order_month']) ?></td>
                <td class="revenue-column">RM <?= number_format($row['monthly_revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No monthly sales data found for completed orders.</p>
    <?php endif; ?>

    <!-- Top Selling Items Table -->
    <h2>Top 10 Selling Items</h2>
    <?php if (!empty($top_items)): ?>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Item Name</th>
                <th>Quantity Sold</th>
                <th>Revenue Generated (RM)</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1; foreach ($top_items as $item): ?>
            <tr>
                <td><?= $rank++ ?></td>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= number_format($item['total_quantity_sold']) ?></td>
                <td class="revenue-column">RM <?= number_format($item['item_revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No top-selling item data found for completed orders.</p>
    <?php endif; ?>

</div>

</body>
</html>
