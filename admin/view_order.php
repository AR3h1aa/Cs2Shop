<?php
// admin/view_order.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header('Location: orders.php');
    exit;
}

// Fetch order
$order_query = "SELECT o.*, u.name AS user_name, u.email
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = $id";
$order_result = mysqli_query($conn, $order_query);
if (mysqli_num_rows($order_result) === 0) {
    header('Location: orders.php');
    exit;
}
$order = mysqli_fetch_assoc($order_result);

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $post_token = $_POST['csrf_token'] ?? '';
    $allowed = ['pending','under_review','in_progress','shipped','rejected','completed'];
    if (!hash_equals($csrf_token, $post_token)) {
        $error = 'درخواست نامعتبر.';
    } elseif (!in_array($new_status, $allowed)) {
        $error = 'وضعیت نامعتبر.';
    } else {
        $status_esc = mysqli_real_escape_string($conn, $new_status);
        mysqli_query($conn, "UPDATE orders SET status = '$status_esc' WHERE id = $id");
        $_SESSION['flash'] = 'وضعیت سفارش به‌روزرسانی شد.';
        header("Location: view_order.php?id=$id");
        exit;
    }
}

$status_labels = [
    'pending'      => 'در انتظار بررسی',
    'under_review' => 'در دست بررسی',
    'in_progress'  => 'در حال ارسال',
    'shipped'      => 'ارسال شده',
    'rejected'     => 'رد شده',
    'completed'    => 'تکمیل شده'
];

// Fetch items
$items_query = "SELECT pl.price_paid, pl.token_code, p.weapon, p.skin_name, p.image
                FROM purchase_log pl
                JOIN products p ON pl.product_id = p.id
                WHERE pl.order_id = $id";
$items_result = mysqli_query($conn, $items_query);

require_once '../includes/header.php';
?>

<div class="admin-view-order">
    <h2>جزئیات سفارش شماره <?php echo $order['id']; ?></h2>

    <div class="order-info-card">
        <h3>اطلاعات مشتری</h3>
        <p><strong>نام کاربری:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
        <p><strong>ایمیل:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
        <p><strong>نام کاربری استیم:</strong> <?php echo htmlspecialchars($order['steam_username']); ?></p>
        <p><strong>رمز استیم / کد ریکاوری:</strong> <?php echo htmlspecialchars($order['steam_password_or_recovery']); ?></p>
        <p><strong>تلفن:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
        <p><strong>آدرس:</strong> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
        <p><strong>لینک استیم:</strong> <a href="<?php echo htmlspecialchars($order['steam_link']); ?>" target="_blank"><?php echo htmlspecialchars($order['steam_link']); ?></a></p>
    </div>

    <div class="order-status-card">
        <h3>وضعیت فعلی: <?php echo $status_labels[$order['status']]; ?></h3>
        <form method="POST" class="status-form-inline">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <select name="status">
                <?php foreach ($status_labels as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php if ($order['status'] == $key) echo 'selected'; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="update_status" class="btn btn-glass">به‌روزرسانی وضعیت</button>
        </form>
    </div>

    <div class="order-items-card">
        <h3>اقلام سفارش</h3>
        <table class="admin-table">
            <thead>
                <tr><th>تصویر</th><th>نام</th><th>قیمت</th><th>توکن</th></tr>
            </thead>
            <tbody>
                <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                <tr>
                    <td><img src="<?php echo $item['image'] ? '../assets/images/products/'.$item['image'] : '../assets/images/products/default.png'; ?>" alt="" class="admin-thumb"></td>
                    <td><?php echo htmlspecialchars($item['weapon'] . ' | ' . $item['skin_name']); ?></td>
                    <td><?php echo number_format($item['price_paid']); ?> تومان</td>
                    <td><code><?php echo htmlspecialchars($item['token_code']); ?></code></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <a href="orders.php" class="btn">بازگشت به لیست سفارش‌ها</a>
</div>

<?php require_once '../includes/footer.php'; ?>