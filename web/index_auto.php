<?php
// این فایل برای مینی اپ تلگرام است - احراز هویت خودکار
require_once 'config.php';

// اگر کاربر قبلاً وارد شده، به صفحه اصلی هدایت کن
if (is_logged_in()) {
    // کاربر قبلاً وارد شده - نمایش صفحه اصلی
    $page_title = 'صفحه اصلی';
    include 'includes/header.php';
    
    // دریافت دسته‌بندی‌ها
    $categories_stmt = $pdo->query("SELECT * FROM sp_cats ORDER BY id DESC");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // دریافت آخرین فیلم/سریال‌ها
    $latest_stmt = $pdo->query("SELECT * FROM sp_files WHERE status=1 ORDER BY id DESC LIMIT 12");
    $latest_files = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // دریافت محبوب‌ترین فیلم/سریال‌ها
    $popular_stmt = $pdo->query("SELECT * FROM sp_files WHERE status=1 ORDER BY views DESC LIMIT 12");
    $popular_files = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
            <i class="fas fa-th-large"></i> دسته‌بندی‌ها
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($categories as $cat): ?>
                <a href="category.php?id=<?= $cat['id'] ?>" class="glass card-hover rounded-xl p-6 text-center text-white">
                    <i class="fas fa-folder text-4xl mb-3"></i>
                    <h3 class="font-semibold"><?= htmlspecialchars($cat['name']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
            <i class="fas fa-fire"></i> محبوب‌ترین‌ها
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <?php foreach ($popular_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="glass card-hover rounded-xl overflow-hidden">
                    <?php if (!empty($file['poster'])): ?>
                        <?php 
                        $poster_url = $file['poster'];
                        if (strpos($poster_url, 'http') !== 0) {
                            $poster_url = BASEURI . '/' . ltrim($poster_url, '/');
                        }
                        ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="w-full h-64 object-cover">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                            <i class="fas fa-film text-6xl text-white/50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="p-4 text-white">
                        <h3 class="font-bold mb-2 truncate"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="flex items-center justify-between text-sm text-white/70">
                            <span><i class="fas fa-eye"></i> <?= fa_num(number_format($file['views'])) ?></span>
                            <?php if ($file['media_type'] == 'series'): ?>
                                <span><i class="fas fa-tv"></i> سریال</span>
                            <?php elseif ($file['media_type'] == 'animation'): ?>
                                <span><i class="fas fa-palette"></i> انیمیشن</span>
                            <?php elseif ($file['media_type'] == 'anime'): ?>
                                <span><i class="fas fa-paint-brush"></i> انیمه</span>
                            <?php else: ?>
                                <span><i class="fas fa-film"></i> فیلم</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="mb-8">
        <h2 class="text-3xl font-bold text-white mb-6 flex items-center gap-3">
            <i class="fas fa-clock"></i> جدیدترین‌ها
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <?php foreach ($latest_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="glass card-hover rounded-xl overflow-hidden">
                    <?php if (!empty($file['poster'])): ?>
                        <?php 
                        $poster_url = $file['poster'];
                        if (strpos($poster_url, 'http') !== 0) {
                            $poster_url = BASEURI . '/' . ltrim($poster_url, '/');
                        }
                        ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="w-full h-64 object-cover">
                    <?php else: ?>
                        <div class="w-full h-64 bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                            <i class="fas fa-film text-6xl text-white/50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="p-4 text-white">
                        <h3 class="font-bold mb-2 truncate"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="flex items-center justify-between text-sm text-white/70">
                            <span><i class="fas fa-eye"></i> <?= fa_num(number_format($file['views'])) ?></span>
                            <?php if ($file['media_type'] == 'series'): ?>
                                <span><i class="fas fa-tv"></i> سریال</span>
                            <?php elseif ($file['media_type'] == 'animation'): ?>
                                <span><i class="fas fa-palette"></i> انیمیشن</span>
                            <?php elseif ($file['media_type'] == 'anime'): ?>
                                <span><i class="fas fa-paint-brush"></i> انیمه</span>
                            <?php else: ?>
                                <span><i class="fas fa-film"></i> فیلم</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php';
    exit;
}

// اگر کاربر وارد نشده، احراز هویت خودکار از طریق Telegram Web App
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>در حال ورود...</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            font-family: 'Vazirmatn', sans-serif;
        }
        .loading {
            text-align: center;
            color: white;
        }
        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <div class="spinner"></div>
        <p>در حال ورود...</p>
    </div>
    
    <script>
    // بررسی اینکه آیا از Telegram Web App باز شده است
    if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
        const tg = Telegram.WebApp;
        tg.ready();
        tg.expand();
        
        // دریافت initData از Telegram
        const initData = tg.initData;
        
        if (initData) {
            // ارسال initData به سرور برای احراز هویت
            fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'initData=' + encodeURIComponent(initData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // ورود موفق - رفرش صفحه
                    window.location.reload();
                } else {
                    // نمایش خطا
                    document.body.innerHTML = '<div class="loading"><p style="color: red;">' + (data.error || 'خطا در ورود') + '</p></div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.body.innerHTML = '<div class="loading"><p style="color: red;">خطا در ارتباط با سرور</p></div>';
            });
        } else {
            document.body.innerHTML = '<div class="loading"><p style="color: red;">این صفحه باید از طریق ربات تلگرام باز شود.</p></div>';
        }
    } else {
        document.body.innerHTML = '<div class="loading"><p style="color: red;">این صفحه باید از طریق ربات تلگرام باز شود.</p></div>';
    }
    </script>
</body>
</html>

