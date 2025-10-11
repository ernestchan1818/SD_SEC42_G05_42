<?php
include "config.php";
session_start(); // 确保 session 启动，以便在 save_order.php 中获取 user_id

$games = $conn->query("SELECT * FROM games");

$itemsByGame = [];
$itemResult = $conn->query("SELECT * FROM game_items");
while ($row = $itemResult->fetch_assoc()) {
    $itemsByGame[$row['game_id']][] = $row;
}

function getImagePath($path) {
    // 确保路径正确，并提供 fallback
    $default = "https://placehold.co/140x140/333/fff?text=No+Image"; 
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DJS Game Topup</title>
<style>
body { 
    margin:0; 
    font-family: 'Inter', Arial, sans-serif; 
    background:#000; 
    color:#fff; 
    overflow-x: hidden; 
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
    background-size: 40px 40px; 
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
    padding: 0 10px;
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
    box-shadow:0 6px 18px rgba(255,102,0,0.3); 
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

/* 模态框 */
.modal { 
    display:none; 
    position:fixed; 
    z-index:1000; 
    left:0; 
    top:0; 
    width:100%; 
    height:100%; 
    background: rgba(0,0,0,0.9); 
    justify-content:center; 
    align-items:flex-start; 
    padding:30px 0; 
} 
.modal-content { 
    background:#111; 
    padding:20px; 
    border-radius:12px; 
    width:90%;
    max-width:500px;
    max-height:85vh; 
    overflow-y:auto; 
    color:#fff; 
    position:relative; 
    box-shadow: 0 0 20px rgba(255,102,0,0.4);
} 
.close { 
    position:absolute; 
    top:10px; 
    right:15px; 
    font-size:24px; 
    cursor:pointer; 
    color:#ff6600; 
    transition: 0.3s;
} 
.close:hover {
    color: #f39c12;
}

/* 物品卡片 */
.item {
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
    transition: background 0.2s;
}
.item-controls button:hover {
    background:#e65c00;
}

.total-box {
    margin-top:15px;
    font-size:18px;
    font-weight:bold;
    text-align: right;
    color:#ff6600;
}
.pay-btn {
    margin-top:15px;
    width:100%;
    padding:12px;
    background:#00ff99; /* 亮绿色支付按钮 */
    border:none;
    border-radius:8px;
    font-size:18px;
    font-weight:bold;
    cursor:pointer;
    color:#000;
    transition: background 0.2s;
}
.pay-btn:hover {
    background:#00e68d;
}

/* 放大镜 Modal */
.zoom-modal, .error-modal {
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

/* 错误提示框样式 */
.error-content {
    background:#ff0000;
    color:white;
    padding:20px;
    border-radius:10px;
    text-align: center;
    box-shadow: 0 0 15px rgba(255,0,0,0.5);
}
.error-content h3 {
    margin-top: 0;
}
.error-close-btn {
    background:#fff;
    color:#ff0000;
    border:none;
    padding:8px 15px;
    margin-top:10px;
    border-radius:5px;
    cursor:pointer;
}
</style>
</head>

<body>

<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <a href="home.php">Home</a>
        <a href="my_order.php">Track Orders</a> <!-- 修正导航链接 -->
        <a href="about.html">About</a>
        <a href="Contact.php">Contact</a>
        <a href="Feedback.php">Feedback</a>
        <a href="view_games.php" style="border-bottom: 2px solid #f39c12; padding-bottom: 3px;">Top-Up Games</a>
        <a href="view_packages.php">Top-Up Packages</a>
    </nav>
</header>

<h1 class="page-title">🎮 Available Games</h1>

<div class="game-list">
    <?php foreach ($games as $game): ?>
    <div class="game-card" onclick="openModal(<?= $game['game_id'] ?>)">
        <img src="<?= htmlspecialchars(getImagePath($game['image'])) ?>" alt="<?= htmlspecialchars($game['game_name']) ?>" onerror="this.onerror=null;this.src='https://placehold.co/140x140/333/fff?text=Image+Error';">
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
        <h2>Select Items for Game ID: <?= htmlspecialchars($gameId) ?></h2>
        <div id="items-<?= $gameId ?>">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div class="item-left">
                    <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" onerror="this.onerror=null;this.src='https://placehold.co/50x50/333/fff?text=N/A';">
                    <!-- 🔍 放大镜按钮 -->
                    <button class="zoom-btn" onclick="openZoom('<?= htmlspecialchars(getImagePath($item['image'])) ?>')">🔍</button>
                    
                    <div>
                        <p style="color:#ff6600; font-weight: bold;"><?= htmlspecialchars($item['item_name']) ?></p>
                        <p>RM <?= number_format($item['price'],2) ?></p>
                    </div>
                </div>
                <div class="item-controls">
                    <button onclick="changeQty(<?= $gameId ?>, <?= $item['item_id'] ?>, -1, <?= $item['price'] ?>)">-</button>
                    <span id="qty-<?= $item['item_id'] ?>" style="min-width: 20px; text-align: center;">0</span>
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
        <button type="submit" class="pay-btn">Go To Payment</button>
    </form>
    </div>
</div>
<?php endforeach; ?>

<!-- 🔍 图片放大 Modal -->
<div id="zoomModal" class="zoom-modal">
    <span class="zoom-close" onclick="closeZoom()">&times;</span>
    <img id="zoomImg" class="zoom-content" src="">
</div>

<!-- 🚫 错误提示 Modal (替换 alert) -->
<div id="errorModal" class="error-modal">
    <div class="error-content">
        <h3>🚨 Error</h3>
        <p id="errorMessage"></p>
        <button class="error-close-btn" onclick="closeErrorModal()">Close</button>
    </div>
</div>

<script>
// --- State Management ---
let cart = {}; 

function openModal(id) { 
    document.getElementById("modal-" + id).style.display = "flex"; 
}
function closeModal(id) { 
    document.getElementById("modal-" + id).style.display = "none"; 
}

// --- 错误提示 UI (替换 alert) ---
function showErrorModal(message) {
    document.getElementById("errorMessage").innerText = message;
    document.getElementById("errorModal").style.display = "flex";
}
function closeErrorModal() {
    document.getElementById("errorModal").style.display = "none";
}

// --- 购物车数量增减逻辑 ---
function changeQty(gameId, itemId, delta, price) {
    let qtyEl = document.getElementById("qty-" + itemId);
    let currentQty = parseInt(qtyEl.innerText);
    let newQty = currentQty + delta;

    if (newQty < 0) newQty = 0;
    qtyEl.innerText = newQty;

    // 使用 gameId (number) 作为键
    if (!cart[gameId]) cart[gameId] = {};

    // 更新购物车：只存储有数量的物品
    if (newQty > 0) {
        // 存储数量和价格。注意：我们不需要存储 itemName，save_order.php 会自己查询。
        cart[gameId][itemId] = { quantity: newQty, price: price }; 
    } else {
        delete cart[gameId][itemId];
    }

    // 计算并更新总价
    let total = 0;
    // 确保 cart[gameId] 存在且是对象
    if (cart[gameId] && typeof cart[gameId] === 'object') {
        for (let id in cart[gameId]) {
            total += cart[gameId][id].quantity * cart[gameId][id].price;
        }
    }
    
    document.getElementById("total-" + gameId).innerText = total.toFixed(2);
}

// --- 表单提交前的准备工作 ---
function prepareOrder(gameId) {
    // 检查购物车中是否有选中的物品
    if (!cart[gameId] || Object.keys(cart[gameId]).length === 0) {
        showErrorModal("Please select at least one item before proceeding to payment.");
        return false; // 阻止表单提交
    }
    
    // 将选中的物品数据转换为 JSON 字符串
    let hiddenInput = document.getElementById("order-items-" + gameId);
    hiddenInput.value = JSON.stringify(cart[gameId]);
    
    // 打印数据到控制台以供调试
    console.log("Submitting Game ID:", gameId);
    console.log("Submitting Order Items JSON:", hiddenInput.value);

    return true; // 允许表单提交到 save_order.php
}


// --- 放大镜功能 ---
function openZoom(src) {
    document.getElementById("zoomImg").src = src;
    document.getElementById("zoomModal").style.display = "flex";
}
function closeZoom() {
    document.getElementById("zoomModal").style.display = "none";
}
</script>

</body>
</html>
