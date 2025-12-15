<?php
// ============================================================
// ارسال همگانی
// ============================================================

// منوی ارسال همگانی
function show_admin_sendtoall_menu($userid)
{
    global $telegram;
    
    $msg = "📢 <b>ارسال همگانی</b>\n\n";
    $msg .= "پیام خود را ارسال کنید تا به همه کاربران ارسال شود.\n\n";
    $msg .= "⚠️ برای لغو، /cancel را ارسال کنید.";
    
    // ذخیره وضعیت ارسال همگانی
    file_put_contents('users/' . $userid . '.txt', 'admin_sendtoall');
    
    $keyboard = [[
        ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_sendtoall']
    ]];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// پردازش ارسال همگانی
function process_admin_sendtoall($userid, $message_text)
{
    global $telegram;
    
    $sql = "SELECT userid FROM sp_users WHERE verified = 1";
    $users = $telegram->db->query($sql)->fetchAll();
    
    $sent = 0;
    $failed = 0;
    
    foreach ($users as $user) {
        try {
            bot('sendMessage', [
                'chat_id' => $user['userid'],
                'text' => $message_text,
                'parse_mode' => 'HTML'
            ]);
            $sent++;
            // تأخیر کوتاه برای جلوگیری از rate limit
            usleep(50000); // 50 میلی‌ثانیه
        } catch (Exception $e) {
            $failed++;
        }
    }
    
    // پاک کردن وضعیت
    file_put_contents('users/' . $userid . '.txt', ' ');
    
    $msg = "✅ <b>ارسال همگانی انجام شد</b>\n\n";
    $msg .= "✅ ارسال موفق: <code>$sent</code>\n";
    if ($failed > 0) {
        $msg .= "❌ ارسال ناموفق: <code>$failed</code>\n";
    }
    
    $keyboard = [[
        ['text' => '◀️ بازگشت به منوی مدیریت', 'callback_data' => 'admin_main_menu']
    ]];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

