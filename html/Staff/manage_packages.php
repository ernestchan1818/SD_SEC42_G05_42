<?php
session_start();
include "config.php"; // 确保 $conn 是 mysqli 连接

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
        $discount = floatval($_POST['discount']);
        $imagePath = "";

        if (!empty($_FILES['image']['name'])) {
            $targetDir = "uploads/packages/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $fileName = basename($_FILES["image"]["name"]);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $imagePath = $targetFile;
            }
        }

        $stmt = $conn->prepare("INSERT INTO topup_packages (package_name, description, discount, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $package_name, $description, $discount, $imagePath);
        $stmt->execute();
        $package_id = $stmt->insert_id;
        $stmt->close();

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
        $conn->query("DELETE FROM package_items WHERE package_id=$id");
        $message = "🗑 Package deleted.";
    }

    // ---- Edit Package ----
    if (isset($_POST['edit_package'])) {
        $package_id = intval($_POST['package_id']);
        $package_name = trim($_POST['package_name']);
        $discount = floatval($_POST['discount']);
        $description = trim($_POST['description']);

        // 更新 package
        $stmt = $conn->prepare("UPDATE topup_packages SET package_name=?, description=?, discount=? WHERE package_id=?");
        $stmt->bind_param("ssdi", $package_name, $description, $discount, $package_id);
        $stmt->execute();
        $stmt->close();

        // 清空原有 items
        $conn->query("DELETE FROM package_items WHERE package_id=$package_id");

        // 插入新的 items
        if (!empty($_POST['item_ids'])) {
            foreach ($_POST['item_ids'] as $item_id) {
                $stmt = $conn->prepare("INSERT INTO package_items (package_id, item_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $package_id, $item_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $message = "✏️ Package updated successfully.";
    }
}

// ----------------- Fetch Packages ------------------
$packages = $conn->query("SELECT * FROM topup_packages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="utf-8">
<title>Manage Top-up Packages</title>
<style>
/* --- Base Styles --- */
body { 
    font-family: 'Inter', Arial, sans-serif; 
    background: #ffffff; /* 修正：白色背景 */
    color: #333; /* 修正：深色字体 */
    margin: 0; 
    padding: 0; 
}
.container { 
    width: 95%; 
    max-width: 1200px;
    margin: auto; 
    padding: 20px; 
}
h1 { 
    text-align: center; 
    color: #007BFF; /* 亮蓝色标题 */
    margin-top: 20px;
    margin-bottom: 30px;
    font-size: 2.5em;
    text-shadow: 0 0 5px rgba(0, 123, 255, 0.2); /* 调整阴影 */
}

/* --- Header & Navigation (Unified Blue/White Theme) --- */
header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: #007BFF; /* 蓝色头部背景 */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.logo {
    font-size: 24px;
    font-weight: bold;
    color: #fff;
}
nav {
    display: flex;
    gap: 15px;
}
nav a {
    color: #fff;
    text-decoration: none;
    padding: 5px 10px;
    border-radius: 4px;
    transition: background 0.3s;
    font-weight: 500;
}
nav a:hover {
    background: #0056B3; /* 深蓝色悬停 */
}

/* --- Message Box --- */
.msg { 
    padding: 10px; 
    margin: 15px auto; 
    border-radius: 8px; 
    color: #fff; 
    text-align: center;
    font-weight: bold;
    background: #28a745;
}

/* --- Card Styles --- */
.card { 
    background: #ffffff; /* 修正：卡片背景为白色 */
    padding: 20px; 
    margin: 25px 0; 
    border-radius: 12px; 
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4); /* 深蓝色阴影 */
}
.card h3 {
    color: #007BFF;
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 8px;
    margin-bottom: 15px;
}

/* --- Form Elements --- */
input, textarea, select { 
    width: calc(50% - 20px); 
    padding: 12px; 
    margin: 8px 4px; 
    border: 1px solid #ccc;
    border-radius: 8px; 
    background: #f8f8f8; /* 浅色输入框背景 */
    color: #333; /* 深色字体 */
    box-sizing: border-box;
}
textarea { width: 100%; }
input[type="file"] { background: none; border: none; }

button { 
    background: #007bff; 
    color:#fff; 
    border:none; 
    padding:10px 18px; 
    border-radius:8px; 
    cursor:pointer; 
    font-weight:600; 
    margin: 8px 4px;
    transition: background 0.3s;
}
button[name="add_package"] { background: #28a745; }
button[name="add_package"]:hover { background: #1e8b4e; }
button:hover { background:#0056b3; }

/* --- Checkbox List --- */
.item-checkbox { 
    display:flex; 
    flex-direction: column; 
    max-height: 200px; 
    overflow-y:auto; 
    border:1px solid #00BFFF; /* 蓝色边框 */
    padding:10px; 
    border-radius:8px; 
    background:#e9f5ff; /* 浅蓝背景 */
    margin:10px 0; 
    color: #333; /* 深色字体 */
}
.item-checkbox label { 
    margin-bottom:6px; 
    color: #333;
    font-size: 0.9em;
}

/* --- Package List & Table --- */
table { 
    border-collapse:collapse; 
    margin-top:15px; 
    background:#ffffff; /* 修正：表格背景为白色 */
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
}
th, td { 
    border:1px solid #e0e0e0; /* 浅色分割线 */
    padding:10px 8px; 
    text-align:center; 
    color: #333; /* 修正：深色字体 */
}
th { 
    background:#007bff; 
    color:#fff; 
    font-weight:600; 
}
img { max-width:60px; height: auto; border-radius: 4px; }
.card table tr:nth-child(even) { background: #f8f8f8; }
.card table tr:hover td { background: #e9f5ff; }

/* --- Special Prices --- */
del { color: #dc3545; /* 红色删除线 */ }
strong { color: #10b981; /* 绿色最终价 */ }

/* --- Modal --- */
.modal-content { 
    background: #ffffff; /* 修正：Modal 背景为白色 */
    color: #333;
    box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4);
}
.close-btn { background:#dc3545; }
.close-btn:hover { background:#c82333; }

/* --- Responsive Adjustments --- */
@media (max-width: 768px) {
    input, textarea, select {
        width: 100%;
        margin: 8px 0;
    }
}
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

// 打开编辑 Modal
function openEditModal(pkgId, pkgName, pkgDesc, pkgDiscount, gameId, itemIds) {
    let modal = document.getElementById("editModal");
    modal.style.display = "flex";

    document.getElementById("edit_package_id").value = pkgId;
    document.getElementById("edit_package_name").value = pkgName.replace(/\\'/g, "'").replace(/\\"/g, '"'); // 修复转义
    document.getElementById("edit_description").value = pkgDesc.replace(/\\'/g, "'").replace(/\\"/g, '"'); // 修复转义
    document.getElementById("edit_discount").value = pkgDiscount;

    // 隐藏所有 item 区域
    document.querySelectorAll("#editModal .item-checkbox").forEach(c => c.style.display="none");
    
    // 选中正确的游戏分类，并显示其 items
    let editGameSelect = document.getElementById('edit_game_select');
    if (editGameSelect) {
         // 检查 gameId 是否是 'null' 字符串，并转换为 null
        let actualGameId = gameId === 'null' ? null : parseInt(gameId);

        // 设置下拉框选中值
        editGameSelect.value = actualGameId || ''; 
        
        // 显示对应的 items
        let container = document.getElementById("edit_items_game_" + actualGameId);
        if(container) {
            container.style.display="block";
            // 取消所有勾选
            container.querySelectorAll("input[type=checkbox]").forEach(cb => cb.checked = false);
            // 勾选已有的
            itemIds.forEach(id => {
                let cb = container.querySelector("input[value='"+id+"']");
                if(cb) cb.checked = true;
            });
        } else {
             // 如果找不到容器，确保至少一个容器是显示的，以便用户选择
             let firstContainer = document.querySelector("#editModal .item-checkbox");
             if (firstContainer) firstContainer.style.display = "block";
        }
    }
}
function closeEditModal() {
    document.getElementById("editModal").style.display="none";
}

// 确保在编辑 Modal 打开时，能够根据当前选择的 gameId 显示 Items
document.addEventListener('DOMContentLoaded', () => {
    // 为 Edit Modal 中的 Select Game 元素添加 onChange 事件
    let editGameSelect = document.getElementById('edit_game_select');
    if (editGameSelect) {
        editGameSelect.addEventListener('change', (e) => {
             // 逻辑与 showItemsByGame 类似，但针对编辑 modal
             let gameId = e.target.value;
             let containers = document.querySelectorAll("#editModal .item-checkbox");
             containers.forEach(c => c.style.display = "none");
             if(gameId) {
                 document.getElementById("edit_items_game_" + gameId).style.display = "block";
             }
        });
    }
});
</script>
</head>
<body>

<header>
    <div class="logo">🎮 DJS Game</div>
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
        <a href="manage_games.php">Top-Up Games</a>
        <a href="manage_packages.php">Top-Up Packages</a>
        <a href="logoutS.php">Sign Out</a>
    </nav>
</header>

<div class="container">
  <h1>🎁 Manage Top-up Packages</h1>
  <?php if ($message): ?>
    <div class="msg"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <!-- Add Package -->
  <div class="card">
    <h3>Add Package</h3>
    <form method="post" enctype="multipart/form-data">
      <input type="text" name="package_name" placeholder="Package Name" required>
      <textarea name="description" placeholder="Description"></textarea>
      <input type="number" step="0.01" name="discount" placeholder="Discount %" min="0" max="100" required>
      <input type="file" name="image" accept="image/*">

      <h4>Select Game Category:</h4>
      <select id="game_select" onchange="showItemsByGame()" required>
        <option value="">-- Select Game --</option>
        <?php $games->data_seek(0); while ($g = $games->fetch_assoc()): ?>
          <option value="<?= $g['game_id'] ?>"><?= htmlspecialchars($g['game_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <?php 
        $games->data_seek(0); 
        while ($g = $games->fetch_assoc()): 
          $gameId = $g['game_id'];
      ?>
        <div class="item-checkbox" id="items_game_<?= $gameId ?>" style="display:none;">
          <?php foreach ($itemsByGame[$gameId] ?? [] as $item): ?>
            <label>
              <input type="checkbox" name="item_ids[]" value="<?= $item['item_id'] ?>">
              <?= htmlspecialchars($item['item_name']) ?> (RM <?= number_format($item['price'], 2) ?>)
            </label>
          <?php endforeach; ?>
        </div>
      <?php endwhile; ?>

      <button type="submit" name="add_package">Add Package</button>
    </form>
  </div>

  <!-- Package List -->
  <?php while ($pkg = $packages->fetch_assoc()): ?>
    <div class="card">
      <h3><?= htmlspecialchars($pkg['package_name']) ?></h3>
      <p><?= htmlspecialchars($pkg['description']) ?></p>
      <?php if ($pkg['image']): ?>
        <img src="<?= htmlspecialchars($pkg['image']) ?>" alt="Package Image">
      <?php endif; ?>
      <p>Discount: <?= number_format($pkg['discount'], 2) ?>%</p>

      <form method="post" style="margin-top:10px; display:inline;">
        <input type="hidden" name="delete_package" value="<?= $pkg['package_id'] ?>">
        <button type="submit" class="close-btn" onclick="return confirm('Delete this package?')">🗑 Delete</button>
      </form>

      <?php
        $items = $conn->query("SELECT gi.* FROM game_items gi JOIN package_items pi ON gi.item_id=pi.item_id WHERE pi.package_id=".$pkg['package_id']);
        $total = 0;
        $gameId = null;
        $itemIds = [];
        while ($item = $items->fetch_assoc()):
          $total += $item['price'];
          $gameId = $item['game_id'];
          $itemIds[] = $item['item_id'];
        endwhile;
      ?>

      <button type="button" class="btn" onclick='openEditModal(
        <?= $pkg['package_id'] ?>,
        <?= json_encode($pkg['package_name']) ?>,
        <?= json_encode($pkg['description']) ?>,
        <?= $pkg['discount'] ?>,
        <?= $gameId ?: "null" ?>,
        <?= json_encode($itemIds) ?>
      )'>✏️ Edit</button>

      <!-- Show Package Items -->
      <table>
        <tr><th>Item</th><th>Price</th></tr>
        <?php
          $items2 = $conn->query("SELECT gi.* FROM game_items gi JOIN package_items pi ON gi.item_id=pi.item_id WHERE pi.package_id=".$pkg['package_id']);
          while ($it = $items2->fetch_assoc()):
        ?>
          <tr>
            <td><?= htmlspecialchars($it['item_name']) ?></td>
            <td>RM <?= number_format($it['price'], 2) ?></td>
          </tr>
        <?php endwhile; ?>
        <tr>
          <td><strong>Total</strong></td>
          <td>
            <del>RM <?= number_format($total, 2) ?></del>
            <strong>RM <?= number_format($total * (1 - $pkg['discount'] / 100), 2) ?></strong>
          </td>
        </tr>
      </table>
    </div>
  <?php endwhile; ?>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <h3>Edit Package</h3>
    <form method="post">
      <input type="hidden" name="package_id" id="edit_package_id">
      <input type="text" name="package_name" id="edit_package_name" required>
      <textarea name="description" id="edit_description"></textarea>
      <input type="number" step="0.01" name="discount" id="edit_discount" min="0" max="100" required>

      <!-- Game Select for Editing -->
      <h4>Select Game Category (Items will update):</h4>
      <select id="edit_game_select" onchange="showItemsByGame(true)" required>
        <option value="">-- Select Game --</option>
        <?php $games->data_seek(0); while ($g = $games->fetch_assoc()): ?>
          <option value="<?= $g['game_id'] ?>"><?= htmlspecialchars($g['game_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <?php 
        $games->data_seek(0); 
        while ($g = $games->fetch_assoc()):
          $gameId = $g['game_id'];
      ?>
        <div class="item-checkbox" id="edit_items_game_<?= $gameId ?>" style="display:none;">
          <?php foreach ($itemsByGame[$gameId] ?? [] as $item): ?>
            <label>
              <input type="checkbox" name="item_ids[]" value="<?= $item['item_id'] ?>">
              <?= htmlspecialchars($item['item_name']) ?> (RM <?= number_format($item['price'], 2) ?>)
            </label>
          <?php endforeach; ?>
        </div>
      <?php endwhile; ?>

      <button type="submit" name="edit_package">Save Changes</button>
      <button type="button" class="close-btn" onclick="closeEditModal()">Cancel</button>
    </form>
  </div>
</div>

</body>
</html>

