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
        /* --- General Styles --- */
        body { 
            font-family: 'Inter', system-ui, Arial, sans-serif;
            background: #0d1a2f; /* 深蓝背景 (科技感) */
            color: #e8ecf1; 
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* --- Header/Navigation --- */
        header { 
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: #007BFF; /* 蓝色头部背景 */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #fff;
        }
        nav a {
            color: #fff;
            margin-right: 15px;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        nav a:hover {
            background: #0056B3; /* 深蓝色悬停 */
        }
        
        /* --- Wrapper/Card --- */
        .wrap {
            max-width: 720px;
            margin: 40px auto;
            padding: 30px;
            background: #1a2a40; /* 略浅的深蓝卡片背景 */
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.3); /* 蓝色发光阴影 */
            flex-grow: 1;
        }

        h2, h3 {
            color: #00BFFF; /* 亮蓝色标题 */
            border-bottom: 2px solid #00BFFF;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }
        h3 {
            font-size: 1.3em;
            margin-top: 30px;
        }

        /* --- Profile Rows --- */
        .row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #1f2937;
        }
        .row:last-child {
            border-bottom: none;
        }
        .label {
            color: #b0e0ff; /* 浅蓝色标签 */
        }
        .value {
            font-weight: 600;
            color: #e8ecf1;
        }
        
        /* --- Avatars and Forms --- */
        img.avatar {
            border-radius: 50%;
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 3px solid #00BFFF; /* 蓝色边框 */
        }
        
        form {
            margin-top: 20px;
            background: #111827; /* 表单区背景 */
            padding: 20px;
            border-radius: 10px;
            box-shadow: inset 0 0 5px rgba(0, 123, 255, 0.1);
        }
        
        input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
            width: calc(100% - 22px);
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            border: 1px solid #374151;
            background: #1f2937;
            color: #fff;
            box-sizing: content-box; /* 修复文件输入框的宽度问题 */
        }
        input:focus {
            border-color: #00BFFF;
            box-shadow: 0 0 8px rgba(0, 191, 255, 0.6);
            outline: none;
        }
        
        /* --- Buttons --- */
        .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 12px 20px;
            border-radius: 10px;
            background: #00BFFF; /* 亮蓝色主按钮 */
            color: #111827;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0099CC;
        }
        .btn.out {
            background: #ef4444; /* 退出按钮红色 */
            color: #fff;
        }
        .btn.out:hover {
            background: #dc2626;
        }
        
        /* --- Messages and Badges --- */
        .msg {
            margin-top: 15px;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            border: 1px solid;
        }
        .success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border-color: #10b981;
        }
        .error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border-color: #ef4444;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        .ok {
            background: #10b98133;
            color: #10b981;
        }
        .no {
            background: #ef444433;
            color: #ef4444;
        }

        /* --- Footer --- */
        footer {
            margin-top: auto;
            background: #111827;
            text-align: center;
            padding: 15px;
            color: #6b7280;
            border-top: 1px solid #1f2937;
        }
    </style>
</head>
<body>
<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <?php
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                echo '<a href="admin_home.php">Home</a>';
            } elseif ($_SESSION['role'] === 'staff') {
                echo '<a href="staff_home.php">Home</a>';
            } else {
                echo '<a href="signinS.php">Home</a>'; // fallback
            }
        } else {
            echo '<a href="signinS.php">Home</a>'; // 未登录
        }
        ?>
        <a href="profileS.php">Profile</a>
        <a href="logoutS.php">Logout</a>
    </nav>
</header>

<div class="wrap">
    <h2>Your Staff/Admin Profile</h2>

    <!-- 显示头像 -->
    <div style="text-align:center;margin-bottom:20px;">
        <?php 
        $avatar_src = !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'default.png';
        ?>
        <img src="<?php echo $avatar_src; ?>" class="avatar" alt="Avatar">
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
