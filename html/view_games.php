<?php
include "config.php";

$games = $conn->query("SELECT * FROM games");

$itemsByGame = [];
$itemResult = $conn->query("SELECT * FROM game_items");
while ($row = $itemResult->fetch_assoc()) {
    $itemsByGame[$row['game_id']][] = $row;
}

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
<title>DJS Game</title>
<style>
body { 
    margin:0; 
    font-family: Arial,sans-serif; 
    background:#000; 
    color:#fff; 
    overflow-y: auto; 
}

/* 顶部导航 */
header { 
    background:#1e1e1e;
    padding:14px 20px; 
    display:flex; 
    align-items:center; 
    justify-content:space-between; 
    box-shadow:0 2px 8px rgba(0,0,0,0.5);
}
header .logo { 
    font-size:22px; 
    font-weight:bold; 
    color:#ff6600; 
}
header nav a { 
    color: white; 
    margin: 0 15px; 
    text-decoration: none; 
    font-weight: 500; 
    transition: color 0.3s ease, border-bottom 0.3s ease; 
}
header nav a:hover { 
    color: #f39c12; 
    border-bottom: 2px solid #f39c12; 
    padding-bottom: 3px; 
}

/* H1 标题条纹背景 */
.page-title {
 background: linear-gradient(135deg, #000 25%, #ff6600c0 25%, #ff66008a 50%, #000 50%, #000 75%, #ff6600b2 75%);
    background-size: 40px 40px; /* 控制条纹宽度 */
    padding:14px 20px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 

}
/* 游戏卡片容器 */
.game-list {
    max-width:900px;
    margin:30px auto;
    display:flex;
    flex-direction:column;
    gap:18px;
}

/* 单个游戏卡片 */
.game-card { 
    display:flex; 
    background:#111; 
    border-radius:10px; 
    overflow:hidden; 
    cursor:pointer; 
    transition: transform 0.25s ease, box-shadow 0.25s ease; 
    padding:12px; 
    align-items:center;
}
.game-card:hover { 
    transform:scale(1.02); 
    box-shadow:0 6px 18px rgba(255,102,0,0.3);  /* 橙色底光 */
}

/* 左边图片 */
.game-card img { 
    width:140px; 
    height:140px; 
    object-fit:cover; 
    border-radius:6px; 
    flex-shrink:0; 
    margin-right:16px;
}

/* 右边信息 */
.game-info {
    display:flex; 
    flex-direction:column; 
    justify-content:center; 
    flex-grow:1;
}
.game-info h3 { 
    margin:0; 
    font-size:20px; 
    font-weight:bold; 
    color:#ff6600; 
}
.game-info p { 
    margin:6px 0; 
    color:#ccc; 
    font-size:14px; 
}

/* 模态框 */
.modal {
    display:none;
    position:fixed;
    z-index:1000;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.8);
    justify-content:center;
    align-items:center;
}
.modal-content {
    background:#1a1a1a;
    padding:20px;
    border-radius:12px;
    max-width:500px;
    width:90%;
    color:#fff;
    box-shadow:0 6px 20px rgba(0,0,0,0.6);
}
/* 弹窗 */ .modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.9); justify-content:center; align-items:flex-start; padding:30px 0; } .modal-content { background:#111; padding:20px; border-radius:12px; width:500px; max-height:85vh; overflow-y:auto; color:#fff; position:relative; } .close { position:absolute; top:10px; right:15px; font-size:22px; cursor:pointer; color:#ff6600; } .modal-content h2 { margin:0 0 15px; color:#ff6600; text-align:center; } /* 物品卡片 */ .item { display:flex; justify-content:space-between; align-items:center; margin:10px 0; padding:10px; background:#222; border-radius:8px; transition:0.3s; } .item:hover { background:#333; } .item div { display:flex; align-items:center; gap:10px; } .item img { width:60px; height:60px; object-fit:cover; border-radius:6px; border:1px solid #444; } .item p { margin:0; font-size:14px; } .item-controls { display:flex; align-items:center; gap:8px; } .item-controls button { background:#ff6600; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold; } .item-controls button:hover { background:#e65c00; } /* 底部合计 */ .total-box { margin-top:20px; font-size:18px; text-align:right; color:#fff; } .total-box span { font-weight:bold; color:#ff6600; } .pay-btn { background:#ff6600; border:none; padding:10px 20px; font-size:16px; border-radius:6px; cursor:pointer; margin-top:15px; width:100%; transition:0.3s; } .pay-btn:hover { background:#e65c00; }
.close {
    float:right;
    font-size:28px;
    cursor:pointer;
    color:#ff6600;
}.item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:#222;
    margin:10px 0;
    padding:10px 15px;
    border-radius:8px;
}

/* 左边：图片+文字 */
.item-left {
    display:flex;
    align-items:center;
    gap:12px;
}
.item-left img {
    width:50px;
    height:50px;
    object-fit:cover;
    border-radius:6px;
}

