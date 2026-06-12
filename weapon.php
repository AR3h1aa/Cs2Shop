<?php
// weapon.php
session_start();
require_once 'includes/db.php';
$weapon = isset($_GET['weapon']) ? trim($_GET['weapon']) : '';
if (empty($weapon)) {
    header('Location: products.php');
    exit;
}
$weapon_escaped = mysqli_real_escape_string($conn, $weapon);
// Sorting / search within this weapon
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$sort = $_GET['sort'] ?? 'newest';
$where = "WHERE p.weapon = '$weapon_escaped'";
if ($search !== '') {
    $where .= " AND p.skin_name LIKE '%$search%'";
}
$order = 'ORDER BY p.created_at DESC';
if ($sort === 'cheapest') $order = 'ORDER BY p.current_price ASC';
elseif ($sort === 'expensive') $order = 'ORDER BY p.current_price DESC';
$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where $order";
$result = mysqli_query($conn, $sql);
require_once 'includes/header.php';
?>

<div class="weapon-page">
    <h2>اسکین‌های سلاح: <?php echo htmlspecialchars($weapon); ?></h2>
    <form method="GET" class="filter-form">
        <input type="hidden" name="weapon" value="<?php echo htmlspecialchars($weapon); ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="search">جستجوی نام اسکین</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="نام اسکین...">
            </div>
            <div class="form-group">
                <label>مرتب‌سازی</label>
                <div class="radio-group">
                    <label><input type="radio" name="sort" value="newest" <?php if($sort=='newest') echo 'checked'; ?>> جدیدترین</label>
                    <label><input type="radio" name="sort" value="cheapest" <?php if($sort=='cheapest') echo 'checked'; ?>> ارزان‌ترین</label>
                    <label><input type="radio" name="sort" value="expensive" <?php if($sort=='expensive') echo 'checked'; ?>> گران‌ترین</label>
                </div>
            </div>
            <button type="submit" class="btn btn-gold">اعمال</button>
        </div>
    </form>
    <div class="product-grid">
        <?php if (mysqli_num_rows($result) === 0): ?>
            <p class="empty-message">اسکینی برای این سلاح یافت نشد.</p>
        <?php else: ?>
            <?php while ($product = mysqli_fetch_assoc($result)):
                $rarity_bg_class = 'rarity-'.strtolower($product['rarity']).'-bg';
                $exterior_map = ['Factory New' => 'exterior-fn','Minimal Wear' => 'exterior-mw','Field-Tested' => 'exterior-ft','Well-Worn' => 'exterior-ww','Battle-Scarred' => 'exterior-bs'];
                $exterior_class = $exterior_map[$product['exterior']] ?? '';
            ?>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                <div class="product-card-image">
                    <span class="rarity-badge <?php echo $rarity_bg_class; ?>"><?php echo $product['rarity']; ?></span>
                    <img src="<?php echo $product['image'] ? '/cs-hub/assets/images/products/'.$product['image'] : '/cs-hub/assets/images/products/default.png'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php if ($product['stock'] == 0): ?>
                        <span class="out-of-stock-overlay">ناموجود</span>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="weapon-name"><?php echo htmlspecialchars($product['weapon']); ?></div>
                    <div class="skin-name"><?php echo htmlspecialchars($product['skin_name']); ?></div>
                    <div class="product-meta">
                        <span class="exterior-badge <?php echo $exterior_class; ?>"><?php echo $product['exterior']; ?></span>
                        <span class="stat-souvenir">
                            <?php if ($product['stattrak']): ?><span class="stat">ST</span><?php endif; ?>
                            <?php if ($product['souvenir']): ?><span class="souvenir">S</span><?php endif; ?>
                        </span>
                    </div>
                    <div class="product-card-price"><?php echo number_format($product['current_price']); ?> تومان</div>
                </div>
            </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>