<?php
// ============================================================
// مدیریت کیفیت‌ها و ویرایش کپشن از طریق ربات
// ============================================================

// مدیریت کیفیت‌های یک فیلم
function manage_qualities()
{
    global $cdata, $cid, $cuserid, $cmsgid, $telegram, $admin, $main_keyboard;
    
    if ($cuserid != $admin) {
        return;
    }
    
    if (preg_match('/manage_qualities_/', $cdata)) {
        $file_id = intval(str_replace('manage_qualities_', '', $cdata));
        
        // دریافت اطلاعات فیلم
        $sql = "SELECT * FROM sp_files WHERE id=$file_id";
        $file_info = $telegram->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        if (!$file_info) {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'فیلم یافت نشد',
                'show_alert' => true
            ]);
            return;
        }
        
        // دریافت کیفیت‌های موجود
        $qualities_sql = "SELECT * FROM sp_qualities WHERE file_id=$file_id ORDER BY quality ASC";
        $qualities = $telegram->db->query($qualities_sql)->fetchAll(PDO::FETCH_ASSOC);
        
        $msg = "🎬 <b>" . htmlspecialchars($file_info['name']) . "</b>\n\n";
        $msg .= "📺 <b>کیفیت‌های موجود:</b>\n\n";
        
        if (empty($qualities)) {
            $msg .= "❌ هیچ کیفیتی ثبت نشده است.\n\n";
        } else {
            foreach ($qualities as $q) {
                $status_icon = $q['status'] == 1 ? '✅' : '❌';
                $msg .= "$status_icon <b>" . $q['quality'] . "</b>";
                if (!empty($q['file_size'])) {
                    $msg .= " (" . $q['file_size'] . ")";
                }
                $msg .= "\n";
            }
        }
        
        $msg .= "\n💡 برای افزودن کیفیت جدید، فایل را فوروارد کنید.";
        
        $keyboard = [];
        if (!empty($qualities)) {
            foreach ($qualities as $q) {
                $keyboard[] = [['text' => "✏️ ویرایش " . $q['quality'], 'callback_data' => 'edit_quality_' . $q['id']]];
            }
        }
        $keyboard[] = [['text' => '➕ افزودن کیفیت جدید', 'callback_data' => 'add_quality_' . $file_id]];
        $keyboard[] = [['text' => '◀️ بازگشت', 'callback_data' => 'back_to_admin']];
        
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'parse_mode' => 'HTML',
            'text' => $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
}

// شروع فرآیند افزودن کیفیت جدید
function start_add_quality()
{
    global $cdata, $cid, $cuserid, $telegram, $admin;
    
    if ($cuserid != $admin) {
        return;
    }
    
    if (preg_match('/add_quality_/', $cdata)) {
        $file_id = intval(str_replace('add_quality_', '', $cdata));
        
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'لطفاً فایل را فوروارد کنید',
            'show_alert' => false
        ]);
        
        file_put_contents('users/' . $cuserid . '.txt', 'add_quality_file:' . $file_id);
        
        $msg = "📤 <b>افزودن کیفیت جدید</b>\n\n";
        $msg .= "لطفاً فایل ویدئو را <b>فوروارد</b> کنید.\n";
        $msg .= "بعد از فوروارد، می‌توانید کپشن را ویرایش کنید.";
        
        bot('sendmessage', [
            'chat_id' => $cuserid,
            'parse_mode' => 'HTML',
            'text' => $msg
        ]);
    }
}

