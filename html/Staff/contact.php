<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - GameStore</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>

<header>
        <div class="logo">🎮 DJS Game</div>
        <nav>
            <a href="home.php">Home</a>
            <a href="about.html">About</a>
            <a href="Contact.php">Contact</a>
            <a href="Feedback.php">Feedback</a>
            <a href="view_games.php">Top-Up Games</a>
            <a href="view_packages.php">Top-Up Packages</a>
            <a href="signout.php">Sign Out</a>
        </nav>
    </header>

<section class="hero">
    <img src="feedback-bg.jpg" alt="Background" class="bg-image">
    <div class="hero-text">
        <h1>Contact Us</h1>
        <p>We’d love to hear from you. Reach us via phone or email below.</p>
    </div>
</section>

<section class="payment">
    <h2>Our Contact Information</h2>
    <div style="max-width:500px; margin:0 auto; text-align:left; font-size:1.2em; line-height:1.8;">
        <p><strong>📞 Phone:</strong> +60 12-345 6789</p>
        <p><strong>✉️ Email:</strong> djssupport@gmail.com</p>
    </div>
</section>

<footer>
    <p>&copy; 2025 DJS Game. All Rights Reserved.</p>
</footer>

<script>
        document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
            anchor.addEventListener("click", function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute("href"))
                    .scrollIntoView({ behavior: "smooth" });
            });
        });
    </script>
</body>
</html>
