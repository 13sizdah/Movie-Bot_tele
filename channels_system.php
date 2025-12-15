<?php
// ============================================================
// سیستم عضویت در کانال‌های اجباری
// ============================================================

// بررسی عضویت کاربر در کانال‌ها
function check_channels_membership($userid)
{
    global $telegram;
    
    // دریافت کانال‌های فعال
    $sql = "SELECT * FROM sp_channels WHERE status=1 ORDER BY order_num ASC";
    $channels = $telegram->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($channels)) {
        // اگر کانالی وجود ندارد، کاربر را تایید می‌کنیم
        return true;
    }
    
    $not_joined = [];
    foreach ($channels as $channel) {
        $channel_id = !empty($channel['channel_id']) ? $channel['channel_id'] : '@' . $channel['channel_username'];
        
        // بررسی عضویت
        $member_status = $telegram->getChatMember($channel_id, $userid);
        
        // اگر عضو نیست یا banned است
        if ($member_status != 'member' && $member_status != 'administrator' && $member_status != 'creator') {
            $not_joined[] = $channel;
        }
    }
    
    if (empty($not_joined)) {
        // همه کانال‌ها عضو شده
        $sql = "UPDATE sp_users SET channels_joined=1 WHERE userid=$userid";
        $telegram->db->query($sql);
        return true;
    }
    
    return $not_joined;
}

// نمایش کانال‌های اجباری
function show_required_channels($userid)
{
    global $telegram;
    
    $not_joined = check_channels_membership($userid);
    
    if ($not_joined === true) {
        // همه کانال‌ها عضو شده
        return true;
    }
    
    $msg = "⚠️ <b>برای ادامه، لطفاً در کانال‌های زیر عضو شوید:</b>\n\n";
    
    $keyboard = [];
    foreach ($not_joined as $channel) {
        $channel_link = !empty($channel['channel_link']) ? $channel['channel_link'] : 'https://t.me/' . $channel['channel_username'];
        $channel_title = !empty($channel['channel_title']) ? $channel['channel_title'] : $channel['channel_username'];
        
        $keyboard[] = [['text' => "📢 " . $channel_title, 'url' => $channel_link]];
        $msg .= "📢 " . $channel_title . "\n";
    }
    
    $msg .= "\n✅ بعد از عضویت، روی دکمه زیر کلیک کنید:";
    
    $keyboard[] = [['text' => '✅ بررسی مجدد', 'callback_data' => 'check_channels']];
    
    bot('sendmessage', [
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text' => $msg,
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
    
    return false;
}

// پردازش callback بررسی کانال‌ها
function handle_channel_check_callback()
{
    global $cdata, $cid, $cuserid, $telegram, $main_keyboard, $welcome_msg;
    
    if ($cdata == 'check_channels') {
        $not_joined = check_channels_membership($cuserid);
        
        if ($not_joined === true) {
            // همه کانال‌ها عضو شده
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '✅ شما در همه کانال‌ها عضو هستید!',
                'show_alert' => false
            ]);
            
            bot('editMessageText', [
                'chat_id' => $cuserid,
                'message_id' => $GLOBALS['cmsgid'],
                'parse_mode' => 'HTML',
                'text' => '✅ <b>تبریک!</b>\n\nشما در همه کانال‌ها عضو هستید. اکنون می‌توانید از ربات استفاده کنید.',
                'reply_markup' => json_encode([
                    'inline_keyboard' => []
                ])
            ]);
            
            // ارسال پیام خوش‌آمدگویی
            $telegram->sendMessageCURL($cuserid, $welcome_msg, $main_keyboard);
        } else {
            // هنوز بعضی کانال‌ها عضو نشده
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '❌ لطفاً در همه کانال‌ها عضو شوید',
                'show_alert' => true
            ]);
            
            // نمایش مجدد کانال‌ها
            show_required_channels($cuserid);
        }
    }
}

// بررسی عضویت بعد از تایید شماره
function check_channels_after_verification($userid)
{
    global $telegram;
    
    // بررسی اینکه آیا کاربر قبلاً کانال‌ها را تایید کرده
    $sql = "SELECT channels_joined FROM sp_users WHERE userid=$userid";
    $user = $telegram->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['channels_joined'] == 1) {
        // قبلاً تایید شده
        return true;
    }
    
    // بررسی عضویت
    return check_channels_membership($userid);
}