// دریافت فایل فوروارد شده برای افزودن کیفیت
function receive_forwarded_quality_file()
{
    global $result, $userid, $telegram, $admin, $fileid, $msgid;
    
    if ($userid != $admin) {
        return;
    }
    
    $status = @file_get_contents('users/' . $userid . '.txt');
    
    if (strpos($status, 'add_quality_file:') === 0) {
        $file_id = intval(str_replace('add_quality_file:', '', $status));
        
        // بررسی اینکه آیا پیام فوروارد شده است
        $is_forwarded = isset($result->message->forward_from) || isset($result->message->forward_from_chat);
        
        // دریافت File ID
        $video_file_id = null;
        $document_file_id = null;
        
        if (isset($result->message->video->file_id)) {
            $video_file_id = $result->message->video->file_id;
        } elseif (isset($result->message->document->file_id)) {
            $document_file_id = $result->message->document->file_id;
            if (isset($result->message->document->mime_type) && 
                strpos($result->message->document->mime_type, 'video') !== false) {
                $video_file_id = $document_file_id;
            }
        }
        
        if ($video_file_id || $document_file_id) {
            $file_id_to_save = $video_file_id ? $video_file_id : $document_file_id;
            
            // دریافت کپشن موجود (اگر وجود دارد)
            $caption = isset($result->message->caption) ? $result->message->caption : '';
            
            // ذخیره اطلاعات موقت
            file_put_contents('users/' . $userid . '.txt', 'add_quality_info:' . $file_id . '|file_id:' . $file_id_to_save . '|caption:' . base64_encode($caption));
            
            $msg = "✅ فایل دریافت شد!\n\n";
            $msg .= "📝 لطفاً <b>کیفیت</b> را وارد کنید:\n";
            $msg .= "(مثلاً: 720p, 1080p, 4K)\n\n";
            
            if (!empty($caption)) {
                $msg .= "📄 کپشن فعلی:\n" . htmlspecialchars($caption) . "\n\n";
            }
            $msg .= "💡 بعد از وارد کردن کیفیت، می‌توانید کپشن را ویرایش کنید.";
            
            bot('sendmessage', [
                'chat_id' => $userid,
                'parse_mode' => 'HTML',
                'text' => $msg
            ]);
        } else {
            $msg = "❌ لطفاً یک فایل ویدئو فوروارد کنید.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg
            ]);
        }
    }
}

