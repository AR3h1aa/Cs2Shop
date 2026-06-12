<?php
session_start();
require_once 'includes/db.php';

// Quantity actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $prod_id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$prod_id])) {
        if ($_GET['action'] === 'increase') {
            $_SESSION['cart'][$prod_id]++;
        } elseif ($_GET['action'] === 'decrease') {
            if ($_SESSION['cart'][$prod_id] > 1) {
                $_SESSION['cart'][$prod_id]--;
            } else {
                unset($_SESSION['cart'][$prod_id]);
            }
        } elseif ($_GET['action'] === 'remove') {
            unset($_SESSION['cart'][$prod_id]);
        }
    }
    header('Location: cart.php');
    exit;
}

if (isset($_GET['checkout'])) {
    $_SESSION['flash'] = 'درگاه پرداخت به زودی راه‌اندازی می‌شود.';
    header('Location: cart.php');
    exit;
}

require_once 'includes/header.php';
?>

<div class="cart-page">
    <h2>سبد خرید</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="empty-cart-msg">
            <p>سبد خرید شما خالی است.</p>
            <a href="products.php" class="btn btn-gold">بازگشت به فروشگاه</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>ردیف</th>
                    <th>تصویر</th>
                    <th>نام محصول</th>
                    <th>تعداد</th>
                    <th>قیمت واحد</th>
                    <th>جمع</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                $counter = 1;
                foreach ($_SESSION['cart'] as $pid => $qty):
                    $prod_query = "SELECT name, image, current_price, weapon, skin_name FROM products WHERE id = $pid";
                    $prod_result = mysqli_query($conn, $prod_query);
                    if ($prod_row = mysqli_fetch_assoc($prod_result)):
                        $line_total = $prod_row['current_price'] * $qty;
                        $total += $line_total;
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td>
                        <img src="<?php echo $prod_row['image'] ? '/cs-hub/assets/images/products/'.$prod_row['image'] : '/cs-hub/assets/images/products/default.png'; ?>" class="cart-item-img" alt="<?php echo htmlspecialchars($prod_row['name']); ?>">
                    </td>
                    <td><?php echo htmlspecialchars($prod_row['weapon'] . ' | ' . $prod_row['skin_name']); ?></td>
                    <td>
                        <div class="quantity-control">
                            <a href="cart.php?action=decrease&id=<?php echo $pid; ?>">-</a>
                            <span><?php echo $qty; ?></span>
                            <a href="cart.php?action=increase&id=<?php echo $pid; ?>">+</a>
                        </div>
                    </td>
                    <td><?php echo number_format($prod_row['current_price']); ?> تومان</td>
                    <td><?php echo number_format($line_total); ?> تومان</td>
                    <td>
                        <a href="cart.php?action=remove&id=<?php echo $pid; ?>" class="cart-remove-link">✕</a>
                    </td>
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <div class="total-price">مجموع کل: <?php echo number_format($total); ?> تومان</div>
            <div class="cart-actions">
                <a href="products.php" class="btn btn-outline">ادامه خرید</a>
                <a href="checkout.php" class="btn btn-gold">نهایی کردن خرید</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>