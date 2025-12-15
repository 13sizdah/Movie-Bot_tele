<?php
/**
 * فایل بررسی خطاها
 * این فایل برای عیب‌یابی مشکلات مینی اپ استفاده می‌شود
 */

// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>بررسی خطاهای مینی اپ</h1>";

// بررسی config.php
echo "<h2>1. بررسی config.php</h2>";
if (file_exists('config.php')) {
    echo "✅ فایل config.php موجود است<br>";
    require_once 'config.php';
    
    // بررسی تعریف ثابت‌ها
    $constants = ['TOKEN', 'HOST', 'USERNAME', 'PASSWORD', 'DBNAME', 'BASEURI'];
    foreach ($constants as $const) {
        if (defined($const)) {
            echo "✅ $const تعریف شده است<br>";
        } else {
            echo "❌ $const تعریف نشده است<br>";
        }
    }
} else {
    echo "❌ فایل config.php یافت نشد<br>";
    die();
}

// بررسی اتصال به دیتابیس
echo "<h2>2. بررسی اتصال به دیتابیس</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . HOST . ";dbname=" . DBNAME . ";charset=utf8",
        USERNAME,
        PASSWORD,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ اتصال به دیتابیس موفق بود<br>";
    
    // بررسی جداول
    echo "<h2>3. بررسی جداول</h2>";
    $tables = ['sp_files', 'sp_cats', 'sp_users', 'sp_webapp_settings'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ جدول $table موجود است (تعداد ردیف: {$result['count']})<br>";
        } catch (PDOException $e) {
            echo "❌ جدول $table یافت نشد یا خطا: " . $e->getMessage() . "<br>";
        }
    }
    
    // بررسی توابع
    echo "<h2>4. بررسی توابع</h2>";
    $functions = ['get_webapp_setting', 'get_webapp_colors', 'is_filter_enabled', 'get_filter_limit'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✅ تابع $func موجود است<br>";
        } else {
            echo "❌ تابع $func موجود نیست<br>";
        }
    }
    
    // تست توابع
    echo "<h2>5. تست توابع</h2>";
    try {
        $test_setting = get_webapp_setting('primary_color', '#000000');
        echo "✅ get_webapp_setting کار می‌کند: $test_setting<br>";
    } catch (Exception $e) {
        echo "❌ خطا در get_webapp_setting: " . $e->getMessage() . "<br>";
    }
    
    try {
        $test_colors = get_webapp_colors();
        echo "✅ get_webapp_colors کار می‌کند (تعداد: " . count($test_colors) . ")<br>";
    } catch (Exception $e) {
        echo "❌ خطا در get_webapp_colors: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ خطا در اتصال به دیتابیس: " . $e->getMessage() . "<br>";
}

// بررسی فایل‌ها
echo "<h2>6. بررسی فایل‌ها</h2>";
$files = ['index.php', 'includes/header.php', 'includes/footer.php', 'config.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ فایل $file موجود است<br>";
    } else {
        echo "❌ فایل $file یافت نشد<br>";
    }
}

echo "<hr>";
echo "<p><strong>✅ بررسی کامل شد. اگر خطایی وجود دارد، آن را رفع کنید.</strong></p>";
?>

