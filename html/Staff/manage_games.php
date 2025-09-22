<?php
session_start();
include "config.php"; 

// 权限限制
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: home.php");
    exit();
}

$message = "";

// ----------------- Handle POST ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- Add Game ----
    if (isset($_POST['add_game'])) {
        $game_name = trim($_POST['game_name']);
        $description = trim($_POST['description']);
        $imagePath = "";

        if (!empty($_FILES['image']['name'])) {
            $fileBaseName = basename($_FILES["image"]["name"]);
            $targetDir = "uploads/games/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $targetFile = $targetDir . $fileBaseName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imagePath = $targetFile;
            }
        }

        $stmt = $conn->prepare("INSERT INTO games (game_name, description, image) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $game_name, $description, $imagePath);
        $stmt->execute();
        $stmt->close();

        $message = "✅ Game Category added.";
    }

    // ---- Add Item ----
    if (isset($_POST['add_item'])) {
        $game_id = $_POST['game_id'];
        $item_name = trim($_POST['item_name']);
        $price = $_POST['price'];
        $imagePath = "";

        if (!empty($_FILES['image']['name'])) {
            $fileBaseName = basename($_FILES["image"]["name"]);
            $targetDir = "uploads/items/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $targetFile = $targetDir . $fileBaseName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imagePath = $targetFile;
            }
        }

        $stmt = $conn->prepare("INSERT INTO game_items (game_id, item_name, price, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $game_id, $item_name, $price, $imagePath);
        $stmt->execute();
        $stmt->close();

        $message = "✅ Item added.";
    }

    // ---- Edit Game ----
    if (isset($_POST['edit_game'])) {
        $id = $_POST['game_id'];
        $game_name = trim($_POST['game_name']);
        $description = trim($_POST['description']);

        $oldImg = $conn->query("SELECT image FROM games WHERE game_id=$id")->fetch_assoc()['image'];
        $imagePath = $oldImg;

        if (!empty($_FILES['image']['name'])) {
            $fileBaseName = basename($_FILES["image"]["name"]);
            $targetDir = "uploads/games/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $targetFile = $targetDir . $fileBaseName;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imagePath = $targetFile;
            }
        }

        $stmt = $conn->prepare("UPDATE games SET game_name=?, description=?, image=? WHERE game_id=?");
        $stmt->bind_param("sssi", $game_name, $description, $imagePath, $id);
        $stmt->execute();
        $stmt->close();

        $message = "✏️ Game updated.";
    }

    // ---- Edit Item ----
    if (isset($_POST['edit_item'])) {
        $id = $_POST['item_id'];
        $item_name = trim($_POST['item_name']);
        $price = $_POST['price'];

        $stmt = $conn->prepare("UPDATE game_items SET item_name=?, price=? WHERE item_id=?");
        $stmt->bind_param("sdi", $item_name, $price, $id);
        $stmt->execute();
        $stmt->close();

        $message = "✏️ Item updated.";
    }

    // ---- Delete Game ----
    if (isset($_POST['delete_game'])) {
        $id = $_POST['delete_game'];
        $conn->query("DELETE FROM games WHERE game_id=$id");
        $conn->query("DELETE FROM game_items WHERE game_id=$id");
        $message = "🗑 Game Category deleted.";
    }

    // ---- Delete Item ----
    if (isset($_POST['delete_item'])) {
        $id = $_POST['delete_item'];
        $conn->query("DELETE FROM game_items WHERE item_id=$id");
        $message = "🗑 Item deleted.";
    }
}

