<?php
// ============================================================
// مدیریت محصولات
// ============================================================

// لیست محصولات
function show_admin_products_list($userid, $page = 1)
{
    global $telegram;
    
    $limit = 5; // تعداد محصولات در هر صفحه
    $offset = ($page - 1) * $limit;
    
    // استفاده از prepared statement برای امنیت بیشتر
    $sql = "SELECT * FROM sp_files ORDER BY id DESC LIMIT :limit OFFSET :offset";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    $total_products = $telegram->db->query("SELECT COUNT(*) as count FROM sp_files")->fetch()['count'];
    $total_pages = ceil($total_products / $limit);
    
    $msg = "🎬 <b>مدیریت محصولات</b>\n\n";
    
    if (empty($products)) {
        $msg .= "❌ محصولی یافت نشد.\n\n";
    } else {
        foreach ($products as $product) {
            $status_icon = $product['status'] == 1 ? '✅' : '❌';
            $media_type_icon = $product['media_type'] == 'movie' ? '🎬' : ($product['media_type'] == 'series' ? '📺' : ($product['media_type'] == 'animation' ? '🎨' : '🌸'));
            $msg .= "$status_icon $media_type_icon <b>" . htmlspecialchars($product['name']) . "</b>\n";
            
            // نمایش دسته‌بندی
            if (!empty($product['catid'])) {
                $cat_sql = "SELECT name FROM sp_cats WHERE id = :catid LIMIT 1";
                $cat_stmt = $telegram->db->prepare($cat_sql);
                $cat_stmt->bindValue(':catid', $product['catid'], PDO::PARAM_INT);
                $cat_stmt->execute();
                $category = $cat_stmt->fetch();
                if ($category) {
                    $msg .= "   📁 دسته‌بندی: " . htmlspecialchars($category['name']) . "\n";
                } else {
                    $msg .= "   📁 دسته‌بندی: ❌ تعیین نشده\n";
                }
            } else {
                $msg .= "   📁 دسته‌بندی: ❌ تعیین نشده\n";
            }
            
            $msg .= "   📥 مشاهده‌ها: " . number_format($product['views']) . "\n";
            $msg .= "   🔗 <code>admin_edit_product#{$product['id']}</code>\n\n";
        }
        
        if ($total_pages > 1) {
            $msg .= "📄 صفحه $page از $total_pages\n";
        }
    }
    
    $keyboard = [];
    
    // دکمه‌های صفحه‌بندی
    if ($total_pages > 1) {
        $pagination_row = [];
        if ($page > 1) {
            $pagination_row[] = ['text' => '◀️ قبل', 'callback_data' => "admin_products_page#" . ($page - 1)];
        }
        $pagination_row[] = ['text' => "📄 $page/$total_pages", 'callback_data' => 'admin_products_info'];
        if ($page < $total_pages) {
            $pagination_row[] = ['text' => 'بعد ▶️', 'callback_data' => "admin_products_page#" . ($page + 1)];
        }
        $keyboard[] = $pagination_row;
    }
    
    // دکمه افزودن محصول
    $keyboard[] = [['text' => '➕ افزودن محصول', 'callback_data' => 'admin_add_product']];
    
    // دکمه بازگشت
    $keyboard[] = [['text' => '◀️ بازگشت به منوی مدیریت', 'callback_data' => 'admin_main_menu']];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش جزئیات یک محصول
function show_admin_product_details($userid, $product_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_files WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        $msg = "❌ محصول یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    $status_text = $product['status'] == 1 ? '✅ فعال' : '❌ غیرفعال';
    $media_type_text = $product['media_type'] == 'movie' ? '🎬 فیلم' : ($product['media_type'] == 'series' ? '📺 سریال' : ($product['media_type'] == 'animation' ? '🎨 انیمیشن' : '🌸 انیمه'));
    
    $msg = "🎬 <b>جزئیات محصول</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($product['name']) . "\n";
    if (!empty($product['name_en'])) {
        $msg .= "<b>نام انگلیسی:</b> " . htmlspecialchars($product['name_en']) . "\n";
    }
    $msg .= "<b>نوع:</b> $media_type_text\n";
    $msg .= "<b>وضعیت:</b> $status_text\n";
    $msg .= "<b>مشاهده‌ها:</b> " . number_format($product['views']) . "\n";
    
    // نمایش دسته‌بندی
    if (!empty($product['catid'])) {
        $cat_sql = "SELECT name FROM sp_cats WHERE id = :catid LIMIT 1";
        $cat_stmt = $telegram->db->prepare($cat_sql);
        $cat_stmt->bindValue(':catid', $product['catid'], PDO::PARAM_INT);
        $cat_stmt->execute();
        $category = $cat_stmt->fetch();
        if ($category) {
            $msg .= "<b>دسته‌بندی:</b> " . htmlspecialchars($category['name']) . "\n";
        } else {
            $msg .= "<b>دسته‌بندی:</b> ❌ تعیین نشده\n";
        }
    } else {
        $msg .= "<b>دسته‌بندی:</b> ❌ تعیین نشده\n";
    }
    
    if (!empty($product['genre'])) {
        $msg .= "<b>ژانر:</b> " . htmlspecialchars($product['genre']) . "\n";
    }
    if (!empty($product['year'])) {
        $msg .= "<b>سال:</b> " . $product['year'] . "\n";
    }
    if (!empty($product['imdb'])) {
        $msg .= "<b>IMDb:</b> " . $product['imdb'] . "\n";
    }
    
    $keyboard = [
        [
            ['text' => $product['status'] == 1 ? '❌ غیرفعال کردن' : '✅ فعال کردن', 'callback_data' => "admin_toggle_product#{$product_id}"]
        ],
        [
            ['text' => '📁 تغییر دسته‌بندی', 'callback_data' => "admin_change_product_category#{$product_id}"]
        ],
        [
            ['text' => '🗑️ حذف', 'callback_data' => "admin_delete_product_confirm#{$product_id}"]
        ],
        [
            ['text' => '◀️ بازگشت به لیست محصولات', 'callback_data' => 'admin_products']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// تغییر وضعیت محصول (فعال/غیرفعال)
function toggle_admin_product_status($userid, $product_id)
{
    global $telegram;
    
    // دریافت وضعیت فعلی
    $sql = "SELECT status FROM sp_files WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        return false;
    }
    
    $new_status = $product['status'] == 1 ? 0 : 1;
    
    // به‌روزرسانی وضعیت
    $sql = "UPDATE sp_files SET status = :status WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':status', $new_status, PDO::PARAM_INT);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        $status_text = $new_status == 1 ? 'فعال' : 'غیرفعال';
        bot('answercallbackquery', [
            'callback_query_id' => $GLOBALS['cid'],
            'text' => "✅ محصول با موفقیت $status_text شد",
            'show_alert' => false
        ]);

        // نمایش مجدد جزئیات محصول
        show_admin_product_details($userid, $product_id);
        return true;
    }

    return false;
}

// نمایش تأیید حذف محصول
function show_admin_delete_product_confirm($userid, $product_id)
{
    global $telegram;
    
    $sql = "SELECT name FROM sp_files WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        $msg = "❌ محصول یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    $msg = "⚠️ <b>تأیید حذف</b>\n\n";
    $msg .= "آیا مطمئن هستید که می‌خواهید محصول <b>" . htmlspecialchars($product['name']) . "</b> را حذف کنید؟\n\n";
    $msg .= "⚠️ این عمل غیرقابل بازگشت است!";
    
    $keyboard = [
        [
            ['text' => '✅ بله، حذف کن', 'callback_data' => 'admin_delete_product_yes#' . $product_id],
            ['text' => '❌ خیر، لغو', 'callback_data' => 'admin_edit_product#' . $product_id]
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// حذف محصول
function delete_admin_product($userid, $product_id)
{
    global $telegram;
    
    // دریافت نام محصول قبل از حذف
    $sql = "SELECT name FROM sp_files WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        $msg = "❌ محصول یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    $product_name = $product['name'];
    
    // حذف محصول
    $sql = "DELETE FROM sp_files WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        $msg = "✅ محصول <b>" . htmlspecialchars($product_name) . "</b> با موفقیت حذف شد.";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست محصولات', 'callback_data' => 'admin_products']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در حذف محصول.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// نمایش منوی انتخاب دسته‌بندی برای محصول
function show_admin_change_product_category($userid, $product_id)
{
    global $telegram;
    
    // دریافت اطلاعات محصول
    $sql = "SELECT * FROM sp_files WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();
    
    if (!$product) {
        $msg = "❌ محصول یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    // دریافت دسته‌بندی‌ها
    $sql = "SELECT * FROM sp_cats ORDER BY name ASC";
    $categories = $telegram->db->query($sql)->fetchAll();
    
    $msg = "📁 <b>تغییر دسته‌بندی محصول</b>\n\n";
    $msg .= "<b>محصول:</b> " . htmlspecialchars($product['name']) . "\n\n";
    
    if (empty($categories)) {
        $msg .= "❌ دسته‌بندی‌ای یافت نشد. لطفاً ابتدا یک دسته‌بندی ایجاد کنید.";
        $keyboard = [[
            ['text' => '◀️ بازگشت', 'callback_data' => 'admin_edit_product#' . $product_id]
        ]];
    } else {
        $msg .= "لطفاً دسته‌بندی مورد نظر را انتخاب کنید:\n\n";
        
        // نمایش دسته‌بندی فعلی
        if (!empty($product['catid'])) {
            $current_cat_sql = "SELECT name FROM sp_cats WHERE id = :catid LIMIT 1";
            $current_cat_stmt = $telegram->db->prepare($current_cat_sql);
            $current_cat_stmt->bindValue(':catid', $product['catid'], PDO::PARAM_INT);
            $current_cat_stmt->execute();
            $current_cat = $current_cat_stmt->fetch();
            if ($current_cat) {
                $msg .= "📌 دسته‌بندی فعلی: <b>" . htmlspecialchars($current_cat['name']) . "</b>\n\n";
            }
        } else {
            $msg .= "📌 دسته‌بندی فعلی: ❌ تعیین نشده\n\n";
        }
        
        $keyboard = [];
        
        // دکمه حذف دسته‌بندی (اگر دسته‌بندی دارد)
        if (!empty($product['catid'])) {
            $keyboard[] = [['text' => '❌ حذف دسته‌بندی', 'callback_data' => 'admin_remove_product_category#' . $product_id]];
        }
        
        // دکمه‌های دسته‌بندی‌ها
        foreach ($categories as $cat) {
            $is_current = (!empty($product['catid']) && $product['catid'] == $cat['id']);
            $text = $is_current ? '✅ ' . htmlspecialchars($cat['name']) : htmlspecialchars($cat['name']);
            $keyboard[] = [['text' => $text, 'callback_data' => 'admin_set_product_category#' . $product_id . '#' . $cat['id']]];
        }
        
        $keyboard[] = [['text' => '◀️ بازگشت', 'callback_data' => 'admin_edit_product#' . $product_id]];
    }
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// تنظیم دسته‌بندی محصول
function set_admin_product_category($userid, $product_id, $category_id)
{
    global $telegram;
    
    // بررسی وجود دسته‌بندی
    $cat_sql = "SELECT name FROM sp_cats WHERE id = :catid LIMIT 1";
    $cat_stmt = $telegram->db->prepare($cat_sql);
    $cat_stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
    $cat_stmt->execute();
    $category = $cat_stmt->fetch();
    
    if (!$category) {
        bot('answercallbackquery', [
            'callback_query_id' => $GLOBALS['cid'],
            'text' => '❌ دسته‌بندی یافت نشد',
            'show_alert' => true
        ]);
        return false;
    }
    
    // به‌روزرسانی دسته‌بندی محصول
    $sql = "UPDATE sp_files SET catid = :catid WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        bot('answercallbackquery', [
            'callback_query_id' => $GLOBALS['cid'],
            'text' => '✅ دسته‌بندی با موفقیت تغییر کرد',
            'show_alert' => false
        ]);
        
        // نمایش مجدد جزئیات محصول
        show_admin_product_details($userid, $product_id);
        return true;
    }
    
    return false;
}

// حذف دسته‌بندی محصول
function remove_admin_product_category($userid, $product_id)
{
    global $telegram;
    
    // حذف دسته‌بندی (تنظیم catid به 0)
    $sql = "UPDATE sp_files SET catid = 0 WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        bot('answercallbackquery', [
            'callback_query_id' => $GLOBALS['cid'],
            'text' => '✅ دسته‌بندی با موفقیت حذف شد',
            'show_alert' => false
        ]);
        
        // نمایش مجدد جزئیات محصول
        show_admin_product_details($userid, $product_id);
        return true;
    }
    
    return false;
}

