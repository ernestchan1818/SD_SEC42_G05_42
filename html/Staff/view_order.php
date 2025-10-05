<?php
include "config.php";
session_start();

// ✅ 更新发货状态
if (isset($_POST['deliver_order_id'])) {
    $deliver_id = $_POST['deliver_order_id'];
    $update = $conn->prepare("UPDATE orders SET status='DELIVERED' WHERE order_id=?");
    $update->bind_param("i", $deliver_id);
    $update->execute();
    echo "<p style='color:lime; font-weight:bold;'>✅ Order #$deliver_id marked as DELIVERED</p>";
}

// ✅ 查询所有订单
$sql = "SELECT * FROM orders ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff - View Orders</title>
<style>
body {
  font-family: Arial, sans-serif;
  background: #000;
  color: #fff;
  padding: 30px;
  margin: 0;
}
h1 {
  text-align: center;
  color: #ff6600;
  margin-bottom: 30px;
}
.details {
  background: #1a1a1a;
  margin: 20px auto;
  padding: 20px;
  border-radius: 10px;
  max-width: 900px;
  box-shadow: 0 4px 15px rgba(255,102,0,0.3);
}
.details h2 {
  color: #ff6600;
  margin-bottom: 10px;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}
th, td {
  border: 1px solid #333;
  padding: 10px;
  text-align: center;
}
th {
  background: #222;
  color: #ffcc80;
}
td {
  background: #111;
}
button {
  background: #ff6600;
  color: white;
  border: none;
  padding: 8px 14px;
  border-radius: 6px;
  cursor: pointer;
  margin-top: 10px;
  font-weight: bold;
}
button:hover { background: #e65c00; }
.delivered {
  color: #00ff88;
  font-weight: bold;
  margin-top: 10px;
}
</style>
</head>
<body>

<h1>📦 Staff Order Management</h1>

<?php
if ($result && $result->num_rows > 0) {
    while ($order = $result->fetch_assoc()) {
        echo "<div class='details'>";
        echo "<h2>Order #{$order['order_id']} | User ID: {$order['user_id']} | Status: {$order['status']}</h2>";
        echo "<p>Total: <strong>RM {$order['total']}</strong> | Payment: {$order['payment_type']} | Date: {$order['created_at']}</p>";

        $order_id = $order['order_id'];

        // ✅ 查询该订单对应的项目
        $query_items = $conn->prepare("SELECT item_name, quantity, price FROM order_items WHERE order_id=?");
        $query_items->bind_param("i", $order_id);
        $query_items->execute();
        $result_items = $query_items->get_result();

        echo "<table>
                <tr><th>Item Name</th><th>Quantity</th><th>Price (RM)</th><th>Subtotal (RM)</th></tr>";
        if ($result_items->num_rows > 0) {
            while ($item = $result_items->fetch_assoc()) {
                $subtotal = $item['quantity'] * $item['price'];
                echo "<tr>
                        <td>{$item['item_name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>{$item['price']}</td>
                        <td>" . number_format($subtotal, 2) . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No items found.</td></tr>";
        }
        echo "</table>";

        // ✅ 发货状态
        if ($order['status'] !== 'DELIVERED') {
            echo "<form method='POST'>
                    <input type='hidden' name='deliver_order_id' value='{$order['order_id']}'>
                    <button type='submit'>Mark as Delivered ✅</button>
                  </form>";
        } else {
            echo "<div class='delivered'>✅ Already Delivered</div>";
        }

        echo "</div>";
    }
} else {
    echo "<p style='text-align:center;'>No orders found.</p>";
}
?>

</body>
</html>
