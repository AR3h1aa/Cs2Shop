<?php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../index.php'); exit; }

$error = '';
$name = $description = $price = $weapon = $skin_name = $usd_price_val = '';
$category_id = 0;
$rarity = 'Mil-Spec';
$exterior = 'Field-Tested';
$stattrak = 0;
$souvenir = 0;

$cat_query = "SELECT id, name FROM categories ORDER BY name";
$cat_result = mysqli_query($conn, $cat_query);

// Fetch conversion rate
$rate_query = "SELECT setting_value FROM settings WHERE setting_key = 'usd_to_toman'";
$rate_result = mysqli_query($conn, $rate_query);
$rate_row = mysqli_fetch_assoc($rate_result);
$conversion_rate = $rate_row ? (float)$rate_row['setting_value'] : 50000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $price = isset($_POST['price']) ? (int)$_POST['price'] : 0;
    $weapon = mysqli_real_escape_string($conn, trim($_POST['weapon']));
    $skin_name = mysqli_real_escape_string($conn, trim($_POST['skin_name']));
    $rarity = mysqli_real_escape_string($conn, $_POST['rarity']);
    $exterior = mysqli_real_escape_string($conn, $_POST['exterior']);
    $stattrak = isset($_POST['stattrak']) ? 1 : 0;
    $souvenir = isset($_POST['souvenir']) ? 1 : 0;
    $usd_price = isset($_POST['usd_price']) ? trim($_POST['usd_price']) : '';
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
if ($stock < 0) $stock = 0;
    if (empty($name) || empty($weapon)) {
        $error = 'نام و سلاح الزامی است.';
    } else {
        // Determine final Toman price
        $usd_price_cleaned = ($usd_price !== '' && is_numeric($usd_price)) ? (float)$usd_price : null;
        $final_price = $price;

        if ($usd_price_cleaned !== null && $final_price <= 0) {
            // Only USD provided, convert
            $final_price = (int)round($usd_price_cleaned * $conversion_rate);
        } elseif ($usd_price_cleaned !== null && $final_price > 0) {
            // Both provided, use Toman price (but store USD anyway)
        } elseif ($usd_price_cleaned === null && $final_price <= 0) {
            $error = 'قیمت (تومان یا دلار) را وارد کنید.';
        }

        if ($final_price <= 0 && !$error) {
            $error = 'قیمت نهایی معتبر نیست.';
        }

        if (!$error) {
            // Image upload
            $image_name = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg','image/png','image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
                finfo_close($finfo);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                if (!in_array($mime, $allowed) || !in_array(strtolower($ext), ['jpg','jpeg','png','webp'])) {
                    $error = 'فقط JPG/PNG/WEBP مجاز.';
                } elseif ($_FILES['image']['size'] > 2*1024*1024) {
                    $error = 'حجم تصویر < 2MB.';
                } else {
                    $image_name = uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $image_name);
                }
            }

            if (!$error) {
                $img_val = $image_name ? "'$image_name'" : "NULL";
                $usd_val = $usd_price_cleaned !== null ? "'" . number_format($usd_price_cleaned, 2, '.', '') . "'" : "NULL";

                $insert = "INSERT INTO products (category_id, name, description, image, current_price, usd_price, weapon, skin_name, rarity, exterior, stattrak, souvenir, stock)
           VALUES ($category_id, '$name', '$description', $img_val, $final_price, $usd_val, '$weapon', '$skin_name', '$rarity', '$exterior', $stattrak, $souvenir, $stock)";
                if (mysqli_query($conn, $insert)) {
                    $new_id = mysqli_insert_id($conn);
                    mysqli_query($conn, "INSERT INTO price_history (product_id, price) VALUES ($new_id, $final_price)");
                    $_SESSION['flash'] = 'محصول جدید اضافه شد.';
                    header('Location: products.php');
                    exit;
                } else $error = 'خطا: '.mysqli_error($conn);
            }
        }
    }
}
require_once '../includes/header.php';
?>

