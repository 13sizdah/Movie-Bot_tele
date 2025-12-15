<?php
// ============================================================
// مدیریت تنظیمات ربات
// ============================================================

// نمایش منوی تنظیمات
function show_admin_settings_menu($userid)
{
    global $telegram;
    
    // دریافت تنظیمات از دیتابیس
    $sql = "SELECT * FROM sp_options ORDER BY id ASC";
    $options = $telegram->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $msg = "⚙️ <b>تنظیمات ربات</b>\n\n";
    $msg .= "لطفاً یکی از تنظیمات زیر را برای ویرایش انتخاب کنید:\n\n";
    
    $keyboard = [];
    
    // نمایش تنظیمات به صورت لیست
    foreach ($options as $option) {
        $option_id = $option['id'];
        $option_name = $option['name'];
        $option_value = $option['value'];
        
        // نمایش مقدار کوتاه شده برای تنظیمات طولانی
        $display_value = mb_strlen($option_value) > 30 ? mb_substr($option_value, 0, 30) . '...' : $option_value;
        
        $keyboard[] = [['text' => "⚙️ $option_name: $display_value", 'callback_data' => "admin_edit_option#$option_id"]];
    }
    
    // دکمه بازگشت
    $keyboard[] = [['text' => '◀️ بازگشت به منو', 'callback_data' => 'admin_main_menu']];
    
    // اگر تنظیماتی وجود ندارد
    if (empty($options)) {
        $msg = "⚙️ <b>تنظیمات ربات</b>\n\n";
        $msg .= "❌ هیچ تنظیماتی یافت نشد.\n\n";
        $msg .= "لطفاً ابتدا تنظیمات را از پنل تحت وب اضافه کنید.";
        
        $keyboard = [[['text' => '◀️ بازگشت به منو', 'callback_data' => 'admin_main_menu']]];
    }
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش فرم ویرایش تنظیمات
function show_admin_edit_option($userid, $option_id, $message_id = null)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_options WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $option_id, PDO::PARAM_INT);
    $stmt->execute();
    $option = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$option) {
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => '❌ تنظیمات یافت نشد',
            'parse_mode' => 'HTML'
        ]);
        show_admin_settings_menu($userid);
        return;
    }
    
    $msg = "⚙️ <b>ویرایش تنظیمات</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($option['name']) . "\n";
    $msg .= "<b>مقدار فعلی:</b> " . htmlspecialchars($option['value']) . "\n\n";
    $msg .= "لطفاً مقدار جدید را ارسال کنید:";
    
    // ذخیره state برای دریافت مقدار جدید
    file_put_contents('users/' . $userid . '.txt', 'admin_edit_option#' . $option_id);
    
    $keyboard = [[['text' => '❌ لغو', 'callback_data' => 'admin_cancel_edit_option']]];
    
    // اگر message_id وجود دارد، پیام را ویرایش کن، در غیر این صورت پیام جدید بفرست
    if (!empty($message_id)) {
        $edit_result = bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $message_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        
        // اگر ویرایش موفق نبود، پیام جدید بفرست
        if (isset($edit_result->ok) && !$edit_result->ok) {
            bot('sendMessage', [
                'chat_id' => $userid,
                'text' => $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
        }
    } else {
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
}

// ذخیره تنظیمات ویرایش شده
function save_admin_option($userid, $option_id, $new_value)
{
    global $telegram;
    
    // بررسی اینکه مقدار جدید خالی نباشد
    if (empty($new_value) && $new_value !== '0' && $new_value !== 0) {
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => '❌ خطا: مقدار تنظیمات نمی‌تواند خالی باشد. لطفاً یک مقدار معتبر وارد کنید.',
            'parse_mode' => 'HTML'
        ]);
        // نمایش مجدد فرم ویرایش
        show_admin_edit_option($userid, $option_id);
        return;
    }
    
    // تبدیل مقدار به string و trim کردن
    $new_value = trim((string)$new_value);
    
    // اگر بعد از trim خالی شد، خطا بده
    if (empty($new_value) && $new_value !== '0') {
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => '❌ خطا: مقدار تنظیمات نمی‌تواند خالی باشد. لطفاً یک مقدار معتبر وارد کنید.',
            'parse_mode' => 'HTML'
        ]);
        show_admin_edit_option($userid, $option_id);
        return;
    }
    
    $sql = "UPDATE sp_options SET value = :value WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':value', $new_value, PDO::PARAM_STR);
    $stmt->bindValue(':id', $option_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // پاک کردن state
        file_put_contents('users/' . $userid . '.txt', ' ');
        
        // دریافت تنظیمات برای ساخت منو
        $sql2 = "SELECT * FROM sp_options ORDER BY id ASC";
        $options = $telegram->db->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
        
        // فقط یک پیام با پیام موفقیت و منوی تنظیمات
        $msg = "✅ <b>تنظیمات با موفقیت به‌روزرسانی شد</b>\n\n";
        $msg .= "⚙️ <b>تنظیمات ربات</b>\n\n";
        $msg .= "لطفاً یکی از تنظیمات زیر را برای ویرایش انتخاب کنید:\n\n";
        
        $keyboard = [];
        
        foreach ($options as $option) {
            $opt_id = $option['id'];
            $option_name = $option['name'];
            $option_value = $option['value'];
            
            $display_value = mb_strlen($option_value) > 30 ? mb_substr($option_value, 0, 30) . '...' : $option_value;
            
            $keyboard[] = [['text' => "⚙️ $option_name: $display_value", 'callback_data' => "admin_edit_option#$opt_id"]];
        }
        
        $keyboard[] = [['text' => '◀️ بازگشت به منو', 'callback_data' => 'admin_main_menu']];
        
        if (empty($options)) {
            $msg = "✅ <b>تنظیمات با موفقیت به‌روزرسانی شد</b>\n\n";
            $msg .= "⚙️ <b>تنظیمات ربات</b>\n\n";
            $msg .= "❌ هیچ تنظیماتی یافت نشد.\n\n";
            $msg .= "لطفاً ابتدا تنظیمات را از پنل تحت وب اضافه کنید.";
            
            $keyboard = [[['text' => '◀️ بازگشت به منو', 'callback_data' => 'admin_main_menu']]];
        }
        
        // فقط یک پیام بفرست
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => '❌ خطا در به‌روزرسانی تنظیمات',
            'parse_mode' => 'HTML'
        ]);
    }
}

