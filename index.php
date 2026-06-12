<?php
// index.php
$page_title = 'GCS2.GG - صفحه اصلی';
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<div class="landing-cards">
    <h2 class="landing-heading">به GCS2.GG خوش آمدید</h2>
    <div class="card-container">
        <div class="landing-card" id="card-trade">
            <h3>خرید و فروش آیتم‌ها</h3>
            <p>اسکین‌ها، چاقوها و دستکش‌ها</p>
            <a href="/cs-hub/products.php" class="btn">ورود به فروشگاه</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>