// دریافت کیفیت و ذخیره
function save_quality_info()
{
    global $text, $userid, $telegram, $admin, $main_keyboard, $baseuri;
    
    if ($userid != $admin) {
        return;
    }
    
    $status = @file_get_contents('users/' . $userid . '.txt');
    
    if (strpos($status, 'add_quality_info:') === 0) {
        $parts = explode('|', $status);
        $file_id = intval(str_replace('add_quality_info:', '', $parts[0]));
        $file_id_to_save = str_replace('file_id:', '', $parts[1]);
        $caption = base64_decode(str_replace('caption:', '', $parts[2]));
        
        // دریافت کیفیت
        $quality = trim($text);
        
        if (empty($quality)) {
            $msg = "❌ لطفاً کیفیت را وارد کنید.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg
            ]);
            return;
        }
        
        // بررسی تکراری نبودن کیفیت
        $check_sql = "SELECT id FROM sp_qualities WHERE file_id=$file_id AND quality='$quality'";
        $exists = $telegram->db->query($check_sql)->fetch(PDO::FETCH_ASSOC);
        
        if ($exists) {
            $msg = "❌ این کیفیت قبلاً ثبت شده است.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg
            ]);
            return;
        }
        
        // تولید لینک اختصاصی
        $unique_link = generate_unique_link($file_id, $quality);
        
        // ذخیره کیفیت
        $sql = "INSERT INTO sp_qualities (file_id, quality, file_url, download_link, status) 
                VALUES ($file_id, :quality, :file_url, :download_link, 1)";
        $stmt = $telegram->db->prepare($sql);
        $stmt->bindParam(':quality', $quality);
        $stmt->bindParam(':file_url', $file_id_to_save);
        $stmt->bindParam(':download_link', $unique_link);
        
        if ($stmt->execute()) {
            $quality_id = $telegram->db->lastInsertId();
            
            // ذخیره کپشن در جدول sp_files (اگر خالی باشد)
            if (empty($caption)) {
                $caption = "دانلود " . $quality;
            }
            
            // ذخیره وضعیت برای ویرایش کپشن
            file_put_contents('users/' . $userid . '.txt', 'edit_caption_quality:' . $quality_id . '|caption:' . base64_encode($caption));
            
            $msg = "✅ کیفیت <b>$quality</b> با موفقیت اضافه شد!\n\n";
            $msg .= "📄 کپشن فعلی:\n" . htmlspecialchars($caption) . "\n\n";
            $msg .= "✏️ برای ویرایش کپشن، متن جدید را ارسال کنید.\n";
            $msg .= "یا برای ادامه، /skip را ارسال کنید.";
            
            bot('sendmessage', [
                'chat_id' => $userid,
                'parse_mode' => 'HTML',
                'text' => $msg
            ]);
        } else {
            $msg = "❌ خطا در ذخیره کیفیت.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg
            ]);
        }
    } elseif (strpos($status, 'edit_caption_quality:') === 0) {
        // ویرایش کپشن کیفیت
        $parts = explode('|', $status);
        $quality_id = intval(str_replace('edit_caption_quality:', '', $parts[0]));
        
        if ($text == '/skip') {
            @unlink('users/' . $userid . '.txt');
            $msg = "✅ کیفیت با موفقیت ثبت شد.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg,
                'reply_markup' => json_encode([
                    'keyboard' => $main_keyboard,
                    'resize_keyboard' => true
                ])
            ]);
            return;
        }
        
        // به‌روزرسانی کپشن در جدول sp_files
        $sql = "SELECT file_id FROM sp_qualities WHERE id=$quality_id";
        $quality_info = $telegram->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        if ($quality_info) {
            $file_id = $quality_info['file_id'];
            $update_sql = "UPDATE sp_files SET caption=:caption WHERE id=$file_id";
            $stmt = $telegram->db->prepare($update_sql);
            $stmt->bindParam(':caption', $text);
            $stmt->execute();
        }
        
        @unlink('users/' . $userid . '.txt');
        
        $msg = "✅ کپشن با موفقیت به‌روزرسانی شد!";
        bot('sendmessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'reply_markup' => json_encode([
                'keyboard' => $main_keyboard,
                'resize_keyboard' => true
            ])
        ]);
    }
}

// تولید لینک اختصاصی
function generate_unique_link($file_id, $quality)
{
    global $baseuri;
    $token = md5($file_id . $quality . time() . rand(1000, 9999));
    return $baseuri . "/download.php?token=" . $token;
}

// ویرایش کپشن فیلم موجود (بعد از فوروارد)
function handle_forwarded_movie_edit()
{
    global $result, $userid, $telegram, $admin, $text, $main_keyboard;
    
    if ($userid != $admin) {
        return;
    }
    
    // بررسی اینکه آیا پیام فوروارد شده است
    $is_forwarded = isset($result->message->forward_from) || isset($result->message->forward_from_chat);
    
    if ($is_forwarded) {
        // بررسی اینکه آیا فایل ویدئو است
        $has_video = isset($result->message->video) || 
                    (isset($result->message->document) && 
                     isset($result->message->document->mime_type) && 
                     strpos($result->message->document->mime_type, 'video') !== false);
        
        if ($has_video) {
            // دریافت کپشن موجود
            $caption = isset($result->message->caption) ? $result->message->caption : '';
            
            $msg = "📤 <b>فایل فوروارد شده دریافت شد</b>\n\n";
            $msg .= "✏️ برای ویرایش کپشن، متن جدید را ارسال کنید.\n";
            $msg .= "یا برای افزودن به کیفیت‌های یک فیلم، /add_quality را ارسال کنید.\n\n";
            
            if (!empty($caption)) {
                $msg .= "📄 کپشن فعلی:\n" . htmlspecialchars($caption);
            }
            
            // ذخیره File ID و کپشن موقت
            $file_id = isset($result->message->video->file_id) ? 
                       $result->message->video->file_id : 
                       $result->message->document->file_id;
            
            file_put_contents('users/' . $userid . '.txt', 'edit_caption_forwarded|file_id:' . $file_id . '|caption:' . base64_encode($caption));
            
            bot('sendmessage', [
                'chat_id' => $userid,
                'parse_mode' => 'HTML',
                'text' => $msg
            ]);
        }
    }
}

