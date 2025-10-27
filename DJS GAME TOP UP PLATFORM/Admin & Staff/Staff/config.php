<?php
$servername = "localhost";
$username = "u827939212_DJS";
$password = "Djsgame123";
$dbname = "u827939212_otpdb";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);

}
?>