<?php
session_start();
include "config.php";

$message = "";

// --------- Delete Customer ----------
if (isset($_POST['delete_id'])) {
    $delId = intval($_POST['delete_id']);
    if ($delId > 0) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $delId);
        if ($stmt->execute()) {
            $message = "✅ Customer deleted successfully.";
        } else {
            $message = "❌ Error deleting customer: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --------- Edit Customer ----------
if (isset($_POST['edit_customer'])) {
    $customerId = intval($_POST['customer_id']);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $created_at = trim($_POST['created_at'] ?? '');

    if ($customerId > 0 && $username !== '' && $email !== '' && $created_at !== '') {
        
        // 检查新的 email 是否已被其他用户使用
        $stmtCheck = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmtCheck->bind_param("si", $email, $customerId);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $message = "❌ This email is already used by another customer.";
            $stmtCheck->close();
        } else {
            $stmtCheck->close();
            
            // 更新用户资料
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, created_at = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $email, $created_at, $customerId);
            if ($stmt->execute()) {
                $message = "✅ Customer details updated successfully.";
            } else {
                $message = "❌ Error updating customer: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $message = "❌ Please provide all required fields to edit customer.";
    }
}

// 读取所有用户
$sql = "SELECT id, username, email, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Customers</title>
<style>
/* --- Base Styles --- */
body { 
    font-family: 'Inter', Arial, sans-serif; 
    background: #ffffff; /* 改为白色 */
    color: #333; /* 字体颜色改为深色以适应白色背景 */
    margin: 0; 
    padding: 0; 
}
.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 20px;
}
h1 { 
    text-align: center; 
    color: #007BFF; /* 亮蓝色标题 */
    margin-bottom: 30px;
    font-size: 2.5em;
    text-shadow: 0 0 5px rgba(0, 123, 255, 0.2); /* 调整阴影以适应白色背景 */
}

/* --- Header & Navigation (Unified Blue/White Theme) --- */
header { 
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: #007BFF; /* 蓝色头部背景 */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); /* 调整阴影以适应白色背景 */
    position: sticky;
    top: 0;
    z-index: 1000;
}
.logo {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}
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

/* --- Message Box --- */
.message { 
    text-align: center; 
    margin: 10px auto 20px; 
    font-weight: 600; 
    padding: 12px 18px; 
    border-radius: 6px; 
    width: 90%; 
    max-width: 900px;
    border: 1px solid;
    /* 动态颜色处理 */
    <?php if (str_starts_with($message, '✅')): ?>
        color: #10b981; 
        background: rgba(16, 185, 129, 0.1); 
        border-color: #10b981;
    <?php elseif (str_starts_with($message, '❌')): ?>
        color: #dc3545; 
        background: rgba(220, 53, 69, 0.1); 
        border-color: #dc3545;
    <?php endif; ?>
}

/* --- Table Styles --- */
table { 
    width: 90%; 
    max-width: 1100px; 
    margin: 0 auto; 
    border-collapse: collapse; 
    background: #ffffff; /* 改为白色 */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* 调整阴影以适应白色背景 */
    border-radius: 12px; 
    overflow: hidden; 
}
th, td { 
    padding: 14px 15px; 
    text-align: center; 
    border-bottom: 1px solid #e0e0e0; /* 浅色分割线 */
    color: #333; /* 字体颜色改为深色 */
}
th { 
    background: #007bff; 
    color: #fff; 
    font-weight: 600; 
    text-transform: uppercase;
}
tr:nth-child(even) td {
    background: #f8f8f8; /* 斑马线效果，浅灰色 */
}
tr:hover td { 
    background: #e9f5ff; /* 悬停背景，浅蓝色 */
}

/* --- Action Buttons --- */
.btn { 
    padding: 8px 12px; 
    border-radius: 6px; 
    border: none; 
    cursor: pointer; 
    font-weight: 600; 
    margin: 2px;
    transition: background 0.3s;
}
.delete-btn { 
    background: #dc3545; 
    color: #fff; 
}
.delete-btn:hover {
    background: #c82333;
}
.edit-btn { 
    background: #ffc107; 
    color: #333; /* 适应白色背景 */
}
.edit-btn:hover {
    background: #e0a800;
}