<div class="admin-form-container">
    <h2>افزودن اسکین جدید</h2>
    <?php if($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="admin-form">
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">دسته‌بندی</label>
                <select id="category_id" name="category_id" required>
                    <?php while($cat = mysqli_fetch_assoc($cat_result)): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="weapon">سلاح (مثال: AK-47)</label>
                <input type="text" id="weapon" name="weapon" value="<?php echo htmlspecialchars($weapon); ?>" required>
            </div>
            <div class="form-group">
                <label for="skin_name">نام اسکین (مثال: Asiimov)</label>
                <input type="text" id="skin_name" name="skin_name" value="<?php echo htmlspecialchars($skin_name); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="name">نام کامل (اختیاری)</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>">
            </div>
            <div class="form-group">
                <label for="usd_price">قیمت به دلار (USD)</label>
                <input type="number" step="0.01" id="usd_price" name="usd_price" value="<?php echo htmlspecialchars($usd_price_val); ?>" placeholder="مبلغ دلار">
            </div>
            <div class="form-group">
    <label for="stock">موجودی (عدد)</label>
    <input type="number" id="stock" name="stock" value="0" min="0" required>
    <small style="color:#888">تعداد موجودی اولیه</small>
</div>
            <div class="form-group">
                <label for="price">قیمت به تومان (در صورت وارد کردن دلار می‌توانید خالی بگذارید)</label>
                <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>">
                <small style="color:#888">تبدیل خودکار با نرخ جاری (۱ دلار = <?php echo number_format($conversion_rate); ?> تومان)</small>
            </div>
        </div>
        <!-- Rarity, exterior, stattrak, souvenir fields same as before -->
        <div class="form-row">
            <div class="form-group">
                <label for="rarity">کمیابی</label>
                <select id="rarity" name="rarity">
                    <option value="Consumer" <?php if($rarity=='Consumer') echo 'selected'; ?>>Consumer</option>
                    <option value="Industrial" <?php if($rarity=='Industrial') echo 'selected'; ?>>Industrial</option>
                    <option value="Mil-Spec" <?php if($rarity=='Mil-Spec') echo 'selected'; ?>>Mil-Spec</option>
                    <option value="Restricted" <?php if($rarity=='Restricted') echo 'selected'; ?>>Restricted</option>
                    <option value="Classified" <?php if($rarity=='Classified') echo 'selected'; ?>>Classified</option>
                    <option value="Covert" <?php if($rarity=='Covert') echo 'selected'; ?>>Covert</option>
                    <option value="Contraband" <?php if($rarity=='Contraband') echo 'selected'; ?>>Contraband</option>
                </select>
            </div>
            <div class="form-group">
                <label for="exterior">وضعیت ظاهری</label>
                <select id="exterior" name="exterior">
                    <option value="Factory New" <?php if($exterior=='Factory New') echo 'selected'; ?>>Factory New</option>
                    <option value="Minimal Wear" <?php if($exterior=='Minimal Wear') echo 'selected'; ?>>Minimal Wear</option>
                    <option value="Field-Tested" <?php if($exterior=='Field-Tested') echo 'selected'; ?>>Field-Tested</option>
                    <option value="Well-Worn" <?php if($exterior=='Well-Worn') echo 'selected'; ?>>Well-Worn</option>
                    <option value="Battle-Scarred" <?php if($exterior=='Battle-Scarred') echo 'selected'; ?>>Battle-Scarred</option>
                </select>
            </div>
        </div>
        <div class="checkbox-group">
            <label><input type="checkbox" name="stattrak" <?php if($stattrak) echo 'checked'; ?>> StatTrak</label>
            <label><input type="checkbox" name="souvenir" <?php if($souvenir) echo 'checked'; ?>> Souvenir</label>
        </div>
        <div class="form-group">
            <label for="description">توضیحات</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="form-group">
            <label for="image">تصویر (JPG/PNG/WEBP)</label>
            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <button type="submit" class="btn btn-gold">ذخیره اسکین</button>
    </form>
</div>
<?php require_once '../includes/footer.php'; ?>