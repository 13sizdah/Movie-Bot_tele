<?php
// ============================================================
// مدیریت ژانرها
// ============================================================

// لیست ژانرها
function show_admin_genres_list($userid)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_genres ORDER BY name ASC";
    $genres = $telegram->db->query($sql)->fetchAll();
    
    $msg = "🏷️ <b>مدیریت ژانرها</b>\n\n";
    
    if (empty($genres)) {
        $msg .= "❌ ژانری یافت نشد.\n\n";
    } else {
        $msg .= "لیست ژانرها:\n\n";
        foreach ($genres as $genre) {
            $msg .= "• " . htmlspecialchars($genre['name']) . "\n";
        }
    }
    
    $keyboard = [
        [
            ['text' => '➕ افزودن ژانر', 'callback_data' => 'admin_add_genre'],
            ['text' => '✏️ ویرایش/حذف', 'callback_data' => 'admin_manage_genres']
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

// نمایش منوی افزودن ژانر
function show_admin_add_genre_menu($userid)
{
    global $telegram;
    
    $msg = "➕ <b>افزودن ژانر جدید</b>\n\n";
    $msg .= "لطفاً نام ژانر را ارسال کنید:\n\n";
    $msg .= "⚠️ برای لغو، /cancel را ارسال کنید.";
    
    // ذخیره وضعیت افزودن ژانر
    file_put_contents('users/' . $userid . '.txt', 'admin_add_genre');
    
    $keyboard = [[
        ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_genre']
    ]];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ذخیره ژانر جدید
function save_admin_genre($userid, $genre_name)
{
    global $telegram;
    
    $genre_name = trim($genre_name);
    
    if (empty($genre_name)) {
        $msg = "❌ نام ژانر نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بررسی تکراری نبودن
    $check_sql = "SELECT id FROM sp_genres WHERE name = :name LIMIT 1";
    $stmt = $telegram->db->prepare($check_sql);
    $stmt->bindValue(':name', $genre_name, PDO::PARAM_STR);
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        $msg = "❌ این ژانر قبلاً ثبت شده است.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // ذخیره ژانر
    $sql = "INSERT INTO sp_genres (name) VALUES (:name)";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':name', $genre_name, PDO::PARAM_STR);
    $result = $stmt->execute();
    
    if ($result) {
        // پاک کردن وضعیت
        file_put_contents('users/' . $userid . '.txt', ' ');
        
        $msg = "✅ ژانر <b>" . htmlspecialchars($genre_name) . "</b> با موفقیت اضافه شد.";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست ژانرها', 'callback_data' => 'admin_genres']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در افزودن ژانر.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// نمایش لیست ژانرها برای ویرایش/حذف
function show_admin_manage_genres($userid)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_genres ORDER BY name ASC";
    $genres = $telegram->db->query($sql)->fetchAll();
    
    $msg = "✏️ <b>ویرایش/حذف ژانرها</b>\n\n";
    
    if (empty($genres)) {
        $msg .= "❌ ژانری یافت نشد.\n\n";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست ژانرها', 'callback_data' => 'admin_genres']
        ]];
    } else {
        $msg .= "لطفاً ژانری را برای ویرایش یا حذف انتخاب کنید:\n\n";
        $keyboard = [];
        
        foreach ($genres as $genre) {
            $keyboard[] = [[
                'text' => htmlspecialchars($genre['name']),
                'callback_data' => 'admin_genre_details#' . $genre['id']
            ]];
        }
        
        $keyboard[] = [['text' => '◀️ بازگشت به لیست ژانرها', 'callback_data' => 'admin_genres']];
    }
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش جزئیات یک ژانر برای ویرایش/حذف
function show_admin_genre_details($userid, $genre_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_genres WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $stmt->execute();
    $genre = $stmt->fetch();
    
    if (!$genre) {
        $msg = "❌ ژانر یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    $msg = "🏷️ <b>جزئیات ژانر</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($genre['name']) . "\n";
    
    $keyboard = [
        [
            ['text' => '✏️ ویرایش', 'callback_data' => 'admin_edit_genre#' . $genre_id],
            ['text' => '🗑️ حذف', 'callback_data' => 'admin_delete_genre_confirm#' . $genre_id]
        ],
        [
            ['text' => '◀️ بازگشت به لیست ویرایش/حذف', 'callback_data' => 'admin_manage_genres']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// نمایش منوی ویرایش ژانر
function show_admin_edit_genre_menu($userid, $genre_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_genres WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $stmt->execute();
    $genre = $stmt->fetch();
    
    if (!$genre) {
        $msg = "❌ ژانر یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    $msg = "✏️ <b>ویرایش ژانر</b>\n\n";
    $msg .= "ژانر فعلی: <b>" . htmlspecialchars($genre['name']) . "</b>\n\n";
    $msg .= "لطفاً نام جدید ژانر را ارسال کنید:\n\n";
    $msg .= "⚠️ برای لغو، /cancel را ارسال کنید.";
    
    // ذخیره وضعیت ویرایش ژانر
    file_put_contents('users/' . $userid . '.txt', 'admin_edit_genre#' . $genre_id);
    
    $keyboard = [[
        ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_edit_genre']
    ]];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// ذخیره ویرایش ژانر
function save_admin_genre_edit($userid, $genre_id, $genre_name)
{
    global $telegram;
    
    $genre_name = trim($genre_name);
    
    if (empty($genre_name)) {
        $msg = "❌ نام ژانر نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بررسی تکراری نبودن (به جز خود این ژانر)
    $check_sql = "SELECT id FROM sp_genres WHERE name = :name AND id != :id LIMIT 1";
    $stmt = $telegram->db->prepare($check_sql);
    $stmt->bindValue(':name', $genre_name, PDO::PARAM_STR);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        $msg = "❌ این ژانر قبلاً ثبت شده است.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // به‌روزرسانی ژانر
    $sql = "UPDATE sp_genres SET name = :name WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':name', $genre_name, PDO::PARAM_STR);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        // پاک کردن وضعیت
        file_put_contents('users/' . $userid . '.txt', ' ');
        
        $msg = "✅ ژانر با موفقیت ویرایش شد.\n\n";
        $msg .= "نام جدید: <b>" . htmlspecialchars($genre_name) . "</b>";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست ژانرها', 'callback_data' => 'admin_genres']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در ویرایش ژانر.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// نمایش تأیید حذف ژانر
function show_admin_delete_genre_confirm($userid, $genre_id)
{
    global $telegram;
    
    $sql = "SELECT * FROM sp_genres WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $stmt->execute();
    $genre = $stmt->fetch();
    
    if (!$genre) {
        $msg = "❌ ژانر یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return;
    }
    
    $msg = "⚠️ <b>تأیید حذف</b>\n\n";
    $msg .= "آیا مطمئن هستید که می‌خواهید ژانر <b>" . htmlspecialchars($genre['name']) . "</b> را حذف کنید؟\n\n";
    $msg .= "این عمل غیرقابل بازگشت است!";
    
    $keyboard = [
        [
            ['text' => '✅ بله، حذف کن', 'callback_data' => 'admin_delete_genre_yes#' . $genre_id],
            ['text' => '❌ خیر، لغو', 'callback_data' => 'admin_genre_details#' . $genre_id]
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// حذف ژانر
function delete_admin_genre($userid, $genre_id)
{
    global $telegram;
    
    // دریافت نام ژانر قبل از حذف
    $sql = "SELECT name FROM sp_genres WHERE id = :id LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $stmt->execute();
    $genre = $stmt->fetch();
    
    if (!$genre) {
        $msg = "❌ ژانر یافت نشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    $genre_name = $genre['name'];
    
    // حذف ژانر
    $sql = "DELETE FROM sp_genres WHERE id = :id";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $genre_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    
    if ($result) {
        $msg = "✅ ژانر <b>" . htmlspecialchars($genre_name) . "</b> با موفقیت حذف شد.";
        $keyboard = [[
            ['text' => '◀️ بازگشت به لیست ژانرها', 'callback_data' => 'admin_genres']
        ]];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        return true;
    } else {
        $msg = "❌ خطا در حذف ژانر.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

