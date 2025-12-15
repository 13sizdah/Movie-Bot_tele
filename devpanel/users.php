<?php
// ============================================================
// مدیریت کاربران
// ============================================================

// لیست کاربران
function show_admin_users_list($userid, $page = 1)
{
    global $telegram;
    
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    // استفاده از prepared statement برای امنیت بیشتر
    $sql = "SELECT * FROM sp_users ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    $total_users = $telegram->db->query("SELECT COUNT(*) as count FROM sp_users")->fetch()['count'];
    $total_pages = ceil($total_users / $limit);
    
    $msg = "👥 <b>مدیریت کاربران</b>\n\n";
    
    if (empty($users)) {
        $msg .= "❌ کاربری یافت نشد.\n\n";
    } else {
        foreach ($users as $user) {
            $verified_icon = isset($user['verified']) && $user['verified'] == 1 ? '✅' : '❌';
            $name = !empty($user['name']) ? htmlspecialchars($user['name']) : 'بدون نام';
            $msg .= "$verified_icon <b>$name</b>\n";
            $msg .= "   📱 ID: <code>{$user['userid']}</code>\n";
            if (!empty($user['phone'])) {
                $msg .= "   📞 تلفن: <code>{$user['phone']}</code>\n";
            }
            $msg .= "\n";
        }
        
        if ($total_pages > 1) {
            $msg .= "📄 صفحه $page از $total_pages\n";
        }
    }
    
    $keyboard = [];
    
    // صفحه‌بندی
    if ($total_pages > 1) {
        $pagination_row = [];
        if ($page > 1) {
            $pagination_row[] = ['text' => '◀️ قبل', 'callback_data' => "admin_users_page#" . ($page - 1)];
        }
        $pagination_row[] = ['text' => "📄 $page/$total_pages", 'callback_data' => 'admin_users_info'];
        if ($page < $total_pages) {
            $pagination_row[] = ['text' => 'بعد ▶️', 'callback_data' => "admin_users_page#" . ($page + 1)];
        }
        $keyboard[] = $pagination_row;
    }
    
    $keyboard[] = [['text' => '◀️ بازگشت به منوی مدیریت', 'callback_data' => 'admin_main_menu']];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

