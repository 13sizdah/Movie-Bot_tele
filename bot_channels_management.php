<?php
// ============================================================
// مدیریت کانال‌های اجباری از طریق ربات (فقط برای ادمین)
// ============================================================

// نمایش منوی مدیریت کانال‌ها
function show_channels_menu($userid)
{
    global $telegram, $admin;
    
    if ($userid != $admin) {
        return;
    }
    
    $sql = "SELECT * FROM sp_channels ORDER BY order_num ASC, id DESC";
    $channels = $telegram->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $msg = "📢 <b>مدیریت کانال‌های اجباری</b>\n\n";
    
    if (empty($channels)) {
        $msg .= "هیچ کانالی ثبت نشده است.\n\n";
    } else {
        $msg .= "کانال‌های موجود:\n\n";
        foreach ($channels as $index => $channel) {
            $status_icon = $channel['status'] == 1 ? '✅' : '❌';
            $status_text = $channel['status'] == 1 ? 'فعال' : 'غیرفعال';
            $msg .= ($index + 1) . ". $status_icon @" . htmlspecialchars($channel['channel_username']);
            if (!empty($channel['channel_title'])) {
                $msg .= " (" . htmlspecialchars($channel['channel_title']) . ")";
            }
            $msg .= " - $status_text\n";
        }
        $msg .= "\n";
    }
    
    $msg .= "لطفاً عملیات مورد نظر را انتخاب کنید:";
    
    $keyboard = [];
    $keyboard[] = [['text' => '➕ افزودن کانال', 'callback_data' => 'channel_add']];
    
    if (!empty($channels)) {
        $keyboard[] = [['text' => '✏️ ویرایش کانال', 'callback_data' => 'channel_edit']];
        $keyboard[] = [['text' => '🗑️ حذف کانال', 'callback_data' => 'channel_delete']];
        $keyboard[] = [['text' => '🔄 تغییر وضعیت', 'callback_data' => 'channel_toggle']];
    }
    
    $keyboard[] = [['text' => '◀️ بازگشت', 'callback_data' => 'admin_back']];
    
    bot('sendmessage', [
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text' => $msg,
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

// شروع افزودن کانال
function start_add_channel($userid)
{
    global $telegram, $admin;
    
    if ($userid != $admin) {
        return;
    }
    
    $msg = "➕ <b>افزودن کانال جدید</b>\n\n";
    $msg .= "لطفاً یوزرنیم کانال را ارسال کنید (بدون @)\n";
    $msg .= "مثال: mychannel";
    
    bot('sendmessage', [
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text' => $msg
    ]);
    
    // ذخیره وضعیت
    file_put_contents('users/' . $userid . '.txt', 'admin_add_channel_username');
}

// پردازش افزودن کانال
function process_add_channel($userid, $text)
{
    global $telegram, $admin;
    
    if ($userid != $admin) {
        return false;
    }
    
    // بررسی وجود فایل قبل از خواندن
    $status_file = 'users/' . $userid . '.txt';
    $status = '';
    if (file_exists($status_file)) {
        $status = @file_get_contents($status_file);
    }
    
    if ($status == 'admin_add_channel_username') {
        $channel_username = trim($text);
        $channel_username = ltrim($channel_username, '@');
        
        if (empty($channel_username)) {
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => '❌ یوزرنیم کانال نمی‌تواند خالی باشد. لطفاً دوباره ارسال کنید.'
            ]);
            return false;
        }
        
        // ذخیره یوزرنیم موقت
        file_put_contents('users/' . $userid . '_channel_temp.txt', $channel_username);
        file_put_contents('users/' . $userid . '.txt', 'admin_add_channel_link');
        
        $msg = "✅ یوزرنیم کانال ثبت شد: @$channel_username\n\n";
        $msg .= "لطفاً لینک دعوت کانال را ارسال کنید (اختیاری)\n";
        $msg .= "یا برای استفاده از لینک پیش‌فرض، /skip را ارسال کنید";
        
        bot('sendmessage', [
            'chat_id' => $userid,
            'text' => $msg
        ]);
        
        return false;
    }
    
    if ($status == 'admin_add_channel_link') {
        // بررسی وجود فایل قبل از خواندن
        $temp_file = 'users/' . $userid . '_channel_temp.txt';
        $channel_username = '';
        if (file_exists($temp_file)) {
            $channel_username = @file_get_contents($temp_file);
        }
        
        if (empty($channel_username)) {
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => '❌ خطا: اطلاعات کانال یافت نشد. لطفاً دوباره شروع کنید.'
            ]);
            @unlink('users/' . $userid . '.txt');
            return false;
        }
        $channel_link = trim($text);
        
        if ($channel_link == '/skip' || empty($channel_link)) {
            $channel_link = 'https://t.me/' . $channel_username;
        }
        
        // افزودن به دیتابیس
        $sql = "INSERT INTO sp_channels (channel_username, channel_link, status, order_num) 
                VALUES (:username, :link, 1, 0)";
        $stmt = $telegram->db->prepare($sql);
        $stmt->bindParam(':username', $channel_username);
        $stmt->bindParam(':link', $channel_link);
        
        if ($stmt->execute()) {
            // پاک کردن فایل‌های موقت
            @unlink('users/' . $userid . '_channel_temp.txt');
            file_put_contents('users/' . $userid . '.txt', ' ');
            
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => "✅ کانال با موفقیت افزوده شد!\n\nیوزرنیم: @$channel_username\nلینک: $channel_link"
            ]);
            
            show_channels_menu($userid);
            return true;
        } else {
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => '❌ خطا در افزودن کانال'
            ]);
            return false;
        }
    }
    
    return false;
}

