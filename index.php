<?php
define('INDEX', TRUE);
include_once 'config.php';
include_once 'jdf.php';
include_once 'telegram.php';
include_once 'initial.php'; // باید قبل از btns.php باشد (چون btns.php از تابع options() استفاده می‌کند)
include_once 'btns.php';
include_once 'channels_system.php';
include_once 'bot_channels_management.php';
include_once 'episode_qualities_handler.php';
include_once 'season_episodes_handler.php';
require_once __DIR__ . '/devpanel/main.php'; // پنل مدیریت داخل ربات

// Inline Query Handler - برای جستجوی سریع با @botname
$inline_query = isset($result->inline_query) ? $result->inline_query : null;
if ($inline_query) {
    handle_inline_query($inline_query);
    exit; // بعد از پردازش inline query، بقیه کد را اجرا نکن
}

// پنل مدیریت (فقط برای ادمین) - باید قبل از callback queries باشد
if(isset($text) && ($text == $admin_panel_btn || $text == '⚙️ پنل مدیریت')){
    global $admin;
    if($userid == $admin){
        show_admin_main_menu($userid);
        exit; // جلوگیری از اجرای handlers دیگر
    } else {
        $msg = "❌ شما دسترسی به این بخش را ندارید.";
        $telegram->sendMessageCURL($userid, $msg, $main_keyboard);
        exit;
    }
}

// CallBack queries - بهینه‌سازی: اول callback های سریال را بررسی کن (احتمال بیشتر)
if (isset($cdata) && !empty($cdata)) {
    // اول callback های پنل مدیریت را بررسی کن
    if (preg_match('/^admin_/', $cdata)) {
        handle_admin_panel_callbacks();
    }
    // سپس callback بازگشت به دسته‌بندی‌ها/فیلم‌ها/سریال‌ها را بررسی کن (باید اول باشد)
    elseif (preg_match('/^back_to_cats$|^back_to_movies$|^back_to_series$/', $cdata)) {
        back_to_cats(); // بازگشت به دسته‌بندی‌ها/فیلم‌ها/سریال‌ها
    }
    // سپس callback های سریال را بررسی کن
    elseif (preg_match('/^season_episodes#|^episode_qualities#/', $cdata)) {
        show_season_episodes(); // نمایش قسمت‌های یک فصل
        show_episode_qualities(); // نمایش کیفیت‌های قسمت سریال
    } 
    // سپس callback دسته‌بندی‌ها و pagination را بررسی کن
    elseif (preg_match('/^cat#|^page#/', $cdata)) {
        show_selected_category_products(); // نمایش محصولات دسته‌بندی (شامل pagination)
    }
    // سپس بقیه callback ها
    else {
        inline_close_btn();
        most_popular_products();
        latest_products();
        show_product();
        download_file();
        get_phone();
        send_product_by_id();
        submit_ticket();
        submit_search();
        show_search_qualities(); // نمایش کیفیت‌ها بعد از جستجو
        handle_channel_check_callback(); // بررسی عضویت در کانال‌ها
        handle_channel_management_callback(); // مدیریت کانال‌ها توسط ادمین
    }
} else {
    // اگر callback نیست، فقط توابع مربوط به message را اجرا کن
    inline_close_btn();
    get_phone();
    send_product_by_id();
    submit_ticket();
    submit_search();
    handle_channel_check_callback();
    handle_channel_management_callback();
}

// BOT START 
if ($text == '/start') {
    if (is_verified($userid)) {
        // بررسی عضویت در کانال‌ها
        $channels_ok = check_channels_after_verification($userid);
        if ($channels_ok === true) {
            $msg = $welcome_msg;
            // اگر ادمین است، از کیبورد ادمین استفاده کن
            $keyboard = ($userid == $admin) ? $admin_keyboard : $main_keyboard;
            $telegram->sendMessageCURL($userid, $msg, $keyboard);
        } else {
            show_required_channels($userid);
        }
    } else {
        request_phone();
    }
}
// BACK TO HOME
if ($text == $home) {
    if (is_verified($userid)) {
        // بررسی عضویت در کانال‌ها
        $channels_ok = check_channels_after_verification($userid);
        if ($channels_ok === true) {
            $msg = $main_menu_msg;
            // اگر ادمین است، از کیبورد ادمین استفاده کن
            $keyboard = ($userid == $admin) ? $admin_keyboard : $main_keyboard;
            $telegram->sendMessageCURL($userid, $msg, $keyboard);
        } else {
            show_required_channels($userid);
        }
    } else {
        request_phone();
    }
}

// فروشگاه و VIP حذف شده - دیگر استفاده نمی‌شود

