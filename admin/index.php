<?php
// admin/index.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../includes/header.php';
?>

<div class="admin-dashboard">
    <h2>پنل مدیریت</h2>
    <div class="admin-stats">
        <?php
        $prod_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM products"))[0];
        $user_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM users"))[0];
        ?>
        <div class="stat-card">
            <h3>محصولات</h3>
            <p><?php echo $prod_count; ?></p>
        </div>
        <div class="stat-card">
            <h3>کاربران</h3>
            <p><?php echo $user_count; ?></p>
        </div>
        <div class="stat-card">
    <h3>سفارشات جدید</h3>
    <?php $pending_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'pending'"))[0]; ?>
    <p><?php echo $pending_count; ?></p>
</div>
    </div>
    <div class="admin-links">
    <a href="orders.php" class="btn">مدیریت سفارش‌ها</a>
    <a href="products.php" class="btn">مدیریت محصولات</a>
        <a href="add_product.php" class="btn">افزودن محصول جدید</a>
        <a href="reviews.php" class="btn">مدیریت نظرات</a>
        <a href="stock.php" class="btn">مدیریت موجودی</a>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>