// لغو ویرایش تنظیمات
function cancel_admin_edit_option($userid, $message_id = null)
{
    file_put_contents('users/' . $userid . '.txt', ' ');
    
    // دریافت تنظیمات برای ساخت منو
    global $telegram;
    
    $sql = "SELECT * FROM sp_options ORDER BY id ASC";
    $options = $telegram->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $msg = "⚙️ <b>تنظیمات ربات</b>\n\n";
    $msg .= "لطفاً یکی از تنظیمات زیر را برای ویرایش انتخاب کنید:\n\n";
    
    $keyboard = [];
    
    foreach ($options as $option) {
        $option_id = $option['id'];
        $option_name = $option['name'];
        $option_value = $option['value'];
        
        $display_value = mb_strlen($option_value) > 30 ? mb_substr($option_value, 0, 30) . '...' : $option_value;
        
        $keyboard[] = [['text' => "⚙️ $option_name: $display_value", 'callback_data' => "admin_edit_option#$option_id"]];
    }
    
    $keyboard[] = [['text' => '◀️ بازگشت به منو', 'callback_data' => 'admin_main_menu']];
    
    if (empty($options)) {
        $msg = "⚙️ <b>تنظیمات ربات</b>\n\n";
        $msg .= "❌ هیچ تنظیماتی یافت نشد.\n\n";
        $msg .= "لطفاً ابتدا تنظیمات را از پنل تحت وب اضافه کنید.";
        
        $keyboard = [[['text' => '◀️ بازگشت به منو', 'callback_data' => 'admin_main_menu']]];
    }
    
    // اگر message_id وجود دارد، سعی کن پیام را ویرایش کن
    if (!empty($message_id)) {
        // ابتدا سعی کن با editMessageText ویرایش کن
        $edit_result = bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $message_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        
        // اگر ویرایش متن موفق نبود، سعی کن با editMessageCaption ویرایش کن (برای پیام‌های با عکس)
        if (isset($edit_result->ok) && !$edit_result->ok) {
            $edit_caption_result = @bot('editMessageCaption', [
                'chat_id' => $userid,
                'message_id' => $message_id,
                'caption' => $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
            
            // اگر هیچکدام موفق نبود، پیام جدید بفرست
            if (isset($edit_caption_result->ok) && !$edit_caption_result->ok) {
                show_admin_settings_menu($userid);
            }
        }
    } else {
        // اگر message_id وجود ندارد، پیام جدید بفرست
        show_admin_settings_menu($userid);
    }
}

