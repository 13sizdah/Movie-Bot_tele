<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>در حال بارگذاری...</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .loading-container {
            text-align: center;
            color: white;
        }
        
        .spinner {
            width: 80px;
            height: 80px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 30px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .loading-subtext {
            font-size: 16px;
            opacity: 0.8;
        }
        
        .dots {
            display: inline-block;
            animation: dots 1.5s steps(4, end) infinite;
        }
        
        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">در حال بارگذاری<span class="dots"></span></div>
        <div class="loading-subtext">لطفاً صبر کنید</div>
    </div>
    
    <script>
        // تنظیمات Telegram Web App (اگر در دسترس باشد)
        if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
            const tg = Telegram.WebApp;
            tg.ready();
            tg.expand();
            
            // تنظیم رنگ پس‌زمینه
            tg.setBackgroundColor('#667eea');
        }
        
        // هدایت به صفحه اصلی پس از بارگذاری
        // این صفحه توسط JavaScript در header.php مخفی می‌شود
    </script>
</body>
</html>

