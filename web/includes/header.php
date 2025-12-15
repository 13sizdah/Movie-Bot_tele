<!DOCTYPE html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <?php
    // دریافت تنظیمات رنگ‌بندی از دیتابیس
    $webapp_colors = [];
    $webapp_colors_dark = [];
    $dark_colors = [];
    
    // بررسی اتصال به دیتابیس قبل از فراخوانی توابع
    if (isset($pdo)) {
        try {
            $webapp_colors = get_webapp_colors();
            if (!is_array($webapp_colors)) {
                $webapp_colors = [];
            }
            
            // get_webapp_settings_by_category یک array ساده برمی‌گرداند (key => value)
            // نه یک array از array ها
            $webapp_colors_dark = get_webapp_settings_by_category('colors_dark');
            if (is_array($webapp_colors_dark)) {
                $dark_colors = $webapp_colors_dark; // مستقیماً استفاده کن
            }
        } catch (Exception $e) {
            // در صورت خطا، از مقادیر پیش‌فرض استفاده کن
            error_log("Error loading webapp colors: " . $e->getMessage());
            $webapp_colors = [];
            $webapp_colors_dark = [];
        }
    }
    
    // رنگ‌های Light Mode
    $primary_color = !empty($webapp_colors['primary_color']) ? $webapp_colors['primary_color'] : '#000000';
    $secondary_color = !empty($webapp_colors['secondary_color']) ? $webapp_colors['secondary_color'] : '#6c757d';
    $background_color = !empty($webapp_colors['background_color']) ? $webapp_colors['background_color'] : '#ffffff';
    $text_color = !empty($webapp_colors['text_color']) ? $webapp_colors['text_color'] : '#000000';
    $accent_color = !empty($webapp_colors['accent_color']) ? $webapp_colors['accent_color'] : '#000000';
    $border_color = !empty($webapp_colors['border_color']) ? $webapp_colors['border_color'] : '#e9ecef';
    
    // رنگ‌های Dark Mode
    $dark_primary_color = !empty($dark_colors['dark_primary_color']) ? $dark_colors['dark_primary_color'] : '#ffffff';
    $dark_secondary_color = !empty($dark_colors['dark_secondary_color']) ? $dark_colors['dark_secondary_color'] : '#a0a0a0';
    $dark_background_color = !empty($dark_colors['dark_background_color']) ? $dark_colors['dark_background_color'] : '#0a0a0a';
    $dark_text_color = !empty($dark_colors['dark_text_color']) ? $dark_colors['dark_text_color'] : '#ffffff';
    $dark_accent_color = !empty($dark_colors['dark_accent_color']) ? $dark_colors['dark_accent_color'] : '#ffffff';
    $dark_border_color = !empty($dark_colors['dark_border_color']) ? $dark_colors['dark_border_color'] : '#2a2a2a';
    
    // دریافت تنظیمات لوگو
    $webapp_logo = ['url' => '', 'width' => '150', 'height' => 'auto'];
    if (isset($pdo)) {
        try {
            $webapp_logo = get_webapp_logo();
        } catch (Exception $e) {
            error_log("Error loading webapp logo: " . $e->getMessage());
        }
    }
    ?>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap');
        
        /* اعمال رنگ‌های سفارشی از دیتابیس - Light Mode */
        :root {
            --bg-primary: <?= htmlspecialchars($background_color) ?>;
            --text-primary: <?= htmlspecialchars($text_color) ?>;
            --accent: <?= htmlspecialchars($accent_color) ?>;
            --border-color: <?= htmlspecialchars($border_color) ?>;
        }
        
        /* اعمال رنگ‌های سفارشی از دیتابیس - Dark Mode */
        [data-theme="dark"] {
            --bg-primary: <?= htmlspecialchars($dark_background_color) ?>;
            --text-primary: <?= htmlspecialchars($dark_text_color) ?>;
            --accent: <?= htmlspecialchars($dark_accent_color) ?>;
            --border-color: <?= htmlspecialchars($dark_border_color) ?>;
        }
        
        /* رنگ‌های ثانویه */
        .text-secondary-custom {
            color: <?= htmlspecialchars($secondary_color) ?>;
        }
        
        [data-theme="dark"] .text-secondary-custom {
            color: <?= htmlspecialchars($dark_secondary_color) ?>;
        }
        
        .bg-primary-custom {
            background-color: <?= htmlspecialchars($primary_color) ?>;
        }
        
        [data-theme="dark"] .bg-primary-custom {
            background-color: <?= htmlspecialchars($dark_primary_color) ?>;
        }
        
        /* Loading Screen - مینیمال و سفید/مشکی */
        #page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out, visibility 0.5s;
        }
        
        [data-theme="dark"] #page-loader {
            background: #0a0a0a;
        }
        
        #page-loader.hidden {
            opacity: 0;
            visibility: hidden;
        }
        
        .loader-content {
            text-align: center;
            color: #000000;
        }
        
        [data-theme="dark"] .loader-content {
            color: #ffffff;
        }
        
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #e9ecef;
            border-top-color: #000000;
            border-radius: 50%;
            animation: loader-spin 0.8s linear infinite;
            margin: 0 auto 24px;
        }
        
        [data-theme="dark"] .loader-spinner {
            border-color: #2a2a2a;
            border-top-color: #ffffff;
        }
        
        @keyframes loader-spin {
            to { transform: rotate(360deg); }
        }
        
        .loader-text {
            font-size: 18px;
            font-weight: 500;
            color: #000000;
            margin-bottom: 8px;
        }
        
        [data-theme="dark"] .loader-text {
            color: #ffffff;
        }
        
        .loader-subtext {
            font-size: 14px;
            color: #6c757d;
            font-weight: 400;
        }
        
        [data-theme="dark"] .loader-subtext {
            color: #a0a0a0;
        }
        
        body.loading {
            overflow: hidden;
        }
        
        /* منوی همبرگری */
        .hamburger-menu {
            position: fixed;
            top: 0;
            right: -320px;
            width: 320px;
            height: 100vh;
            background: var(--bg-card);
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            transition: right 0.3s ease;
            overflow-y: auto;
            border-left: 1px solid var(--border-color);
        }
        
        [data-theme="dark"] .hamburger-menu {
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.5);
        }
        
        .hamburger-menu.active {
            right: 0;
        }
        
        .hamburger-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .hamburger-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .hamburger-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--bg-primary);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .hamburger-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .hamburger-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-primary);
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
        }
        
        .hamburger-close:hover {
            color: var(--accent);
        }
        
        .hamburger-content {
            padding: 20px;
        }
        
        .category-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            margin-bottom: 8px;
            background: var(--bg-secondary);
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .category-menu-item:hover {
            background: var(--bg-card);
            border-color: var(--border-color);
            transform: translateX(-4px);
        }
        
        .category-menu-item i {
            font-size: 20px;
            color: var(--text-secondary);
            width: 24px;
            text-align: center;
        }
        
        .category-menu-item span {
            font-weight: 500;
            flex: 1;
        }
        
        .hamburger-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-primary);
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s;
            margin-left: 16px;
        }
        
        .hamburger-btn:hover {
            color: var(--accent);
        }
        
        @media (max-width: 768px) {
            .hamburger-menu {
                width: 280px;
                right: -280px;
            }
        }
    </style>
