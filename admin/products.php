<?php
// admin/products.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Delete product
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    // Fetch image to delete file
    $img_query = "SELECT image FROM products WHERE id = $delete_id";
    $img_result = mysqli_query($conn, $img_query);
    if ($row = mysqli_fetch_assoc($img_result)) {
        if ($row['image'] && file_exists('../assets/images/products/' . $row['image'])) {
            unlink('../assets/images/products/' . $row['image']);
        }
    }
    mysqli_query($conn, "DELETE FROM products WHERE id = $delete_id");
    $_SESSION['flash'] = 'محصول با موفقیت حذف شد.';
    header('Location: products.php');
    exit;
}

$products_query = "SELECT p.id, p.name, p.image, p.current_price, c.name AS category_name
                   FROM products p
                   LEFT JOIN categories c ON p.category_id = c.id
                   ORDER BY p.id DESC";
$products_result = mysqli_query($conn, $products_query);

require_once '../includes/header.php';
?>

<div class="admin-products">
    <h2>مدیریت محصولات</h2>
    <a href="add_product.php" class="btn btn-primary">افزودن محصول جدید</a>
            <a href="settings.php" class="btn btn-outline">تنظیمات نرخ تبدیل</a>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>تصویر</th>
                <th>نام</th>
                <th>دسته</th>
                <th>قیمت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
            <tr>
                <td><?php echo $product['id']; ?></td>
                <td>
                    <?php
                    $img = $product['image'] ? '../assets/images/products/' . $product['image'] : '../assets/images/products/default.png';
                    ?>
                    <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="admin-thumb">
                </td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name'] ?? 'نامشخص'); ?></td>
                <td><?php echo number_format($product['current_price']); ?> تومان</td>
                <td>
                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm">ویرایش</a>
                    <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('آیا از حذف این محصول مطمئن هستید؟');">حذف</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>