// VIP Subscription (بزودی فعال خواهد شد)
if($text == '🌟 اشتراک ویژه' || $text == $vip_member){
    if (is_verified($userid)) {
        $msg = "🌟 <b>اشتراک ویژه</b>\n\n";
        $msg .= "سیستم اشتراک ویژه به زودی فعال خواهد شد.\n\n";
        $msg .= "با فعال شدن این سیستم، می‌توانید به تمام فیلم‌ها و سریال‌ها دسترسی داشته باشید.";
        $keyboard = ($userid == $admin) ? $admin_keyboard : $main_keyboard;
        $telegram->sendMessageCURL($userid, $msg, $keyboard);
    } else {
        request_phone();
    }
}

// Ticket and support
if($text == $send_ticket){
    if (is_verified($userid)) {
      ticket();
    } else {
        request_phone();
    }
}

// Search in products
if($text == $search_products){
    if (is_verified($userid)) {
        init_search();
    } else {
        request_phone();
    }
}


// Show movies (categories)
if($text == $movies_btn){
    if (is_verified($userid)) {
        show_movies_list();
    } else {
        request_phone();
    }
}

// Show series (categories)
if($text == $series_btn){
    if (is_verified($userid)) {
        show_series_list();
    } else {
        request_phone();
    }
}

// مدیریت کانال‌ها توسط ادمین (فقط در ربات)
if($text == '/channels' || $text == '📢 مدیریت کانال‌ها'){
    global $admin;
    if($userid == $admin){
        show_channels_menu($userid);
    }
}

// پردازش افزودن کانال توسط ادمین
if($userid == $admin){
    process_add_channel($userid, $text);
}

// User's profile information
if($text == $account){
    if(is_verified($userid)){
        account_info();
    }else{
        request_phone();
    }
}

// پردازش ارسال همگانی (پنل مدیریت)
if($userid == $admin && file_exists('users/' . $userid . '.txt')){
    $user_status = trim(file_get_contents('users/' . $userid . '.txt'));
    
    // ارسال همگانی
    if($user_status == 'admin_sendtoall'){
        if($text == '/cancel'){
            file_put_contents('users/' . $userid . '.txt', ' ');
            $msg = "❌ ارسال همگانی لغو شد.";
            $telegram->sendMessageCURL($userid, $msg, $admin_keyboard);
        } else {
            process_admin_sendtoall($userid, $text);
        }
        exit;
    }
    
    // افزودن دسته‌بندی
    if($user_status == 'admin_add_category'){
        if($text == '/cancel'){
            file_put_contents('users/' . $userid . '.txt', ' ');
            $msg = "❌ افزودن دسته‌بندی لغو شد.";
            $telegram->sendMessageCURL($userid, $msg, $admin_keyboard);
        } else {
            save_admin_category($userid, $text);
        }
        exit;
    }
    
    // ویرایش دسته‌بندی
    if(preg_match('/^admin_edit_category#(\d+)$/', $user_status, $matches)){
        if($text == '/cancel'){
            file_put_contents('users/' . $userid . '.txt', ' ');
            $msg = "❌ ویرایش دسته‌بندی لغو شد.";
            $telegram->sendMessageCURL($userid, $msg, $admin_keyboard);
        } else {
            save_admin_category_edit($userid, intval($matches[1]), $text);
        }
        exit;
    }
    
    // افزودن محصول - دریافت نام/IMDb و ذخیره مستقیم
    if($user_status == 'admin_add_product_step1'){
        if($text == '/cancel'){
            cancel_admin_add_product($userid);
        } else {
            process_admin_add_product_step1($userid, $text);
        }
        exit;
    }
    
    // ویرایش تنظیمات
    if(preg_match('/^admin_edit_option#(\d+)$/', $user_status, $matches)){
        if($text == '/cancel'){
            file_put_contents('users/' . $userid . '.txt', ' ');
            // فقط منوی تنظیمات را نمایش بده (بدون پیام جداگانه)
            show_admin_settings_menu($userid);
        } else {
            // بررسی اینکه $text تعریف شده و خالی نیست
            if (isset($text) && !empty(trim($text))) {
                save_admin_option($userid, intval($matches[1]), $text);
            } else {
                // اگر متن خالی است، خطا نمایش بده
                bot('sendMessage', [
                    'chat_id' => $userid,
                    'text' => '❌ خطا: لطفاً یک مقدار معتبر وارد کنید. مقدار نمی‌تواند خالی باشد.',
                    'parse_mode' => 'HTML'
                ]);
                // نمایش مجدد فرم ویرایش
                show_admin_edit_option($userid, intval($matches[1]));
            }
        }
        exit;
    }
}

// سیستم آپلود از ربات حذف شده - فقط از پنل تحت وب

