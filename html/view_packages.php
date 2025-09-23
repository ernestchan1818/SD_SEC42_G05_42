<?php
include "config.php";

// 读取所有套餐
$packages = $conn->query("SELECT * FROM topup_packages ORDER BY created_at DESC");

// 读取 package_items
$itemsByPackage = [];
$itemResult = $conn->query("SELECT gi.*, pi.package_id 
                            FROM game_items gi 
                            JOIN package_items pi ON gi.item_id = pi.item_id");
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
    z-index:1000; 
    left:0; top:0; 
    width:100%; height:100%; 
    background: rgba(0,0,0,0.9); 
    overflow:auto; 
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
    padding:8px 14px; 
    border-radius:6px; 
    cursor:pointer; 
    margin-top:10px; 
    transition: background 0.3s ease; 
}
.pay-btn:hover { 
    background:#ff8533; 
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
<div class="navbar">
    <h1>🎁 Available Packages</h1>
</div>
<div class="package-container">
<?php
$packages->data_seek(0);
while($pkg = $packages->fetch_assoc()): ?>
    <div class="package-card" onclick="openModal(<?= $pkg['package_id'] ?>)">
        <img src="<?= htmlspecialchars(getImagePath($pkg['image'])) ?>" alt="<?= htmlspecialchars($pkg['package_name']) ?>">
        <div class="package-info">
            <h3><?= htmlspecialchars($pkg['package_name']) ?></h3>
            <p><?= htmlspecialchars($pkg['description']) ?></p>
            <div class="package-discount">Discount: <?= number_format($pkg['discount'],2) ?>%</div>
        </div>
    </div>
<?php endwhile; ?>
</div>

<?php
$packages->data_seek(0);
while($pkg = $packages->fetch_assoc()):
$pkgId = $pkg['package_id'];
$items = $itemsByPackage[$pkgId] ?? []; 
$total = 0;
foreach($items as $item) { $total += $item['price']; }
$discount = isset($pkg['discount']) ? $pkg['discount'] : 0;
$final = $total * (1 - $discount/100);
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
        <button class="pay-btn">Pay</button>
    </div>
</div>
<?php endwhile; ?>

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
</script>

</body>
</html>
