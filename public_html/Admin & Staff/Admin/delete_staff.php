<?php
session_start();
include "config.php"; // 确保包含数据库配置

// 仅 admin 可用
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$message = "";

// --------- Add Staff ----------
if (isset($_POST['add_staff'])) {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');

    if ($email === '' || $username === '') {
        $message = "❌ Please provide username and email.";
    } else {
        $stmt = $conn->prepare("SELECT staffid FROM staff_admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "❌ This email is already used.";
            $stmt->close();
        } else {
            $stmt->close();

            // 生成唯一 staff ID
            do {
                $randomNumber = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $staffIdGenerated = 'S' . $randomNumber;

                $checkStmt = $conn->prepare("SELECT staffid FROM staff_admin WHERE staffid = ? LIMIT 1");
                $checkStmt->bind_param("s", $staffIdGenerated);
                $checkStmt->execute();
                $checkStmt->store_result();
            } while ($checkStmt->num_rows > 0);
            $checkStmt->close();

            $passwordHash = password_hash("12345", PASSWORD_BCRYPT); // 默认密码
            $stmt2 = $conn->prepare("INSERT INTO staff_admin (staffid, username, email, password, role, created_at) VALUES (?, ?, ?, ?, 'staff', NOW())");
            $stmt2->bind_param("ssss", $staffIdGenerated, $username, $email, $passwordHash);

            if ($stmt2->execute()) {
                $message = "✅ Staff added successfully. Staff ID: {$staffIdGenerated} (default password = 12345)";
            } else {
                $message = "❌ Error adding staff: " . $stmt2->error;
            }
            $stmt2->close();
        }
    }
}

// --------- Edit Staff ----------
if (isset($_POST['edit_staff'])) {
    $staffId = trim($_POST['staff_id'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $created_at = trim($_POST['created_at'] ?? '');

    if ($staffId === '' || $username === '' || $email === '' || $created_at === '') {
        $message = "❌ Please provide all required fields to edit staff.";
    } else {
        $stmtCheck = $conn->prepare("SELECT staffid FROM staff_admin WHERE email = ? AND staffid != ? LIMIT 1");
        $stmtCheck->bind_param("ss", $email, $staffId);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $message = "❌ This email is already used by another staff.";
            $stmtCheck->close();
        } else {
            $stmtCheck->close();

            $stmtUpdate = $conn->prepare("UPDATE staff_admin SET username = ?, email = ?, created_at = ? WHERE staffid = ? AND role = 'staff'");
            $stmtUpdate->bind_param("ssss", $username, $email, $created_at, $staffId);
            if ($stmtUpdate->execute()) {
                $message = "✅ Staff updated successfully.";
            } else {
                $message = "❌ Error updating staff: " . $stmtUpdate->error;
            }
            $stmtUpdate->close();
        }
    }
}

// --------- Delete Staff ----------
if (isset($_POST['delete_id'])) {
    $delId = trim($_POST['delete_id']);
    if ($delId !== '') {
        $stmt = $conn->prepare("DELETE FROM staff_admin WHERE staffid = ? AND role = 'staff'");
        $stmt->bind_param("s", $delId);
        if ($stmt->execute()) {
            $message = "✅ Staff deleted successfully.";
        } else {
            $message = "❌ Error deleting staff: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 读取 staff 列表
$sql = "SELECT staffid, username, email, created_at FROM staff_admin WHERE role='staff' ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="utf-8">
<title>Manage Staff - Admin</title>
<style>
/* --- Base Styles --- */
body { 
    font-family: 'Inter', Arial, sans-serif; 
    background: #ffffff; /* 修正：白色背景 */
    color: #333; /* 修正：深色字体 */
    margin: 0; 
    padding: 0; 
}
h2 { 
    text-align: center; 
    color: #007BFF; /* 亮蓝色标题 */
    margin-top: 30px;
    margin-bottom: 20px;
    font-size: 2em;
    text-shadow: 0 0 5px rgba(0, 191, 255, 0.2); /* 调整阴影 */
}

/* --- Header & Navigation (Unified Blue/White Theme) --- */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: #007BFF; /* 蓝色头部背景 */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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
}
.message[class*="✅"] { /* 成功消息 */
    color: #10b981; 
    background: rgba(16, 185, 129, 0.1); /* 浅色背景 */
    border-color: #10b981;
}
.message[class*="❌"] { /* 错误消息 */
    color: #ef4444; 
    background: rgba(239, 68, 68, 0.1); /* 浅色背景 */
    border-color: #ef4444;
}

/* --- Forms & Inputs --- */
.add-form { 
    width: 90%; 
    max-width: 900px; 
    margin: 18px auto; 
    padding: 20px; 
    background: #ffffff; /* 修正：卡片背景为白色 */
    border-radius: 12px; 
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4); /* 深蓝色阴影 */
}
.add-form h3 {
    color: #007BFF; /* 蓝色 */
    border-bottom: 1px solid #e0e0e0; /* 浅色分割线 */
    padding-bottom: 8px;
    margin-bottom: 15px;
}
.add-form input { 
    padding: 12px; 
    margin: 8px 4px; 
    width: calc(50% - 20px); 
    box-sizing: border-box; 
    border: 1px solid #ccc; /* 浅色边框 */
    background: #f8f8f8; /* 浅色输入框背景 */
    color: #333;
    border-radius: 8px;
    transition: border-color 0.3s;
}
.add-form input:focus {
    border-color: #00BFFF;
    outline: none;
}
.add-form button { 
    padding: 10px 18px; 
    background: #28a745; /* 绿色添加按钮 */
    color: #fff; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-weight: 600; 
    transition: background 0.3s;
}
.add-form button:hover {
    background: #1e8b4e;
}

/* --- Table Styles --- */
table { 
    width: 90%; 
    max-width: 1100px; 
    margin: 20px auto 60px; 
    border-collapse: collapse; 
    background: #ffffff; /* 修正：卡片背景为白色 */
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4); /* 深蓝色阴影 */
    border-radius: 12px; 
    overflow: hidden; 
}
th, td { 
    padding: 14px 15px; 
    text-align: center; 
    border-bottom: 1px solid #e0e0e0; /* 浅色分割线 */
    color: #333; /* 修正：深色字体 */
}
th { 
    background: #007bff; 
    color: #fff; 
    font-weight: 600; 
    text-transform: uppercase;
}
tr:nth-child(even) td {
    background: #f8f8f8; /* 斑马线效果 */
}
tr:hover td { 
    background: #e9f5ff; /* 悬停背景 (浅蓝色) */
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
    color: #111827; 
}
.edit-btn:hover {
    background: #e0a800;
}

/* --- Modal Styles --- */
.modal { 
    display:none; 
    position:fixed; 
    z-index:999; 
    left:0; 
    top:0; 
    width:100%; 
    height:100%; 
    background-color:rgba(0,0,0,0.6); 
    justify-content:center; 
    align-items:center; 
}
.modal-content { 
    background: #ffffff; /* 修正：白色背景 */
    color: #333; /* 修正：深色字体 */
    padding: 30px; 
    border-radius: 10px; 
    width: 400px; 
    max-width: 95%; 
    text-align: center; 
    box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4);
}
.modal-content h3 {
    color: #007BFF;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 8px;
    margin-bottom: 20px;
}
.modal-content input { 
    padding: 10px; 
    width: 80%; 
    margin: 10px 0; 
    background: #f8f8f8;
    border: 1px solid #ccc;
    color: #333;
    border-radius: 8px;
}
.save-btn { 
    background:#28a745; 
    color:#fff; 
    transition: background 0.3s;
}
.save-btn:hover {
    background: #1e8b4e;
}
.cancel-btn { 
    background:#6c757d; 
    color:#fff; 
    transition: background 0.3s;
}
.cancel-btn:hover {
    background: #5a6268;
}

