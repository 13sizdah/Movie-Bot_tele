<?php
define('INDEX',true);
ob_start();
session_start();
header('Content-type: text/html; charset=UTF-8');

if (isset($_SESSION['username'])) { ?>
<!DOCTYPE html>
<html lang="fa">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title><?= $title ?></title>
    <link rel="icon" type="image/x-icon" href="src/img/favicon.png">
    <meta name="theme-color" content="#229ED9" />


</head>

<?php 
// استفاده از مسیر مطلق برای اطمینان از پیدا کردن فایل‌ها
// head.php در admin-panel/src/ است
// db.php در admin-panel/ است

// تعیین مسیر پایه (admin-panel/)
$base_dir = dirname(__DIR__);

// مسیرهای فایل‌ها
$db_path = $base_dir . DIRECTORY_SEPARATOR . 'db.php';
$jdf_path = __DIR__ . DIRECTORY_SEPARATOR . 'jdf.php';
$func_path = __DIR__ . DIRECTORY_SEPARATOR . 'func.php';
$config_path = $base_dir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';

// بررسی و include کردن db.php
if (file_exists($db_path)) {
    include $db_path;
} elseif (file_exists('../db.php')) {
    // مسیر نسبی از admin-panel/src/
    include '../db.php';
} else {
    // اگر db.php پیدا نشد، از config.php استفاده کن و اتصال را خودمان بسازیم
    if (file_exists($config_path)) {
        require_once $config_path;
        // ایجاد اتصال دیتابیس دستی
        try {
            $db = new PDO(
                "mysql:host=" . HOST . ";dbname=" . DBNAME . ";charset=utf8mb4",
                USERNAME,
                PASSWORD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            die('❌ خطا در اتصال به دیتابیس: ' . htmlspecialchars($e->getMessage()));
        }
    } else {
        die('❌ فایل db.php و config.php یافت نشدند. مسیر بررسی شده: ' . htmlspecialchars($db_path));
    }
}

// بررسی و include کردن jdf.php
if (file_exists($jdf_path)) {
    include $jdf_path;
} elseif (file_exists($base_dir . '/../jdf.php')) {
    include $base_dir . '/../jdf.php';
}

// include کردن func.php
if (file_exists($func_path)) {
    include_once $func_path; // استفاده از include_once برای جلوگیری از include تکراری
} else {
    // اگر func_path پیدا نشد، از مسیر نسبی امتحان کن
    $func_path_alt = __DIR__ . '/func.php';
    if (file_exists($func_path_alt)) {
        include_once $func_path_alt;
    } else {
        die('❌ فایل func.php یافت نشد. مسیرهای بررسی شده: ' . htmlspecialchars($func_path) . ' و ' . htmlspecialchars($func_path_alt));
    }
}

// اطمینان از اینکه $db تعریف شده است
if (!isset($db)) {
    die('❌ متغیر $db تعریف نشده است. لطفاً فایل db.php را بررسی کنید.');
}

// اطمینان از اینکه توابع مهم تعریف شده‌اند
if (!function_exists('total_income')) {
    error_log("Warning: total_income() function not found after including func.php");
}
?>
<?php
} else {
    header('Location: login.php');
}
?>

<?php
if(isset($_GET['logout'])){
session_unset();
session_destroy();
header('Location:login.php');
}
?>