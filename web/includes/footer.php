    </main>
    <footer style="background: var(--bg-card); border-top: 1px solid var(--border-color); padding: 32px 0; margin-top: 64px;">
        <div class="container" style="text-align: center; color: var(--text-secondary);">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. تمامی حقوق محفوظ است.</p>
        </div>
    </footer>
    <script>
        // Theme Toggle
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        }
        
        function updateThemeIcon(theme) {
            const icon = document.getElementById('theme-icon');
            if (icon) {
                icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
        
        // Telegram Web App Settings
        let isTelegramWebApp = false;
        if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
            try {
                const tg = Telegram.WebApp;
                tg.ready();
                tg.expand();
                isTelegramWebApp = true;
                
                // تنظیم رنگ پس‌زمینه بر اساس تم
                const theme = document.documentElement.getAttribute('data-theme');
                if (theme === 'dark') {
                    tg.setHeaderColor('#0a0a0a');
                    tg.setBackgroundColor('#0a0a0a');
                } else {
                    tg.setHeaderColor('#ffffff');
                    tg.setBackgroundColor('#ffffff');
                }
            } catch (e) {
                // اگر Telegram WebApp در دسترس نباشد (مثلاً در دسکتاپ)، ادامه می‌دهیم
                console.log('Telegram WebApp not available:', e);
                isTelegramWebApp = false;
            }
        }
        
        // پنهان کردن صفحه loading
        function hideLoader() {
            const loader = document.getElementById('page-loader');
            if (loader && !loader.classList.contains('hidden')) {
                loader.classList.add('hidden');
                document.body.classList.remove('loading');
            }
        }
        
        // اگر Telegram WebApp در دسترس نباشد (مثلاً در دسکتاپ)، loading را فوراً پنهان کن
        // بررسی فوری پس از لود شدن DOM
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                    setTimeout(hideLoader, 200);
                }
            });
        } else {
            // اگر DOM قبلاً لود شده
            if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
                setTimeout(hideLoader, 200);
            }
        }
        
        // پنهان کردن loading پس از لود کامل صفحه (برای همه حالات)
        window.addEventListener('load', function() {
            setTimeout(hideLoader, 300);
        });
        
        // Fallback: اگر صفحه خیلی سریع لود شد، loading را حداکثر بعد از 1 ثانیه پنهان کن
        setTimeout(function() {
            hideLoader();
        }, 1000);
        
        // تبدیل اعداد به فارسی
        function faNum(str) {
            const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            return str.toString().replace(/\d/g, (d) => persian[parseInt(d)]);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.fa-num').forEach(el => {
                el.textContent = faNum(el.textContent);
            });
        });
        
        // توابع منوی همبرگری
        function toggleHamburgerMenu() {
            const menu = document.getElementById('hamburgerMenu');
            const overlay = document.getElementById('hamburgerOverlay');
            
            if (menu && overlay) {
                menu.classList.toggle('active');
                overlay.classList.toggle('active');
                
                // جلوگیری از اسکرول body وقتی منو باز است
                if (menu.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
        }
        
        function closeHamburgerMenu() {
            const menu = document.getElementById('hamburgerMenu');
            const overlay = document.getElementById('hamburgerOverlay');
            
            if (menu && overlay) {
                menu.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // بستن منو با کلید ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeHamburgerMenu();
            }
        });
    </script>
</body>
</html>