/* --- Media Queries (Responsive Design) --- */
@media screen and (max-width: 600px) {
    .add-form input {
        width: 100%;
        margin: 6px 0;
    }
    .add-form button {
        width: 100%;
        margin-top: 10px;
    }
    table, .add-form {
        width: 100%;
        padding: 10px;
    }
    .add-form input:nth-child(even) { 
        margin-right: 0;
    }
}

</style>
<script>
function openEditModal(staffId, username, email, createdAt) {
    // 将日期格式化为 YYYY-MM-DD，确保 input type="date" 能正确显示
    const datePart = createdAt.split(' ')[0]; 

    document.getElementById('modal').style.display = 'flex';
    document.getElementById('edit_staff_id').value = staffId;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
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
            } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
                echo '<a href="staff_home.php">Home</a>';
            } 
        } 
        ?>
        <a href="manage_orders.php">Manage Orders</a>
        <a href="sales_report.php">Sales Report</a>
        <a href="contactS.php">Contact</a>
        <a href="contactus.php">Feedback</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="logoutS.php">Sign Out</a>
    </nav>
</header>

<h2>Manage Staff Accounts</h2>

<?php if ($message !== ""): ?>
    <div class="message <?php echo (str_starts_with($message, '✅') ? 'message-success' : 'message-error'); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Add Staff -->
<div class="add-form">
    <h3>Add New Staff</h3>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <button type="submit" name="add_staff">Add Staff (default password: 12345)</button>
    </form>
</div>

<!-- Staff Table -->
<table>
    <tr>
        <th>Staff ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Created At</th>
        <th>Action</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
            $staffId = htmlspecialchars($row['staffid']);
            $username = htmlspecialchars($row['username']);
            $email = htmlspecialchars($row['email']);
            $created = date('Y-m-d', strtotime($row['created_at']));
        ?>
        <tr>
            <td><?php echo $staffId; ?></td>
            <td><?php echo $username; ?></td>
            <td><?php echo $email; ?></td>
            <td><?php echo $created; ?></td>
            <td>
                <button class="btn edit-btn" type="button" onclick="openEditModal('<?php echo $staffId; ?>','<?php echo $username; ?>','<?php echo $email; ?>','<?php echo $created; ?>')">Edit</button>
                <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure to delete this staff?');">
                    <input type="hidden" name="delete_id" value="<?php echo $staffId; ?>">
                    <button class="btn delete-btn" type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5">No staff found.</td></tr>
    <?php endif; ?>
</table>

<!-- Edit Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <h3>Edit Staff</h3>
        <form method="post">
            <input type="hidden" name="staff_id" id="edit_staff_id">
            <input type="text" name="username" id="edit_username" placeholder="Username" required>
            <input type="email" name="email" id="edit_email" placeholder="Email" required>
            <input type="date" name="created_at" id="edit_created_at" required><br>
            <button type="submit" name="edit_staff" class="save-btn">Save</button>
            <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

</body>
</html>
