<?php
// admin/orders.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];

// Status change handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $post_token = $_POST['csrf_token'] ?? '';

    $allowed = ['pending','under_review','in_progress','shipped','rejected','completed'];
    if (!hash_equals($csrf_token, $post_token)) {
        $_SESSION['flash'] = 'درخواست نامعتبر.';
    } elseif (!in_array($new_status, $allowed)) {
        $_SESSION['flash'] = 'وضعیت نامعتبر.';
    } else {
        $status_esc = mysqli_real_escape_string($conn, $new_status);
        mysqli_query($conn, "UPDATE orders SET status = '$status_esc' WHERE id = $order_id");
        $_SESSION['flash'] = 'وضعیت سفارش به‌روزرسانی شد.';
    }
    header('Location: orders.php');
    exit;
}

// Fetch orders
$orders_query = "SELECT o.*, u.name AS user_name, u.email,
                        COUNT(pl.id) AS item_count,
                        COALESCE(SUM(pl.price_paid), 0) AS total_amount
                 FROM orders o
                 JOIN users u ON o.user_id = u.id
                 LEFT JOIN purchase_log pl ON pl.order_id = o.id
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

require_once '../includes/header.php';
?>

<div class="admin-orders">
    <h2>مدیریت سفارش‌ها</h2>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-message"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <table class="admin-table">
        <thead>
            <tr>
                <th>شماره سفارش</th>
                <th>کاربر</th>
                <th>استیم</th>
                <th>تاریخ</th>
                <th>مبلغ کل</th>
                <th>وضعیت</th>
                <th>عملیات</th>
                <th> </th>
            </tr>
        </thead>
        <tbody>
            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
            <tr>
                <td><?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                <td><?php echo htmlspecialchars($order['steam_username']); ?></td>
                <td><?php echo $order['created_at']; ?></td>
                <td><?php echo number_format($order['total_amount']); ?> تومان</td>
                <td><?php echo $status_labels[$order['status']] ?? $order['status']; ?></td>
                <td>
                    <!-- Status update form (inline) -->
                    <form method="POST" class="status-form">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <select name="status" class="status-select">
                            <?php foreach ($status_labels as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php if ($order['status'] == $key) echo 'selected'; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-sm">تغییر</button>
                    </form>
                </td>
                <td>
                    <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">مشاهده</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>