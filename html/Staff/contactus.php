<?php
include "config.php";

// 可选：在这里检查是否登录
// session_start();
// if (!isset($_SESSION['role']) || $_SESSION['role'] != 'staff') {
//     header("Location: signinS.php");
//     exit();
// }

$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Messages - DJS Game</title>
<link rel="stylesheet" href="contactus.css">
</head>
<body>
<header>
    <div class="logo">🎮 DJS Game Staff</div>
    <nav>
           <?php
    session_start();
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
        <a href="contactus.php">User Messages</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<section class="messages-section">
    <h1>User Messages</h1>
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
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['message']); ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>

</body>
</html>
