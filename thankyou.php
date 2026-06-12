<?php
// thankyou.php
session_start();
require_once 'includes/db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id === 0) {
    header('Location: index.php');
    exit;
}

$order_query = "SELECT o.*, u.name AS user_name, u.email AS user_email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = $order_id";
$order_result = mysqli_query($conn, $order_query);
if (mysqli_num_rows($order_result) === 0) {
    header('Location: index.php');
    exit;
}
$order = mysqli_fetch_assoc($order_result);

// Fetch items with tokens
$items_query = "SELECT pl.price_paid, pl.token_code, p.weapon, p.skin_name, p.image
                FROM purchase_log pl
                JOIN products p ON pl.product_id = p.id
                WHERE pl.order_id = $order_id
                ORDER BY pl.id";
$items_result = mysqli_query($conn, $items_query);

require_once 'includes/header.php';
?>

<div class="thankyou-page">
    <div class="thankyou-card">
        <h2>مشتری گرامی، سفارش شما با موفقیت ثبت شد</h2>
        <div class="order-details">
            <p><strong>شماره سفارش:</strong> <?php echo $order['id']; ?></p>
            <p><strong>نام کاربری:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>

            <h3>خلاصه سفارش</h3>
            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>تصویر</th>
                        <th>نام کالا</th>
                        <th>قیمت (تومان)</th>
                        <th>توکن</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                    <tr>
                        <td>
                            <img src="<?php echo $item['image'] ? '/cs-hub/assets/images/products/'.$item['image'] : '/cs-hub/assets/images/products/default.png'; ?>" alt="" class="order-item-thumb">
                        </td>
                        <td><?php echo htmlspecialchars($item['weapon'] . ' | ' . $item['skin_name']); ?></td>
                        <td><?php echo number_format($item['price_paid']); ?></td>
                        <td><code><?php echo htmlspecialchars($item['token_code']); ?></code></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <p class="highlight-note">لطفاً توکن‌های بالا را نزد خود نگه دارید. این توکن‌ها برای پیگیری و دریافت کالا ضروری هستند.</p>
        </div>
        <a href="my_orders.php" class="btn">پیگیری سفارش‌ها</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>