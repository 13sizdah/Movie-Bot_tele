<?php
/**
 * فایل تست ورود
 * این فایل برای عیب‌یابی مشکل ورود استفاده می‌شود
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>تست ورود به پنل مدیریت</h1>";

// بررسی db.php
echo "<h2>1. بررسی db.php</h2>";
$db_path = __DIR__ . '/db.php';
if (file_exists($db_path)) {
    echo "✅ فایل db.php موجود است<br>";
    include $db_path;
    
    if (isset($db)) {
        echo "✅ متغیر \$db تعریف شده است<br>";
    } else {
        echo "❌ متغیر \$db تعریف نشده است<br>";
        die();
    }
} else {
    echo "❌ فایل db.php یافت نشد<br>";
    die();
}

// بررسی جدول sp_admins
echo "<h2>2. بررسی جدول sp_admins</h2>";
try {
    $stmt = $db->query("SELECT * FROM sp_admins LIMIT 1");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "❌ هیچ ادمینی در دیتابیس وجود ندارد<br>";
        echo "⚠️ لطفاً فایل CREATE_ADMIN_USER.sql را اجرا کنید<br>";
    } else {
        echo "✅ ادمین‌ها موجود هستند:<br>";
        foreach ($admins as $admin) {
            echo "- Username: " . htmlspecialchars($admin['username']) . "<br>";
            echo "- Password Hash: " . htmlspecialchars(substr($admin['password'], 0, 20)) . "...<br>";
        }
    }
} catch (PDOException $e) {
    echo "❌ خطا در بررسی جدول: " . $e->getMessage() . "<br>";
}

// تست password_verify
echo "<h2>3. تست password_verify</h2>";
if (!empty($admins)) {
    $test_password = 'admin123';
    $admin = $admins[0];
    $hash = $admin['password'];
    
    echo "تست پسورد: $test_password<br>";
    echo "Hash موجود: " . htmlspecialchars(substr($hash, 0, 30)) . "...<br>";
    
    $result = password_verify($test_password, $hash);
    if ($result) {
        echo "✅ password_verify موفق بود!<br>";
    } else {
        echo "❌ password_verify ناموفق بود<br>";
        echo "⚠️ ممکن است پسورد یا hash اشتباه باشد<br>";
    }
}

// تست تابع login_user
echo "<h2>4. تست تابع login_user</h2>";
session_start();

function test_login_user($username, $password)
{
    global $db;
    
    if (!isset($db)) {
        return "❌ متغیر \$db تعریف نشده است";
    }
    
    try {
        $sql = "SELECT * FROM sp_admins WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $pwd = $result['password'];
            $checkpass = password_verify($password, $pwd);
            
            if ($checkpass === false) {
                return "❌ پسورد اشتباه است";
            } elseif ($checkpass === true) {
                return "✅ ورود موفق! Username: " . htmlspecialchars($username);
            }
        } else {
            return "❌ کاربر یافت نشد";
        }
    } catch (PDOException $e) {
        return "❌ خطا: " . $e->getMessage();
    }
}

if (isset($_POST['test_login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $result = test_login_user($username, $password);
        echo "<div style='padding: 10px; background: #f0f0f0; border-radius: 5px; margin: 10px 0;'>";
        echo $result;
        echo "</div>";
    }
}

// فرم تست
echo "<h2>5. فرم تست ورود</h2>";
echo "<form method='POST' style='max-width: 400px; padding: 20px; background: #f9f9f9; border-radius: 5px;'>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label>Username:</label><br>";
echo "<input type='text' name='username' value='admin' style='width: 100%; padding: 8px;' required>";
echo "</div>";
echo "<div style='margin-bottom: 10px;'>";
echo "<label>Password:</label><br>";
echo "<input type='password' name='password' value='admin123' style='width: 100%; padding: 8px;' required>";
echo "</div>";
echo "<button type='submit' name='test_login' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>تست ورود</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='login.php'>بازگشت به صفحه ورود</a></p>";
?>

