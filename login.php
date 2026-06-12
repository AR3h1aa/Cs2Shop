<?php
// login.php
session_start();
require_once 'includes/db.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'لطفاً ایمیل و رمز عبور را وارد کنید.';
    } else {
        $query = "SELECT id, name, password, role FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                $_SESSION['role'] = $row['role'];
                session_regenerate_id(true);
                $_SESSION['flash'] = 'خوش آمدید، ' . $row['name'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'رمز عبور اشتباه است.';
            }
        } else {
            $error = 'کاربری با این ایمیل یافت نشد.';
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-form-container">
    <h2>ورود به حساب کاربری</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" class="auth-form">
        <div class="form-group">
            <label for="email">ایمیل</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">رمز عبور</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">ورود</button>
    </form>
    <p class="auth-link">حساب ندارید؟ <a href="register.php">ثبت‌نام کنید</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>