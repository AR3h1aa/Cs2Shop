<?php
// checkout.php
session_start();
require_once 'includes/db.php';

$errors = [];
$form_data = [
    'steam_username' => '',
    'steam_password' => '',
    'phone' => '',
    'address' => '',
    'steam_link' => ''
];

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash'] = 'لطفاً ابتدا وارد حساب کاربری خود شوید.';
    header('Location: login.php');
    exit;
}

// If cart is empty, redirect
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_data['steam_username'] = trim($_POST['steam_username'] ?? '');
    $form_data['steam_password'] = trim($_POST['steam_password'] ?? '');
    $form_data['phone'] = trim($_POST['phone'] ?? '');
    $form_data['address'] = trim($_POST['address'] ?? '');
    $form_data['steam_link'] = trim($_POST['steam_link'] ?? '');

    // Validation
    if ($form_data['steam_username'] === '') $errors[] = 'نام کاربری استیم الزامی است.';
    if ($form_data['steam_password'] === '') $errors[] = 'رمز استیم / کد ریکاوری الزامی است.';
    if ($form_data['phone'] === '') $errors[] = 'شماره تلفن الزامی است.';
    elseif (!preg_match('/^09\d{9}$/', $form_data['phone']))
        $errors[] = 'شماره تلفن نامعتبر است (مثال: 09123456789).';
    if ($form_data['address'] === '') $errors[] = 'آدرس الزامی است.';
    if ($form_data['steam_link'] === '') $errors[] = 'لینک پروفایل استیم الزامی است.';
    elseif (!filter_var($form_data['steam_link'], FILTER_VALIDATE_URL))
        $errors[] = 'لینک استیم باید یک URL معتبر باشد.';

    if (empty($errors)) {
        // --- Stock validation --------------------------------
        $stock_error = false;
        foreach ($_SESSION['cart'] as $product_id => $qty) {
            $pid = (int)$product_id;
            $qty = (int)$qty;

            $stock_check = mysqli_query($conn, "SELECT stock, weapon, skin_name FROM products WHERE id = $pid");
            $stock_row = mysqli_fetch_assoc($stock_check);
            if ($stock_row) {
                if ((int)$stock_row['stock'] < $qty) {
                    $errors[] = "موجودی کالای «{$stock_row['weapon']} | {$stock_row['skin_name']}» کافی نیست. (موجود: {$stock_row['stock']}, درخواست: $qty)";
                    $stock_error = true;
                }
            } else {
                $errors[] = "کالای شماره $pid یافت نشد.";
                $stock_error = true;
            }
        }
        // -----------------------------------------------------
    }

    if (empty($errors)) {
        $user_id = (int)$_SESSION['user_id'];
        $steam_username = mysqli_real_escape_string($conn, $form_data['steam_username']);
        $steam_password = mysqli_real_escape_string($conn, $form_data['steam_password']);
        $phone = mysqli_real_escape_string($conn, $form_data['phone']);
        $address = mysqli_real_escape_string($conn, $form_data['address']);
        $steam_link = mysqli_real_escape_string($conn, $form_data['steam_link']);

        // Insert order
        $insert_order = "INSERT INTO orders (user_id, steam_username, steam_password_or_recovery, phone, address, steam_link, status)
                         VALUES ($user_id, '$steam_username', '$steam_password', '$phone', '$address', '$steam_link', 'pending')";
        if (mysqli_query($conn, $insert_order)) {
            $order_id = mysqli_insert_id($conn);

            // Process cart
            foreach ($_SESSION['cart'] as $product_id => $qty) {
                $pid = (int)$product_id;
                $qty = (int)$qty;

                // Fetch current price
                $price_result = mysqli_query($conn, "SELECT current_price FROM products WHERE id = $pid");
                $price_row = mysqli_fetch_assoc($price_result);
                $price = $price_row ? (int)$price_row['current_price'] : 0;

                for ($i = 0; $i < $qty; $i++) {
                    // Generate unique 12-character token
                    do {
                        $token = bin2hex(random_bytes(6)); // 12 hex chars
                        $token_check = mysqli_query($conn, "SELECT id FROM purchase_log WHERE token_code = '$token'");
                    } while (mysqli_num_rows($token_check) > 0);

                    $token_esc = mysqli_real_escape_string($conn, $token);
                    $insert_log = "INSERT INTO purchase_log (user_id, product_id, price_paid, token_code, order_id)
                                   VALUES ($user_id, $pid, $price, '$token_esc', $order_id)";
                    mysqli_query($conn, $insert_log);
                }

                // Deduct stock
                mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id = $pid");
            }

            // Clear cart
            unset($_SESSION['cart']);

            $_SESSION['flash'] = 'سفارش شما با موفقیت ثبت شد.';
            header("Location: thankyou.php?order_id=$order_id");
            exit;
        } else {
            $errors[] = 'خطا در ثبت سفارش. لطفاً دوباره تلاش کنید.';
        }
    }
}

// Build cart summary for display
require_once 'includes/header.php';
?>

<div class="checkout-page">
    <h2>تکمیل خرید</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Cart Review Table -->
    <div class="cart-review-section">
        <h3>مرور سبد خرید</h3>
        <table class="cart-review-table">
            <thead>
                <tr>
                    <th>کد کالا</th>
                    <th>نام کالا</th>
                    <th>تعداد</th>
                    <th>قیمت واحد</th>
                    <th>جمع</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $pid => $qty):
                $pid = (int)$pid;
                $prod_query = "SELECT weapon, skin_name, current_price FROM products WHERE id = $pid";
                $prod_result = mysqli_query($conn, $prod_query);
                if ($prod_row = mysqli_fetch_assoc($prod_result)):
                    $line = $prod_row['current_price'] * $qty;
                    $total += $line;
            ?>
                <tr>
                    <td><?php echo $pid; ?></td>
                    <td><?php echo htmlspecialchars($prod_row['weapon'] . ' | ' . $prod_row['skin_name']); ?></td>
                    <td><?php echo $qty; ?></td>
                    <td><?php echo number_format($prod_row['current_price']); ?> تومان</td>
                    <td><?php echo number_format($line); ?> تومان</td>
                </tr>
            <?php
                endif;
            endforeach;
            ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align:left;">مجموع کل</td>
                    <td><?php echo number_format($total); ?> تومان</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Checkout Form -->
    <div class="checkout-form-container">
        <h3>اطلاعات استیم و تحویل</h3>
        <form method="POST" class="checkout-form">
            <div class="form-group">
                <label for="steam_username">نام کاربری استیم خریدار</label>
                <input type="text" id="steam_username" name="steam_username" value="<?php echo htmlspecialchars($form_data['steam_username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="steam_password">رمز استیم / کد ریکاوری</label>
                <input type="password" id="steam_password" name="steam_password" value="<?php echo htmlspecialchars($form_data['steam_password']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">شماره تلفن (ایران)</label>
                <input type="text" id="phone" name="phone" placeholder="09xxxxxxxxx" value="<?php echo htmlspecialchars($form_data['phone']); ?>" required>
            </div>
            <div class="form-group">
                <label for="address">آدرس</label>
                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($form_data['address']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="steam_link">لینک پروفایل استیم</label>
                <input type="url" id="steam_link" name="steam_link" placeholder="https://steamcommunity.com/id/..." value="<?php echo htmlspecialchars($form_data['steam_link']); ?>" required>
            </div>
            <button type="submit" class="btn btn-glass">ثبت سفارش</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>