<?php
// product.php
session_start();
require_once 'includes/db.php';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) {
    header('Location: products.php');
    exit;
}
$product_query = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = $product_id";
$product_result = mysqli_query($conn, $product_query);
if (mysqli_num_rows($product_result) === 0) {
    header('Location: products.php');
    exit;
}
$product = mysqli_fetch_assoc($product_result);
$history_query = "SELECT price, changed_at FROM price_history WHERE product_id = $product_id ORDER BY changed_at DESC LIMIT 5";
$history_result = mysqli_query($conn, $history_query);

// Add to cart via redirect (used by AJAX or direct link)
if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart') {
    // Stock guard
    $stock_check = mysqli_query($conn, "SELECT stock FROM products WHERE id = $product_id");
    $stock_row = mysqli_fetch_assoc($stock_check);
    if ($stock_row && $stock_row['stock'] > 0) {
        $_SESSION['cart'][$product_id] = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] + 1 : 1;
        $_SESSION['flash'] = 'آیتم به سبد خرید اضافه شد.';
    } else {
        $_SESSION['flash'] = 'این کالا موجود نیست.';
    }
    header('Location: product.php?id=' . $product_id);
    exit;
}

// Review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash'] = 'برای ثبت نظر باید وارد شوید.';
        header('Location: login.php');
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = mysqli_real_escape_string($conn, trim($_POST['comment']));
    if ($rating < 1 || $rating > 5 || empty($comment)) {
        $_SESSION['flash'] = 'لطفاً امتیاز و نظر را وارد کنید.';
    } else {
        $insert = "INSERT INTO reviews (product_id, user_id, rating, comment) VALUES ($product_id, $user_id, $rating, '$comment')";
        if (mysqli_query($conn, $insert)) {
            $_SESSION['flash'] = 'نظر شما با موفقیت ثبت شد.';
        } else {
            $_SESSION['flash'] = 'خطا در ثبت نظر.';
        }
        header('Location: product.php?id=' . $product_id);
        exit;
    }
}

$reviews_query = "SELECT r.rating, r.comment, r.created_at, u.name AS user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = $product_id ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_query);

// Map rarity/exterior for display
$rarity_bg_class = 'rarity-' . strtolower($product['rarity']) . '-bg';
$exterior_map = [
    'Factory New' => 'exterior-fn',
    'Minimal Wear' => 'exterior-mw',
    'Field-Tested' => 'exterior-ft',
    'Well-Worn' => 'exterior-ww',
    'Battle-Scarred' => 'exterior-bs'
];
$exterior_display_class = $exterior_map[$product['exterior']] ?? '';

require_once 'includes/header.php';
$show_toast = false;
if (isset($_SESSION['flash']) && $_SESSION['flash'] === 'آیتم به سبد خرید اضافه شد.') {
    $show_toast = true;
    unset($_SESSION['flash']);
}
?>

