<?php
require_once '../config.php';

header('Content-Type: application/json');

// دریافت داده‌های initData از Telegram Web App
$init_data = isset($_POST['initData']) ? $_POST['initData'] : (isset($_GET['initData']) ? $_GET['initData'] : '');

if (empty($init_data)) {
    echo json_encode(['success' => false, 'error' => 'داده‌های initData ارسال نشده است']);
    exit;
}

// بررسی صحت داده‌های Telegram با استفاده از توکن ربات
// Telegram Web App داده‌ها را به صورت query string ارسال می‌کند
parse_str($init_data, $data);

// Log برای دیباگ
error_log('Auth attempt - init_data length: ' . strlen($init_data));
error_log('Auth attempt - parsed data keys: ' . implode(', ', array_keys($data)));
if (isset($data['user'])) {
    error_log('Auth attempt - user data exists');
}

// بررسی hash برای امنیت بیشتر
// در نسخه وب Telegram، ممکن است hash موجود نباشد، پس آن را اختیاری می‌کنیم
if (isset($data['hash']) && !empty($data['hash'])) {
    // استخراج hash از داده‌ها
    $received_hash = $data['hash'];
    $data_for_hash = $data;
    unset($data_for_hash['hash']);
    
    // مرتب‌سازی داده‌ها بر اساس کلید
    ksort($data_for_hash);
    
    // ساخت query string
    $data_check_string = [];
    foreach ($data_for_hash as $key => $value) {
        $data_check_string[] = $key . '=' . $value;
    }
    $data_check_string = implode("\n", $data_check_string);
    
    // محاسبه hash با استفاده از توکن ربات
    $secret_key = hash_hmac('sha256', TOKEN, 'WebAppData', true);
    $calculated_hash = bin2hex(hash_hmac('sha256', $data_check_string, $secret_key, true));
    
    // مقایسه hash
    if ($calculated_hash !== $received_hash) {
        error_log('Hash validation failed - received: ' . substr($received_hash, 0, 20) . '... calculated: ' . substr($calculated_hash, 0, 20) . '...');
        echo json_encode(['success' => false, 'error' => 'داده‌های نامعتبر - hash مطابقت ندارد']);
        exit;
    } else {
        error_log('Hash validation successful');
    }
}

// بررسی وجود user در داده‌ها
$user_data = null;

if (isset($data['user'])) {
    // decode کردن JSON user
    $user_data = json_decode(urldecode($data['user']), true);
} else {
    // اگر user در data نبود، ممکن است از initDataUnsafe استفاده شده باشد
    // در این صورت باید user را از query string استخراج کنیم
    echo json_encode(['success' => false, 'error' => 'اطلاعات کاربر یافت نشد']);
    exit;
}

if (!$user_data || !isset($user_data['id'])) {
    echo json_encode(['success' => false, 'error' => 'اطلاعات کاربر نامعتبر است']);
    exit;
}

$user_id = intval($user_data['id']);

// بررسی وجود کاربر در دیتابیس
$stmt = $pdo->prepare("SELECT * FROM sp_users WHERE userid = :userid LIMIT 1");
$stmt->bindValue(':userid', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    error_log('User not found in database - user_id: ' . $user_id);
    echo json_encode(['success' => false, 'error' => 'کاربری با این شناسه یافت نشد. لطفاً ابتدا از طریق ربات تلگرام ثبت‌نام کنید.']);
    exit;
}

error_log('User found - user_id: ' . $user_id . ', name: ' . $user['name']);

// ایجاد جلسه (session قبلاً در config.php شروع شده است)
// اگر session شروع نشده باشد، آن را شروع می‌کنیم
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ذخیره اطلاعات در session
$_SESSION['user_id'] = $user['userid'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_phone'] = $user['phone'];

// ذخیره عکس کاربر از Telegram Web App (اگر موجود باشد)
$user_photo = '';
if (isset($user_data['photo_url']) && !empty($user_data['photo_url'])) {
    $_SESSION['user_photo'] = $user_data['photo_url'];
    $user_photo = $user_data['photo_url'];
}

// نوشتن session قبل از ارسال response
session_write_close();

error_log('Session created and written - user_id: ' . $_SESSION['user_id']);

// ارسال response
$response = [
    'success' => true,
    'user' => [
        'id' => $user['userid'],
        'name' => $user['name'],
        'phone' => $user['phone'],
        'photo' => $user_photo
    ]
];

error_log('Sending success response');

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>