/* 右边：数量按钮 */
.item-controls {
    display:flex;
    align-items:center;
    gap:8px;
}
.item-controls button {
    background:#ff6600;
    border:none;
    color:#fff;
    font-size:18px;
    width:32px;
    height:32px;
    border-radius:6px;
    cursor:pointer;
}

.total-box {
    margin-top:15px;
    font-size:18px;
    font-weight:bold;
    color:#ff6600;
}
.pay-btn {
    margin-top:15px;
    width:100%;
    padding:10px;
    background:#ff6600;
    border:none;
    border-radius:8px;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    color:#fff;
}
.pay-btn:hover {
    background:#e65c00;
}

.zoom-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    justify-content: center;
    align-items: center;
}
.zoom-content {
    max-width: 90%;
    max-height: 90%;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(255,102,0,0.8);
}
.zoom-close {
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 30px;
    color: #ff6600;
    cursor: pointer;
}
.zoom-btn {
    margin-left: 8px;
    background: #ff660060;
    border: none;
    color: #fff;
    font-size: 14px;
    padding: 4px 8px;
    border-radius: 6px;
    cursor: pointer;
}
.zoom-btn:hover {
    background: #f08102d8;
}
</style>
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
    </nav>
</header>

<h1 class="page-title">🎮 Available Games</h1>

<div class="game-list">
    <?php foreach ($games as $game): ?>
    <div class="game-card" onclick="openModal(<?= $game['game_id'] ?>)">
        <img src="<?= htmlspecialchars(getImagePath($game['image'])) ?>" alt="<?= htmlspecialchars($game['game_name']) ?>">
        <div class="game-info">
            <h3><?= htmlspecialchars($game['game_name']) ?></h3>
            <p><?= htmlspecialchars($game['description']) ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php foreach ($itemsByGame as $gameId => $items): ?>
<div id="modal-<?= $gameId ?>" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal(<?= $gameId ?>)">&times;</span>
        <h2>Items</h2>
        <div id="items-<?= $gameId ?>">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div style="display:flex;align-items:center;">
                    <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                    <!-- 🔍 放大镜按钮 -->
                    <button class="zoom-btn" onclick="openZoom('<?= htmlspecialchars(getImagePath($item['image'])) ?>')">🔍</button>
                   
                    <div>
                        <p><?= htmlspecialchars($item['item_name']) ?></p>
                        <p>RM <?= number_format($item['price'],2) ?></p>
                    </div>
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
            Total: <span>RM <span id="total-<?= $gameId ?>">0.00</span></span>
        </div>
        <form method="POST" action="save_order.php" onsubmit="return prepareOrder(<?= $gameId ?>)">
  <input type="hidden" name="game_id" value="<?= $gameId ?>">
  <input type="hidden" name="order_items" id="order-items-<?= $gameId ?>">
  <button type="submit" class="pay-btn">Pay</button>
</form>

</form>

</form>

</form>

    </div>
</div>
<?php endforeach; ?>

<!-- 🔍 图片放大 Modal -->
<div id="zoomModal" class="zoom-modal">
    <span class="zoom-close" onclick="closeZoom()">&times;</span>
    <img id="zoomImg" class="zoom-content" src="">
</div>
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

// 🔍 放大镜功能
function openZoom(src) {
    document.getElementById("zoomImg").src = src;
    document.getElementById("zoomModal").style.display = "flex";
}
function closeZoom() {
    document.getElementById("zoomModal").style.display = "none";
}

function saveToSession(gameId) {
    if (!cart[gameId] || Object.keys(cart[gameId]).length === 0) {
        alert("Please select at least one item.");
        return false;
    }
    let orderData = JSON.stringify(cart[gameId]);

    // AJAX 保存到 session
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "save_order.php", false); // 同步请求，保证保存后再跳转
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("game_id=" + gameId + "&order_data=" + encodeURIComponent(orderData));

    return true; // 提交表单继续去 payment.php
}


let cart = {}; // 保存所有选择的 item

function changeQty(gameId, itemId, delta, price) {
    let qtyEl = document.getElementById("qty-" + itemId);
    let qty = parseInt(qtyEl.innerText) + delta;
    if (qty < 0) qty = 0;
    qtyEl.innerText = qty;

    if (!cart[gameId]) cart[gameId] = {};

    // 更新购物车
    if (qty > 0) {
        cart[gameId][itemId] = { qty: qty, price: price };
    } else {
        delete cart[gameId][itemId];
    }

    // 计算总价
    let total = 0;
    for (let id in cart[gameId]) {
        total += cart[gameId][id].qty * cart[gameId][id].price;
    }
    document.getElementById("total-" + gameId).innerText = total.toFixed(2);
}

// 在提交之前准备数据
function prepareOrder(gameId) {
  // cart[gameId] 是你之前保存的结构
  if (!cart[gameId] || Object.keys(cart[gameId]).length === 0) {
    alert("Please select at least one item.");
    return false;
  }
  document.getElementById("order-items-" + gameId).value = JSON.stringify(cart[gameId]);
  return true;
}

</script>

</body>
</html>

