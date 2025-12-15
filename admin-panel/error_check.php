<?php
/**
 * فایل بررسی خطاهای پنل مدیریت
 * این فایل برای عیب‌یابی مشکلات پنل مدیریت استفاده می‌شود
 */

// فعال کردن نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>بررسی خطاهای پنل مدیریت</h1>";

// بررسی db.php
echo "<h2>1. بررسی db.php</h2>";
if (file_exists('db.php')) {
    echo "✅ فایل db.php موجود است<br>";
    include 'db.php';
    
    if (isset($db)) {
        echo "✅ متغیر \$db تعریف شده است<br>";
    } else {
        echo "❌ متغیر \$db تعریف نشده است<br>";
    }
} else {
    echo "❌ فایل db.php یافت نشد<br>";
}

// بررسی config.php
echo "<h2>2. بررسی config.php</h2>";
if (file_exists('../config.php')) {
    echo "✅ فایل config.php موجود است<br>";
    require_once '../config.php';
    
    $constants = ['TOKEN', 'HOST', 'USERNAME', 'PASSWORD', 'DBNAME'];
    foreach ($constants as $const) {
        if (defined($const)) {
            echo "✅ $const تعریف شده است<br>";
        } else {
            echo "❌ $const تعریف نشده است<br>";
        }
    }
} else {
    echo "❌ فایل config.php یافت نشد<br>";
}

// بررسی اتصال به دیتابیس
echo "<h2>3. بررسی اتصال به دیتابیس</h2>";
if (isset($db)) {
    try {
        $stmt = $db->query("SELECT 1");
        echo "✅ اتصال به دیتابیس موفق بود<br>";
        
        // بررسی جدول sp_admins
        echo "<h2>4. بررسی جدول sp_admins</h2>";
        try {
            $stmt = $db->query("SELECT * FROM sp_admins LIMIT 1");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ جدول sp_admins موجود است<br>";
            echo "تعداد ادمین‌ها: " . count($admins) . "<br>";
            
            if (empty($admins)) {
                echo "⚠️ هیچ ادمینی در دیتابیس وجود ندارد. لطفاً فایل CREATE_ADMIN_USER.sql را اجرا کنید.<br>";
            } else {
                echo "✅ ادمین‌ها:<br>";
                foreach ($admins as $admin) {
                    echo "- Username: " . htmlspecialchars($admin['username']) . "<br>";
                }
            }
        } catch (PDOException $e) {
            echo "❌ خطا در بررسی جدول sp_admins: " . $e->getMessage() . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ خطا در اتصال به دیتابیس: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ متغیر \$db تعریف نشده است<br>";
}

// بررسی session
echo "<h2>5. بررسی Session</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session فعال است<br>";
    if (isset($_SESSION['username'])) {
        echo "✅ کاربر وارد شده است: " . htmlspecialchars($_SESSION['username']) . "<br>";
    } else {
        echo "⚠️ کاربر وارد نشده است<br>";
    }
} else {
    echo "❌ Session فعال نیست<br>";
}

// بررسی فایل‌ها
echo "<h2>6. بررسی فایل‌ها</h2>";
$files = ['login.php', 'index.php', 'src/head.php', 'src/func.php', 'src/nav.php', 'src/dashboard.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ فایل $file موجود است<br>";
    } else {
        echo "❌ فایل $file یافت نشد<br>";
    }
}

// بررسی توابع
echo "<h2>7. بررسی توابع</h2>";
if (file_exists('src/func.php')) {
    // تعریف INDEX قبل از include کردن func.php
    if (!defined('INDEX')) {
        define('INDEX', true);
    }
    include 'src/func.php';
    $functions = ['total_income', 'list_admins'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✅ تابع $func موجود است<br>";
        } else {
            echo "❌ تابع $func موجود نیست<br>";
        }
    }
} else {
    echo "❌ فایل src/func.php یافت نشد<br>";
}

echo "<hr>";
echo "<p><strong>✅ بررسی کامل شد. اگر خطایی وجود دارد، آن را رفع کنید.</strong></p>";
echo "<p><a href='login.php'>بازگشت به صفحه ورود</a></p>";
?>

