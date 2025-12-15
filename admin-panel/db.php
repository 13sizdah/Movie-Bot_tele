<?php
// ============================================================
// فایل تنظیمات اتصال به دیتابیس
// ============================================================
// این فایل ابتدا از config.php استفاده می‌کند
// اگر config.php پیدا نشد، از مقادیر دستی استفاده می‌کند
// ============================================================

// تلاش برای استفاده از config.php
$host = 'localhost';
$database = 'isheresh_8';
$user = 'isheresh_8';
$pass = 'gJ&!uE9)j;kzFVJ?';

// استفاده از مقادیر config.php (اولویت اول)
if (file_exists('../config.php')) {
    require_once '../config.php';
    if (defined('HOST') && defined('DBNAME') && defined('USERNAME') && defined('PASSWORD')) {
        $host = HOST;
        $database = DBNAME;
        $user = USERNAME;
        $pass = PASSWORD;
    }
}
// اگر config.php در مسیر دیگری است، این مسیرها را هم بررسی کنید
elseif (file_exists('../../config.php')) {
    require_once '../../config.php';
    if (defined('HOST') && defined('DBNAME') && defined('USERNAME') && defined('PASSWORD')) {
        $host = HOST;
        $database = DBNAME;
        $user = USERNAME;
        $pass = PASSWORD;
    }
}
// اگر config.php پیدا نشد، از مقادیر دستی بالا استفاده می‌شود

try {
    // اتصال با charset utf8 در DSN
    $db = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // تنظیم charset به utf8 برای سازگاری با داده‌های موجود
    // برای پشتیبانی از اموجی‌ها، باید charset جدول را به utf8mb4 تبدیل کنید
    $db->exec("SET NAMES utf8 COLLATE utf8_general_ci");
    $db->exec("SET CHARACTER SET utf8");
    $db->exec("SET character_set_connection=utf8");
} catch (PDOException $error) {
    // نمایش خطای دقیق برای عیب‌یابی (بعد از رفع مشکل، این بخش را غیرفعال کنید)
    $error_message = $error->getMessage();
    $error_code = $error->getCode();
    
    // برای نمایش خطای کامل (فقط برای عیب‌یابی)
    echo "<div style='direction:rtl; font-family:tahoma; padding:20px; background:#f8d7da; border:1px solid #f5c6cb; border-radius:5px; margin:20px;'>";
    echo "<h3 style='color:#721c24;'>❌ خطا در اتصال به دیتابیس</h3>";
    echo "<p><strong>خطا:</strong> " . htmlspecialchars($error_message) . "</p>";
    echo "<p><strong>کد خطا:</strong> " . $error_code . "</p>";
    echo "<hr>";
    echo "<p><strong>اطلاعات اتصال:</strong></p>";
    echo "<ul>";
    echo "<li>Host: " . htmlspecialchars($host) . "</li>";
    echo "<li>Database: " . htmlspecialchars($database) . "</li>";
    echo "<li>User: " . htmlspecialchars($user) . "</li>";
    echo "<li>Password: " . (empty($pass) ? '❌ خالی' : '✅ تنظیم شده') . "</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p><strong>راه‌حل‌های پیشنهادی:</strong></p>";
    echo "<ol>";
    echo "<li>بررسی کنید که اطلاعات دیتابیس درست وارد شده باشد</li>";
    echo "<li>مطمئن شوید دیتابیس ایجاد شده است</li>";
    echo "<li>بررسی کنید کاربر دیتابیس به دیتابیس دسترسی دارد</li>";
    echo "<li>Host را بررسی کنید (ممکن است localhost نباشد)</li>";
    echo "<li>اطلاعات را با فایل config.php مقایسه کنید</li>";
    echo "</ol>";
    echo "<p style='color:#856404; background:#fff3cd; padding:10px; border-radius:3px; margin-top:10px;'>";
    echo "⚠️ <strong>توجه:</strong> بعد از رفع مشکل، این پیام خطا را غیرفعال کنید تا امنیت حفظ شود.";
    echo "</p>";
    echo "</div>";
    die();
}
