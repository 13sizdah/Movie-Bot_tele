<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

// دریافت تنظیمات از دیتابیس
function get_webapp_setting($key, $default = '') {
    global $db;
    if (!isset($db)) {
        return $default;
    }
    try {
        $sql = "SELECT setting_value FROM sp_webapp_settings WHERE setting_key = :key LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':key', $key, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Error in get_webapp_setting: " . $e->getMessage());
        return $default;
    }
}

// دریافت تنظیمات بر اساس دسته
function get_webapp_settings_by_category($category) {
    global $db;
    if (!isset($db)) {
        return [];
    }
    try {
        $sql = "SELECT setting_key, setting_value FROM sp_webapp_settings WHERE category = :category ORDER BY id ASC";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // تبدیل به array ساده (key => value)
        $settings = [];
        foreach ($results as $row) {
            if (isset($row['setting_key']) && isset($row['setting_value'])) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        return $settings;
    } catch (PDOException $e) {
        error_log("Error in get_webapp_settings_by_category: " . $e->getMessage());
        return [];
    }
}

// دریافت تنظیمات رنگ‌بندی (فقط اگر $db تعریف شده باشد)
$color_settings = [];
$color_settings_dark = [];
$logo_settings = [];
$menu_settings = [];
$homepage_settings = [];
$filter_settings = [];
$user_account_settings = [];

if (isset($db)) {
    try {
        $color_settings = get_webapp_settings_by_category('colors');
        $color_settings_dark = get_webapp_settings_by_category('colors_dark');
        $logo_settings = get_webapp_settings_by_category('logo');
        $menu_settings = get_webapp_settings_by_category('menu');
        $homepage_settings = get_webapp_settings_by_category('homepage');
        $filter_settings = get_webapp_settings_by_category('filters');
        $user_account_settings = get_webapp_settings_by_category('user_account');
    } catch (Exception $e) {
        error_log("Error loading webapp settings: " . $e->getMessage());
    }
}

// دریافت منوی فعلی (فقط اگر $db تعریف شده باشد)
$menu_items_json = '[]';
$menu_items = [];

if (isset($db)) {
    try {
        $menu_items_json = get_webapp_setting('menu_items', '[]');
        $menu_items = json_decode($menu_items_json, true);
        if (!is_array($menu_items)) {
            $menu_items = [];
        }
    } catch (Exception $e) {
        error_log("Error loading menu items: " . $e->getMessage());
    }
}

// دریافت بخش‌های صفحه اصلی (فقط اگر $db تعریف شده باشد)
$homepage_sections_json = '[]';
$homepage_sections = [];

if (isset($db)) {
    try {
        $homepage_sections_json = get_webapp_setting('homepage_sections', '[]');
        $homepage_sections = json_decode($homepage_sections_json, true);
        if (!is_array($homepage_sections)) {
            $homepage_sections = [];
        }
    } catch (Exception $e) {
        error_log("Error loading homepage sections: " . $e->getMessage());
    }
}

include 'webapp_action.php';
?>

<div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
    <div class="flex flex-col flex-wrap sm:flex-row">
        <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
            <div class="py-8">
                <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                    <h2 class="text-2xl leading-tight">
                        <?= $title ?>
                    </h2>
                </div>

                <!-- تب‌ها -->
                <div class="bg-white rounded-lg shadow mb-4">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button onclick="showTab('colors')" id="tab-colors" class="tab-button active py-4 px-6 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                                🎨 رنگ‌بندی
                            </button>
                            <button onclick="showTab('logo')" id="tab-logo" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                🖼️ لوگو
                            </button>
                            <button onclick="showTab('menu')" id="tab-menu" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                📋 منوها
                            </button>
                            <button onclick="showTab('homepage')" id="tab-homepage" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                🏠 صفحه اصلی
                            </button>
                            <button onclick="showTab('filters')" id="tab-filters" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                🔍 فیلترها
                            </button>
                            <button onclick="showTab('user_account')" id="tab-user_account" class="tab-button py-4 px-6 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                                👤 حساب کاربری
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- محتوای تب‌ها -->
                <form method="POST" action="" enctype="multipart/form-data" accept-charset="UTF-8">
                    
                    <!-- تب رنگ‌بندی -->
                    <div id="content-colors" class="tab-content">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-4">🎨 مدیریت رنگ‌بندی</h3>
                            
                            <!-- رنگ‌بندی Light Mode -->
                            <div class="mb-8">
                                <h4 class="text-lg font-semibold mb-4 text-gray-800">☀️ حالت روشن (Light Mode)</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php 
                                    $light_colors = ['primary_color', 'secondary_color', 'background_color', 'text_color', 'accent_color', 'border_color'];
                                    foreach ($light_colors as $color_key):
                                        // حالا $color_settings یک array ساده است (key => value)
                                        $setting_value = isset($color_settings[$color_key]) ? $color_settings[$color_key] : '#000000';
                                        $description = ucfirst(str_replace('_', ' ', $color_key));
                                    ?>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <?= htmlspecialchars($description) ?>
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input type="color" 
                                                       name="color[<?= htmlspecialchars($color_key) ?>]" 
                                                       value="<?= htmlspecialchars($setting_value) ?>"
                                                       class="w-16 h-10 rounded border border-gray-300 cursor-pointer"
                                                       onchange="this.nextElementSibling.value = this.value">
                                                <input type="text" 
                                                       name="color[<?= htmlspecialchars($color_key) ?>]" 
                                                       value="<?= htmlspecialchars($setting_value) ?>"
                                                       class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                       onchange="this.previousElementSibling.value = this.value">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- رنگ‌بندی Dark Mode -->
                            <div class="mb-8">
                                <h4 class="text-lg font-semibold mb-4 text-gray-800">🌙 حالت تاریک (Dark Mode)</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <?php 
                                    $dark_colors = [
                                        'dark_primary_color' => 'رنگ اصلی',
                                        'dark_secondary_color' => 'رنگ ثانویه',
                                        'dark_background_color' => 'رنگ پس‌زمینه',
                                        'dark_text_color' => 'رنگ متن',
                                        'dark_accent_color' => 'رنگ تاکیدی',
                                        'dark_border_color' => 'رنگ حاشیه'
                                    ];
                                    foreach ($dark_colors as $color_key => $color_desc):
                                        // حالا $color_settings_dark یک array ساده است (key => value)
                                        $default_value = ($color_key == 'dark_background_color') ? '#0a0a0a' : (($color_key == 'dark_text_color') ? '#ffffff' : '#ffffff');
                                        $setting_value = isset($color_settings_dark[$color_key]) ? $color_settings_dark[$color_key] : $default_value;
                                    ?>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                <?= htmlspecialchars($color_desc) ?>
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input type="color" 
                                                       name="color[<?= htmlspecialchars($color_key) ?>]" 
                                                       value="<?= htmlspecialchars($setting_value) ?>"
                                                       class="w-16 h-10 rounded border border-gray-300 cursor-pointer"
                                                       onchange="this.nextElementSibling.value = this.value">
                                                <input type="text" 
                                                       name="color[<?= htmlspecialchars($color_key) ?>]"
                                                       value="<?= htmlspecialchars($setting_value) ?>"
                                                       class="flex-1 rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                       onchange="this.previousElementSibling.value = this.value">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تب لوگو -->
                    <div id="content-logo" class="tab-content hidden">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-4">🖼️ مدیریت لوگو</h3>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        آدرس لوگو (URL)
                                    </label>
                                    <input type="text" 
                                           name="logo[logo_url]" 
                                           value="<?= htmlspecialchars(get_webapp_setting('logo_url')) ?>"
                                           placeholder="https://example.com/logo.png"
                                           class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="mt-1 text-sm text-gray-500">یا فایل را آپلود کنید:</p>
                                    <input type="file" 
                                           name="logo_file" 
                                           accept="image/*"
                                           class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            عرض لوگو (px)
                                        </label>
                                        <input type="text" 
                                               name="logo[logo_width]" 
                                               value="<?= htmlspecialchars(get_webapp_setting('logo_width', '150')) ?>"
                                               class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            ارتفاع لوگو (px)
                                        </label>
                                        <input type="text" 
                                               name="logo[logo_height]" 
                                               value="<?= htmlspecialchars(get_webapp_setting('logo_height', 'auto')) ?>"
                                               class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                                <?php if (get_webapp_setting('logo_url')): ?>
                                    <div class="mt-4">
                                        <p class="text-sm font-medium text-gray-700 mb-2">پیش‌نمایش:</p>
                                        <img src="<?= htmlspecialchars(get_webapp_setting('logo_url')) ?>" 
                                             alt="لوگو" 
                                             class="max-w-xs h-auto rounded-lg border border-gray-300">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- تب منوها -->
                    <div id="content-menu" class="tab-content hidden">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-4">📋 مدیریت منوها</h3>
                            <div id="menu-items-container" class="space-y-4">
                                <?php foreach ($menu_items as $index => $item): ?>
                                    <div class="menu-item border border-gray-300 rounded-lg p-4" data-index="<?= $index ?>">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">عنوان</label>
                                                <input type="text" 
                                                       name="menu_items[<?= $index ?>][title]" 
                                                       value="<?= htmlspecialchars($item['title'] ?? '') ?>"
                                                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">لینک</label>
                                                <input type="text" 
                                                       name="menu_items[<?= $index ?>][url]" 
                                                       value="<?= htmlspecialchars($item['url'] ?? '') ?>"
                                                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">آیکون (emoji)</label>
                                                <input type="text" 
                                                       name="menu_items[<?= $index ?>][icon]" 
                                                       value="<?= htmlspecialchars($item['icon'] ?? '') ?>"
                                                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        <button type="button" onclick="removeMenuItem(<?= $index ?>)" class="mt-2 text-red-600 hover:text-red-800 text-sm">حذف</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addMenuItem()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                ➕ افزودن آیتم منو
                            </button>
                            <input type="hidden" name="menu_items_json" id="menu_items_json" value='<?= htmlspecialchars($menu_items_json) ?>'>
                        </div>
                    </div>

                    <!-- تب صفحه اصلی -->
                    <div id="content-homepage" class="tab-content hidden">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-4">🏠 مدیریت صفحه اصلی</h3>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        نوع چیدمان
                                    </label>
                                    <select name="homepage[homepage_layout]" 
                                            class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="default" <?= get_webapp_setting('homepage_layout') == 'default' ? 'selected' : '' ?>>پیش‌فرض</option>
                                        <option value="grid" <?= get_webapp_setting('homepage_layout') == 'grid' ? 'selected' : '' ?>>شبکه‌ای</option>
                                        <option value="list" <?= get_webapp_setting('homepage_layout') == 'list' ? 'selected' : '' ?>>لیستی</option>
                                    </select>
                                </div>
                                <div class="space-y-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_popular]" 
                                               value="1"
                                               <?= get_webapp_setting('show_popular', '1') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش محبوب‌ترین‌ها</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_latest]" 
                                               value="1"
                                               <?= get_webapp_setting('show_latest', '1') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش جدیدترین‌ها</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_categories]" 
                                               value="1"
                                               <?= get_webapp_setting('show_categories', '1') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش دسته‌بندی‌ها</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_series]" 
                                               value="1"
                                               <?= get_webapp_setting('show_series', '0') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش سریال‌ها</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_movies]" 
                                               value="1"
                                               <?= get_webapp_setting('show_movies', '0') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش فیلم‌ها</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_korean]" 
                                               value="1"
                                               <?= get_webapp_setting('show_korean', '0') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش کره‌ای</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_turkish]" 
                                               value="1"
                                               <?= get_webapp_setting('show_turkish', '0') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش ترکیه‌ای</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_anime]" 
                                               value="1"
                                               <?= get_webapp_setting('show_anime', '0') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش انیمه</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               name="homepage[show_animation]" 
                                               value="1"
                                               <?= get_webapp_setting('show_animation', '0') == '1' ? 'checked' : '' ?>
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">نمایش انیمیشن</span>
                                    </label>
                                </div>
                                
                                <!-- صفحه ساز اختصاصی -->
                                <div class="mt-8 pt-8 border-t border-gray-300">
                                    <h4 class="text-lg font-semibold mb-4">📝 صفحه ساز اختصاصی (HTML)</h4>
                                    <p class="text-sm text-gray-600 mb-4">
                                        می‌توانید کد HTML سفارشی خود را برای نمایش در صفحه اصلی وارد کنید.
                                    </p>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            کد HTML سفارشی
                                        </label>
                                        <textarea name="homepage[homepage_custom_html]" 
                                                  rows="15"
                                                  placeholder="مثال: &lt;div class='custom-section'&gt;&lt;h2&gt;عنوان سفارشی&lt;/h2&gt;&lt;p&gt;متن سفارشی&lt;/p&gt;&lt;/div&gt;"
                                                  class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"><?= htmlspecialchars(get_webapp_setting('homepage_custom_html', '')) ?></textarea>
                                        <p class="mt-2 text-xs text-gray-500">
                                            💡 می‌توانید از HTML، CSS و JavaScript استفاده کنید. این کد در ابتدای صفحه اصلی نمایش داده می‌شود.
                                        </p>
                                    </div>
                                    <div class="mt-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="homepage[enable_custom_html]" 
                                                   value="1"
                                                   <?= get_webapp_setting('enable_custom_html', '0') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">فعال کردن صفحه ساز اختصاصی</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تب فیلترها -->
                    <div id="content-filters" class="tab-content hidden">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-4">🔍 مدیریت فیلترها</h3>
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="flex items-center mb-2">
                                            <input type="checkbox" 
                                                   name="filters[filter_popular_enabled]" 
                                                   value="1"
                                                   <?= get_webapp_setting('filter_popular_enabled', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">فعال بودن فیلتر محبوب‌ترین</span>
                                        </label>
                                        <input type="number" 
                                               name="filters[popular_limit]" 
                                               value="<?= htmlspecialchars(get_webapp_setting('popular_limit', '20')) ?>"
                                               placeholder="تعداد"
                                               class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="flex items-center mb-2">
                                            <input type="checkbox" 
                                                   name="filters[filter_most_viewed_enabled]" 
                                                   value="1"
                                                   <?= get_webapp_setting('filter_most_viewed_enabled', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">فعال بودن فیلتر پر بازدیدترین</span>
                                        </label>
                                        <input type="number" 
                                               name="filters[most_viewed_limit]" 
                                               value="<?= htmlspecialchars(get_webapp_setting('most_viewed_limit', '20')) ?>"
                                               placeholder="تعداد"
                                               class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label class="flex items-center mb-2">
                                            <input type="checkbox" 
                                                   name="filters[filter_latest_enabled]" 
                                                   value="1"
                                                   <?= get_webapp_setting('filter_latest_enabled', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm font-medium text-gray-700">فعال بودن فیلتر جدیدترین</span>
                                        </label>
                                        <input type="number" 
                                               name="filters[latest_limit]" 
                                               value="<?= htmlspecialchars(get_webapp_setting('latest_limit', '20')) ?>"
                                               placeholder="تعداد"
                                               class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تب حساب کاربری -->
                    <div id="content-user_account" class="tab-content hidden">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-xl font-semibold mb-4">👤 مدیریت حساب کاربری</h3>
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-semibold mb-4 text-gray-800">تنظیمات صفحه پروفایل</h4>
                                    <div class="space-y-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[show_profile_page]" 
                                                   value="1"
                                                   <?= get_webapp_setting('show_profile_page', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">نمایش صفحه پروفایل</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[show_profile_avatar]" 
                                                   value="1"
                                                   <?= get_webapp_setting('show_profile_avatar', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">نمایش عکس پروفایل</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[show_profile_stats]" 
                                                   value="1"
                                                   <?= get_webapp_setting('show_profile_stats', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">نمایش آمار بازدیدها</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[show_profile_recent_views]" 
                                                   value="1"
                                                   <?= get_webapp_setting('show_profile_recent_views', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">نمایش آخرین بازدیدها</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[show_profile_vip_status]" 
                                                   value="1"
                                                   <?= get_webapp_setting('show_profile_vip_status', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">نمایش وضعیت VIP</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="pt-6 border-t border-gray-300">
                                    <h4 class="text-lg font-semibold mb-4 text-gray-800">تنظیمات احراز هویت</h4>
                                    <div class="space-y-4">
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[require_login]" 
                                                   value="1"
                                                   <?= get_webapp_setting('require_login', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">اجبار به ورود برای دسترسی به مینی اپ</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="user_account[show_login_page]" 
                                                   value="1"
                                                   <?= get_webapp_setting('show_login_page', '1') == '1' ? 'checked' : '' ?>
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">نمایش صفحه ورود</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-gray-300">
                                    <h4 class="text-lg font-semibold mb-4 text-gray-800">تنظیمات نمایش اطلاعات</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                تعداد آخرین بازدیدها در پروفایل
                                            </label>
                                            <input type="number" 
                                                   name="user_account[profile_recent_views_limit]" 
                                                   value="<?= htmlspecialchars(get_webapp_setting('profile_recent_views_limit', '20')) ?>"
                                                   min="1"
                                                   max="100"
                                                   class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- دکمه ذخیره -->
                    <div class="fixed bottom-5 left-10">
                        <button type="submit" name="webapp_update" class="py-2 px-4 bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 focus:ring-offset-indigo-200 text-white transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-lg">
                            💾 ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let menuItemIndex = <?= count($menu_items) ?>;

function showTab(tabName) {
    // پنهان کردن تمام تب‌ها
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // نمایش تب انتخاب شده
    document.getElementById('content-' + tabName).classList.remove('hidden');
    const button = document.getElementById('tab-' + tabName);
    button.classList.add('active', 'border-blue-500', 'text-blue-600');
    button.classList.remove('border-transparent', 'text-gray-500');
}

function addMenuItem() {
    const container = document.getElementById('menu-items-container');
    const newItem = document.createElement('div');
    newItem.className = 'menu-item border border-gray-300 rounded-lg p-4';
    newItem.setAttribute('data-index', menuItemIndex);
    newItem.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">عنوان</label>
                <input type="text" name="menu_items[${menuItemIndex}][title]" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">لینک</label>
                <input type="text" name="menu_items[${menuItemIndex}][url]" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">آیکون (emoji)</label>
                <input type="text" name="menu_items[${menuItemIndex}][icon]" class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
        <button type="button" onclick="removeMenuItem(${menuItemIndex})" class="mt-2 text-red-600 hover:text-red-800 text-sm">حذف</button>
    `;
    container.appendChild(newItem);
    menuItemIndex++;
    updateMenuItemsJson();
}

function removeMenuItem(index) {
    const item = document.querySelector(`.menu-item[data-index="${index}"]`);
    if (item) {
        item.remove();
        updateMenuItemsJson();
    }
}

function updateMenuItemsJson() {
    const items = [];
    document.querySelectorAll('.menu-item').forEach(item => {
        const index = item.getAttribute('data-index');
        const title = item.querySelector(`input[name="menu_items[${index}][title]"]`)?.value || '';
        const url = item.querySelector(`input[name="menu_items[${index}][url]"]`)?.value || '';
        const icon = item.querySelector(`input[name="menu_items[${index}][icon]"]`)?.value || '';
        if (title || url) {
            items.push({ title, url, icon });
        }
    });
    document.getElementById('menu_items_json').value = JSON.stringify(items);
}

// به‌روزرسانی JSON هنگام تغییر فیلدها
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.menu-item input').forEach(input => {
        input.addEventListener('input', updateMenuItemsJson);
    });
});
</script>