// نمایش لیست کانال‌ها برای ویرایش/حذف
function show_channels_list_for_action($userid, $action_type)
{
    global $telegram, $admin;
    
    if ($userid != $admin) {
        return;
    }
    
    $sql = "SELECT * FROM sp_channels ORDER BY order_num ASC, id DESC";
    $channels = $telegram->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($channels)) {
        bot('sendmessage', [
            'chat_id' => $userid,
            'text' => '❌ هیچ کانالی ثبت نشده است'
        ]);
        return;
    }
    
    $msg = "📢 <b>انتخاب کانال</b>\n\n";
    $keyboard = [];
    
    foreach ($channels as $channel) {
        $status_icon = $channel['status'] == 1 ? '✅' : '❌';
        $channel_name = '@' . htmlspecialchars($channel['channel_username']);
        if (!empty($channel['channel_title'])) {
            $channel_name .= " (" . htmlspecialchars($channel['channel_title']) . ")";
        }
        
        $keyboard[] = [[
            'text' => "$status_icon $channel_name",
            'callback_data' => "channel_{$action_type}_" . $channel['id']
        ]];
    }
    
    $keyboard[] = [['text' => '◀️ بازگشت', 'callback_data' => 'channel_menu']];
    
    bot('sendmessage', [
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text' => $msg,
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

// پردازش callback های مدیریت کانال
function handle_channel_management_callback()
{
    global $cdata, $cuserid, $cid, $cmsgid, $telegram, $admin;
    
    if ($cuserid != $admin) {
        return;
    }
    
    // منوی اصلی
    if (preg_match('/channel_menu/', $cdata)) {
        bot('deleteMessage', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid
        ]);
        show_channels_menu($cuserid);
        return;
    }
    
    // افزودن کانال
    if (preg_match('/channel_add/', $cdata)) {
        bot('deleteMessage', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid
        ]);
        start_add_channel($cuserid);
        return;
    }
    
    // ویرایش کانال
    if (preg_match('/channel_edit/', $cdata)) {
        bot('deleteMessage', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid
        ]);
        show_channels_list_for_action($cuserid, 'edit');
        return;
    }
    
    // حذف کانال
    if (preg_match('/channel_delete/', $cdata)) {
        bot('deleteMessage', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid
        ]);
        show_channels_list_for_action($cuserid, 'delete');
        return;
    }
    
    // تغییر وضعیت
    if (preg_match('/channel_toggle/', $cdata)) {
        bot('deleteMessage', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid
        ]);
        show_channels_list_for_action($cuserid, 'toggle');
        return;
    }
    
    // حذف کانال
    if (preg_match('/channel_delete_(\d+)/', $cdata, $matches)) {
        $channel_id = intval($matches[1]);
        
        $sql = "DELETE FROM sp_channels WHERE id=$channel_id";
        if ($telegram->db->query($sql)) {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'کانال حذف شد',
                'show_alert' => false
            ]);
            
            bot('deleteMessage', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid
            ]);
            show_channels_menu($cuserid);
        } else {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'خطا در حذف کانال',
                'show_alert' => true
            ]);
        }
        return;
    }
    
    // تغییر وضعیت
    if (preg_match('/channel_toggle_(\d+)/', $cdata, $matches)) {
        $channel_id = intval($matches[1]);
        
        // دریافت وضعیت فعلی
        $sql = "SELECT status FROM sp_channels WHERE id=$channel_id";
        $current = $telegram->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        $new_status = $current['status'] == 1 ? 0 : 1;
        
        $sql = "UPDATE sp_channels SET status=$new_status WHERE id=$channel_id";
        if ($telegram->db->query($sql)) {
            $status_text = $new_status == 1 ? 'فعال' : 'غیرفعال';
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "وضعیت به $status_text تغییر یافت",
                'show_alert' => false
            ]);
            
            bot('deleteMessage', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid
            ]);
            show_channels_menu($cuserid);
        } else {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'خطا در تغییر وضعیت',
                'show_alert' => true
            ]);
        }
        return;
    }
}

