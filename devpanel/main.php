<?php
// ============================================================
// فایل اصلی پنل مدیریت ربات تلگرام
// ============================================================

// بارگذاری تمام بخش‌های پنل مدیریت
require_once __DIR__ . '/dashboard.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/products_add.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/tickets.php';
require_once __DIR__ . '/sendtoall.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/callbacks.php';

// منوی اصلی مدیریت
function show_admin_main_menu($userid)
{
    global $telegram;
    
    $msg = "⚙️ <b>پنل مدیریت ربات</b>\n\n";
    $msg .= "لطفاً یکی از گزینه‌های زیر را انتخاب کنید:";
    
    $keyboard = [
        [
            ['text' => '📊 داشبورد', 'callback_data' => 'admin_dashboard'],
            ['text' => '🎬 محصولات', 'callback_data' => 'admin_products']
        ],
        [
            ['text' => '👥 کاربران', 'callback_data' => 'admin_users'],
            ['text' => '📁 دسته‌بندی‌ها', 'callback_data' => 'admin_categories']
        ],
        [
            ['text' => '⚙️ تنظیمات', 'callback_data' => 'admin_settings'],
            ['text' => '📢 ارسال همگانی', 'callback_data' => 'admin_sendtoall']
        ],
        [
            ['text' => '🎫 تیکت‌ها', 'callback_data' => 'admin_tickets'],
            ['text' => '👤 مدیریت ادمین‌ها', 'callback_data' => 'admin_admins']
        ],
        [
            ['text' => '◀️ بازگشت به منو', 'callback_data' => 'back_to_cats']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

