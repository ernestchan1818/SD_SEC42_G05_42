<?php
include "config.php";

// 读取所有套餐
$packages_result = $conn->query("SELECT * FROM topup_packages ORDER BY created_at DESC");
// Check for query error
if (!$packages_result) {
    die("Database Error reading packages: " . $conn->error);
}
$packages = [];
while ($row = $packages_result->fetch_assoc()) {
    $packages[] = $row;
}

// 读取 package_items
$itemsByPackage = [];
$itemResult = $conn->query("
    SELECT gi.*, pi.package_id 
    FROM game_items gi 
    JOIN package_items pi ON gi.item_id = pi.item_id
");
if (!$itemResult) {
     die("Database Error reading package items: " . $conn->error);
}
while ($row = $itemResult->fetch_assoc()) {
    $itemsByPackage[$row['package_id']][] = $row;
}

// 图片路径处理
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
<title>Available Packages</title>
<style>
body { 
    margin:0; 
    font-family: Arial,sans-serif; 
    background:#000; 
    color:#fff; 
}
/* 顶部导航栏 */
header { 
    background: linear-gradient(90deg, #0b0b0b, #2a2a2a 40%, #0b0b0b); /* 黑灰渐变 */
    padding: 14px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #c75c2b; /* 底部橙色线条 */
    box-shadow: 0 3px 12px rgba(199,92,43,0.2); /* 橙色柔光 */
}

/* logo */
header .logo { 
    font-size: 22px; 
    font-weight: bold; 
    color: #ff6600;  /* 亮橙 logo */
    letter-spacing: 1px;
}

/* 导航链接 */
header nav a { 
    color: #eee; 
    margin: 0 18px; 
    text-decoration: none; 
    font-weight: 500; 
    position: relative;
    transition: color 0.3s ease; 
}

/* 下划线 hover 动画 */
header nav a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -6px;
    width: 100%;
    height: 2px;
    background: #c75c2b;
    transform: scaleX(0);
    transition: transform 0.3s ease;
    transform-origin: right;
}

header nav a:hover {
    color: #c75c2b; 
}

header nav a:hover::after {
    transform: scaleX(1);
    transform-origin: left;
}

.navbar { 
    background: linear-gradient(135deg, #000 25%, #ff6600c0 25%, #ff66008a 50%, #000 50%, #000 75%, #ff6600b2 75%);
    background-size: 40px 40px; /* 控制条纹宽度 */
    padding:14px 20px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
}

.navbar h1 { 
    margin:0; 
    font-size:24px; 
    color:#fff; 
    text-shadow: 2px 2px 6px rgba(0,0,0,0.8), 0 0 6px rgba(245, 165, 45, 0.6); 
    font-weight: bold;
    letter-spacing: 1px;
}

header .logo { 
    font-size:20px; 
    font-weight:bold; 
    color:#fff; 
}


/* 改成纵向列表布局 */
.package-container { 
    max-width:900px;
    margin:30px auto;
    display:flex;
    flex-direction:column;
    gap:18px; 
}

/* 单个卡片样式 */
.package-card { 
    display:flex; 
    background:#111; 
    border-radius:10px; 
    overflow:hidden; 
    cursor:pointer; 
    transition: transform 0.25s ease, box-shadow 0.25s ease; 
    padding:12px; 
    align-items:center;
}
.package-card:hover { 
    transform:scale(1.02); 
    box-shadow:0 6px 18px rgba(255,102,0,0.3); 
}

/* 左边图片 */
.package-card img { 
    width:140px; 
    height:140px; 
    object-fit:cover; 
    border-radius:6px; 
    flex-shrink:0; 
    margin-right:16px;
}

/* 右边信息 */
.package-info {
    display:flex; 
    flex-direction:column; 
    justify-content:center; 
    flex-grow:1;
}
.package-info h3 { 
    margin:0; 
    font-size:20px; 
    font-weight:bold; 
    color:#ff6600; 
}
.package-info p { 
    margin:6px 0; 
    color:#ccc; 
    font-size:14px; 
}
.package-discount { 
    font-size: 14px; 
    font-weight: bold; 
    color: #fff; 
    background: linear-gradient(135deg, #ffe600ff, #ff3300); /* 渐变背景 */
    padding: 4px 12px 4px 28px;  /* 左边多留空间放图标 */
    border-radius: 16px; 
    align-self: flex-start; 
    box-shadow: 0 0 10px rgba(255, 80, 0, 0.7); 
    margin-top: 8px;
    position: relative;
    animation: pulse 1.5s infinite;
}

/* 前面的火焰图标 */
.package-discount::before {
    content: "🔥"; 
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
}

/* 呼吸动画 */
@keyframes pulse {
    0% { box-shadow: 0 0 6px rgba(255,102,0,0.6); }
    50% { box-shadow: 0 0 16px rgba(255,51,0,0.9); }
    100% { box-shadow: 0 0 6px rgba(255,102,0,0.6); }
}



/* modal 不改，保持功能 */
.modal { 
    display:none; 
    position:fixed; 
    top:0; 
    left:0;
    width:100%; 
    height:100%; 
    background: rgba(0,0,0,0.9); 
    overflow:auto; 
    z-index:1000;
    justify-content:center; 
    align-items:flex-start; 
    padding:30px 0; 
}
.modal-content { 
    background:#111; 
    padding:20px; 
    border-radius:10px; 
    width:500px; 
    color:#fff; 
    position:relative; 
    margin:auto; 
}
.close { 
    position:absolute; 
    top:10px; 
    right:15px; 
    font-size:20px; 
    cursor:pointer; 
    color:#ff6600; 
}
.item { 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    margin:10px 0; 
    padding:10px; 
    background:#222; 
    border-radius:6px; 
}
.item img { 
    width:60px; 
    height:60px; 
    object-fit:cover; 
    border-radius:6px; 
}
.price-info { 
    text-align:right; 
    margin-top:10px; 
}
.price-info del { 
    color:#888; 
    margin-right:8px; 
}
.price-info span { 
    font-weight:bold; 
    color:#ff6600; 
}
.pay-btn { 
    background:#ff6600; 
    border:none; 
    color:#fff; 
    padding:12px 20px; /* 增加点击区域 */
    border-radius:8px; /* 圆角更大 */
    cursor:pointer; 
    margin-top:20px; /* 增加边距 */
    width: 100%; /* 全宽按钮 */
    font-size: 16px;
    font-weight: bold;
    transition: background 0.3s ease; 
}
.pay-btn:hover { 
    background:#ff8533; 
}


.zoom-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    z-index: 2000;
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
.zoom-btn:hover {
    background: #f08102d8;
}

/* Custom Alert Styling */
.custom-alert {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
}
.custom-alert-content {
    background: #1a1a1a;
    padding: 25px;
    border-radius: 10px;
    max-width: 350px;
    color: #fff;
    text-align: center;
    box-shadow: 0 0 20px rgba(255,102,0,0.5);
}
.custom-alert-content h4 {
    color: #ff6600;
    margin-top: 0;
    font-size: 1.4em;
}
.custom-alert-content p {
    margin: 15px 0;
    color: #ccc;
}
.custom-alert-content button {
    background: #ff6600;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    margin-top: 15px;
    cursor: pointer;
    font-weight: bold;
}
</style>
</head>
<body>


<header>
    <div class="logo">🎮 DJS Game</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.html">About</a>
        <a href="Contact.php">Contact</a>
        <a href="Feedback.php">Feedback</a>
        <a href="view_games.php">Top-Up Games</a>
        <a href="view_packages.php">Top-Up Packages</a>
    </nav>
</header>

<div class="navbar">
    <h1>🎁 Available Packages</h1>
</div>
<div class="package-container">
<?php
foreach($packages as $pkg): // Loop 1: Display cards
?>
    <div class="package-card" onclick="openModal(<?= $pkg['package_id'] ?>)">
        <img src="<?= htmlspecialchars(getImagePath($pkg['image'])) ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>">
        <div class="package-info">
            <h3><?= htmlspecialchars($pkg['package_name']) ?></h3>
            <p><?= htmlspecialchars($pkg['description']) ?></p>
            <div class="package-discount">Discount: <?= number_format($pkg['discount'],2) ?>%</div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php
foreach($packages as $pkg): // Loop 2: Display modals
$pkgId = $pkg['package_id'];
$items = $itemsByPackage[$pkgId] ?? []; 
$total = 0;
foreach($items as $item) { $total += $item['price']; }
$discount = isset($pkg['discount']) ? $pkg['discount'] : 0;
$final = $total * (1 - $discount/100);

// 修正：将 $pkgId 作为 game_id 的值传递，确保 orders 表记录正确
$gameIdForSubmission = $pkgId; 
?>
<div id="modal-<?= $pkgId ?>" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal(<?= $pkgId ?>)">&times;</span>
        <h2><?= htmlspecialchars($pkg['package_name']) ?> Items</h2>

        <?php if(empty($items)): ?>
            <p>No items in this package.</p>
        <?php else: ?>
            <?php foreach($items as $item): ?>
            <div class="item">
                <div>
                    <img src="<?= htmlspecialchars(getImagePath($item['image'])) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
                    <!-- 🔍 放大镜按钮 -->
                    <button class="zoom-btn" onclick="openZoom('<?= htmlspecialchars(getImagePath($item['image'])) ?>')">🔍</button>
                    <p><?= htmlspecialchars($item['item_name']) ?></p>
                </div>
                <div>RM <?= number_format($item['price'],2) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="price-info">
            <p>Original: <del>RM <?= number_format($total,2) ?></del></p>
            <div class="package-discount">Discount: <?= number_format($pkg['discount'],2) ?>%</div>
            <p>Total after discount: <span>RM <?= number_format($final,2) ?></span></p>
        </div>
        
        <!-- ✅ 购买表单：点击 Pay 按钮触发 JS 准备数据 -->
        <form method="POST" action="save_order.php" onsubmit="return preparePackageOrder(
            <?= $pkgId ?>, 
            '<?= addslashes(htmlspecialchars($pkg['package_name'])) ?>', 
            <?= $final ?>,
            <?= $gameIdForSubmission ?>
        )">
            <input type="hidden" name="game_id" value="<?= $gameIdForSubmission ?>">
            <!-- 隐藏字段用于传递 JSON 格式的订单数据 -->
            <input type="hidden" name="order_items" id="order-items-pkg-<?= $pkgId ?>">
            <button type="submit" class="pay-btn">Purchase Package (RM <?= number_format($final, 2) ?>)</button>
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


// 🔍 放大镜功能
function openZoom(src) {
    document.getElementById("zoomImg").src = src;
    document.getElementById("zoomModal").style.display = "flex";
}
function closeZoom() {
    document.getElementById("zoomModal").style.display = "none";
}

// Custom Alert function (替代 alert())
function showCustomAlert(title, message) {
    let modal = document.createElement('div');
    modal.className = 'custom-alert';
    modal.innerHTML = `
        <div class="custom-alert-content">
            <h4>${title}</h4>
            <p>${message}</p>
            <button onclick="document.body.removeChild(this.parentNode.parentNode)">OK</button>
        </div>
    `;
    document.body.appendChild(modal);
}

/**
 * 准备套餐订单数据，并填充隐藏表单字段
 * @param {number} pkgId - 套餐ID
 * @param {string} pkgName - 套餐名称
 * @param {number} finalPrice - 最终价格
 * @param {number} gameId - 提交订单时关联的 Game ID (应为 pkgId)
 * @returns {boolean} - 是否提交表单
 */
function preparePackageOrder(pkgId, pkgName, finalPrice, gameId) {
    // 1. 验证价格
    if (finalPrice <= 0) {
        showCustomAlert("Package Error", "The final price is RM0.00. Cannot process a zero-value order.");
        return false;
    }

    // 2. 构造 order_items JSON (将整个套餐视为一个 ID 为 -pkgId 的特殊商品)
    let orderData = {};
    let uniquePkgItemId = -pkgId; 

    // save_order.php 期望的结构: {item_id: {quantity: x, price: y, name: z}}
    orderData[uniquePkgItemId] = { 
        quantity: 1, 
        price: finalPrice, 
        name: "Package: " + pkgName 
    };

    // 3. 填充隐藏字段
    let hiddenInput = document.getElementById("order-items-pkg-" + pkgId);
    hiddenInput.value = JSON.stringify(orderData);
    
    // 4. 允许表单提交
    return true;
}
</script>

</body>
</html>
