<?php
session_start();
include("config.php"); // 数据库连接

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // 登录成功，保存用户ID和用户名到 session
            $_SESSION['user_id'] = $row['id'];       // ✅ 加上用户ID
            $_SESSION['username'] = $row['username'];

            // 登录成功提示并跳转
            echo "<script>
                    alert('Welcome, " . $row['username'] . "!');
                    window.location.href = 'home.php'; // 建议用PHP页面而不是html
                  </script>";
        } else {
            echo "<script>alert('Wrong password'); window.location.href='signin.php';</script>";
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='signin.php';</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In</title>
    <link rel="stylesheet" href="../css/signin.css">
</head>
<body>
    <header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
            <a href="home.html">Home</a>
            <a href="about.html">About</a>
            <a href="contact.html">Contact</a>
        </nav>
    </header>

    <section class="signin-container">
        <h2>Sign In</h2>
        <form action="signin.php" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn">Sign In</button>
            <div style="margin-top: 15px; text-align: center;">
            <a href="home.php" class="btn" style="display:inline-block; padding:10px 20px; background:#444; color:#fff; border-radius:5px; text-decoration:none;">
                Sign In as Guest
            </a>
        </div>
            <a href="forgetpassword.php">Forget Password?</a>
        </form>
        <p class="signup-text">Don't have an account? <a href="register.php">Register here</a></p>
        
        <img src="../image/ciallo.jpg" alt="Game Background" class="bg-image">
    </section>
    
    <footer>
        <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
    </footer>
</body>
</html>
