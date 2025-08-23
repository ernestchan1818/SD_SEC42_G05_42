<?php
session_start();
require "config.php";

// 确保用户已登录
if (!isset($_SESSION['staffid'])) {
    header("Location: signinS.php");
    exit();
}

$staffId = $_SESSION['staffid'];

// 从 staff_admin 表取数据
$sql = "SELECT staffid, username, email, role, otp, created_at, password, avatar FROM staff_admin WHERE staffid = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("s", $staffId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    session_unset();
    session_destroy();
    header("Location: signinS.php");
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
        $update = $conn->prepare("UPDATE staff_admin SET password=? WHERE staffid=?");
        $update->bind_param("ss", $hashed, $staffId);
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
        $update = $conn->prepare("UPDATE staff_admin SET username=? WHERE staffid=?");
        $update->bind_param("ss", $new_username, $staffId);
        if ($update->execute()) {
            $username_message = "✅ Username updated successfully!";
            $user['username'] = $new_username;
            $_SESSION['username'] = $new_username;
        } else {
            $username_message = "❌ Error updating username.";
        }
    }
}

// ⚡ 上传头像
$avatar_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["upload_avatar"])) {
    if (!empty($_FILES["avatar"]["name"])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . "_" . basename($_FILES["avatar"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFilePath)) {
                $update = $conn->prepare("UPDATE staff_admin SET avatar=? WHERE staffid=?");
                $update->bind_param("ss", $targetFilePath, $staffId);
                if ($update->execute()) {
                    $avatar_message = "✅ Avatar uploaded successfully!";
                    $user['avatar'] = $targetFilePath;
                } else {
                    $avatar_message = "❌ Database update failed.";
                }
            } else {
                $avatar_message = "❌ Failed to upload file.";
            }
        } else {
            $avatar_message = "❌ Only JPG, JPEG, PNG, GIF allowed.";
        }
    } else {
        $avatar_message = "❌ Please select a file.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Profile - Staff/Admin</title>
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
        .btn{display:inline-block;margin-top:10px;padding:10px 16px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;border:none;cursor:pointer}
        .btn.out{background:#ef4444}
        form{margin-top:20px}
        input{width:100%;padding:10px;margin:8px 0;border-radius:8px;border:1px solid #374151;background:#1f2937;color:#fff}
        .msg{margin-top:10px;padding:10px;border-radius:8px}
        .success{background:#10b98133;color:#10b981}
        .error{background:#ef444433;color:#ef4444}
        img.avatar{border-radius:50%;width:120px;height:120px;object-fit:cover}
    </style>
</head>
<body>
<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <a href="staff_home.php">Home</a>
        <a href="profileS.php">Profile</a>
        <a href="logoutS.php">Logout</a>
    </nav>
</header>

<div class="wrap">
    <h2>Your Staff/Admin Profile</h2>

    <!-- 显示头像 -->
    <div style="text-align:center;margin-bottom:20px;">
        <?php if (!empty($user['avatar'])): ?>
            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" class="avatar" alt="Avatar">
        <?php else: ?>
            <img src="default.png" class="avatar" alt="Default Avatar">
        <?php endif; ?>
    </div>

    <!-- 上传头像 -->
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="avatar" accept="image/*" required>
        <button type="submit" name="upload_avatar" class="btn">Upload Avatar</button>
    </form>
    <?php if ($avatar_message): ?>
        <div class="msg <?php echo (str_starts_with($avatar_message, '✅') ? 'success' : 'error'); ?>">
            <?php echo $avatar_message; ?>
        </div>
    <?php endif; ?>

    <!-- 用户资料 -->
    <div class="row"><div class="label">Staff ID</div><div class="value"><?php echo htmlspecialchars($user['staffid']); ?></div></div>
    <div class="row"><div class="label">Role</div><div class="value"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></div></div>
    <div class="row"><div class="label">Username</div><div class="value"><?php echo htmlspecialchars($user['username']); ?></div></div>
    <div class="row"><div class="label">Email</div><div class="value"><?php echo htmlspecialchars($user['email']); ?></div></div>
    <div class="row"><div class="label">Verified</div><div class="value"><?php echo empty($user['otp']) ? '<span class="badge ok">Verified</span>' : '<span class="badge no">Not verified</span>'; ?></div></div>
    <div class="row"><div class="label">Joined</div><div class="value"><?php echo htmlspecialchars($user['created_at']); ?></div></div>

    <!-- 修改用户名 -->
    <h3>Change Username</h3>
    <form method="POST">
        <input type="text" name="new_username" placeholder="New Username" required>
        <button type="submit" name="change_username" class="btn">Update Username</button>
    </form>
    <?php if ($username_message): ?>
        <div class="msg <?php echo (str_starts_with($username_message, '✅') ? 'success' : 'error'); ?>">
            <?php echo $username_message; ?>
        </div>
    <?php endif; ?>

    <!-- 修改密码 -->
    <h3>Change Password</h3>
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

    <a class="btn out" href="logoutS.php">Logout</a>
</div>

<footer>
    <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
</footer>
</body>
</html>
