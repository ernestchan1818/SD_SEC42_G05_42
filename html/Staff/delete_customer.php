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
nav { background-color:#2c3e50; padding:12px 20px; display:flex; align-items:center; gap:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 6px rgba(0,0,0,0.2); }
nav a { color:white; text-decoration:none; padding:8px 14px; border-radius:6px; transition:0.3s ease; }
nav a:hover { background-color:#e74c3c; }
nav a[href*="logout"] { margin-left:auto; background-color:#c0392b; }
nav a[href*="logout"]:hover { background-color:#e74c3c; }

/* Modal 样式 */
.modal {
    display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%;
    background-color: rgba(0,0,0,0.6); justify-content:center; align-items:center;
}
.modal-content {
    background:#fff; padding:20px; border-radius:8px; width:400px; max-width:95%; text-align:center;
    box-shadow:0 4px 10px rgba(0,0,0,0.3);
}
.modal-content h3 { margin-bottom:12px; }
.modal-content input { padding:8px; width:80%; margin:10px 0; }
.modal-content button { margin:6px; padding:8px 14px; border:none; border-radius:6px; cursor:pointer; font-weight:600; }
.save-btn { background:#28a745; color:#fff; }
.cancel-btn { background:#6c757d; color:#fff; }
</style>
<script>
function openEditModal(customerId, createdAt) {
    document.getElementById('modal').style.display = 'flex';
    document.getElementById('edit_customer_id').value = customerId;
    document.getElementById('edit_created_at').value = createdAt;
}
function closeModal() {
    document.getElementById('modal').style.display = 'none';
}
</script>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game Staff</div>
            <nav>
    <?php
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            echo '<a href="admin_home.php">Home</a>';
        } elseif ($_SESSION['role'] === 'staff') {
            echo '<a href="staff_home.php">Home</a>';
        } 
    } 
    ?>
            <a href="Contact.php">Contact</a>
            <a href="contactus.php">Feedback</a>
            <a href="view_games.php">Top-Up Games</a>
            <a href="view_packages.php">Top-Up Packages</a>
            <a href="signout.php">Sign Out</a>
        </nav>
    </header>
</header>

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
                <button class="btn edit-btn" type="button" onclick="openEditModal('<?= $row['id']; ?>','<?= date('Y-m-d', strtotime($row['created_at'])); ?>')">Edit Date</button>
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
        <h3>Edit Customer Date</h3>
        <form method="post">
            <input type="hidden" name="customer_id" id="edit_customer_id">
            <input type="date" name="created_at" id="edit_created_at" required><br>
            <button type="submit" name="edit_customer" class="save-btn">Save</button>
            <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
        </form>
    </div>
</div>

</body>
</html>
