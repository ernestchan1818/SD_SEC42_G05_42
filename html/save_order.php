<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $gameId = $_POST["game_id"] ?? null;
    $orderData = $_POST["order_data"] ?? null;

    if ($gameId && $orderData) {
        $_SESSION["order"] = [
            "game_id" => $gameId,
            "items" => json_decode($orderData, true)
        ];
    }
}
?>
