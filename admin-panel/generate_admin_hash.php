<?php
/**
 * ============================================================
 * تولید Hash پسورد برای ادمین
 * ============================================================
 * این فایل برای تولید hash پسورد ادمین استفاده می‌شود
 * ============================================================
 * نحوه استفاده:
 * 1. این فایل را در مرورگر باز کنید
 * 2. پسورد مورد نظر خود را در متغیر $password وارد کنید
 * 3. Hash تولید شده را کپی کنید
 * 4. Hash را در فایل CREATE_ADMIN_USER.sql استفاده کنید
 * ============================================================
 */

// پسورد مورد نظر خود را اینجا وارد کنید
$password = 'admin123';

// تولید hash با استفاده از password_hash (bcrypt)
$hash = password_hash($password, PASSWORD_BCRYPT);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تولید Hash پسورد ادمین</title>
    <style>
        body {
            font-family: 'Tahoma', Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }
        .hash-box {
            background: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            font-size: 14px;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .success {
            background: #d4edda;
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 تولید Hash پسورد ادمین</h1>
        
        <div class="info-box">
            <strong>📝 پسورد وارد شده:</strong> <code><?= htmlspecialchars($password) ?></code>
        </div>
        
        <div class="success">
            <strong>✅ Hash تولید شده:</strong>
        </div>
        
        <div class="hash-box">
            <?= htmlspecialchars($hash) ?>
        </div>
        
        <div class="warning">
            <strong>⚠️ نکات مهم:</strong>
            <ul>
                <li>این Hash را در فایل <code>CREATE_ADMIN_USER.sql</code> استفاده کنید</li>
                <li>یا مستقیماً در دیتابیس با کوئری زیر استفاده کنید:</li>
            </ul>
            <div class="hash-box" style="margin-top: 10px;">
                INSERT INTO `sp_admins` (`username`, `password`) VALUES<br>
                ('admin', '<?= htmlspecialchars($hash) ?>');
            </div>
        </div>
        
        <div class="info-box">
            <strong>📋 مراحل استفاده:</strong>
            <ol>
                <li>Hash بالا را کپی کنید</li>
                <li>فایل <code>CREATE_ADMIN_USER.sql</code> را باز کنید</li>
                <li>Hash موجود را با Hash جدید جایگزین کنید</li>
                <li>فایل SQL را در دیتابیس اجرا کنید</li>
                <li>با username: <code>admin</code> و password: <code><?= htmlspecialchars($password) ?></code> وارد شوید</li>
            </ol>
        </div>
        
        <div class="warning">
            <strong>🔒 امنیت:</strong>
            <ul>
                <li>بعد از استفاده، این فایل را حذف کنید</li>
                <li>بعد از اولین ورود، حتماً پسورد را تغییر دهید</li>
                <li>از پسوردهای قوی استفاده کنید</li>
            </ul>
        </div>
    </div>
</body>
</html>