// پردازش ویرایش کپشن فوروارد شده
function process_forwarded_caption_edit()
{
    global $text, $userid, $telegram, $admin, $main_keyboard;
    
    if ($userid != $admin) {
        return;
    }
    
    $status = @file_get_contents('users/' . $userid . '.txt');
    
    if (strpos($status, 'edit_caption_forwarded') === 0) {
        if ($text == '/add_quality') {
            // شروع فرآیند افزودن به کیفیت
            $msg = "📝 لطفاً <b>شناسه فیلم</b> را وارد کنید:\n";
            $msg .= "(شناسه فیلم را از پنل مدیریت دریافت کنید)";
            
            file_put_contents('users/' . $userid . '.txt', 'add_quality_by_id|' . $status);
            
            bot('sendmessage', [
                'chat_id' => $userid,
                'parse_mode' => 'HTML',
                'text' => $msg
            ]);
            return;
        }
        
        // ویرایش کپشن
        $parts = explode('|', $status);
        $file_id = str_replace('file_id:', '', $parts[1]);
        
        // پیدا کردن فیلم بر اساس File ID
        $sql = "SELECT id FROM sp_files WHERE fileurl='$file_id' LIMIT 1";
        $file_info = $telegram->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        if ($file_info) {
            $movie_id = $file_info['id'];
            $update_sql = "UPDATE sp_files SET caption=:caption WHERE id=$movie_id";
            $stmt = $telegram->db->prepare($update_sql);
            $stmt->bindParam(':caption', $text);
            $stmt->execute();
            
            $msg = "✅ کپشن با موفقیت به‌روزرسانی شد!";
        } else {
            $msg = "⚠️ فیلم با این File ID یافت نشد.\n";
            $msg .= "می‌توانید از /add_quality برای افزودن به کیفیت‌های یک فیلم استفاده کنید.";
        }
        
        @unlink('users/' . $userid . '.txt');
        
        bot('sendmessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'reply_markup' => json_encode([
                'keyboard' => $main_keyboard,
                'resize_keyboard' => true
            ])
        ]);
    } elseif (strpos($status, 'add_quality_by_id') === 0) {
        // افزودن کیفیت با شناسه فیلم
        $parts = explode('|', $status);
        $old_status = $parts[1];
        $old_parts = explode('|', $old_status);
        $file_id_to_save = str_replace('file_id:', '', $old_parts[1]);
        $caption = base64_decode(str_replace('caption:', '', $old_parts[2]));
        
        $movie_id = intval($text);
        
        if (!is_numeric($movie_id) || $movie_id <= 0) {
            $msg = "❌ لطفاً یک شناسه معتبر وارد کنید.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg
            ]);
            return;
        }
        
        // بررسی وجود فیلم
        $check_sql = "SELECT id FROM sp_files WHERE id=$movie_id";
        $exists = $telegram->db->query($check_sql)->fetch(PDO::FETCH_ASSOC);
        
        if (!$exists) {
            $msg = "❌ فیلم با این شناسه یافت نشد.";
            bot('sendmessage', [
                'chat_id' => $userid,
                'text' => $msg
            ]);
            return;
        }
        
        // درخواست کیفیت
        file_put_contents('users/' . $userid . '.txt', 'add_quality_info:' . $movie_id . '|file_id:' . $file_id_to_save . '|caption:' . base64_encode($caption));
        
        $msg = "✅ فیلم پیدا شد!\n\n";
        $msg .= "📝 لطفاً <b>کیفیت</b> را وارد کنید:\n";
        $msg .= "(مثلاً: 720p, 1080p, 4K)";
        
        bot('sendmessage', [
            'chat_id' => $userid,
            'parse_mode' => 'HTML',
            'text' => $msg
        ]);
    }
}

