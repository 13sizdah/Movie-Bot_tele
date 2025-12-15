<?php
include 'db.php';
ob_start();
session_start();

// بررسی وجود توکن
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: login.php?error=no_token');
    exit;
}

$token = $_GET['token'];
$token_file = '../admin_tokens/' . $token . '.txt';

// بررسی وجود فایل توکن
if (!file_exists($token_file)) {
    header('Location: login.php?error=invalid_token');
    exit;
}

// خواندن اطلاعات توکن
$token_data = json_decode(file_get_contents($token_file), true);

// بررسی انقضای توکن
if (time() > $token_data['expires']) {
    unlink($token_file); // حذف توکن منقضی شده
    header('Location: login.php?error=expired_token');
    exit;
}

// بررسی اینکه آیا userid در جدول sp_admins وجود دارد
// در اینجا می‌توانیم از اولین ادمین استفاده کنیم یا userid را در توکن ذخیره کنیم
// برای سادگی، از اولین ادمین استفاده می‌کنیم
$sql = "SELECT * FROM sp_admins ORDER BY id ASC LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin) {
    // ایجاد session
    $_SESSION['username'] = $admin['username'];
    
    // حذف توکن استفاده شده
    if (file_exists($token_file)) {
        unlink($token_file);
    }
    
    // هدایت به داشبورد
    header('Location: index.php');
    exit;
} else {
    header('Location: login.php?error=admin_not_found');
    exit;
}
?>