</head>
<body class="loading">
    <!-- Loading Screen -->
    <div id="page-loader">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <div class="loader-text">در حال بارگذاری...</div>
            <div class="loader-subtext">لطفاً صبر کنید</div>
        </div>
    </div>
    
    <!-- Overlay برای منوی همبرگری -->
    <div class="hamburger-overlay" id="hamburgerOverlay" onclick="closeHamburgerMenu()"></div>
    
    <!-- منوی همبرگری -->
    <div class="hamburger-menu" id="hamburgerMenu">
        <div class="hamburger-header">
            <div class="hamburger-title">
                <i class="fas fa-folder"></i>
                دسته‌بندی‌ها
            </div>
            <button class="hamburger-close" onclick="closeHamburgerMenu()" aria-label="بستن منو">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="hamburger-content">
            <?php
            // دریافت منوها از دیتابیس
            $menu_items = [];
            $categories = [];
            
            if (isset($pdo)) {
                try {
                    $menu_items = get_webapp_menu_items();
                    
                    // نمایش منوهای سفارشی
                    if (!empty($menu_items)) {
                        foreach ($menu_items as $item) {
                            if (!empty($item['title']) || !empty($item['url'])) {
                                $icon = !empty($item['icon']) ? $item['icon'] : '📁';
                                $title = htmlspecialchars($item['title'] ?? '');
                                $url = htmlspecialchars($item['url'] ?? '#');
                                echo '<a href="' . $url . '" class="category-menu-item" onclick="closeHamburgerMenu()">';
                                echo '<span style="font-size: 20px;">' . $icon . '</span>';
                                echo '<span>' . $title . '</span>';
                                echo '</a>';
                            }
                        }
                    }
                    
                    // نمایش دسته‌بندی‌ها
                    $categories_stmt = $pdo->query("SELECT * FROM sp_cats ORDER BY name ASC");
                    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($categories)) {
                        foreach ($categories as $cat) {
                            echo '<a href="category.php?id=' . htmlspecialchars($cat['id']) . '" class="category-menu-item" onclick="closeHamburgerMenu()">';
                            echo '<i class="fas fa-folder"></i>';
                            echo '<span>' . htmlspecialchars($cat['name']) . '</span>';
                            echo '</a>';
                        }
                    }
                } catch(PDOException $e) {
                    error_log("Error loading menu items: " . $e->getMessage());
                }
            }
            
            // اگر هیچ منو و دسته‌بندی‌ای وجود ندارد
            if (empty($menu_items) && empty($categories)) {
                echo '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">هیچ آیتمی یافت نشد</p>';
            }
            ?>
        </div>
    </div>
    
    <nav class="navbar">
        <div class="container">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button class="hamburger-btn" onclick="toggleHamburgerMenu()" aria-label="منوی دسته‌بندی‌ها">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="index.php" style="font-size: 24px; font-weight: 700; color: var(--text-primary); text-decoration: none; display: flex; align-items: center; gap: 8px;">
                        <?php if (!empty($webapp_logo['url'])): ?>
                            <img src="<?= htmlspecialchars($webapp_logo['url']) ?>" 
                                 alt="<?= SITE_NAME ?>" 
                                 style="height: 40px; width: <?= htmlspecialchars($webapp_logo['width']) ?>px; object-fit: contain;">
                        <?php else: ?>
                            <i class="fas fa-film"></i>
                        <?php endif; ?>
                        <?= SITE_NAME ?>
                    </a>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <a href="search.php" style="color: var(--text-primary); text-decoration: none; font-weight: 500; transition: color 0.3s;">
                        <i class="fas fa-search"></i> جستجو
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="profile.php" style="color: var(--text-primary); text-decoration: none; font-weight: 500; transition: color 0.3s;">
                            <i class="fas fa-user"></i> پروفایل
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> ورود
                        </a>
                    <?php endif; ?>
                    <button class="theme-toggle" onclick="toggleTheme()" aria-label="تغییر تم">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>
    <main class="container" style="padding: 32px 20px;">
