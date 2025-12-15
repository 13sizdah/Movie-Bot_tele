<?php
// تنظیمات وب اپ
define('WEB_APP', TRUE);

// بارگذاری تنظیمات اصلی از فایل config.php
require_once dirname(__DIR__) . '/config.php';

// تنظیمات وب اپ
define('SITE_NAME', 'ونسو');

// آدرس اصلی بات
// BASEURI از config.php اصلی خوانده می‌شود
// اگر BASEURI در config.php اصلی تعریف نشده باشد، از آدرس پیش‌فرض استفاده می‌کنیم
if (!defined('BASEURI')) {
    define('BASEURI', 'https://13ishere.shop/8');
}

define('SITE_URL', BASEURI . '/web');
define('API_URL', BASEURI . '/web/api');

// تنظیمات جلسه
session_start();

// توابع کمکی
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_user_id() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function fa_num($str) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($english, $persian, $str);
}

function en_num($str) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($persian, $english, $str);
}

// تابع translate_genre در config.php اصلی تعریف شده است
// تابع برای ساخت URL پوستر
function get_poster_url($poster_path) {
    if (empty($poster_path)) {
        return '';
    }
    
    // اگر URL کامل است (با http یا https شروع می‌شود)، همان را برگردان
    if (strpos($poster_path, 'http://') === 0 || strpos($poster_path, 'https://') === 0) {
        return $poster_path;
    }
    
    // اگر مسیر نسبی است، با BASEURI ترکیب کن
    // حذف اسلش اول اگر وجود دارد
    $poster_path = ltrim($poster_path, '/');
    
    // ساخت URL کامل
    $full_url = BASEURI . '/' . $poster_path;
    
    // Debug: برای بررسی URL (می‌توانید بعداً حذف کنید)
    // error_log("Poster URL: " . $full_url);
    
    return $full_url;
}

// بررسی VIP بودن کاربر
function is_vip($userid) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT vip_date FROM sp_users WHERE userid = :userid LIMIT 1");
    $stmt->bindValue(':userid', $userid, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && !empty($user['vip_date'])) {
        $vip_date = intval($user['vip_date']);
        $now = time();
        return ($vip_date > $now);
    }
    return false;
}

// اتصال به دیتابیس با استفاده از اطلاعات config.php اصلی
try {
    $pdo = new PDO(
        "mysql:host=" . HOST . ";dbname=" . DBNAME . ";charset=utf8",
        USERNAME,
        PASSWORD,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("❌ خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// ============================================================
// توابع مدیریت تنظیمات مینی اپ
// ============================================================

// دریافت تنظیمات از دیتابیس
function get_webapp_setting($key, $default = '') {
    global $pdo;
    try {
        $sql = "SELECT setting_value FROM sp_webapp_settings WHERE setting_key = :key LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch(PDOException $e) {
        // در صورت خطا، مقدار پیش‌فرض را برگردان
        return $default;
    }
}

// دریافت تمام تنظیمات یک دسته
function get_webapp_settings_by_category($category) {
    global $pdo;
    try {
        if (!isset($pdo)) {
            return [];
        }
        $sql = "SELECT setting_key, setting_value FROM sp_webapp_settings WHERE category = :category";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // اگر می‌خواهیم array ساده برگردانیم (key => value)
        $settings = [];
        foreach ($results as $row) {
            if (is_array($row) && isset($row['setting_key']) && isset($row['setting_value'])) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    } catch(PDOException $e) {
        error_log("Error in get_webapp_settings_by_category: " . $e->getMessage());
        return [];
    } catch(Exception $e) {
        error_log("Error in get_webapp_settings_by_category: " . $e->getMessage());
        return [];
    }
}

// دریافت منوها از دیتابیس
function get_webapp_menu_items() {
    $menu_json = get_webapp_setting('menu_items', '[]');
    $menu_items = json_decode($menu_json, true);
    return is_array($menu_items) ? $menu_items : [];
}

// دریافت تنظیمات رنگ‌بندی
function get_webapp_colors() {
    return get_webapp_settings_by_category('colors');
}

// دریافت تنظیمات رنگ‌بندی Dark Mode
function get_webapp_colors_dark() {
    return get_webapp_settings_by_category('colors_dark');
}

// دریافت تنظیمات لوگو
function get_webapp_logo() {
    return [
        'url' => get_webapp_setting('logo_url', ''),
        'width' => get_webapp_setting('logo_width', '150'),
        'height' => get_webapp_setting('logo_height', 'auto')
    ];
}

// بررسی فعال بودن فیلتر
function is_filter_enabled($filter_name) {
    return get_webapp_setting($filter_name, '1') == '1';
}

// دریافت محدودیت فیلتر
function get_filter_limit($filter_name, $default = 20) {
    return intval(get_webapp_setting($filter_name, $default));
}

// بررسی فعال بودن حالت VIP
function is_vip_mode_enabled() {
    return get_webapp_setting('enable_vip_mode', '0') == '1';
}

// بررسی دسترسی کاربر به دانلود (بر اساس حالت VIP)
function can_user_download($user_id) {
    // اگر حالت VIP فعال نباشد، همه می‌توانند دانلود کنند
    if (!is_vip_mode_enabled()) {
        return true;
    }
    
    // اگر حالت VIP فعال باشد، کاربر باید VIP باشد
    return is_vip($user_id);
}
?>

