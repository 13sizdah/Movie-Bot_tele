<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

if (isset($_POST['webapp_update'])) {
    // بررسی وجود جدول
    try {
        $check_table = $db->query("SHOW TABLES LIKE 'sp_webapp_settings'");
        if ($check_table->rowCount() == 0) {
            // اگر جدول وجود ندارد، ایجاد کن
            $create_table_sql = "CREATE TABLE IF NOT EXISTS `sp_webapp_settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) NOT NULL,
                `setting_value` text NOT NULL,
                `setting_type` varchar(50) NOT NULL DEFAULT 'text',
                `category` varchar(50) NOT NULL DEFAULT 'general',
                `description` text DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $db->exec($create_table_sql);
        }
    } catch (PDOException $e) {
        // خطا را نادیده بگیر
    }
    
    try {
        // پردازش رنگ‌بندی
        if (isset($_POST['color']) && is_array($_POST['color'])) {
            foreach ($_POST['color'] as $key => $value) {
                $value = trim($value);
                if (!empty($value)) {
                    // تعیین دسته بر اساس نام کلید
                    $category = (strpos($key, 'dark_') === 0) ? 'colors_dark' : 'colors';
                    $description = '';
                    if (strpos($key, 'primary_color') !== false) {
                        $description = (strpos($key, 'dark_') === 0) ? 'رنگ اصلی (دارک)' : 'رنگ اصلی';
                    } elseif (strpos($key, 'secondary_color') !== false) {
                        $description = (strpos($key, 'dark_') === 0) ? 'رنگ ثانویه (دارک)' : 'رنگ ثانویه';
                    } elseif (strpos($key, 'background_color') !== false) {
                        $description = (strpos($key, 'dark_') === 0) ? 'رنگ پس‌زمینه (دارک)' : 'رنگ پس‌زمینه';
                    } elseif (strpos($key, 'text_color') !== false) {
                        $description = (strpos($key, 'dark_') === 0) ? 'رنگ متن (دارک)' : 'رنگ متن';
                    } elseif (strpos($key, 'accent_color') !== false) {
                        $description = (strpos($key, 'dark_') === 0) ? 'رنگ تاکیدی (دارک)' : 'رنگ تاکیدی';
                    } elseif (strpos($key, 'border_color') !== false) {
                        $description = (strpos($key, 'dark_') === 0) ? 'رنگ حاشیه (دارک)' : 'رنگ حاشیه';
                    }
                    
                    $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category, description) 
                            VALUES (:key, :value, 'color', :category, :description)
                            ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                    $stmt->bindValue(':value', $value, PDO::PARAM_STR);
                    $stmt->bindValue(':value2', $value, PDO::PARAM_STR);
                    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
                    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
                    if (!$stmt->execute()) {
                        error_log("Failed to save color setting: $key = $value");
                        throw new PDOException("خطا در ذخیره رنگ: $key");
                    }
                }
            }
        }

        // پردازش لوگو
        if (isset($_POST['logo']) && is_array($_POST['logo'])) {
            // آپلود فایل لوگو
            if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] == 0) {
                $upload_dir = '../web/assets/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = 'logo_' . time() . '_' . basename($_FILES['logo_file']['name']);
                $file_path = $upload_dir . $file_name;
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($_FILES['logo_file']['type'], $allowed_types)) {
                    if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $file_path)) {
                        // استفاده از BASEURI از config.php یا مقدار پیش‌فرض
                        $base_uri = defined('BASEURI') ? BASEURI : 'https://13ishere.shop/8';
                        $_POST['logo']['logo_url'] = $base_uri . '/web/assets/uploads/' . $file_name;
                    }
                }
            }
            
            foreach ($_POST['logo'] as $key => $value) {
                $value = trim($value);
                $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category) 
                        VALUES (:key, :value, 'text', 'logo')
                        ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                $stmt->bindValue(':value', $value, PDO::PARAM_STR);
                $stmt->bindValue(':value2', $value, PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        // پردازش منوها
        if (isset($_POST['menu_items']) && is_array($_POST['menu_items'])) {
            $menu_items = [];
            foreach ($_POST['menu_items'] as $item) {
                if (!empty($item['title']) || !empty($item['url'])) {
                    $menu_items[] = [
                        'title' => trim($item['title'] ?? ''),
                        'url' => trim($item['url'] ?? ''),
                        'icon' => trim($item['icon'] ?? '')
                    ];
                }
            }
            $menu_json = json_encode($menu_items, JSON_UNESCAPED_UNICODE);
            
            $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category) 
                    VALUES ('menu_items', :value, 'json', 'menu')
                    ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':value', $menu_json, PDO::PARAM_STR);
            $stmt->bindValue(':value2', $menu_json, PDO::PARAM_STR);
            $stmt->execute();
        }

        // پردازش صفحه اصلی
        // لیست checkbox های صفحه اصلی
        $homepage_checkboxes = ['show_popular', 'show_latest', 'show_categories', 'show_series', 'show_movies', 'show_korean', 'show_turkish', 'show_anime', 'show_animation', 'enable_custom_html'];
        
        // ابتدا checkbox های تیک نخورده را به '0' تنظیم کن
        foreach ($homepage_checkboxes as $checkbox_key) {
            if (!isset($_POST['homepage'][$checkbox_key])) {
                $_POST['homepage'][$checkbox_key] = '0';
            }
        }
        
        if (isset($_POST['homepage']) && is_array($_POST['homepage'])) {
            foreach ($_POST['homepage'] as $key => $value) {
                if ($key == 'homepage_custom_html') {
                    // برای HTML، مقدار را بدون trim نگه دار (ممکن است فاصله‌های مهم داشته باشد)
                    $value = $value;
                    $type = 'text';
                } else {
                    $value = is_array($value) ? (in_array('1', $value) ? '1' : '0') : trim($value);
                    $type = ($key == 'homepage_layout') ? 'text' : 'boolean';
                }
                
                $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category) 
                        VALUES (:key, :value, :type, 'homepage')
                        ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                $stmt->bindValue(':value', $value, PDO::PARAM_STR);
                $stmt->bindValue(':value2', $value, PDO::PARAM_STR);
                $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        // پردازش فیلترها
        // لیست checkbox های فیلترها
        $filter_checkboxes = ['filter_popular_enabled', 'filter_most_viewed_enabled', 'filter_latest_enabled'];
        
        // ابتدا checkbox های تیک نخورده را به '0' تنظیم کن
        if (isset($_POST['filters']) && is_array($_POST['filters'])) {
            foreach ($filter_checkboxes as $checkbox_key) {
                if (!isset($_POST['filters'][$checkbox_key])) {
                    $_POST['filters'][$checkbox_key] = '0';
                }
            }
        }
        
        if (isset($_POST['filters']) && is_array($_POST['filters'])) {
            foreach ($_POST['filters'] as $key => $value) {
                if (strpos($key, '_enabled') !== false) {
                    $value = is_array($value) ? (in_array('1', $value) ? '1' : '0') : ($value == '1' ? '1' : '0');
                    $type = 'boolean';
                } else {
                    $value = trim($value);
                    $type = 'text';
                }
                
                $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category) 
                        VALUES (:key, :value, :type, 'filters')
                        ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                $stmt->bindValue(':value', $value, PDO::PARAM_STR);
                $stmt->bindValue(':value2', $value, PDO::PARAM_STR);
                $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        // پردازش حساب کاربری
        // لیست checkbox های حساب کاربری
        $user_account_checkboxes = ['show_profile_page', 'show_profile_avatar', 'show_profile_stats', 'show_profile_recent_views', 'show_profile_vip_status', 'require_login', 'show_login_page'];
        
        // ابتدا checkbox های تیک نخورده را به '0' تنظیم کن
        if (isset($_POST['user_account']) && is_array($_POST['user_account'])) {
            foreach ($user_account_checkboxes as $checkbox_key) {
                if (!isset($_POST['user_account'][$checkbox_key])) {
                    $_POST['user_account'][$checkbox_key] = '0';
                }
            }
        }
        
        if (isset($_POST['user_account']) && is_array($_POST['user_account'])) {
            foreach ($_POST['user_account'] as $key => $value) {
                if ($key == 'profile_recent_views_limit') {
                    $value = intval(trim($value));
                    $type = 'text';
                } else {
                    $value = is_array($value) ? (in_array('1', $value) ? '1' : '0') : trim($value);
                    $type = 'boolean';
                }
                
                $sql = "INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category) 
                        VALUES (:key, :value, :type, 'user_account')
                        ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = CURRENT_TIMESTAMP";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':key', $key, PDO::PARAM_STR);
                $stmt->bindValue(':value', $value, PDO::PARAM_STR);
                $stmt->bindValue(':value2', $value, PDO::PARAM_STR);
                $stmt->bindValue(':type', $type, PDO::PARAM_STR);
                $stmt->execute();
            }
        }

        // هدایت به صفحه با پیام موفقیت
        header("Location: webapp.php?updated=1");
        exit;
        
    } catch (PDOException $e) {
        // ثبت خطا در error_log
        error_log("Error in webapp_action.php: " . $e->getMessage());
        error_log("SQL Error Info: " . print_r($e->errorInfo, true));
        error_log("Stack Trace: " . $e->getTraceAsString());
        
        // هدایت به صفحه با پیام خطا
        $error_message = "خطا در ذخیره تنظیمات: " . $e->getMessage();
        header("Location: webapp.php?error=" . urlencode($error_message));
        exit;
    }
}

// نمایش پیام موفقیت یا خطا
if (isset($_GET['updated'])) {
    echo '<div id="success-message" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            ✅ تنظیمات با موفقیت ذخیره شد
          </div>';
    echo '<script>setTimeout(function(){ 
        var msg = document.getElementById("success-message");
        if(msg) msg.remove();
    }, 3000);</script>';
}

if (isset($_GET['error'])) {
    echo '<div id="error-message" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            ❌ خطا در ذخیره تنظیمات: ' . htmlspecialchars($_GET['error']) . '
          </div>';
    echo '<script>setTimeout(function(){ 
        var msg = document.getElementById("error-message");
        if(msg) msg.remove();
    }, 5000);</script>';
}
?>

