<?php
// admin/reviews.php
session_start();
require_once '../includes/db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Delete action (POST + CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $review_id = (int)$_POST['delete_review_id'];
    $post_token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrf_token, $post_token)) {
        $_SESSION['flash'] = 'درخواست نامعتبر.';
    } else {
        mysqli_query($conn, "DELETE FROM reviews WHERE id = $review_id");
        $_SESSION['flash'] = 'نظر حذف شد.';
    }
    header('Location: reviews.php');
    exit;
}

// Fetch all reviews with product and user info
$reviews_query = "
    SELECT r.id, r.rating, r.comment, r.created_at,
           p.id AS product_id, p.weapon, p.skin_name,
           u.name AS user_name, u.email
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
";
$reviews_result = mysqli_query($conn, $reviews_query);

require_once '../includes/header.php';
?>

<div class="admin-reviews">
    <h2>مدیریت نظرات</h2>

    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-message"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($reviews_result) === 0): ?>
        <p class="empty-message">هیچ نظری ثبت نشده است.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>محصول</th>
                    <th>کاربر</th>
                    <th>امتیاز</th>
                    <th>نظر</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                <tr>
                    <td><?php echo $review['id']; ?></td>
                    <td>
                        <a href="../product.php?id=<?php echo $review['product_id']; ?>">
                            <?php echo htmlspecialchars($review['weapon'] . ' | ' . $review['skin_name']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($review['user_name']); ?><br><small><?php echo htmlspecialchars($review['email']); ?></small></td>
                    <td><?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']); ?></td>
                    <td><?php echo htmlspecialchars($review['comment']); ?></td>
                    <td><?php echo $review['created_at']; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_review_id" value="<?php echo $review['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('آیا از حذف این نظر اطمینان دارید؟');">حذف</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>