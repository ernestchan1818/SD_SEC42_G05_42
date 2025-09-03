<?php
session_start();
include "config.php";

// 权限限制
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header("Location: home.php");
    exit();
}

$message = "";

// 读取游戏分类
$games = $conn->query("SELECT * FROM games ORDER BY game_name");

// 读取所有商品
$itemsByGame = [];
$itemResult = $conn->query("SELECT * FROM game_items ORDER BY item_name");
while ($row = $itemResult->fetch_assoc()) {
    $itemsByGame[$row['game_id']][] = $row;
}

// ----------------- Handle POST ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- Add Package ----
    if (isset($_POST['add_package'])) {
        $package_name = trim($_POST['package_name']);
        $description = trim($_POST['description']);
        $discount = floatval($_POST['discount']); // 10 = 10%
        $imagePath = "";

        if (!empty($_FILES['image']['name'])) {
    $targetDir = "uploads/packages/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    // ✅ 使用原始文件名（不会加号码）
    $fileName = basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $imagePath = $targetFile;
    }
}


        // 插入 package
        $stmt = $conn->prepare("INSERT INTO topup_packages (package_name, description, discount, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $package_name, $description, $discount, $imagePath);
        $stmt->execute();
        $package_id = $stmt->insert_id;
        $stmt->close();

        // 插入 package_items
        if (!empty($_POST['item_ids'])) {
            foreach ($_POST['item_ids'] as $item_id) {
                $stmt = $conn->prepare("INSERT INTO package_items (package_id, item_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $package_id, $item_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $message = "✅ Package added successfully.";
    }

    // ---- Delete Package ----
    if (isset($_POST['delete_package'])) {
        $id = $_POST['delete_package'];
        $conn->query("DELETE FROM topup_packages WHERE package_id=$id");
        $message = "🗑 Package deleted.";
    }
}

// ----------------- Fetch Packages ------------------
$packages = $conn->query("SELECT * FROM topup_packages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Top-up Packages</title>
<style>
body { font-family: Arial,sans-serif; background:#e0f0ff; color:#000; margin:0; }
.navbar { background:#007bff; padding:14px 20px; display:flex; align-items:center; }
.navbar h1 { margin:0; font-size:20px; color:#fff; }
.container { padding:20px; }
.card { background:#fff; padding:15px; margin:15px 0; border-radius:8px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
input, textarea, select { width:100%; padding:8px; margin:6px 0; border:1px solid #ccc; border-radius:4px; background:#f9f9f9; color:#000; }
button { background:#007bff; color:#fff; border:none; padding:8px 14px; border-radius:5px; cursor:pointer; }
button:hover { background:#0056b3; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
th { background:#007bff; color:#fff; }
img { max-width:80px; }
.msg { background:#28a745; padding:8px; margin-bottom:15px; border-radius:5px; color:#fff; }

/* 新增：item checkbox 容器 */
.item-checkbox { display:flex; flex-direction: column; max-height: 200px; overflow-y:auto; border:1px solid #ccc; padding:8px; border-radius:4px; background:#f1f9ff; margin:6px 0; }
.item-checkbox label { margin-bottom:4px; cursor:pointer; }
</style>
<script>
function showItemsByGame() {
    let gameId = document.getElementById("game_select").value;
    let containers = document.querySelectorAll(".item-checkbox");
    containers.forEach(c => c.style.display = "none");
    if(gameId) {
        document.getElementById("items_game_" + gameId).style.display = "block";
    }
}
</script>
</head>
<body>

<div class="navbar">
    <h1>🎁 Manage Top-up Packages</h1>
</div>

<div class="container">

<?php if($message): ?>
<div class="msg"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Add Package -->
<div class="card">
    <h3>Add Package</h3>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="package_name" placeholder="Package Name" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input type="number" name="discount" placeholder="Discount %" min="0" max="100" required>
        <input type="file" name="image" accept="image/*">

        <h4>Select Game Category:</h4>
        <select id="game_select" onchange="showItemsByGame()" required>
            <option value="">-- Select Game --</option>
            <?php while($g = $games->fetch_assoc()): ?>
                <option value="<?= $g['game_id'] ?>"><?= htmlspecialchars($g['game_name']) ?></option>
            <?php endwhile; $games->data_seek(0); ?>
        </select>

        <?php foreach($itemsByGame as $gameId => $items): ?>
            <div class="item-checkbox" id="items_game_<?= $gameId ?>" style="display:none;">
                <?php foreach($items as $item): ?>
                    <label>
                        <input type="checkbox" name="item_ids[]" value="<?= $item['item_id'] ?>">
                        <?= htmlspecialchars($item['item_name']) ?> (RM <?= number_format($item['price'],2) ?>)
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" name="add_package">Add Package</button>
    </form>
</div>

<!-- Package List -->
<?php while($pkg = $packages->fetch_assoc()): ?>
<div class="card">
    <h3><?= htmlspecialchars($pkg['package_name']) ?></h3>
    <p><?= htmlspecialchars($pkg['description']) ?></p>
    <?php if($pkg['image']): ?><img src="<?= htmlspecialchars($pkg['image']) ?>" alt="Package Image"><?php endif; ?>
    <p>Discount: <?= number_format($pkg['discount'],2) ?>%</p>

    <form method="post" style="margin-top:10px;">
        <input type="hidden" name="delete_package" value="<?= $pkg['package_id'] ?>">
        <button type="submit" onclick="return confirm('Delete this package?')">🗑 Delete Package</button>
    </form>

    <!-- Show Package Items -->
    <table>
        <tr><th>Item</th><th>Price</th></tr>
        <?php
        $items = $conn->query("SELECT gi.* FROM game_items gi JOIN package_items pi ON gi.item_id=pi.item_id WHERE pi.package_id=".$pkg['package_id']);
        $total = 0;
        while($item = $items->fetch_assoc()):
            $total += $item['price'];
        ?>
        <tr>
            <td><?= htmlspecialchars($item['item_name']) ?></td>
            <td>RM <?= number_format($item['price'],2) ?></td>
        </tr>
        <?php endwhile; ?>
        <tr>
            <td><strong>Total</strong></td>
            <td><del>RM <?= number_format($total,2) ?></del> <strong>RM <?= number_format($total * (1 - $pkg['discount']/100),2) ?></strong></td>
        </tr>
    </table>
</div>
<?php endwhile; ?>

</div>
</body>
</html>
