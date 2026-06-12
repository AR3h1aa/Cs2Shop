<?php
// products.php
session_start();
require_once 'includes/db.php';

// Fetch categories for filter
$cat_query = "SELECT id, name FROM categories ORDER BY name";
$cat_result = mysqli_query($conn, $cat_query);

// Build query
$where = [];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = $_GET['sort'] ?? 'newest';

if ($search !== '') {
    $where[] = "p.name LIKE '%$search%'";
}
if ($category_id > 0) {
    $where[] = "p.category_id = $category_id";
}

$where_clause = '';
if (!empty($where)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where);
}

$order = 'ORDER BY p.created_at DESC';
if ($sort === 'cheapest') {
    $order = 'ORDER BY p.current_price ASC';
} elseif ($sort === 'expensive') {
    $order = 'ORDER BY p.current_price DESC';
}

// *** UPDATED: included p.stock in SELECT ***
$sql = "SELECT p.id, p.name, p.image, p.current_price, p.stock, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where_clause $order";
$products_result = mysqli_query($conn, $sql);

require_once 'includes/header.php';
?>

<div class="products-page">
    <h2>فروشگاه آیتم‌های کانتر</h2>
    <form method="GET" class="filter-form">
        <div class="form-row">
            <div class="form-group">
                <label for="search">جستجوی نام</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="نام آیتم...">
            </div>
            <div class="form-group">
                <label for="category">دسته‌بندی</label>
                <select id="category" name="category">
                    <option value="0">همه</option>
                    <?php while ($cat = mysqli_fetch_assoc($cat_result)): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php if ($category_id == $cat['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>مرتب‌سازی</label>
                <div class="radio-group">
                    <label><input type="radio" name="sort" value="newest" <?php if ($sort === 'newest') echo 'checked'; ?>> جدیدترین</label>
                    <label><input type="radio" name="sort" value="cheapest" <?php if ($sort === 'cheapest') echo 'checked'; ?>> ارزان‌ترین</label>
                    <label><input type="radio" name="sort" value="expensive" <?php if ($sort === 'expensive') echo 'checked'; ?>> گران‌ترین</label>
                </div>
            </div>
            <!-- Weapons list -->
            <?php
            $weapons_query = "SELECT DISTINCT weapon FROM products WHERE weapon != '' ORDER BY weapon";
            $weapons_result = mysqli_query($conn, $weapons_query);
            if (mysqli_num_rows($weapons_result) > 0):
            ?>
            <div class="weapon-list-bar">
                <span class="label">سلاح‌ها:</span>
                <div class="weapon-links">
                    <?php while ($w = mysqli_fetch_assoc($weapons_result)): ?>
                    <a href="weapon.php?weapon=<?php echo urlencode($w['weapon']); ?>" class="weapon-link-item"><?php echo htmlspecialchars($w['weapon']); ?></a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
            <button type="submit" class="btn">اعمال فیلتر</button>
        </div>
    </form>

    <div class="product-grid">
        <?php if (mysqli_num_rows($products_result) === 0): ?>
            <p class="empty-message">هیچ آیتمی یافت نشد.</p>
        <?php else: ?>
            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                <div class="product-card-image">
                    <?php $img = $product['image'] ? '/cs-hub/assets/images/products/' . $product['image'] : '/cs-hub/assets/images/products/default.png'; ?>
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php if ($product['stock'] == 0): ?>
                        <span class="out-of-stock-overlay">ناموجود</span>
                    <?php endif; ?>
                </div>
                <h3 class="product-card-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="product-card-category"><?php echo htmlspecialchars($product['category_name'] ?? 'بدون دسته'); ?></p>
                <div class="product-card-price"><?php echo number_format($product['current_price']); ?> تومان</div>
            </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>