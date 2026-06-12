<?php
// my_orders.php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = 'برای مشاهده وضعیت سفارش‌ها باید وارد شوید.';
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$orders_query = "SELECT o.*,
                        COUNT(pl.id) AS item_count,
                        SUM(pl.price_paid) AS total_amount
                 FROM orders o
                 LEFT JOIN purchase_log pl ON pl.order_id = o.id
                 WHERE o.user_id = $user_id
                 GROUP BY o.id
                 ORDER BY o.created_at DESC";
$orders_result = mysqli_query($conn, $orders_query);

$status_labels = [
    'pending'      => 'در انتظار بررسی',
    'under_review' => 'در دست بررسی',
    'in_progress'  => 'در حال ارسال',
    'shipped'      => 'ارسال شده',
    'rejected'     => 'رد شده',
    'completed'    => 'تکمیل شده'
];

require_once 'includes/header.php';
?>

<div class="my-orders-page">
    <h2>وضعیت خریدها</h2>

    <?php if (mysqli_num_rows($orders_result) === 0): ?>
        <p class="empty-message">شما هنوز سفارشی ثبت نکرده‌اید.</p>
    <?php else: ?>
        <div class="orders-list">
            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
            <div class="order-card">
                <div class="order-header">
                    <div class="order-id">شماره سفارش: <?php echo $order['id']; ?></div>
                    <div class="order-date">تاریخ ثبت: <?php echo $order['created_at']; ?></div>
                    <div class="order-status <?php echo $order['status']; ?>">
                        وضعیت: <?php echo $status_labels[$order['status']] ?? $order['status']; ?>
                    </div>
                    <div class="order-summary">
                        <span>تعداد اقلام: <?php echo $order['item_count']; ?></span>
                        <span>مبلغ کل: <?php echo number_format($order['total_amount']); ?> تومان</span>
                    </div>
                    <button class="btn toggle-details" data-order="<?php echo $order['id']; ?>">مشاهده جزئیات</button>
                </div>
                <div class="order-details-content" id="details-<?php echo $order['id']; ?>" style="display:none;">
                    <?php
                    $detail_query = "SELECT pl.price_paid, pl.token_code, p.weapon, p.skin_name, p.image
                                     FROM purchase_log pl
                                     JOIN products p ON pl.product_id = p.id
                                     WHERE pl.order_id = {$order['id']}
                                     ORDER BY pl.id";
                    $detail_result = mysqli_query($conn, $detail_query);
                    ?>
                    <table class="detail-table">
                        <thead>
                            <tr><th>تصویر</th><th>نام</th><th>قیمت</th><th>توکن</th></tr>
                        </thead>
                        <tbody>
                            <?php while ($item = mysqli_fetch_assoc($detail_result)): ?>
                            <tr>
                                <td><img src="<?php echo $item['image'] ? '/cs-hub/assets/images/products/'.$item['image'] : '/cs-hub/assets/images/products/default.png'; ?>" alt="" class="item-thumb"></td>
                                <td><?php echo htmlspecialchars($item['weapon'] . ' | ' . $item['skin_name']); ?></td>
                                <td><?php echo number_format($item['price_paid']); ?> تومان</td>
                                <td><code><?php echo htmlspecialchars($item['token_code']); ?></code></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('toggle-details')) {
        var id = e.target.getAttribute('data-order');
        var detailDiv = document.getElementById('details-' + id);
        if (detailDiv.style.display === 'none' || detailDiv.style.display === '') {
            detailDiv.style.display = 'block';
            e.target.textContent = 'بستن جزئیات';
        } else {
            detailDiv.style.display = 'none';
            e.target.textContent = 'مشاهده جزئیات';
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>