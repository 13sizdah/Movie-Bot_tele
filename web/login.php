<?php
require_once 'config.php';

// اگر کاربر قبلاً وارد شده، به صفحه اصلی هدایت کن
if (is_logged_in()) {
    redirect('index.php');
}

// اگر از Telegram Web App باز شده، احراز هویت خودکار
if (isset($_GET['tgWebAppStartParam']) || isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'telegram') !== false) {
    // این صفحه از طریق Telegram Web App باز شده است
    // احراز هویت از طریق JavaScript انجام می‌شود
}

$page_title = 'ورود به حساب کاربری';
include 'includes/header.php';
?>

<div id="loading" class="max-w-md mx-auto mt-12">
    <div class="glass rounded-2xl p-8 shadow-2xl text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto mb-4"></div>
        <p class="text-white text-xl">در حال ورود...</p>
    </div>
</div>

<div id="error-message" class="max-w-md mx-auto mt-12 hidden">
    <div class="glass rounded-2xl p-8 shadow-2xl">
        <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
            <i class="fas fa-exclamation-circle"></i> <span id="error-text"></span>
        </div>
        <div class="text-center">
            <a href="<?= BASEURI ?>/index.php" class="text-white/70 hover:text-white transition">
                <i class="fas fa-arrow-right"></i> بازگشت به ربات تلگرام
            </a>
        </div>
    </div>
</div>

<script src="https://telegram.org/js/telegram-web-app.js"></script>
<script>
// تابع برای احراز هویت با initData
function authenticate(initData) {
    if (!initData) {
        throw new Error('initData موجود نیست');
    }
    
    return fetch('api/auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'initData=' + encodeURIComponent(initData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('خطا در ارتباط با سرور: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            console.log('Authentication successful, redirecting...');
            // ورود موفق - هدایت به صفحه اصلی
            window.location.href = 'index.php';
        } else {
            throw new Error(data.error || 'خطا در ورود');
        }
    })
    .catch(error => {
        console.error('Authentication error:', error);
        throw error;
    });
}

// تابع نمایش خطا
function showError(message) {
    const loading = document.getElementById('loading');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    
    if (loading) loading.classList.add('hidden');
    if (errorMessage) errorMessage.classList.remove('hidden');
    if (errorText) errorText.textContent = message;
}

// بررسی اینکه آیا از Telegram Web App باز شده است
function initAuth() {
    // صبر برای لود شدن Telegram WebApp (در نسخه وب تلگرام ممکن است کمی طول بکشد)
    let attempts = 0;
    const maxAttempts = 50; // 5 ثانیه (50 * 100ms)
    
    const checkTelegram = setInterval(() => {
        attempts++;
        
        if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
            clearInterval(checkTelegram);
            
            const tg = Telegram.WebApp;
            
            try {
                tg.ready();
                tg.expand();
                
                // دریافت initData از Telegram
                let initData = tg.initData;
                
                console.log('Telegram WebApp initialized');
                console.log('initData:', initData ? 'موجود' : 'خالی');
                console.log('initDataUnsafe:', tg.initDataUnsafe);
                
                // اگر initData خالی باشد (مثل نسخه وب)، از initDataUnsafe استفاده می‌کنیم
                if (!initData || initData === '') {
                    if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                        console.log('Using initDataUnsafe for web version');
                        const user = tg.initDataUnsafe.user;
                        const queryId = tg.initDataUnsafe.query_id || '';
                        const authDate = tg.initDataUnsafe.auth_date || Math.floor(Date.now() / 1000);
                        const hash = tg.initDataUnsafe.hash || '';
                        
                        // ساخت query string از initDataUnsafe
                        const params = new URLSearchParams();
                        if (queryId) params.append('query_id', queryId);
                        params.append('user', JSON.stringify(user));
                        params.append('auth_date', authDate.toString());
                        if (hash) params.append('hash', hash);
                        
                        initData = params.toString();
                        console.log('Constructed initData from initDataUnsafe');
                    } else {
                        console.log('Neither initData nor initDataUnsafe available');
                    }
                }
                
                if (initData) {
                    authenticate(initData).catch(error => {
                        console.error('Authentication error:', error);
                        showError(error.message || 'خطا در ورود');
                    });
                } else {
                    showError('اطلاعات احراز هویت یافت نشد. لطفاً از طریق ربات تلگرام وارد شوید.');
                }
            } catch (error) {
                console.error('Telegram WebApp error:', error);
                showError('خطا در اتصال به Telegram Web App');
            }
        } else if (attempts >= maxAttempts) {
            clearInterval(checkTelegram);
            // اگر Telegram WebApp لود نشد، احتمالاً در مرورگر معمولی باز شده
            showError('این صفحه باید از طریق ربات تلگرام باز شود.');
        }
    }, 100); // بررسی هر 100ms
}

// شروع احراز هویت پس از لود شدن صفحه
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuth);
} else {
    initAuth();
}
</script>

<?php include 'includes/footer.php'; ?>

