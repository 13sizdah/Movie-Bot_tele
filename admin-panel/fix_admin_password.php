<?php
/**
 * فایل تبدیل hash پسورد ادمین از MD5 به bcrypt
 * این فایل hash موجود در دیتابیس را به bcrypt تبدیل می‌کند
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تبدیل Hash پسورد ادمین</title>";
echo "<style>
    body { font-family: Tahoma, sans-serif; direction: rtl; text-align: right; background-color: #f4f4f4; padding: 20px; }
    .container { max-width: 800px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1, h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    pre { background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto; }
    button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
    button:hover { background-color: #0056b3; }
    .danger { background-color: #dc3545; }
    .danger:hover { background-color: #c82333; }
</style></head><body><div class='container'>";
echo "<h1>تبدیل Hash پسورد ادمین</h1>";

// بررسی وجود db.php
$db_path = __DIR__ . DIRECTORY_SEPARATOR . 'db.php';
if (!file_exists($db_path)) {
    $db_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db.php';
}

if (file_exists($db_path)) {
    include $db_path;
} else {
    // Fallback to config.php
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
            die("❌ خطا در اتصال به دیتابیس: " . htmlspecialchars($e->getMessage()));
        }
    } else {
        die("❌ فایل db.php و config.php یافت نشدند.");
    }
}

if (!isset($db) || !$db instanceof PDO) {
    die("❌ متغیر \$db تعریف نشده است.");
}

// بررسی وجود جدول sp_admins
try {
    $stmt = $db->query("SHOW TABLES LIKE 'sp_admins'");
    if ($stmt->rowCount() == 0) {
        die("❌ جدول sp_admins در دیتابیس یافت نشد.");
    }
} catch (PDOException $e) {
    die("❌ خطا در بررسی جدول: " . htmlspecialchars($e->getMessage()));
}

// دریافت اطلاعات ادمین فعلی
echo "<h2>1. بررسی ادمین فعلی</h2>";
try {
    $stmt = $db->query("SELECT id, username, password FROM sp_admins WHERE username = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin) {
        die("❌ ادمین با username 'admin' یافت نشد.");
    }
    
    echo "<p>✅ ادمین یافت شد:</p>";
    echo "<ul>";
    echo "<li>ID: " . htmlspecialchars($admin['id']) . "</li>";
    echo "<li>Username: " . htmlspecialchars($admin['username']) . "</li>";
    echo "<li>Hash فعلی: <pre>" . htmlspecialchars($admin['password']) . "</pre></li>";
    
    // بررسی نوع hash
    $current_hash = $admin['password'];
    $is_md5 = (strlen($current_hash) == 32 && ctype_xdigit($current_hash));
    $is_bcrypt = (strpos($current_hash, '$2y$') === 0 || strpos($current_hash, '$2a$') === 0 || strpos($current_hash, '$2b$') === 0);
    
    if ($is_bcrypt) {
        echo "<li class='success'>✅ Hash فعلی از نوع bcrypt است (نیازی به تبدیل نیست)</li>";
        echo "</ul>";
        echo "<p class='success'>✅ پسورد شما از قبل به bcrypt تبدیل شده است. می‌توانید با username و password 'admin123' وارد شوید.</p>";
        echo "<p><a href='login.php'><button>بازگشت به صفحه ورود</button></a></p>";
        echo "</div></body></html>";
        exit;
    } elseif ($is_md5) {
        echo "<li class='warning'>⚠️ Hash فعلی از نوع MD5 است (نیاز به تبدیل به bcrypt)</li>";
        echo "<li class='error'>❌ مشکل: password_verify فقط با bcrypt hash کار می‌کند!</li>";
    } else {
        echo "<li class='warning'>⚠️ نوع hash نامشخص است</li>";
        echo "<li class='error'>❌ مشکل: این hash با password_verify سازگار نیست!</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    die("❌ خطا در دریافت اطلاعات ادمین: " . htmlspecialchars($e->getMessage()));
}

// اگر فرم ارسال شده باشد
if (isset($_POST['convert_password'])) {
    $new_password = trim($_POST['new_password'] ?? 'admin123');
    
    if (empty($new_password)) {
        echo "<p class='error'>❌ پسورد نمی‌تواند خالی باشد.</p>";
    } else {
        echo "<h2>2. تبدیل Hash</h2>";
        
        // تولید hash جدید با bcrypt
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        echo "<p>پسورد جدید: <strong>" . htmlspecialchars($new_password) . "</strong></p>";
        echo "<p>Hash جدید (bcrypt): <pre>" . htmlspecialchars($new_hash) . "</pre></p>";
        
        // به‌روزرسانی در دیتابیس
        try {
            $sql = "UPDATE sp_admins SET password = :password WHERE username = 'admin'";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':password', $new_hash, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                echo "<p class='success'>✅ Hash با موفقیت به bcrypt تبدیل شد!</p>";
                echo "<p class='success'>✅ اکنون می‌توانید با username 'admin' و password '" . htmlspecialchars($new_password) . "' وارد شوید.</p>";
                
                // تست password_verify
                echo "<h2>3. تست password_verify</h2>";
                if (password_verify($new_password, $new_hash)) {
                    echo "<p class='success'>✅ password_verify موفق بود!</p>";
                } else {
                    echo "<p class='error'>❌ password_verify ناموفق بود!</p>";
                }
                
                echo "<p><a href='login.php'><button>بازگشت به صفحه ورود</button></a></p>";
            } else {
                echo "<p class='error'>❌ خطا در به‌روزرسانی hash در دیتابیس.</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>❌ خطا در به‌روزرسانی: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else {
    // نمایش فرم
    echo "<h2>2. تبدیل Hash به bcrypt</h2>";
    echo "<p class='warning'>⚠️ هشدار: این عملیات hash فعلی را به bcrypt تبدیل می‌کند.</p>";
    echo "<p>پسورد پیش‌فرض: <strong>admin123</strong></p>";
    echo "<form method='POST' action=''>";
    echo "<label>پسورد جدید (یا همان admin123):</label><br>";
    echo "<input type='password' name='new_password' value='admin123' style='width: 300px; padding: 8px; margin: 10px 0;'><br>";
    echo "<button type='submit' name='convert_password' class='danger'>تبدیل Hash به bcrypt</button>";
    echo "</form>";
    echo "<p><a href='login.php'><button>بازگشت به صفحه ورود</button></a></p>";
}

echo "</div></body></html>";
?>

