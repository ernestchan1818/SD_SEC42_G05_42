<?php
session_start();
include("config.php"); // make sure this connects to otpdb correctly

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $code     = trim($_POST['code']);

    // Check user in DB
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Store session
            $_SESSION['username'] = $user['username'];

            // Redirect based on code
            if ($code === "djsstaff") {
                header("Location: homestaff.php");
                exit;
            } elseif ($code === "djsadmin") {
                header("Location: homeadmin.php");
                exit;
            } else {
                header("Location: homecustomer.php");
                exit;
            }
        } else {
            $message = "Invalid password!";
        }
    } else {
        $message = "User not found!";
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

        <?php if (!empty($message)) echo "<p style='color:red;'>$message</p>"; ?>

        <form action="signin.php" method="post">
            <div class="form-group">
                <label for="text">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="form-group">
                <label for="code">Code</label>
                <input type="text" id="code" name="code" placeholder="Enter code (leave blank if not staff/admin)">
            </div>
            <button type="submit" class="btn">Sign In</button>
            <a href="forgetpassword.html">Forget Password?</a>
        </form>
        <p class="signup-text">Don't have an account? <a href="register.php">Register here</a></p>
        
        <img src="../image/ciallo.jpg" alt="Game Background" class="bg-image">
    </section>
    
    <footer>
        <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
    </footer>
</body>
</html>
