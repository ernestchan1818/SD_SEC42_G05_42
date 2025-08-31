<?php
session_start();
include "config.php"; // 确保 $conn 是 mysqli 连接

// 仅 admin 可用
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit();
}

$message = "";

// 处理 POST 请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --------- Add Staff ----------
    if (isset($_POST['add_staff'])) {
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');

        if ($email === '' || $username === '') {
            $message = "Please provide username and email.";
        } else {
            // 检查 email 是否存在
            $stmt = $conn->prepare("SELECT staffid FROM staff_admin WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = "This email is already used.";
                $stmt->close();
            } else {
                $stmt->close();

                // 生成唯一 staff ID (S + 6位随机数字)
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
                    $message = "✅ Staff added successfully. Staff ID: $staffIdGenerated (default password = 12345)";
                } else {
                    $message = "Error adding staff: " . $stmt2->error;
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
            $message = "Please provide all required fields to edit staff.";
        } else {
            // 检查 email 是否被其他 staff 使用
            $stmtCheck = $conn->prepare("SELECT staffid FROM staff_admin WHERE email = ? AND staffid != ? LIMIT 1");
            $stmtCheck->bind_param("ss", $email, $staffId);
            $stmtCheck->execute();
            $stmtCheck->store_result();

            if ($stmtCheck->num_rows > 0) {
                $message = "This email is already used by another staff.";
                $stmtCheck->close();
            } else {
                $stmtCheck->close();

                $stmtUpdate = $conn->prepare("UPDATE staff_admin SET username = ?, email = ?, created_at = ? WHERE staffid = ? AND role = 'staff'");
                $stmtUpdate->bind_param("ssss", $username, $email, $created_at, $staffId);
                if ($stmtUpdate->execute()) {
                    $message = "✅ Staff updated successfully.";
                } else {
                    $message = "Error updating staff: " . $stmtUpdate->error;
                }
                $stmtUpdate->close();
            }
        }
    }

    // --------- Delete Staff ----------
    if (isset($_POST['delete_id'])) {
        $delId = trim($_POST['delete_id']); // 保留原字符串 S123456
        if ($delId !== '') {
            $stmt = $conn->prepare("DELETE FROM staff_admin WHERE staffid = ? AND role = 'staff'");
            $stmt->bind_param("s", $delId); // 使用 s 代表 string
            if ($stmt->execute()) {
                $message = "✅ Staff deleted successfully.";
            } else {
                $message = "Error deleting staff: " . $stmt->error;
            }
            $stmt->close();
        }
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
body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:20px; }
h2 { text-align:center; color:#333; }
.message { text-align:center; margin:10px auto; font-weight:600; color: #155724; background:#d4edda; padding:8px 12px; border-radius:6px; width:90%; max-width:900px; }
.add-form, .edit-form { width:90%; max-width:900px; margin: 18px auto; padding:16px; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.08); }
.add-form input, .edit-form input { padding:8px; margin:6px 4px; width: calc(50% - 20px); box-sizing:border-box; }
.add-form button, .edit-form button { padding:10px 14px; background:#28a745; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
table { width:90%; max-width:1100px; margin: 12px auto 60px; border-collapse:collapse; background:#fff; box-shadow:0 4px 8px rgba(0,0,0,.08); border-radius:10px; overflow:hidden; }
th, td { padding:12px 10px; text-align:center; border-bottom:1px solid #eee; }
th { background:#007bff; color:#fff; font-weight:600; }
tr:hover td { background:#fafafa; }
.btn { padding:6px 10px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
.delete-btn { background:#dc3545; color:#fff; }
.edit-btn { background:#ffc107; color:#fff; }
.small { font-size:13px; color:#666; }
.nav { width:90%; max-width:1100px; margin: 0 auto 12px; display:flex; gap:12px; align-items:center; }
.nav a { text-decoration:none; background:#2c3e50; color:#fff; padding:8px 12px; border-radius:6px; }
.nav a.logout { margin-left:auto; background:#c0392b; }
</style>
<script>
function fillEditForm(staffId, username, email, createdAt) {
    document.getElementById('edit_staff_id').value = staffId;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_created_at').value = createdAt; // yyyy-mm-dd
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</head>
<body>
<header class="nav">
    <div class="logo">🎮 DJS Game Staff</div>
    <a href="admin_home.php">Home</a>
    <a href="contactus.php">User Messages</a>
    <a class="logout" href="logoutS.php">Logout</a>
</header>

<h2>Manage Staff Accounts</h2>

<?php if ($message !== ""): ?>
    <div class="message"><?php echo htmlspecialchars($message); ?></div>
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

<!-- Edit Staff -->
<div class="edit-form">
    <h3>Edit Staff</h3>
    <form method="post">
        <input type="hidden" name="staff_id" id="edit_staff_id">
        <input type="text" name="username" id="edit_username" placeholder="Username" required>
        <input type="email" name="email" id="edit_email" placeholder="Email" required>
        <input type="date" name="created_at" id="edit_created_at" required>
        <button type="submit" name="edit_staff">Update Staff</button>
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
                <button class="btn edit-btn" type="button" onclick="fillEditForm('<?php echo $staffId; ?>','<?php echo $username; ?>','<?php echo $email; ?>','<?php echo $created; ?>')">Edit</button>
                <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure to delete this staff?');">
                    <input type="hidden" name="delete_id" value="<?php echo $staffId; ?>">
                    <button class="btn delete-btn" type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5" class="small">No staff found.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
