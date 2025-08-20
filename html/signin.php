<?php
session_start();
include("config.php"); // 数据库连接

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // 登录成功，保存用户名到 session
            $_SESSION['username'] = $row['username'];

            // 用 JavaScript 跳转并弹出欢迎信息
            echo "<script>
                    alert('Welcome, " . $row['username'] . "!');
                    window.location.href = 'home.html';
                  </script>";
        } else {
            echo "<script>alert('Wrong password'); window.location.href='signin.html';</script>";
        }
    } else {
        echo "<script>alert('User not found'); window.location.href='signin.html';</script>";
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
        <form action="#" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn">Sign In</button>
                <a href="forgetpassword.html">Forget Password?</a>
        </form>
        <p class="signup-text">Don't have an account? <a href="register.html">Register here</a></p>
        
        <img src="../image/ciallo.jpg" alt="Game Background" class="bg-image">
    </section>
    
    <footer>
        <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
    </footer>
</body>
</html>
