<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Count items in cart
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GCS2.GG - بزرگ بازار کانتر</title>
    <link rel="stylesheet" href="/cs-hub/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-container">
            <h1 class="site-title"><a href="/cs-hub/index.php">GCS2.GG</a></h1>
            <nav class="main-nav">
                <ul>
                    <button id="theme-toggle" class="theme-toggle-btn" title="تغییر حالت روز/شب">
    ☀️ / 🌙
</button>
                   <li><a href="/cs-hub/products.php">فروشگاه</a></li>
                    <li>
                        <a href="/cs-hub/cart.php" class="cart-link">
                            سبد خرید
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="/cs-hub/admin/index.php">پنل مدیریت</a></li>
                        <?php endif; ?>
                        <li><a href="/cs-hub/logout.php">خروج</a></li>
                    <?php else: ?>
                        <li><a href="/cs-hub/login.php">ورود</a></li>
                        <li><a href="/cs-hub/register.php">ثبت‌نام</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="user-dropdown">
        <h class="user-dropdown-btn">
            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </h>
        <div class="user-dropdown-content">
            <a href="/cs-hub/my_orders.php">وضعیت خریدها</a>
        </div>
        <?php endif; ?>
    </div>

<script>
// Theme toggle logic (persist in localStorage)
(function() {
    const body = document.body;
    const toggleBtn = document.getElementById('theme-toggle');

    // Check saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        body.classList.add('light-theme');
    }

    toggleBtn.addEventListener('click', function() {
        if (body.classList.contains('light-theme')) {
            body.classList.remove('light-theme');
            localStorage.setItem('theme', 'dark');
        } else {
            body.classList.add('light-theme');
            localStorage.setItem('theme', 'light');
        }
    });
})();
</script>
                </ul>
            </nav>
        </div>
    </header>

    <?php
    // Flash message (shown normally except if it's the "added to cart" which we handle via toast)
    if (isset($_SESSION['flash']) && $_SESSION['flash'] !== 'آیتم به سبد خرید اضافه شد.'):
    ?>
        <div class="flash-message"><?php echo htmlspecialchars($_SESSION['flash']); ?></div>
    <?php
        unset($_SESSION['flash']);
    endif;
    ?>
    <?php
    if (isset($_GET['delete_review']) && isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    $review_id_to_delete = (int)$_GET['delete_review'];
    // Delete review (ensure it belongs to the current product is optional)
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $review_id_to_delete AND product_id = $product_id");
    $_SESSION['flash'] = 'نظر با موفقیت حذف شد.';
    header("Location: product.php?id=$product_id");
    exit;
    }
    ?>
    <main class="site-content container">