<div class="product-detail">
    <div class="product-detail-top">
        <div class="product-detail-image">
            <img src="<?php echo $product['image'] ? '/cs-hub/assets/images/products/' . $product['image'] : '/cs-hub/assets/images/products/default.png'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-detail-info">
            <div class="detail-heading">
                <h2><?php echo htmlspecialchars($product['weapon'] . ' | ' . $product['skin_name']); ?></h2>
                <span class="rarity-badge-big <?php echo $rarity_bg_class; ?>"><?php echo $product['rarity']; ?></span>
                <span class="exterior-badge-big <?php echo $exterior_display_class; ?>"><?php echo $product['exterior']; ?></span>
            </div>
            <div class="stat-souvenir-icons">
                <?php if ($product['stattrak']): ?><span class="stat">StatTrak</span><?php endif; ?>
                <?php if ($product['souvenir']): ?><span class="souvenir">Souvenir</span><?php endif; ?>
            </div>
            <p class="product-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            <div class="product-purchase">
                <div class="product-price-big"><?php echo number_format($product['current_price']); ?> تومان</div>

                <!-- Stock aware button -->
                <?php if ($product['stock'] > 0): ?>
                    <a href="product.php?id=<?php echo $product_id; ?>&action=add_to_cart" class="btn btn-gold add-to-cart-btn">افزودن به سبد خرید</a>
                    <span class="stock-info">موجودی: <?php echo $product['stock']; ?> عدد</span>
                <?php else: ?>
                    <span class="out-of-stock-badge">ناموجود</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- rest of page (price history, reviews, suggestions) unchanged -->
    <div class="price-history">
        <h3>تاریخچه قیمت</h3>
        <?php if (mysqli_num_rows($history_result) > 0): ?>
            <table class="price-history-table">
                <thead><tr><th>تاریخ</th><th>قیمت</th></tr></thead>
                <tbody>
                    <?php while ($hist = mysqli_fetch_assoc($history_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($hist['changed_at']); ?></td>
                        <td><?php echo number_format($hist['price']); ?> تومان</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>تاریخچه قیمتی ثبت نشده است.</p>
        <?php endif; ?>
    </div>
    <div class="reviews-section">
        <h3>نظرات کاربران</h3>
        <?php if (mysqli_num_rows($reviews_result) > 0): ?>
            <div class="review-list">
                <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                <div class="review-item">
                    <div class="review-header">
                        <span class="review-author"><?php echo htmlspecialchars($review['user_name']); ?></span>
                        <span class="review-rating"><?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                    <span class="review-date"><?php echo $review['created_at']; ?></span>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="empty-message">هنوز نظری ثبت نشده.</p>
        <?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="review-form-container">
                <h4>ثبت نظر جدید</h4>
                <form method="POST" class="review-form">
                    <div class="form-group">
                        <label for="rating">امتیاز</label>
                        <select id="rating" name="rating" required>
                            <option value="">انتخاب</option>
                            <option value="1">۱ - خیلی بد</option>
                            <option value="2">۲ - بد</option>
                            <option value="3">۳ - متوسط</option>
                            <option value="4">۴ - خوب</option>
                            <option value="5">۵ - عالی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="comment">نظر شما</label>
                        <textarea id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-gold">ارسال نظر</button>
                </form>
            </div>
        <?php else: ?>
            <p class="auth-message">برای ثبت نظر باید <a href="login.php">وارد شوید</a>.</p>
        <?php endif; ?>
    </div>
    <!-- Suggestions -->
    <div class="suggestions-section">
        <h3>پیشنهادهای مشابه</h3>
        <?php
        $weapon_esc = mysqli_real_escape_string($conn, $product['weapon']);
        $price = $product['current_price'];
        $min_price_sug = (int)($price * 0.8);
        $max_price_sug = (int)($price * 1.2);
        $sug_query = "SELECT id, image, name, weapon, skin_name, rarity, exterior, stattrak, souvenir, current_price, stock
                      FROM products
                      WHERE id != $product_id AND (weapon = '$weapon_esc' OR (current_price BETWEEN $min_price_sug AND $max_price_sug))
                      ORDER BY (weapon = '$weapon_esc') DESC, current_price ASC LIMIT 4";
        $sug_result = mysqli_query($conn, $sug_query);
        if (mysqli_num_rows($sug_result) > 0):
        ?>
        <div class="suggestions-row">
            <?php while ($sug = mysqli_fetch_assoc($sug_result)):
                $rarity_bg = 'rarity-'.strtolower($sug['rarity']).'-bg';
                $ext_map = ['Factory New' => 'exterior-fn','Minimal Wear' => 'exterior-mw','Field-Tested' => 'exterior-ft','Well-Worn' => 'exterior-ww','Battle-Scarred' => 'exterior-bs'];
                $ext_class = $ext_map[$sug['exterior']] ?? '';
            ?>
            <a href="product.php?id=<?php echo $sug['id']; ?>" class="suggestion-card">
                <div class="suggestion-image">
                    <span class="rarity-badge <?php echo $rarity_bg; ?>"><?php echo $sug['rarity']; ?></span>
                    <img src="<?php echo $sug['image'] ? '/cs-hub/assets/images/products/'.$sug['image'] : '/cs-hub/assets/images/products/default.png'; ?>" alt="">
                    <?php if ($sug['stock'] == 0): ?>
                        <span class="out-of-stock-overlay">ناموجود</span>
                    <?php endif; ?>
                </div>
                <div class="suggestion-info">
                    <div class="weapon-name"><?php echo htmlspecialchars($sug['weapon'].' | '.$sug['skin_name']); ?></div>
                    <div class="suggestion-price"><?php echo number_format($sug['current_price']); ?> تومان</div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <p>مورد مشابهی یافت نشد.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="toast <?php if ($show_toast) echo 'show'; ?>">محصول به سبد خرید اضافه شد</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.querySelector('.add-to-cart-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            fetch(url)
                .then(() => {
                    showToast('محصول به سبد خرید اضافه شد');
                });
        });
    }
    const toast = document.getElementById('toast');
    if (toast && toast.classList.contains('show')) {
        setTimeout(() => { toast.classList.remove('show'); }, 2000);
    }
});
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => { toast.classList.remove('show'); }, 2000);
    setTimeout(() => {
            location.reload()
    }, 2500);
}
</script>

<?php require_once 'includes/footer.php'; ?>