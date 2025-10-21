<?php
include "config.php";

// 可选：在这里检查是否登录 (将检查逻辑移动到这里)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
//     header("Location: signinS.php");
//     exit();
// }

$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

// 确保查询结果可用
if (!$result) {
    $error_message = "Database Error: " . $conn->error;
} else {
    $error_message = "";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Messages - DJS Game</title>
<style>
/* --- Blue/White Staff Theme --- */
body {
    font-family: 'Inter', sans-serif;
    background-color: #f4f7f9; /* 浅灰色背景 */
    color: #333;
    margin: 0;
    padding: 0;
}

/* --- Header & Navigation Bar --- */
header {
    background: #007bff; /* 蓝色头部背景 */
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

header .logo {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}

header nav {
    display: flex;
    gap: 15px;
}

header nav a {
    color: #fff;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background 0.3s, color 0.3s;
    font-size: 0.95em;
}

header nav a:hover {
    background: #0056b3; /* 深蓝色悬停 */
    color: #fff;
}

/* --- 主内容区域 --- */
.messages-section {
    max-width: 1200px;
    margin: 40px auto;
    padding: 20px;
    background: #ffffff; /* 白色卡片背景 */
    border-radius: 12px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
}

.messages-section h1 {
    text-align: center;
    color: #007bff; /* 蓝色标题 */
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
    margin-bottom: 30px;
}

/* --- 表格样式 --- */
table {
    width: 100%;
    border-collapse: separate; /* 使用 separate 和 border-spacing 来圆角 */
    border-spacing: 0;
    margin-top: 20px;
    overflow: hidden; /* 隐藏内容，保证圆角效果 */
    border-radius: 10px;
}

table th, table td {
    padding: 15px;
    text-align: left;
    font-size: 0.95em;
}

table thead tr {
    background-color: #007bff; /* 蓝色表头 */
    color: #fff;
}

table th {
    font-weight: bold;
    text-transform: uppercase;
}

table tbody tr:nth-child(even) {
    background-color: #f0f0f0; /* 浅灰条纹 */
}

table tbody tr:hover {
    background-color: #e0f7ff; /* 极浅蓝色悬停 */
    box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2);
}

/* 消息内容 (防止溢出) */
table td:nth-child(4) {
    max-width: 350px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* 响应式表格 (保持您之前提供的逻辑，但适应新样式) */
@media screen and (max-width: 768px) {
    table, thead, tbody, th, td, tr {
        display: block;
    }
    table thead {
        display: none; /* 隐藏原始表头 */
    }
    table tr {
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
    }
    table td {
        text-align: right;
        padding-left: 50%;
        position: relative;
    }
    table td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: calc(50% - 30px);
        text-align: left;
        font-weight: bold;
        color: #007bff;
    }
}
</style>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game Staff</div>
    <nav>
        <?php
        // 确保 session 已经启动
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] === 'admin') {
                echo '<a href="admin_home.php">Home</a>';
            } elseif ($_SESSION['role'] === 'staff') {
                echo '<a href="staff_home.php">Home</a>';
            } else {
                echo '<a href="home.php">Home</a>'; // fallback
            }
        } else {
            echo '<a href="home.php">Home</a>'; // 未登录
        }
        ?>
        <a href="Contact.php">Contact</a>
        <a href="contactus.php">User Messages</a>
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="logoutS.php">Logout</a>
    </nav>
</header>

<section class="messages-section">
    <h1>User Messages</h1>
    
    <?php if ($error_message): ?>
        <p style="color: red; text-align: center; font-weight: bold;"><?php echo $error_message; ?></p>
    <?php elseif ($result->num_rows === 0): ?>
        <p style="text-align: center; color: #555;">No feedback messages found.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Sent At</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="ID"><?php echo $row['id']; ?></td>
                    <td data-label="Name"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td data-label="Email"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td data-label="Message"><?php echo htmlspecialchars($row['message']); ?></td>
                    <td data-label="Sent At"><?php echo $row['created_at']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

</body>
</html>
