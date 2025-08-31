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
            $message = "Error deleting customer: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --------- Edit Customer ----------
if (isset($_POST['edit_customer'])) {
    $customerId = intval($_POST['customer_id']);
    $created_at = trim($_POST['created_at'] ?? '');

    if ($customerId > 0 && $created_at !== '') {
        $stmt = $conn->prepare("UPDATE users SET created_at = ? WHERE id = ?");
        $stmt->bind_param("si", $created_at, $customerId);
        if ($stmt->execute()) {
            $message = "✅ Customer date updated successfully.";
        } else {
            $message = "Error updating customer: " . $stmt->error;
        }
        $stmt->close();
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
body { font-family: Arial, sans-serif; margin: 20px; background:#f4f7f9; }
h1 { text-align:center; margin-bottom:20px; }
.message { text-align:center; margin:10px auto; font-weight:600; color:#155724; background:#d4edda; padding:8px 12px; border-radius:6px; width:90%; max-width:900px; }
table { width:90%; margin:0 auto; border-collapse:collapse; background:#fff; box-shadow:0 0 10px rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; }
th, td { padding:12px; border:1px solid #ddd; text-align:center; }
th { background:#007bff; color:#fff; }
tr:hover { background:#f1f1f1; }
.btn { padding:6px 12px; border-radius:6px; border:none; cursor:pointer; font-weight:600; }
.delete-btn { background:#dc3545; color:#fff; }
.edit-btn { background:#ffc107; color:#fff; }
.edit-form { width:90%; max-width:900px; margin: 18px auto; padding:16px; background:#fff; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.08); }
.edit-form input { padding:8px; margin:6px 4px; width: calc(50% - 20px); box-sizing:border-box; }
.edit-form button { padding:10px 14px; background:#28a745; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
nav { background-color:#2c3e50; padding:12px 20px; display:flex; align-items:center; gap:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
nav a { color:white; text-decoration:none; padding:8px 14px; border-radius:6px; transition:0.3s ease; }
nav a:hover { background-color:#e74c3c; }
nav a[href*="logout"] { margin-left:auto; background-color:#c0392b; }
nav a[href*="logout"]:hover { background-color:#e74c3c; }
</style>
<script>
function fillEditForm(customerId, createdAt) {
    document.getElementById('edit_customer_id').value = customerId;
    document.getElementById('edit_created_at').value = createdAt;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game Admin</div>
    <nav>
        <a href="admin_home.php">Home</a>
        <a href="contactus.php">User Messages</a>
        <a href="logoutS.php">Logout</a>
    </nav>
</header>

<h1>Manage Customers</h1>

<?php if ($message !== ""): ?>
    <div class="message"><?= htmlspecialchars($message); ?></div>
<?php endif; ?>

<!-- Edit Customer -->
<div class="edit-form">
    <h3>Edit Customer Date</h3>
    <form method="post">
        <input type="hidden" name="customer_id" id="edit_customer_id">
        <input type="date" name="created_at" id="edit_created_at" required>
        <button type="submit" name="edit_customer">Update Date</button>
    </form>
</div>

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
                <button class="btn edit-btn" type="button" onclick="fillEditForm('<?= $row['id']; ?>','<?= date('Y-m-d', strtotime($row['created_at'])); ?>')">Edit Date</button>
                <form method="post" style="display:inline-block;" onsubmit="return confirm('Are you sure to delete this customer?');">
                    <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                    <button class="btn delete-btn" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
