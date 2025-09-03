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
body { margin:0; font-family: Arial,sans-serif; background:#000; color:#fff; }
.navbar { background:#ff6600; padding:14px 20px; display:flex; align-items:center; }
.navbar h1 { margin:0; font-size:20px; color:#fff; }

.package-container { display:grid; grid-template-columns: repeat(auto-fit, minmax(350px,1fr)); gap:20px; padding:20px; }
.package-card { 
    display:flex; 
    background:#111; 
    border-radius:10px; 
    overflow:hidden; 
    cursor:pointer; 
    transition:0.3s; 
}
.package-card:hover { background:#222; }

.package-card img { 
    width:150px; 
    height:150px; 
    object-fit:cover; 
    flex-shrink:0; 
}

.package-info {
    display:flex; 
    flex-direction:column; 
    justify-content:space-between; 
    padding:10px; 
    flex-grow:1;
}
.package-info h3 { margin:0; font-size:18px; color:#ff6600; }
.package-info p { margin:5px 0; color:#ccc; font-size:14px; }
.package-discount { font-size:14px; font-weight:bold; color:#ff6600; text-align:right; }

.modal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background: rgba(0,0,0,0.9); overflow:auto; justify-content:center; align-items:flex-start; padding:30px 0; }
.modal-content { background:#111; padding:20px; border-radius:10px; width:500px; color:#fff; position:relative; margin:auto; }
.close { position:absolute; top:10px; right:15px; font-size:20px; cursor:pointer; color:#ff6600; }
.item { display:flex; justify-content:space-between; align-items:center; margin:10px 0; padding:10px; background:#222; border-radius:6px; }
.item img { width:60px; height:60px; object-fit:cover; border-radius:6px; }
.price-info { text-align:right; margin-top:10px; }
.price-info del { color:#888; margin-right:8px; }
.price-info span { font-weight:bold; color:#ff6600; }
.pay-btn { background:#ff6600; border:none; color:#fff; padding:8px 14px; border-radius:6px; cursor:pointer; margin-top:10px; }
</style>
</head>
<body>

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
                    <p><?= htmlspecialchars($item['item_name']) ?></p>
                </div>
                <div>RM <?= number_format($item['price'],2) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="price-info">
            <p>Original: <del>RM <?= number_format($total,2) ?></del></p>
            <p>Discount: <?= number_format($discount,2) ?>%</p>
            <p>Total after discount: <span>RM <?= number_format($final,2) ?></span></p>
        </div>
        <button class="pay-btn">Pay</button>
    </div>
</div>
<?php endwhile; ?>

<script>
function openModal(id) { document.getElementById("modal-" + id).style.display = "flex"; }
function closeModal(id) { document.getElementById("modal-" + id).style.display = "none"; }
</script>

</body>
</html>
