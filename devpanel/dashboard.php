<?php
// ============================================================
// داشبورد مدیریت - نمایش آمار کلی
// ============================================================

// داشبورد مدیریت
function show_admin_dashboard($userid)
{
    global $telegram;
    
    // دریافت آمار
    $users_count = $telegram->db->query("SELECT COUNT(*) as count FROM sp_users")->fetch()['count'];
    $products_count = $telegram->db->query("SELECT COUNT(*) as count FROM sp_files")->fetch()['count'];
    $tickets_count = $telegram->db->query("SELECT COUNT(*) as count FROM sp_tickets")->fetch()['count'];
    $genres_count = $telegram->db->query("SELECT COUNT(*) as count FROM sp_genres")->fetch()['count'];
    
    // محاسبه درآمد کل (اگر جدول orders وجود دارد)
    $total_income = 0;
    try {
        $income_result = $telegram->db->query("SELECT SUM(price) as total FROM sp_orders")->fetch();
        $total_income = $income_result['total'] ?? 0;
    } catch (Exception $e) {
        // جدول orders ممکن است وجود نداشته باشد
    }
    
    $msg = "📊 <b>داشبورد مدیریت</b>\n\n";
    $msg .= "📈 <b>آمار کلی:</b>\n";
    $msg .= "👥 تعداد کاربران: <code>$users_count</code>\n";
    $msg .= "🎬 تعداد محصولات: <code>$products_count</code>\n";
    $msg .= "🎫 تعداد تیکت‌ها: <code>$tickets_count</code>\n";
    $msg .= "🏷️ تعداد ژانرها: <code>$genres_count</code>\n";
    if ($total_income > 0) {
        $msg .= "💰 درآمد کل: <code>" . number_format($total_income) . " تومان</code>\n";
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

