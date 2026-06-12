<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Fetch current rate
$rate_query = "SELECT setting_value FROM settings WHERE setting_key = 'usd_to_toman'";
$rate_result = mysqli_query($conn, $rate_query);
$current_rate = '50000';
if ($row = mysqli_fetch_assoc($rate_result)) {
    $current_rate = $row['setting_value'];
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate'])) {
    $new_rate = (float)$_POST['rate'];
    if ($new_rate <= 0) {
        $message = 'نرخ تبدیل باید عددی مثبت باشد.';
    } else {
        $new_rate_str = mysqli_real_escape_string($conn, (string)$new_rate);

        // Update the conversion rate in settings
        mysqli_query($conn, "UPDATE settings SET setting_value = '$new_rate_str' WHERE setting_key = 'usd_to_toman'");

        // ---------- NEW: Recalculate all product prices ----------
        $updated_count = 0;
        // Fetch all products that have a USD price
        $products_rs = mysqli_query($conn, "SELECT id, usd_price, current_price FROM products WHERE usd_price IS NOT NULL");
        while ($p = mysqli_fetch_assoc($products_rs)) {
            $product_id    = (int)$p['id'];
            $old_price     = (int)$p['current_price'];
            $new_toman     = (int)round((float)$p['usd_price'] * $new_rate);

            // Only update if the price actually changes
            if ($new_toman !== $old_price) {
                // Insert the old price into price_history
                mysqli_query($conn, "INSERT INTO price_history (product_id, price) VALUES ($product_id, $old_price)");
                // Update the product with the new Toman price
                mysqli_query($conn, "UPDATE products SET current_price = $new_toman WHERE id = $product_id");
                $updated_count++;
            }
        }
        // ---------------------------------------------------------

        $current_rate = $new_rate_str;
        $message = 'نرخ تبدیل با موفقیت به‌روزرسانی شد.';
        if ($updated_count > 0) {
            $message .= " تعداد $updated_count محصول با قیمت دلاری به‌روز شدند.";
        } else {
            $message .= ' هیچ محصولی نیاز به به‌روزرسانی نداشت.';
        }
    }
}

require_once '../includes/header.php';
?>

<div class="admin-form-container">
    <h2>تنظیمات نرخ تبدیل</h2>
    <?php if ($message): ?>
        <div class="flash-message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="POST" class="admin-form">
        <div class="form-group">
            <label for="rate">نرخ تبدیل دلار به تومان (هر ۱ دلار چند تومان؟)</label>
            <input type="number" id="rate" name="rate" step="0.01" value="<?php echo htmlspecialchars($current_rate); ?>" required>
        </div>
        <button type="submit" class="btn btn-gold">ذخیره</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?>