<?php
// ============================================================
// مدیریت دسته‌بندی‌ها
// ============================================================

// لیست دسته‌بندی‌ها
function show_admin_categories_list($userid)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_cats ORDER BY name ASC";
    $categories = $telegram->db->query($sql)->fetchAll();
    
    $msg = "📁 <b>مدیریت دسته‌بندی‌ها</b>\n\n";
    
    if (empty($categories)) {
        $msg .= "❌ دسته‌بندی‌ای یافت نشد.\n\n";
    } else {
        $msg .= "لیست دسته‌بندی‌ها:\n\n";
        foreach ($categories as $cat) {
            // شمارش تعداد محصولات در این دسته‌بندی
            $count_sql = "SELECT COUNT(*) as count FROM sp_files WHERE catid = :catid AND status = 1";
            $count_stmt = $telegram->db->prepare($count_sql);
            $count_stmt->bindValue(':catid', $cat['id'], PDO::PARAM_INT);
            $count_stmt->execute();
            $product_count = $count_stmt->fetch()['count'];
            
            $msg .= "• <b>" . htmlspecialchars($cat['name']) . "</b> ({$product_count} محصول)\n";
        }
    }
    
    $keyboard = [
        [
            ['text' => '➕ افزودن دسته‌بندی', 'callback_data' => 'admin_add_category'],
            ['text' => '✏️ ویرایش/حذف', 'callback_data' => 'admin_manage_categories']
        ],
        [
            ['text' => '◀️ بازگشت به منوی مدیریت', 'callback_data' => 'admin_main_menu']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش منوی افزودن دسته‌بندی
function show_admin_add_category_menu($userid)
{
    global $telegram;
    
    $msg = "➕ <b>افزودن دسته‌بندی جدید</b>\n\n";
    $msg .= "لطفاً نام دسته‌بندی را ارسال کنید:\n\n";
    $msg .= "⚠️ برای لغو، /cancel را ارسال کنید.";
    
    // ذخیره وضعیت کاربر
    file_put_contents('users/' . $userid . '.txt', 'admin_add_category');
    
    $keyboard = [
        [['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_category']]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ذخیره دسته‌بندی جدید
function save_admin_category($userid, $category_name)
{
    global $telegram;
    
    if (empty(trim($category_name))) {
        $msg = "❌ نام دسته‌بندی نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بررسی تکراری نبودن نام
    $check_sql = "SELECT id FROM sp_cats WHERE name = :name LIMIT 1";
    $check_stmt = $telegram->db->prepare($check_sql);
    $check_stmt->bindValue(':name', trim($category_name), PDO::PARAM_STR);
    $check_stmt->execute();
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        $msg = "❌ دسته‌بندی با این نام قبلاً وجود دارد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // افزودن دسته‌بندی
    $sql = "INSERT INTO sp_cats (name) VALUES (:name)";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':name', trim($category_name), PDO::PARAM_STR);
    $result = $stmt->execute();
    
    if ($result) {
        file_put_contents('users/' . $userid . '.txt', ' ');
        $msg = "✅ دسته‌بندی <b>" . htmlspecialchars($category_name) . "</b> با موفقیت افزوده شد.";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست دسته‌بندی‌ها', 'callback_data' => 'admin_categories']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در افزودن دسته‌بندی.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// نمایش لیست دسته‌بندی‌ها برای ویرایش/حذف
function show_admin_manage_categories($userid)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_cats ORDER BY name ASC";
    $categories = $telegram->db->query($sql)->fetchAll();
    
    $msg = "✏️ <b>ویرایش/حذف دسته‌بندی‌ها</b>\n\n";
    
    if (empty($categories)) {
        $msg .= "❌ دسته‌بندی‌ای یافت نشد.\n\n";
        $keyboard = [[
            ['text' => '◀️ بازگشت', 'callback_data' => 'admin_categories']
        ]];
    } else {
        $msg .= "لطفاً دسته‌بندی مورد نظر را انتخاب کنید:\n\n";
        $keyboard = [];
        
        foreach ($categories as $cat) {
            $keyboard[] = [['text' => htmlspecialchars($cat['name']), 'callback_data' => 'admin_category_details#' . $cat['id']]];
        }
        
        $keyboard[] = [['text' => '◀️ بازگشت', 'callback_data' => 'admin_categories']];
    }
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش جزئیات یک دسته‌بندی
function show_admin_category_details($userid, $category_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_cats WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        $msg = "❌ دسته‌بندی یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    // شمارش تعداد محصولات
    $count_sql = "SELECT COUNT(*) as count FROM sp_files WHERE catid = :catid";
    $count_stmt = $telegram->db->prepare($count_sql);
    $count_stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $product_count = $count_stmt->fetch()['count'];
    
    $msg = "📁 <b>جزئیات دسته‌بندی</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($category['name']) . "\n";
    $msg .= "<b>تعداد محصولات:</b> " . number_format($product_count) . "\n";
    
    $keyboard = [
        [
            ['text' => '✏️ ویرایش', 'callback_data' => 'admin_edit_category#' . $category_id],
            ['text' => '🗑️ حذف', 'callback_data' => 'admin_delete_category_confirm#' . $category_id]
        ],
        [
            ['text' => '◀️ بازگشت', 'callback_data' => 'admin_manage_categories']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش منوی ویرایش دسته‌بندی
function show_admin_edit_category_menu($userid, $category_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_cats WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        $msg = "❌ دسته‌بندی یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    $msg = "✏️ <b>ویرایش دسته‌بندی</b>\n\n";
    $msg .= "دسته‌بندی فعلی: <b>" . htmlspecialchars($category['name']) . "</b>\n\n";
    $msg .= "لطفاً نام جدید را ارسال کنید:\n\n";
    $msg .= "⚠️ برای لغو، /cancel را ارسال کنید.";
    
    // ذخیره وضعیت کاربر
    file_put_contents('users/' . $userid . '.txt', 'admin_edit_category#' . $category_id);
    
    $keyboard = [
        [['text' => '❌ لغو', 'callback_data' => 'admin_cancel_edit_category']]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ذخیره ویرایش دسته‌بندی
function save_admin_category_edit($userid, $category_id, $new_name)
{
    global $telegram;
    
    if (empty(trim($new_name))) {
        $msg = "❌ نام دسته‌بندی نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بررسی تکراری نبودن نام
    $check_sql = "SELECT id FROM sp_cats WHERE name = :name AND id != :id LIMIT 1";
    $check_stmt = $telegram->db->prepare($check_sql);
    $check_stmt->bindValue(':name', trim($new_name), PDO::PARAM_STR);
    $check_stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        $msg = "❌ دسته‌بندی با این نام قبلاً وجود دارد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // به‌روزرسانی دسته‌بندی
    $sql = "UPDATE sp_cats SET name = :name WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':name', trim($new_name), PDO::PARAM_STR);
    $stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        file_put_contents('users/' . $userid . '.txt', ' ');
        $msg = "✅ دسته‌بندی با موفقیت ویرایش شد.";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست دسته‌بندی‌ها', 'callback_data' => 'admin_categories']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در ویرایش دسته‌بندی.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// نمایش تأیید حذف دسته‌بندی
function show_admin_delete_category_confirm($userid, $category_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_cats WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        $msg = "❌ دسته‌بندی یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    // شمارش تعداد محصولات
    $count_sql = "SELECT COUNT(*) as count FROM sp_files WHERE catid = :catid";
    $count_stmt = $telegram->db->prepare($count_sql);
    $count_stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $product_count = $count_stmt->fetch()['count'];
    
    $msg = "⚠️ <b>تأیید حذف</b>\n\n";
    $msg .= "آیا مطمئن هستید که می‌خواهید دسته‌بندی <b>" . htmlspecialchars($category['name']) . "</b> را حذف کنید؟\n\n";
    
    if ($product_count > 0) {
        $msg .= "⚠️ <b>هشدار:</b> این دسته‌بندی دارای $product_count محصول است. با حذف آن، محصولات بدون دسته‌بندی می‌شوند.\n\n";
    }
    
    $msg .= "⚠️ این عمل غیرقابل بازگشت است!";
    
    $keyboard = [
        [
            ['text' => '✅ بله، حذف کن', 'callback_data' => 'admin_delete_category_yes#' . $category_id],
            ['text' => '❌ خیر، لغو', 'callback_data' => 'admin_category_details#' . $category_id]
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// حذف دسته‌بندی
function delete_admin_category($userid, $category_id)
{
    global $telegram;
    
    // دریافت نام دسته‌بندی قبل از حذف
    $sql = "SELECT name FROM sp_cats WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch();
    
    if (!$category) {
        $msg = "❌ دسته‌بندی یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    $category_name = $category['name'];
    
    // حذف دسته‌بندی
    $sql = "DELETE FROM sp_cats WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        // به‌روزرسانی محصولات: حذف catid از محصولات این دسته‌بندی (اختیاری - می‌توانید catid را 0 کنید)
        // $update_sql = "UPDATE sp_files SET catid = 0 WHERE catid = :catid";
        // $update_stmt = $telegram->db->prepare($update_sql);
        // $update_stmt->bindValue(':catid', $category_id, PDO::PARAM_INT);
        // $update_stmt->execute();
        
        $msg = "✅ دسته‌بندی <b>" . htmlspecialchars($category_name) . "</b> با موفقیت حذف شد.";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست دسته‌بندی‌ها', 'callback_data' => 'admin_categories']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در حذف دسته‌بندی.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