// ----------------- Fetch Data ------------------
$games = $conn->query("SELECT * FROM games ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Games & Items</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f0f8ff; color:#333; margin:0; }
        .navbar { background:#007bff; padding:12px; }
        .navbar a { color:#fff; text-decoration:none; margin-right:20px; font-weight:600; }
        .container { width:95%; margin:auto; padding:20px; }
        .card { background:#ffffff; padding:15px; margin:15px 0; border-radius:8px; box-shadow:0 0 5px rgba(0,0,0,0.2);}
        h2 { color:#007bff; }
        input, textarea, select { width:100%; padding:8px; margin:6px 0; border:1px solid #ccc; border-radius:4px; background:#e9f5ff; color:#333; }
        button { background:#007bff; color:#fff; border:none; padding:6px 12px; border-radius:5px; cursor:pointer; margin:2px; }
        table { width:100%; border-collapse:collapse; margin-top:15px; }
        th, td { border:1px solid #ccc; padding:8px; text-align:center; }
        th { background:#007bff; color:#fff; }
        img { max-width:80px; }
        .msg { background:#17a2b8; padding:8px; margin-bottom:15px; border-radius:5px; color:#fff; }
        .nav { width:90%; max-width:1100px; margin:0 auto 12px; display:flex; gap:12px; align-items:center; }
.nav a { text-decoration:none; background:#2c3e50; color:#fff; padding:8px 12px; border-radius:6px; }
.nav a.logout { margin-left:auto; background:#c0392b; }
    </style>
</head>
<body>

<div class="navbar">
     <nav>
    <?php
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            echo '<a href="admin_home.php">Home</a>';
        } elseif ($_SESSION['role'] === 'staff') {
            echo '<a href="staff_home.php">Home</a>';
        } 
    } 
    ?>
            <a href="Contact.php">Contact</a>
            <a href="contactus.php">Feedback</a>
            <a href="view_games.php">Top-Up Games</a>
            <a href="view_packages.php">Top-Up Packages</a>
            <a href="signout.php">Sign Out</a>
        </nav>
</div>

<div class="container">
    <h2>🎮 Manage Game Categories & Items</h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add Game -->
    <div class="card">
        <h3>Add Game Category</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="text" name="game_name" placeholder="Game Name" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="file" name="image" accept="image/*">
            <button type="submit" name="add_game">Add Game</button>
        </form>
    </div>

    <!-- Add Item -->
    <div class="card">
        <h3>Add Item to Game</h3>
        <form method="post" enctype="multipart/form-data">
            <select name="game_id" required>
                <option value="">-- Select Game --</option>
                <?php while ($g = $games->fetch_assoc()): ?>
                    <option value="<?= $g['game_id'] ?>"><?= htmlspecialchars($g['game_name']) ?></option>
                <?php endwhile; $games->data_seek(0); ?>
            </select>
            <input type="text" name="item_name" placeholder="Item Name (e.g., 月卡, 648 Gems)" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
            <input type="file" name="image" accept="image/*">
            <button type="submit" name="add_item">Add Item</button>
        </form>
    </div>

    <!-- Game & Item List -->
    <?php while ($game = $games->fetch_assoc()): ?>
        <div class="card">
            <h3><?= htmlspecialchars($game['game_name']) ?></h3>
            <p><?= htmlspecialchars($game['description']) ?></p>
            <?php if ($game['image']): ?>
                <img src="<?= htmlspecialchars($game['image']) ?>" alt="Game Image">
            <?php endif; ?>

            <!-- Edit Game -->
            <details>
                <summary>✏️ Edit Game</summary>
                <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                    <input type="hidden" name="game_id" value="<?= $game['game_id'] ?>">
                    <input type="text" name="game_name" value="<?= htmlspecialchars($game['game_name']) ?>" required>
                    <textarea name="description"><?= htmlspecialchars($game['description']) ?></textarea>
                    <input type="file" name="image" accept="image/*">
                    <button type="submit" name="edit_game">Save Changes</button>
                </form>
            </details>

            <!-- Delete Game -->
            <form method="post" style="margin-top:10px;">
                <input type="hidden" name="delete_game" value="<?= $game['game_id'] ?>">
                <button type="submit" onclick="return confirm('Delete this game and all its items?')">🗑 Delete Game</button>
            </form>

            <h4>Items:</h4>
            <table>
                <tr><th>ID</th><th>Name</th><th>Price</th><th>Image</th><th>Action</th></tr>
                <?php 
                $items = $conn->query("SELECT * FROM game_items WHERE game_id=" . $game['game_id']);
                if ($items->num_rows > 0):
                    while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?= $item['item_id'] ?></td>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td>RM <?= number_format($item['price'],2) ?></td>
                            <td><?php if ($item['image']): ?><img src="<?= htmlspecialchars($item['image']) ?>"><?php endif; ?></td>
                            <td>
                                <!-- Edit Item -->
                                <details>
                                    <summary>✏️ Edit</summary>
                                    <form method="post" style="margin-top:6px;">
                                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
                                        <input type="text" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                                        <input type="number" step="0.01" name="price" value="<?= number_format($item['price'],2) ?>" required>
                                        <button type="submit" name="edit_item">Save</button>
                                    </form>
                                </details>
                                <!-- Delete Item -->
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_item" value="<?= $item['item_id'] ?>">
                                    <button type="submit" onclick="return confirm('Delete this item?')">🗑 Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr><td colspan="5">No items yet.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>
