<?php
// admin/stock.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$message = '';

// Process stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock'])) {
    $post_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf_token, $post_token)) {
        $message = 'درخواست نامعتبر.';
    } else {
        $updated = 0;
        foreach ($_POST['stock'] as $product_id => $new_stock) {
            $pid = (int)$product_id;
            $qty = (int)$new_stock;
            if ($qty < 0) $qty = 0;

            mysqli_query($conn, "UPDATE products SET stock = $qty WHERE id = $pid");
            $updated++;
        }
        $message = "تعداد $updated محصول به‌روزرسانی شد.";
    }
}

// Fetch all products
$products_query = "SELECT id, name, image, weapon, skin_name, stock FROM products ORDER BY weapon, skin_name";
$products_result = mysqli_query($conn, $products_query);

require_once '../includes/header.php';
?>

<div class="admin-stock-page">
    <h2>مدیریت موجودی</h2>

    <?php if ($message): ?>
        <div class="flash-message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" class="stock-form">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>تصویر</th>
                    <th>نام محصول</th>
                    <th>موجودی فعلی</th>
                    <th>موجودی جدید</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td>
                        <img src="<?php echo $product['image'] ? '../assets/images/products/'.$product['image'] : '../assets/images/products/default.png'; ?>" alt="" class="admin-thumb">
                    </td>
                    <td><?php echo htmlspecialchars($product['weapon'] . ' | ' . $product['skin_name']); ?></td>
                    <td><strong><?php echo $product['stock']; ?></strong></td>
                    <td>
                        <input type="number" name="stock[<?php echo $product['id']; ?>]" value="<?php echo $product['stock']; ?>" min="0" class="stock-input">
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-gold">ذخیره تغییرات</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>