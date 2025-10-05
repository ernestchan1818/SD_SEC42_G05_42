<?php
include "config.php";
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    die("You must log in first to view your orders.");
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? "Guest";

// 查询该用户的订单
$stmt = $conn->prepare("SELECT order_id, total, status, created_at FROM orders WHERE user_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Track My Orders - DJS Game</title>
<style>
body {
    background-color: #000;
    color: #fff;
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}
header {
    background: #111;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
header .logo {
    font-size: 22px;
    font-weight: bold;
    color: #ff6600;
}
header nav a {
    color: #fff;
    text-decoration: none;
    margin-left: 20px;
    font-weight: bold;
}
header nav a:hover {
    color: #ff6600;
}

.container {
    max-width: 900px;
    margin: 40px auto;
    background: #1a1a1a;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 6px 20px rgba(255,102,0,0.3);
}
h1 {
    text-align: center;
    color: #ff6600;
    margin-bottom: 25px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    padding: 12px;
    text-align: center;
}
th {
    background: #ff6600;
    color: #fff;
    border: none;
}
tr:nth-child(even) {
    background-color: #222;
}
tr:nth-child(odd) {
    background-color: #2a2a2a;
}
td {
    border-bottom: 1px solid #333;
}
.status {
    font-weight: bold;
    color: #f39c12;
}
.status.complete {
    color: #00ff99;
}
.status.pending {
    color: #ff6600;
}
.empty {
    text-align: center;
    padding: 30px;
    font-size: 18px;
    color: #ccc;
}
.back-btn {
    display: block;
    text-align: center;
    background: #ff6600;
    color: #fff;
    padding: 12px 0;
    border-radius: 8px;
    width: 220px;
    margin: 25px auto 0;
    text-decoration: none;
    font-weight: bold;
}
.back-btn:hover {
    background: #e65c00;
}
</style>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <a href="home.php">Home</a>
        <a href="track_order.php">Track Orders</a>
    </nav>
</header>

<div class="container">
    <h1>Your Orders</h1>

    <?php if ($res->num_rows > 0): ?>
    <table>
        <tr>
            <th>Order ID</th>
            <th>Total (RM)</th>
            <th>Status</th>
            <th>Date</th>
        </tr>
        <?php while ($row = $res->fetch_assoc()): 
            $status = $row['status'] ?: 'Pending'; // 如果数据库没status，则默认Pending
            $statusClass = strtolower(str_replace(' ', '', $status));
        ?>
        <tr>
            <td><?= htmlspecialchars($row['order_id']) ?></td>
            <td><?= number_format($row['total'], 2) ?></td>
            <td class="status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <div class="empty">You have no orders yet.</div>
    <?php endif; ?>

    <a href="home.php" class="back-btn">⬅ Back to Home</a>
</div>

</body>
</html>