/* --- Modal Styles --- */
.modal {
    display:none; 
    position:fixed; 
    z-index:9999; 
    left:0; 
    top:0; 
    width:100%; 
    height:100%;
    background-color: rgba(0,0,0,0.6); 
    justify-content:center; 
    align-items:center;
}
.modal-content {
    background:#ffffff; /* 改为白色 */
    padding:30px; 
    border-radius:10px; 
    width:400px; 
    max-width:95%; 
    text-align:center;
    box-shadow:0 10px 30px rgba(0, 0, 0, 0.2); /* 调整阴影 */
}
.modal-content h3 { 
    margin-bottom:20px; 
    color: #007BFF;
    border-bottom: 1px solid #e0e0e0; /* 调整分割线颜色 */
    padding-bottom: 5px;
}
.modal-content input { 
    padding:10px; 
    width:80%; 
    margin:10px 0; 
    background: #f8f8f8; /* 浅色输入框背景 */
    border: 1px solid #ccc; /* 浅色边框 */
    color: #333; /* 字体颜色改为深色 */
    border-radius: 6px;
}
.save-btn { 
    background:#28a745; 
    color:#fff; 
}
.save-btn:hover {
    background: #1e8b4e;
}
.cancel-btn { 
    background:#6c757d; 
    color:#fff; 
}
.cancel-btn:hover {
    background: #5a6268;
}
</style>
<script>
// 修改 openEditModal 函数，现在它接收 username 和 email
function openEditModal(customerId, username, email, createdAt) {
    // 将日期格式化为 YYYY-MM-DD，确保 input type="date" 能正确显示
    const datePart = createdAt.split(' ')[0]; 

    document.getElementById('modal').style.display = 'flex';
    document.getElementById('edit_customer_id').value = customerId;
    document.getElementById('edit_username').value = username; // 新增
    document.getElementById('edit_email').value = email;     // 新增
    document.getElementById('edit_created_at').value = datePart;
}
function closeModal() {
    document.getElementById('modal').style.display = 'none';
}
</script>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <?php
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                echo '<a href="admin_home.php">Home</a>';
            } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') { // 确保 isset 检查
                echo '<a href="staff_home.php">Home</a>';
            } 
        } 
        ?>
        <a href="Contact.php">Contact</a>
        <a href="contactus.php">Feedback</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="logoutS.php">Sign Out</a>
    </nav>
</header>

<div class="container">
    <h1>Manage Customers</h1>

    <?php if ($message !== ""): ?>
        <div class="message"><?= htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Customer Table -->
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Created At</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id']; ?></td>
                <td><?= htmlspecialchars($row['username']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= date('Y-m-d', strtotime($row['created_at'])); ?></td>
                <td>
                    <!-- 修正：现在传递 username 和 email 到 modal -->
                    <button class="btn edit-btn" type="button" 
                        onclick="openEditModal(
                            '<?= $row['id']; ?>',
                            '<?= htmlspecialchars($row['username']); ?>',
                            '<?= htmlspecialchars($row['email']); ?>',
                            '<?= date('Y-m-d H:i:s', strtotime($row['created_at'])); ?>'
                        )">Edit</button>
                    <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure to delete this customer?');">
                        <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                        <button class="btn delete-btn" type="submit">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <!-- Edit Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h3>Edit Customer Details</h3>
            <form method="post">
                <input type="hidden" name="customer_id" id="edit_customer_id">
                
                <label for="edit_username" style="color: #666; display: block; margin-bottom: 5px; text-align: left; width: 80%; margin: 0 auto;">Username:</label>
                <input type="text" name="username" id="edit_username" placeholder="Username" required>
                
                <label for="edit_email" style="color: #666; display: block; margin-bottom: 5px; text-align: left; width: 80%; margin: 0 auto;">Email:</label>
                <input type="email" name="email" id="edit_email" placeholder="Email" required>

                <label for="edit_created_at" style="color: #666; display: block; margin-bottom: 5px; text-align: left; width: 80%; margin: 0 auto;">Join Date:</label>
                <input type="date" name="created_at" id="edit_created_at" required><br>
                
                <button type="submit" name="edit_customer" class="save-btn">Save Changes</button>
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
