
<?php
include "config.php";

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $msg = trim($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?,?,?)");
    $stmt->bind_param("sss", $name, $email, $msg);

    if ($stmt->execute()) {
        $message = "Your message has been sent! We will get back to you soon.";
    } else {
        $message = "Failed to send your message. Try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - DJS Game</title>
    <link rel="stylesheet" href="../css/contact.css">
</head>
<body>
    <header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="about.html">About Us</a>
            <a href="contact.php">Contact Us</a>
            <a href="register.php">Register</a>
            <a href="signin.php">Sign In</a>
        </nav>
    </header>

    <section class="contact-hero">
        <h1>Contact Us</h1>
        <p>We’d love to hear from you! Fill out the form below and we’ll get back to you as soon as possible.</p>
    </section>

    <section class="contact-form-section">
        <form action="#" method="post" class="contact-form">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" placeholder="Your Name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Your Email" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" placeholder="Your Message" required></textarea>

            <button type="submit" class="btn">Send Message</button>
        </form>
    </section>

    <footer>
        &copy; 2025 DJS Game. All rights reserved.
    </footer>
</body>
</html>
