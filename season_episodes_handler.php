<?php
// تابع نمایش قسمت‌های یک فصل
function show_season_episodes()
{
    global $cdata, $cid, $cuserid, $cmsgid, $telegram, $footer_msg;
    
    if (isset($cdata) && !empty($cdata) && preg_match('/season_episodes/', $cdata)) {
        $input = explode('#', $cdata);
        
        // بررسی سریع داده‌ها
        if (count($input) < 3) {
            return;
        }
        
        $file_id = intval($input[1]);
        $season = intval($input[2]);
        
        if ($file_id <= 0 || $season <= 0) {
            return;
        }
        
        // استفاده از prepared statement برای امنیت و سرعت بیشتر
        $episodes_sql = "SELECT se.id, se.episode, se.episode_title, f.name as series_name, f.media_type 
                        FROM sp_series_episodes se 
                        INNER JOIN sp_files f ON se.file_id = f.id 
                        WHERE se.file_id=:file_id AND se.season=:season AND se.status=1 AND f.status=1 
                        ORDER BY se.episode ASC, se.order_num ASC";
        $stmt = $telegram->db->prepare($episodes_sql);
        $stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->bindValue(':season', $season, PDO::PARAM_INT);
        $stmt->execute();
        $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($episodes)) {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'قسمتی برای این فصل تعریف نشده است',
                'show_alert' => true
            ]);
            return;
        }
        
        // پاسخ سریع به callback
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '',
            'show_alert' => false
        ]);
        
        // استخراج نام و نوع محتوا از اولین رکورد
        $series_name = $episodes[0]['series_name'];
        $media_type = isset($episodes[0]['media_type']) ? $episodes[0]['media_type'] : 'series';
        
        // تعیین برچسب نوع محتوا
        if ($media_type == 'animation') {
            $media_label = 'انیمیشن';
            $media_icon = '🎨';
        } elseif ($media_type == 'anime') {
            $media_label = 'انیمه';
            $media_icon = '🌸';
        } else {
            $media_label = 'سریال';
            $media_icon = '📺';
        }
        
        // ساخت دکمه‌های قسمت و پیام به صورت همزمان (بهینه‌سازی: کاهش loop ها)
        $episodes_keyboard = [];
        $msg = "📁 <b>فصل $season</b>\n\n";
        $msg .= "🎬 $media_label: $series_name\n\n";
        $msg .= "📋 🔗 قسمت‌های موجود:\n";
        
        foreach ($episodes as $ep) {
            $ep_title = !empty($ep['episode_title']) ? " - " . $ep['episode_title'] : '';
            $button_text = "🔗 قسمت {$ep['episode']}$ep_title";
            
            // محدود کردن طول متن دکمه
            if (mb_strlen($button_text) > 60) {
                $button_text = mb_substr($button_text, 0, 57) . "...";
            }
            
            // استفاده از callback_data برای نمایش کیفیت‌های قسمت
            $episodes_keyboard[] = [['text' => $button_text, 'callback_data' => "episode_qualities#{$ep['id']}"]];
            $msg .= "• 🔗 قسمت {$ep['episode']}$ep_title\n";
        }
        
        // دکمه بازگشت به سریال
        $back_button = [['text' => '◀️ بازگشت', 'callback_data' => "file#$file_id"]];
        $episodes_keyboard[] = $back_button;
        
        $msg .= "\n" . $footer_msg;
        $msg = fa_num($msg);
        
        // ارسال یا ویرایش پیام
        if (!empty($cmsgid) && !empty($cuserid)) {
            $result = bot('editMessageText', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $episodes_keyboard
                ])
            ]);
            
            // اگر editMessageText موفق نبود، sendMessage را امتحان کن
            if (isset($result->ok) && !$result->ok) {
                bot('sendMessage', [
                    'chat_id' => $cuserid,
                    'parse_mode' => "HTML",
                    'text' => $msg,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $episodes_keyboard
                    ])
                ]);
            }
        } else {
            bot('sendMessage', [
                'chat_id' => $cuserid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $episodes_keyboard
                ])
            ]);
        }
    }
}
