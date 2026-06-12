<?php
// register.php
session_start();
require_once 'includes/db.php';

$error = '';
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'لطفاً تمام فیلدها را پر کنید.';
    } elseif ($password !== $confirm) {
        $error = 'رمز عبور و تکرار آن یکسان نیستند.';
    } elseif (strlen($password) < 6) {
        $error = 'رمز عبور باید حداقل ۶ کاراکتر باشد.';
    } else {
        // Check email uniqueness
        $check_query = "SELECT id FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'این ایمیل قبلاً ثبت شده است.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = "INSERT INTO users (name, email, password) VALUES ('$name', '$email', '$hashed')";
            if (mysqli_query($conn, $insert)) {
                $user_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['role'] = 'user';
                session_regenerate_id(true);
                $_SESSION['flash'] = 'ثبت‌نام با موفقیت انجام شد.';
                header('Location: index.php');
                exit;
            } else {
                $error = 'خطا در ثبت‌نام: ' . mysqli_error($conn);
            }
        }
    }
}
require_once 'includes/header.php';
?>

<div class="auth-form-container">
    <h2>ثبت‌نام در GCS2.GG</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" class="auth-form">
        <div class="form-group">
            <label for="name">نام</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="form-group">
            <label for="email">ایمیل</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="form-group">
            <label for="password">رمز عبور</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div class="form-group">
            <label for="confirm_password">تکرار رمز عبور</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary">ثبت‌نام</button>
    </form>
    <p class="auth-link">قبلاً ثبت‌نام کرده‌اید؟ <a href="login.php">وارد شوید</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>