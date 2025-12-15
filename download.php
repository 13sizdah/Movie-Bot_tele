<?php
// ============================================================
// صفحه دانلود با لینک اختصاصی
// ============================================================

include_once 'config.php';
include_once 'telegram.php';

$telegram = new telegram(TOKEN, HOST, USERNAME, PASSWORD, DBNAME);
global $botuser;

// دریافت توکن از URL
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    die('لینک نامعتبر است');
}

// پیدا کردن کیفیت بر اساس لینک
$sql = "SELECT q.*, f.name, f.type, f.price 
        FROM sp_qualities q 
        INNER JOIN sp_files f ON q.file_id = f.id 
        WHERE q.download_link LIKE :token AND q.status=1 AND f.status=1";
$stmt = $telegram->db->prepare($sql);
$token_param = '%token=' . $token;
$stmt->bindParam(':token', $token_param);
$stmt->execute();
$quality_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quality_info) {
    die('لینک یافت نشد یا منقضی شده است');
}

$file_url = $quality_info['file_url'];
$file_name = $quality_info['name'];
$quality_name = $quality_info['quality'];
$file_type = $quality_info['type'];
$file_price = $quality_info['price'];

// بررسی دسترسی کاربر (اگر نیاز به لاگین باشد)
// در اینجا می‌توانید سیستم احراز هویت اضافه کنید

// اگر File ID است، مستقیماً به تلگرام هدایت می‌شود
// اگر URL است، به آن URL هدایت می‌شود
if (strpos($file_url, 'http') === 0) {
    // URL خارجی
    header('Location: ' . $file_url);
    exit;
} else {
    // File ID تلگرام - نمایش پیام
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>دانلود <?= htmlspecialchars($file_name) ?></title>
        <style>
            body {
                font-family: Tahoma, Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 15px;
                padding: 40px;
                max-width: 500px;
                width: 100%;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
            }
            h1 {
                color: #333;
                margin-bottom: 20px;
            }
            .info {
                background: #f5f5f5;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
            .info p {
                margin: 10px 0;
                color: #666;
            }
            .telegram-link {
                display: inline-block;
                background: #0088cc;
                color: white;
                padding: 15px 30px;
                border-radius: 8px;
                text-decoration: none;
                margin-top: 20px;
                font-weight: bold;
                transition: background 0.3s;
            }
            .telegram-link:hover {
                background: #006699;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎬 <?= htmlspecialchars($file_name) ?></h1>
            <div class="info">
                <p><strong>کیفیت:</strong> <?= htmlspecialchars($quality_name) ?></p>
                <p>برای دانلود این فایل، لطفاً از طریق ربات تلگرام اقدام کنید.</p>
            </div>
            <a href="https://t.me/<?= $botuser ?>?start=quality_<?= $quality_info['id'] ?>" class="telegram-link">
                📥 دانلود از تلگرام
            </a>
        </div>
    </body>
    </html>
    <?php
}
?>

