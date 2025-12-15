<?php
// ============================================================
// مدیریت تیکت‌ها
// ============================================================

// لیست تیکت‌ها
function show_admin_tickets_list($userid, $page = 1)
{
    global $telegram;
    
    $limit = 5;
    $offset = ($page - 1) * $limit;
    
    // استفاده از prepared statement برای امنیت بیشتر
    $sql = "SELECT * FROM sp_tickets ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tickets = $stmt->fetchAll();
    
    $total_tickets = $telegram->db->query("SELECT COUNT(*) as count FROM sp_tickets")->fetch()['count'];
    $total_pages = ceil($total_tickets / $limit);
    
    $msg = "🎫 <b>تیکت‌های کاربران</b>\n\n";
    
    if (empty($tickets)) {
        $msg .= "❌ تیکتی یافت نشد.\n\n";
    } else {
        foreach ($tickets as $ticket) {
            $msg .= "📨 <b>تیکت #{$ticket['id']}</b>\n";
            $msg .= "👤 کاربر: <code>{$ticket['userid']}</code>\n";
            $ticket_text = mb_substr($ticket['text'], 0, 50);
            if (mb_strlen($ticket['text']) > 50) {
                $ticket_text .= '...';
            }
            $msg .= "💬 " . htmlspecialchars($ticket_text) . "\n";
            $msg .= "🔗 <code>admin_view_ticket#{$ticket['id']}</code>\n\n";
        }
        
        if ($total_pages > 1) {
            $msg .= "📄 صفحه $page از $total_pages\n";
        }
    }
    
    $keyboard = [];
    
    if ($total_pages > 1) {
        $pagination_row = [];
        if ($page > 1) {
            $pagination_row[] = ['text' => '◀️ قبل', 'callback_data' => "admin_tickets_page#" . ($page - 1)];
        }
        if ($page < $total_pages) {
            $pagination_row[] = ['text' => 'بعد ▶️', 'callback_data' => "admin_tickets_page#" . ($page + 1)];
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

