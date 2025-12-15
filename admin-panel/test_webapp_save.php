<?php
/**
 * فایل تست برای بررسی ذخیره‌سازی تنظیمات مینی اپ در دیتابیس
 */

// تعریف INDEX
if (!defined('INDEX')) {
    define('INDEX', true);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست ذخیره‌سازی تنظیمات مینی اپ</title>";
echo "<style>
    body { font-family: Tahoma, sans-serif; direction: rtl; text-align: right; background-color: #f4f4f4; padding: 20px; }
    .container { max-width: 1000px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1, h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
    .success { color: green; font-weight: bold; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .error { color: red; font-weight: bold; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .warning { color: orange; font-weight: bold; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
    .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
    pre { background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
    th { background-color: #f2f2f2; }
    button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
    button:hover { background-color: #0056b3; }
</style></head><body><div class='container'>";
echo "<h1>تست ذخیره‌سازی تنظیمات مینی اپ</h1>";

// اتصال به دیتابیس
$db_path = __DIR__ . DIRECTORY_SEPARATOR . 'db.php';
if (!file_exists($db_path)) {
    $db_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db.php';
}

if (file_exists($db_path)) {
    include $db_path;
} else {
    $config_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
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
            die("<div class='error'>❌ خطا در اتصال به دیتابیس: " . htmlspecialchars($e->getMessage()) . "</div>");
        }
    } else {
        die("<div class='error'>❌ فایل db.php و config.php یافت نشدند.</div>");
    }
}

if (!isset($db) || !$db instanceof PDO) {
    die("<div class='error'>❌ متغیر \$db تعریف نشده است.</div>");
}

echo "<div class='success'>✅ اتصال به دیتابیس برقرار شد.</div>";

// 1. بررسی وجود جدول
echo "<h2>1. بررسی وجود جدول sp_webapp_settings</h2>";
try {
    $check_table = $db->query("SHOW TABLES LIKE 'sp_webapp_settings'");
    if ($check_table->rowCount() == 0) {
        echo "<div class='error'>❌ جدول sp_webapp_settings وجود ندارد!</div>";
        echo "<div class='info'>💡 در حال ایجاد جدول...</div>";
        
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `sp_webapp_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text NOT NULL,
            `setting_type` varchar(50) NOT NULL DEFAULT 'text',
            `category` varchar(50) NOT NULL DEFAULT 'general',
            `description` text DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($create_table_sql);
        echo "<div class='success'>✅ جدول sp_webapp_settings ایجاد شد.</div>";
    } else {
        echo "<div class='success'>✅ جدول sp_webapp_settings موجود است.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ خطا در بررسی/ایجاد جدول: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// 2. تست ذخیره‌سازی یک تنظیمات
echo "<h2>2. تست ذخیره‌سازی تنظیمات</h2>";
$test_key = 'test_setting_' . time();
$test_value = '#FF0000';
$test_category = 'colors';

try {
    $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category, description) 
            VALUES (:key, :value, 'color', :category, 'تست ذخیره‌سازی')
            ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':key', $test_key, PDO::PARAM_STR);
    $stmt->bindValue(':value', $test_value, PDO::PARAM_STR);
    $stmt->bindValue(':value2', $test_value, PDO::PARAM_STR);
    $stmt->bindValue(':category', $test_category, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ تنظیمات تست با موفقیت ذخیره شد.</div>";
        echo "<p>کلید: <strong>" . htmlspecialchars($test_key) . "</strong></p>";
        echo "<p>مقدار: <strong>" . htmlspecialchars($test_value) . "</strong></p>";
    } else {
        echo "<div class='error'>❌ خطا در ذخیره‌سازی تنظیمات تست.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ خطا در ذخیره‌سازی: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>SQL: " . htmlspecialchars($sql) . "</pre>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// 3. بررسی خواندن تنظیمات
echo "<h2>3. تست خواندن تنظیمات</h2>";
try {
    $sql = "SELECT * FROM sp_webapp_settings WHERE setting_key = :key LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':key', $test_key, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<div class='success'>✅ تنظیمات تست با موفقیت خوانده شد.</div>";
        echo "<table>";
        echo "<tr><th>فیلد</th><th>مقدار</th></tr>";
        foreach ($result as $field => $value) {
            echo "<tr><td>" . htmlspecialchars($field) . "</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ تنظیمات تست یافت نشد!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ خطا در خواندن تنظیمات: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 4. نمایش تمام تنظیمات موجود
echo "<h2>4. نمایش تمام تنظیمات موجود</h2>";
try {
    $sql = "SELECT * FROM sp_webapp_settings ORDER BY category, id ASC";
    $stmt = $db->query($sql);
    $all_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($all_settings)) {
        echo "<div class='warning'>⚠️ هیچ تنظیماتی در دیتابیس یافت نشد.</div>";
    } else {
        echo "<div class='success'>✅ تعداد تنظیمات: " . count($all_settings) . "</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>کلید</th><th>مقدار</th><th>نوع</th><th>دسته</th><th>آخرین به‌روزرسانی</th></tr>";
        foreach ($all_settings as $setting) {
            $value_display = mb_strlen($setting['setting_value']) > 50 ? mb_substr($setting['setting_value'], 0, 50) . '...' : $setting['setting_value'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($setting['id']) . "</td>";
            echo "<td>" . htmlspecialchars($setting['setting_key']) . "</td>";
            echo "<td>" . htmlspecialchars($value_display) . "</td>";
            echo "<td>" . htmlspecialchars($setting['setting_type']) . "</td>";
            echo "<td>" . htmlspecialchars($setting['category']) . "</td>";
            echo "<td>" . htmlspecialchars($setting['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ خطا در خواندن تنظیمات: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// 5. تست ذخیره‌سازی رنگ
echo "<h2>5. تست ذخیره‌سازی رنگ (مثل فرم واقعی)</h2>";
$test_color_key = 'primary_color';
$test_color_value = '#FF5733';
$test_color_category = 'colors';

try {
    $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category, description) 
            VALUES (:key, :value, 'color', :category, 'رنگ اصلی')
            ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':key', $test_color_key, PDO::PARAM_STR);
    $stmt->bindValue(':value', $test_color_value, PDO::PARAM_STR);
    $stmt->bindValue(':value2', $test_color_value, PDO::PARAM_STR);
    $stmt->bindValue(':category', $test_color_category, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        echo "<div class='success'>✅ رنگ تست با موفقیت ذخیره شد.</div>";
        
        // بررسی خواندن
        $check_sql = "SELECT setting_value FROM sp_webapp_settings WHERE setting_key = :key LIMIT 1";
        $check_stmt = $db->prepare($check_sql);
        $check_stmt->bindValue(':key', $test_color_key, PDO::PARAM_STR);
        $check_stmt->execute();
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check_result && $check_result['setting_value'] == $test_color_value) {
            echo "<div class='success'>✅ رنگ تست با موفقیت خوانده شد و مطابقت دارد.</div>";
        } else {
            echo "<div class='error'>❌ رنگ تست خوانده شد اما مقدار مطابقت ندارد!</div>";
            echo "<p>مقدار ذخیره شده: " . htmlspecialchars($check_result['setting_value'] ?? 'NULL') . "</p>";
            echo "<p>مقدار مورد انتظار: " . htmlspecialchars($test_color_value) . "</p>";
        }
    } else {
        echo "<div class='error'>❌ خطا در ذخیره‌سازی رنگ تست.</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>❌ خطا در ذخیره‌سازی رنگ: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>SQL: " . htmlspecialchars($sql) . "</pre>";
}

// 6. پاک کردن تنظیمات تست
echo "<h2>6. پاک کردن تنظیمات تست</h2>";
try {
    $delete_sql = "DELETE FROM sp_webapp_settings WHERE setting_key = :key";
    $delete_stmt = $db->prepare($delete_sql);
    $delete_stmt->bindValue(':key', $test_key, PDO::PARAM_STR);
    $delete_stmt->execute();
    echo "<div class='success'>✅ تنظیمات تست پاک شد.</div>";
} catch (PDOException $e) {
    echo "<div class='warning'>⚠️ خطا در پاک کردن تنظیمات تست: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr>";
echo "<p><strong>✅ تست کامل شد.</strong></p>";
echo "<p><a href='webapp.php'><button>بازگشت به پنل مدیریت مینی اپ</button></a></p>";
echo "</div></body></html>";
?>

