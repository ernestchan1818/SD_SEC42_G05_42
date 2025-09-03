<?php
include "config.php";

// 读取所有游戏分类
$games = $conn->query("SELECT * FROM games");

// 读取所有商品
$itemsByGame = [];
$itemResult = $conn->query("SELECT * FROM game_items");
while ($row = $itemResult->fetch_assoc()) {
    $itemsByGame[$row['game_id']][] = $row;
}

// 处理图片路径 → 确保是相对路径，支持绝对路径或缺失图片
function getImagePath($path) {
    $default = "uploads/default.png";
    if (!$path) return $default;

    $pos = stripos($path, 'uploads/');
    if ($pos !== false) return substr($path, $pos);

    return $path ?: $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Games</title>
<style>
    body { margin:0; font-family: Arial,sans-serif; background:#000; color:#fff; overflow-y: auto; }
    .navbar { background:#ff6600; padding:14px 20px; display:flex; align-items:center; }
    .navbar h1 { margin:0; font-size:20px; color:#fff; }
    .game-list { padding:20px; display:flex; flex-direction:column; gap:20px; }

    .game-card {
        display:flex;
        background:#1c1c1c;
        border-radius:10px;
        overflow:hidden;
        cursor:pointer;
        transition:0.3s;
    }
    .game-card:hover { background:#333; }
    .game-card img { width:150px; height:150px; object-fit:cover; }
    .game-info {
        flex:1;
        padding:10px 15px;
        display:flex;
        flex-direction:column;
        justify-content:space-between;
    }
    .game-info h3 { margin:0; font-size:18px; color:#ff6600; }
    .game-info p { margin:0; font-size:14px; color:#fff; }

    /* Modal */
    .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.8); justify-content:center; align-items:center; }
    .modal-content { background:#222; padding:20px; border-radius:10px; width:500px; max-height:80vh; overflow-y:auto; color:#fff; position:relative; }
    .close { position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; color:#ff6600; }
    .item { display:flex; justify-content:space-between; align-items:center; margin:10px 0; padding:10px; background:#333; border-radius:6px; }
    .item img { width:60px; height:60px; object-fit:cover; border-radius:6px; }
    .item-controls { display:flex; align-items:center; gap:8px; }
    .item-controls button { background:#ff6600; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; }
    .total-box { margin-top:20px; font-size:18px; text-align:right; }
    .pay-btn { background:#ff6600; border:none; padding:10px 20px; font-size:16px; border-radius:6px; cursor:pointer; margin-top:10px; width:100%; }
</style>
</head>
<body>

<div class="navbar">
    <h1>🎮 Available Games</h1>
</div>

<div class="game-list">
    <?php while ($game = $games->fetch_assoc()): ?>
    <div class="game-card" onclick="openModal(<?= $game['game_id'] ?>)">
        <img src="<?= htmlspecialchars(getImagePath($game['image'])) ?>" alt="<?= htmlspecialchars($game['game_name']) ?>">
        <div class="game-info">
            <h3><?= htmlspecialchars($game['game_name']) ?></h3>
            <p><?= htmlspecialchars($game['description']) ?></p>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php foreach ($itemsByGame as $gameId => $items): ?>
<div id="modal-<?= $gameId ?>" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal(<?= $gameId ?>)">&times;</span>
        <h2>Items</h2>
        <div id="items-<?= $gameId ?>">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div>
                    <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                    <p><?= htmlspecialchars($item['item_name']) ?></p>
                    <p>RM <?= number_format($item['price'],2) ?></p>
                </div>
                <div class="item-controls">
                    <button onclick="changeQty(<?= $gameId ?>, <?= $item['item_id'] ?>, -1, <?= $item['price'] ?>)">-</button>
                    <span id="qty-<?= $item['item_id'] ?>">0</span>
                    <button onclick="changeQty(<?= $gameId ?>, <?= $item['item_id'] ?>, 1, <?= $item['price'] ?>)">+</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="total-box">
            Total: RM <span id="total-<?= $gameId ?>">0.00</span>
        </div>
        <button class="pay-btn">Pay</button>
    </div>
</div>
<?php endforeach; ?>

<script>
function openModal(id) { document.getElementById("modal-" + id).style.display = "flex"; }
function closeModal(id) { document.getElementById("modal-" + id).style.display = "none"; }

let totals = {};
function changeQty(gameId, itemId, delta, price) {
    let qtyEl = document.getElementById("qty-" + itemId);
    let qty = parseInt(qtyEl.innerText) + delta;
    if (qty < 0) qty = 0;
    qtyEl.innerText = qty;

    if (!totals[gameId]) totals[gameId] = 0;
    totals[gameId] += delta * price;
    if (totals[gameId] < 0) totals[gameId] = 0;
    document.getElementById("total-" + gameId).innerText = totals[gameId].toFixed(2);
}
</script>

</body>
</html>
