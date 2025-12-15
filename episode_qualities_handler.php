<?php
// تابع نمایش کیفیت‌های یک قسمت
function show_episode_qualities()
{
    global $cdata, $cid, $cuserid, $cmsgid, $telegram, $footer_msg, $keyboard;
    
    if (isset($cdata) && !empty($cdata) && preg_match('/episode_qualities/', $cdata)) {
        $input = explode('#', $cdata);
        $episode_id = intval($input[1]);
        
        if ($episode_id <= 0) {
            return;
        }
        
        // استفاده از prepared statement برای امنیت و سرعت بیشتر
        $qualities_sql = "SELECT eq.quality, eq.download_link, eq.file_size, se.file_id, se.season, se.episode, se.episode_title, f.name as series_name
                         FROM sp_episode_qualities eq
                         INNER JOIN sp_series_episodes se ON eq.episode_id = se.id
                         INNER JOIN sp_files f ON se.file_id = f.id
                         WHERE eq.episode_id=:episode_id AND eq.status=1 AND se.status=1 AND f.status=1
                         ORDER BY eq.order_num ASC, eq.quality ASC";
        $stmt = $telegram->db->prepare($qualities_sql);
        $stmt->bindValue(':episode_id', $episode_id, PDO::PARAM_INT);
        $stmt->execute();
        $qualities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($qualities)) {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'کیفیتی برای این قسمت تعریف نشده است',
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
        
        // استخراج اطلاعات قسمت از اولین رکورد
        $episode = [
            'file_id' => $qualities[0]['file_id'],
            'season' => $qualities[0]['season'],
            'episode' => $qualities[0]['episode'],
            'episode_title' => $qualities[0]['episode_title'],
            'series_name' => $qualities[0]['series_name']
        ];
        
        // ساخت دکمه‌های کیفیت و پیام به صورت همزمان (بهینه‌سازی: کاهش loop ها)
        $qualities_keyboard = [];
        $ep_title = !empty($episode['episode_title']) ? " - " . $episode['episode_title'] : '';
        $msg = "📁 فصل {$episode['season']} - 🔗 قسمت {$episode['episode']}$ep_title\n\n";
        $msg .= "🎬 سریال: {$episode['series_name']}\n\n";
        $msg .= "📋 کیفیت‌های موجود:\n";
        
        foreach ($qualities as $q) {
            $quality_name = $q['quality'];
            $file_size = !empty($q['file_size']) ? " ({$q['file_size']})" : '';
            $button_text = "📥 $quality_name$file_size";
            
            if (!empty($q['download_link'])) {
                $qualities_keyboard[] = [['text' => $button_text, 'url' => $q['download_link']]];
            }
            $msg .= "• $quality_name$file_size\n";
        }
        
        // دکمه بازگشت به قسمت‌های فصل
        $back_button = [['text' => '◀️ بازگشت به قسمت‌ها', 'callback_data' => "season_episodes#{$episode['file_id']}#{$episode['season']}"]];
        $qualities_keyboard[] = $back_button;
        
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
                    'inline_keyboard' => $qualities_keyboard
                ])
            ]);
            
            // اگر editMessageText موفق نبود، sendMessage را امتحان کن
            if (isset($result->ok) && !$result->ok) {
                bot('sendMessage', [
                    'chat_id' => $cuserid,
                    'parse_mode' => "HTML",
                    'text' => $msg,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $qualities_keyboard
                    ])
                ]);
            }
        } else {
            bot('sendMessage', [
                'chat_id' => $cuserid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $qualities_keyboard
                ])
            ]);
        }
    }
}

