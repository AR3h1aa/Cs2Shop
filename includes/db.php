<?php
// includes/db.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cs_hub';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die('خطا در اتصال به پایگاه داده: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');