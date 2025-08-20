<?php
session_start();
require "config.php";

// 确保用户已登录
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$userId = $_SESSION['user_id'];

// 取用户资料
$sql = "SELECT id, username, email, is_verified, created_at, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    session_unset();
    session_destroy();
    header("Location: signin.php");
    exit();
}

// ⚡ 修改密码
$pwd_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
    $old_password = $_POST["old_password"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if (!password_verify($old_password, $user['password'])) {
        $pwd_message = "❌ Old password incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $pwd_message = "❌ New passwords do not match.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/", $new_password)) {
        $pwd_message = "❌ Password must include uppercase, lowercase, number and be at least 6 characters.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $update->bind_param("si", $hashed, $userId);
        if ($update->execute()) {
            $pwd_message = "✅ Password updated successfully!";
        } else {
            $pwd_message = "❌ Error updating password.";
        }
    }
}

// ⚡ 修改用户名
$username_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_username"])) {
    $new_username = trim($_POST["new_username"]);

    if (empty($new_username)) {
        $username_message = "❌ Username cannot be empty.";
    } elseif (strlen($new_username) < 3) {
        $username_message = "❌ Username must be at least 3 characters.";
    } else {
        $update = $conn->prepare("UPDATE users SET username=? WHERE id=?");
        $update->bind_param("si", $new_username, $userId);
        if ($update->execute()) {
            $username_message = "✅ Username updated successfully!";
            $user['username'] = $new_username; // ⚡ 更新 session 里的显示
            $_SESSION['username'] = $new_username;
        } else {
            $username_message = "❌ Error updating username.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Profile - DJS Game</title>
    <link rel="stylesheet" href="../css/profile.css">
    <style>
        body{font-family:system-ui,Arial,sans-serif;background:#0b0f17;color:#e8ecf1;margin:0}
        header,footer{display:flex;justify-content:space-between;align-items:center;padding:14px 20px;background:#111827}
        nav a{color:#cbd5e1;margin-right:14px;text-decoration:none}
        .wrap{max-width:720px;margin:40px auto;padding:24px;background:#111827;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.3)}
        .row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid #1f2937}
        .row:last-child{border-bottom:none}
        .label{color:#9ca3af}
        .value{font-weight:600}
        .badge{padding:2px 8px;border-radius:999px;font-size:12px}
        .ok{background:#10b98133;color:#10b981}
        .no{background:#ef444433;color:#ef4444}
        .btn{display:inline-block;margin-top:16px;padding:10px 16px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;border:none;cursor:pointer}
        .btn.out{background:#ef4444}
        form{margin-top:30px}
        input{width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #374151;background:#1f2937;color:#fff}
        .msg{margin-top:10px;padding:10px;border-radius:8px}
        .success{background:#10b98133;color:#10b981}
        .error{background:#ef444433;color:#ef4444}
    </style>
</head>
<body>
<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <a href="home.html">Home</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="wrap">
    <h2 style="margin-top:0;">Your Profile</h2>

    <div class="row">
        <div class="label">Username</div>
        <div class="value"><?php echo htmlspecialchars($user['username']); ?></div>
    </div>
    <div class="row">
        <div class="label">Email</div>
        <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
    </div>
    <div class="row">
        <div class="label">Verified</div>
        <div class="value">
            <?php if (!empty($user['is_verified'])): ?>
                <span class="badge ok">Verified</span>
            <?php else: ?>
                <span class="badge no">Not verified</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <div class="label">Joined</div>
        <div class="value"><?php echo htmlspecialchars($user['created_at']); ?></div>
    </div>

    <a class="btn out" href="logout.php">Logout</a>

    <!-- 🔹 修改用户名表单 -->
    <h3 style="margin-top:40px;">Edit Username</h3>
    <form method="POST">
        <input type="text" name="new_username" placeholder="New Username" required>
        <button type="submit" name="change_username" class="btn">Update Username</button>
    </form>
    <?php if ($username_message): ?>
        <div class="msg <?php echo (str_starts_with($username_message, '✅') ? 'success' : 'error'); ?>">
            <?php echo $username_message; ?>
        </div>
    <?php endif; ?>

    <!-- 🔹 修改密码表单 -->
    <h3 style="margin-top:40px;">Change Password</h3>
    <form method="POST">
        <input type="password" name="old_password" placeholder="Old Password" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit" name="change_password" class="btn">Update Password</button>
    </form>
    <?php if ($pwd_message): ?>
        <div class="msg <?php echo (str_starts_with($pwd_message, '✅') ? 'success' : 'error'); ?>">
            <?php echo $pwd_message; ?>
        </div>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
</footer>
</body>
</html>
