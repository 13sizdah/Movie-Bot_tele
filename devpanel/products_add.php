<?php
// ============================================================
// افزودن محصول (گام به گام)
// ============================================================

// تابع translate_genre از config.php در دسترس است

// نمایش منوی افزودن محصول
function show_admin_add_product_menu($userid)
{
    global $telegram;
    
    $msg = "➕ <b>افزودن محصول سریع از IMDb</b>\n\n";
    $msg .= "📝 می‌توانید:\n";
    $msg .= "• نام فیلم/سریال را به <b>انگلیسی</b> ارسال کنید\n";
    $msg .= "• یا کد <b>IMDb ID</b> را ارسال کنید (مثلاً: tt0133093)\n\n";
    $msg .= "💡 اطلاعات از IMDb دریافت شده و محصول به صورت پیش‌فرض ذخیره می‌شود.\n";
    $msg .= "📝 بعداً می‌توانید جزئیات را از پنل وب تکمیل کنید.\n\n";
    $msg .= "⚠️ برای لغو، /cancel را ارسال کنید.";
    
    // ذخیره وضعیت افزودن محصول
    file_put_contents('users/' . $userid . '.txt', 'admin_add_product_step1');
    
    // پاک کردن اطلاعات موقت قبلی (اگر وجود داشته باشد)
    $temp_file = 'temp/product_' . $userid . '.json';
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    $keyboard = [[
        ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_product']
    ]];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

// مرحله 1: دریافت نام/IMDb ID
function process_admin_add_product_step1($userid, $input_text)
{
    global $telegram;
    
    $input_text = trim($input_text);
    
    if (empty($input_text)) {
        $msg = "❌ لطفاً نام فیلم/سریال یا کد IMDb را وارد کنید.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بررسی اینکه آیا IMDb ID است (شروع با tt)
    $is_imdb_id = preg_match('/^tt\d+$/', $input_text);
    
    // ذخیره اطلاعات موقت
    $temp_data = [
        'step' => 1,
        'name_or_imdb' => $input_text,
        'is_imdb_id' => $is_imdb_id
    ];
    
    $temp_dir = 'temp';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0777, true);
    }
    
    $temp_file = $temp_dir . '/product_' . $userid . '.json';
    file_put_contents($temp_file, json_encode($temp_data, JSON_UNESCAPED_UNICODE));
    
    // اگر IMDb ID بود، اطلاعات را از API دریافت کنیم
    if ($is_imdb_id) {
        return fetch_imdb_info_by_id($userid, $input_text);
    } else {
        // اگر نام بود، سعی کنیم از API اطلاعات بگیریم
        return fetch_imdb_info_by_title($userid, $input_text);
    }
}

// دریافت اطلاعات از IMDb با استفاده از عنوان
function fetch_imdb_info_by_title($userid, $title)
{
    global $telegram;
    
    $msg = "⏳ در حال دریافت اطلاعات از IMDb...\n\n";
    $msg .= "لطفاً کمی صبر کنید...";
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML'
    ]);
    
    // فراخوانی API IMDb
    $api_url = BASEURI . '/web/api/imdb.php?title=' . urlencode($title);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($response)) {
        // اگر خطا بود، بدون اطلاعات IMDb ادامه دهیم
        return show_admin_add_product_step2_no_imdb($userid, $title);
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success'] === true && isset($data['data'])) {
        // اطلاعات با موفقیت دریافت شد
        return process_imdb_data($userid, $data['data'], $title);
    } else {
        // اطلاعات یافت نشد، بدون اطلاعات IMDb ادامه دهیم
        return show_admin_add_product_step2_no_imdb($userid, $title);
    }
}

// دریافت اطلاعات از IMDb با استفاده از IMDb ID
function fetch_imdb_info_by_id($userid, $imdb_id)
{
    global $telegram;
    
    $msg = "⏳ در حال دریافت اطلاعات از IMDb...\n\n";
    $msg .= "لطفاً کمی صبر کنید...";
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML'
    ]);
    
    // استفاده از i parameter برای IMDb ID در OMDb API
    $api_url = BASEURI . '/web/api/imdb.php?imdb_id=' . urlencode($imdb_id);
    
    // اما API فعلی از title استفاده می‌کند، پس باید API را بررسی کنیم
    // برای حالا از عنوان استفاده می‌کنیم (اگر API از IMDb ID پشتیبانی کند، باید آن را اضافه کنیم)
    return fetch_imdb_info_by_title($userid, $imdb_id);
}

// پردازش اطلاعات دریافت شده از IMDb و ذخیره مستقیم
function process_imdb_data($userid, $imdb_data, $original_input)
{
    global $telegram;
    
    // تعیین نوع محصول بر اساس type از IMDb
    $media_type = 'movie';
    if (isset($imdb_data['type'])) {
        if ($imdb_data['type'] == 'series') {
            $media_type = 'series';
        } else {
            $media_type = 'movie';
        }
    }
    
    // آماده‌سازی اطلاعات برای ذخیره
    $name = $imdb_data['title'] ?? $original_input;
    $name_en = $imdb_data['title'] ?? null;
    $description = $imdb_data['plot'] ?? 'توضیحات در دسترس نیست.';
    $catid = 0;
    $fileurl = ''; // لینک دانلود باید بعداً از پنل وب اضافه شود
    $type = 'free'; // پیش‌فرض رایگان
    $year = isset($imdb_data['year']) ? intval($imdb_data['year']) : null;
    
    // پردازش ژانر
    $genre = '';
    if (isset($imdb_data['genre']) && !empty($imdb_data['genre'])) {
        $genres = explode(',', $imdb_data['genre']);
        $genres_fa = array_map(function($g) {
            return translate_genre(trim($g));
        }, $genres);
        $genre = implode('، ', array_filter($genres_fa));
    }
    
    $quality = '';
    $imdb = isset($imdb_data['imdb_rating']) ? $imdb_data['imdb_rating'] : '';
    $director = isset($imdb_data['director']) ? $imdb_data['director'] : '';
    $cast = isset($imdb_data['actors']) ? $imdb_data['actors'] : '';
    $duration = isset($imdb_data['runtime']) ? $imdb_data['runtime'] : '';
    $season = null;
    $episode = null;
    $poster = isset($imdb_data['poster']) && $imdb_data['poster'] != 'N/A' ? $imdb_data['poster'] : '';
    $price = 0;
    $status = 0; // غیرفعال به صورت پیش‌فرض - باید بعداً فعال شود
    $demo = '';
    
    // درج در دیتابیس
    $sql = "INSERT INTO sp_files (name, name_en, description, catid, fileurl, type, media_type, year, genre, quality, imdb, director, cast, duration, season, episode, poster, price, status, demo, views) 
            VALUES (:name, :name_en, :desc, :catid, :fileurl, :type, :media_type, :year, :genre, :quality, :imdb, :director, :cast, :duration, :season, :episode, :poster, :price, :status, :demo, 0)";
    
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':name_en', $name_en, PDO::PARAM_STR);
    $stmt->bindValue(':desc', $description, PDO::PARAM_STR);
    $stmt->bindValue(':catid', $catid, PDO::PARAM_INT);
    $stmt->bindValue(':fileurl', $fileurl, PDO::PARAM_STR);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
    $stmt->bindValue(':media_type', $media_type, PDO::PARAM_STR);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':genre', $genre, PDO::PARAM_STR);
    $stmt->bindValue(':quality', $quality, PDO::PARAM_STR);
    $stmt->bindValue(':imdb', $imdb, PDO::PARAM_STR);
    $stmt->bindValue(':director', $director, PDO::PARAM_STR);
    $stmt->bindValue(':cast', $cast, PDO::PARAM_STR);
    $stmt->bindValue(':duration', $duration, PDO::PARAM_STR);
    $stmt->bindValue(':season', $season, PDO::PARAM_INT);
    $stmt->bindValue(':episode', $episode, PDO::PARAM_INT);
    $stmt->bindValue(':poster', $poster, PDO::PARAM_STR);
    $stmt->bindValue(':price', $price, PDO::PARAM_INT);
    $stmt->bindValue(':status', $status, PDO::PARAM_INT);
    $stmt->bindValue(':demo', $demo, PDO::PARAM_STR);
    
    $result = $stmt->execute();
    
    // پاک کردن وضعیت
    file_put_contents('users/' . $userid . '.txt', ' ');
    
    // پاک کردن فایل موقت (اگر وجود داشته باشد)
    $temp_file = 'temp/product_' . $userid . '.json';
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    if ($result) {
        $product_id = $telegram->db->lastInsertId();
        
        // نمایش پیام موفقیت
        $msg = "✅ <b>محصول با موفقیت افزوده شد!</b>\n\n";
        $msg .= "<b>نام:</b> " . htmlspecialchars($name) . "\n";
        if ($name_en && $name_en != $name) {
            $msg .= "<b>نام انگلیسی:</b> " . htmlspecialchars($name_en) . "\n";
        }
        if ($year) {
            $msg .= "<b>سال:</b> $year\n";
        }
        if ($genre) {
            $msg .= "<b>ژانر:</b> " . htmlspecialchars($genre) . "\n";
        }
        if ($imdb) {
            $msg .= "<b>امتیاز IMDb:</b> $imdb\n";
        }
        $msg .= "<b>نوع:</b> " . ($media_type == 'movie' ? '🎬 فیلم' : '📺 سریال') . "\n";
        $msg .= "<b>شناسه:</b> <code>$product_id</code>\n\n";
        $msg .= "⚠️ <b>نکات مهم:</b>\n";
        $msg .= "• محصول به صورت <b>غیرفعال</b> ذخیره شد\n";
        $msg .= "• لینک دانلود هنوز اضافه نشده است\n";
        $msg .= "• برای تکمیل اطلاعات، ویرایش و فعال کردن محصول از <b>پنل وب</b> استفاده کنید\n\n";
        $msg .= "🌐 لینک پنل وب برای ویرایش:";
        
        $keyboard = [
            [
                ['text' => '✏️ ویرایش در پنل وب', 'url' => BASEURI . '/admin-panel/products.php?edit_product=' . $product_id]
            ],
            [
                ['text' => '📋 مشاهده در ربات', 'callback_data' => 'admin_edit_product#' . $product_id],
                ['text' => '◀️ بازگشت به لیست', 'callback_data' => 'admin_products']
            ]
        ];
        
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
        
        return true;
    } else {
        $msg = "❌ خطا در ذخیره محصول. لطفاً دوباره تلاش کنید.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// نمایش مرحله 2 بدون اطلاعات IMDb
function show_admin_add_product_step2_no_imdb($userid, $title)
{
    global $telegram;
    
    // پاک کردن وضعیت
    file_put_contents('users/' . $userid . '.txt', ' ');
    
    $msg = "❌ <b>اطلاعات از IMDb دریافت نشد</b>\n\n";
    $msg .= "ممکن است فیلم/سریال در IMDb یافت نشده باشد یا نام وارد شده دقیق نباشد.\n\n";
    $msg .= "💡 <b>راهنمایی:</b>\n";
    $msg .= "• نام را به <b>انگلیسی</b> و دقیق وارد کنید\n";
    $msg .= "• یا از کد <b>IMDb ID</b> استفاده کنید (مثلاً: tt0133093)\n";
    $msg .= "• برای افزودن محصول بدون اطلاعات IMDb، از <b>پنل وب</b> استفاده کنید\n\n";
    $msg .= "🌐 لینک پنل وب:";
    
    $keyboard = [
        [
            ['text' => '🌐 افزودن از پنل وب', 'url' => BASEURI . '/admin-panel/products.php?create_product']
        ],
        [
            ['text' => '🔄 تلاش مجدد', 'callback_data' => 'admin_add_product'],
            ['text' => '◀️ بازگشت', 'callback_data' => 'admin_products']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    return false;
}

// مرحله 2: دریافت/تأیید نام فارسی
function process_admin_add_product_step2_name($userid, $name_fa)
{
    global $telegram;
    
    $name_fa = trim($name_fa);
    
    if (empty($name_fa)) {
        $msg = "❌ نام فارسی نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // ذخیره نام فارسی
    $temp_data['name_fa'] = $name_fa;
    if (!isset($temp_data['name_en']) && isset($temp_data['imdb_info']['title'])) {
        $temp_data['name_en'] = $temp_data['imdb_info']['title'];
    }
    
    file_put_contents($temp_file, json_encode($temp_data, JSON_UNESCAPED_UNICODE));
    
    // رفتن به مرحله بعد: انتخاب نوع محصول
    return show_admin_add_product_step3_type($userid);
}

// مرحله 3: انتخاب نوع محصول
function show_admin_add_product_step3_type($userid)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // اگر از IMDb اطلاعات داریم، نوع را پیشنهاد می‌دهیم
    $suggested_type = 'movie';
    if (isset($temp_data['imdb_info']['type'])) {
        $suggested_type = $temp_data['imdb_info']['type'] == 'series' ? 'series' : 'movie';
    }
    
    $msg = "🎬 <b>انتخاب نوع محصول</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($temp_data['name_fa']) . "\n\n";
    $msg .= "لطفاً نوع محصول را انتخاب کنید:";
    
    // به‌روزرسانی وضعیت
    file_put_contents('users/' . $userid . '.txt', 'admin_add_product_step3_type');
    
    $keyboard = [
        [
            ['text' => '🎬 فیلم', 'callback_data' => 'admin_add_product_type#movie'],
            ['text' => '📺 سریال', 'callback_data' => 'admin_add_product_type#series']
        ],
        [
            ['text' => '🎨 انیمیشن', 'callback_data' => 'admin_add_product_type#animation'],
            ['text' => '🌸 انیمه', 'callback_data' => 'admin_add_product_type#anime']
        ],
        [
            ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_product']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    return true;
}

// پردازش انتخاب نوع محصول
function process_admin_add_product_step3_type($userid, $media_type)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // ذخیره نوع محصول
    $temp_data['media_type'] = $media_type;
    file_put_contents($temp_file, json_encode($temp_data, JSON_UNESCAPED_UNICODE));
    
    // رفتن به مرحله بعد: دریافت توضیحات
    return show_admin_add_product_step4_description($userid);
}

// مرحله 4: دریافت توضیحات
function show_admin_add_product_step4_description($userid)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    $msg = "📝 <b>توضیحات محصول</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($temp_data['name_fa']) . "\n";
    $msg .= "<b>نوع:</b> " . ($temp_data['media_type'] == 'movie' ? '🎬 فیلم' : ($temp_data['media_type'] == 'series' ? '📺 سریال' : ($temp_data['media_type'] == 'animation' ? '🎨 انیمیشن' : '🌸 انیمه'))) . "\n\n";
    
    // اگر از IMDb اطلاعات plot داریم، نمایش دهیم
    if (isset($temp_data['imdb_info']['plot']) && !empty($temp_data['imdb_info']['plot'])) {
        $plot = mb_substr($temp_data['imdb_info']['plot'], 0, 200);
        $msg .= "💡 <b>توضیحات پیشنهادی از IMDb:</b>\n" . htmlspecialchars($plot) . "...\n\n";
    }
    
    $msg .= "لطفاً <b>توضیحات</b> محصول را ارسال کنید:\n\n";
    $msg .= "💡 می‌توانید توضیحات IMDb را تایید کنید (دکمه زیر) یا توضیحات جدید وارد کنید.\n\n";
    $msg .= "⚠️ برای استفاده از توضیحات IMDb، دکمه «استفاده از توضیحات IMDb» را بزنید.";
    
    // به‌روزرسانی وضعیت
    file_put_contents('users/' . $userid . '.txt', 'admin_add_product_step4_description');
    
    $keyboard = [];
    if (isset($temp_data['imdb_info']['plot']) && !empty($temp_data['imdb_info']['plot'])) {
        $keyboard[] = [['text' => '✅ استفاده از توضیحات IMDb', 'callback_data' => 'admin_add_product_use_imdb_plot']];
    }
    $keyboard[] = [['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_product']];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    return true;
}

// پردازش توضیحات
function process_admin_add_product_step4_description($userid, $description)
{
    global $telegram;
    
    $description = trim($description);
    
    if (empty($description)) {
        $msg = "❌ توضیحات نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // ذخیره توضیحات
    $temp_data['description'] = $description;
    file_put_contents($temp_file, json_encode($temp_data, JSON_UNESCAPED_UNICODE));
    
    // رفتن به مرحله بعد: دریافت قیمت و نوع دسترسی
    return show_admin_add_product_step5_price($userid);
}

// مرحله 5: دریافت قیمت و نوع دسترسی
function show_admin_add_product_step5_price($userid)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    $msg = "💰 <b>قیمت و نوع دسترسی</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($temp_data['name_fa']) . "\n\n";
    $msg .= "لطفاً نوع دسترسی را انتخاب کنید:";
    
    // به‌روزرسانی وضعیت
    file_put_contents('users/' . $userid . '.txt', 'admin_add_product_step5_price');
    
    $keyboard = [
        [
            ['text' => '🆓 رایگان', 'callback_data' => 'admin_add_product_type_access#free'],
            ['text' => '💎 ویژه (VIP)', 'callback_data' => 'admin_add_product_type_access#vip']
        ],
        [
            ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_product']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    return true;
}

// پردازش انتخاب نوع دسترسی
function process_admin_add_product_step5_price($userid, $access_type)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // ذخیره نوع دسترسی
    $temp_data['type'] = $access_type;
    $temp_data['price'] = 0; // قیمت حذف شده است
    
    file_put_contents($temp_file, json_encode($temp_data, JSON_UNESCAPED_UNICODE));
    
    // رفتن به مرحله بعد: دریافت لینک دانلود
    return show_admin_add_product_step6_download($userid);
}

// مرحله 6: دریافت لینک دانلود
function show_admin_add_product_step6_download($userid)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    $msg = "🔗 <b>لینک دانلود</b>\n\n";
    $msg .= "<b>نام:</b> " . htmlspecialchars($temp_data['name_fa']) . "\n";
    $msg .= "<b>نوع دسترسی:</b> " . ($temp_data['type'] == 'free' ? '🆓 رایگان' : '💎 ویژه') . "\n\n";
    $msg .= "لطفاً <b>لینک دانلود</b> یا <b>File ID</b> تلگرام را ارسال کنید:\n\n";
    $msg .= "💡 می‌توانید لینک مستقیم دانلود یا File ID فایل تلگرام را وارد کنید.";
    
    // به‌روزرسانی وضعیت
    file_put_contents('users/' . $userid . '.txt', 'admin_add_product_step6_download');
    
    $keyboard = [[
        ['text' => '❌ لغو', 'callback_data' => 'admin_cancel_add_product']
    ]];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    return true;
}

// پردازش لینک دانلود
function process_admin_add_product_step6_download($userid, $download_link)
{
    global $telegram;
    
    $download_link = trim($download_link);
    
    if (empty($download_link)) {
        $msg = "❌ لینک دانلود نمی‌تواند خالی باشد.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // ذخیره لینک دانلود
    $temp_data['fileurl'] = $download_link;
    file_put_contents($temp_file, json_encode($temp_data, JSON_UNESCAPED_UNICODE));
    
    // رفتن به مرحله نهایی: نمایش خلاصه و ذخیره
    return show_admin_add_product_summary($userid);
}

// نمایش خلاصه و ذخیره محصول
function show_admin_add_product_summary($userid)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    $msg = "📋 <b>خلاصه اطلاعات محصول</b>\n\n";
    $msg .= "<b>نام فارسی:</b> " . htmlspecialchars($temp_data['name_fa']) . "\n";
    if (!empty($temp_data['name_en'])) {
        $msg .= "<b>نام انگلیسی:</b> " . htmlspecialchars($temp_data['name_en']) . "\n";
    }
    $msg .= "<b>نوع:</b> " . ($temp_data['media_type'] == 'movie' ? '🎬 فیلم' : ($temp_data['media_type'] == 'series' ? '📺 سریال' : ($temp_data['media_type'] == 'animation' ? '🎨 انیمیشن' : '🌸 انیمه'))) . "\n";
    $msg .= "<b>دسترسی:</b> " . ($temp_data['type'] == 'free' ? '🆓 رایگان' : '💎 ویژه') . "\n";
    $msg .= "<b>توضیحات:</b> " . htmlspecialchars(mb_substr($temp_data['description'], 0, 100)) . "...\n\n";
    
    // اگر اطلاعات IMDb داریم، نمایش دهیم
    if (isset($temp_data['imdb_info']) && $temp_data['imdb_info']) {
        if (!empty($temp_data['imdb_info']['year'])) {
            $msg .= "<b>سال:</b> " . $temp_data['imdb_info']['year'] . "\n";
        }
        if (!empty($temp_data['imdb_info']['genre'])) {
            $msg .= "<b>ژانر:</b> " . htmlspecialchars($temp_data['imdb_info']['genre']) . "\n";
        }
        if (!empty($temp_data['imdb_info']['imdb_rating'])) {
            $msg .= "<b>امتیاز IMDb:</b> " . $temp_data['imdb_info']['imdb_rating'] . "\n";
        }
    }
    
    $msg .= "\n✅ آیا اطلاعات درست است؟";
    
    // به‌روزرسانی وضعیت
    file_put_contents('users/' . $userid . '.txt', 'admin_add_product_step7_confirm');
    
    $keyboard = [
        [
            ['text' => '✅ بله، ذخیره کن', 'callback_data' => 'admin_add_product_save'],
            ['text' => '❌ خیر، لغو', 'callback_data' => 'admin_cancel_add_product']
        ]
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    
    return true;
}

// ذخیره محصول نهایی
function save_admin_product($userid)
{
    global $telegram;
    
    // بارگذاری اطلاعات موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    if (!file_exists($temp_file)) {
        $msg = "❌ اطلاعات محصول یافت نشد. لطفاً دوباره شروع کنید.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    
    $temp_data = json_decode(file_get_contents($temp_file), true);
    
    // استخراج اطلاعات IMDb
    $imdb_info = $temp_data['imdb_info'] ?? null;
    
    // آماده‌سازی داده‌ها برای درج
    $name = $temp_data['name_fa'];
    $name_en = $temp_data['name_en'] ?? null;
    $description = $temp_data['description'];
    $catid = 0; // دسته‌بندی حذف شده
    $fileurl = $temp_data['fileurl'];
    $type = $temp_data['type'];
    $media_type = $temp_data['media_type'];
    $year = isset($imdb_info['year']) ? intval($imdb_info['year']) : null;
    
    // پردازش ژانر
    $genre = '';
    if (isset($imdb_info['genre']) && !empty($imdb_info['genre'])) {
        // تبدیل ژانرهای انگلیسی به فارسی
        $genres = explode(',', $imdb_info['genre']);
        $genres_fa = array_map(function($g) {
            return translate_genre(trim($g));
        }, $genres);
        $genre = implode('، ', array_filter($genres_fa));
    }
    
    $quality = '';
    $imdb = isset($imdb_info['imdb_rating']) ? $imdb_info['imdb_rating'] : '';
    $director = isset($imdb_info['director']) ? $imdb_info['director'] : '';
    $cast = isset($imdb_info['actors']) ? $imdb_info['actors'] : '';
    $duration = isset($imdb_info['runtime']) ? $imdb_info['runtime'] : '';
    $season = null;
    $episode = null;
    $poster = isset($imdb_info['poster']) ? $imdb_info['poster'] : '';
    $price = 0;
    $status = 1; // فعال
    $demo = '';
    
    // درج در دیتابیس
    $sql = "INSERT INTO sp_files (name, name_en, description, catid, fileurl, type, media_type, year, genre, quality, imdb, director, cast, duration, season, episode, poster, price, status, demo, views) 
            VALUES (:name, :name_en, :desc, :catid, :fileurl, :type, :media_type, :year, :genre, :quality, :imdb, :director, :cast, :duration, :season, :episode, :poster, :price, :status, :demo, 0)";
    
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
    $stmt->bindValue(':name_en', $name_en, PDO::PARAM_STR);
    $stmt->bindValue(':desc', $description, PDO::PARAM_STR);
    $stmt->bindValue(':catid', $catid, PDO::PARAM_INT);
    $stmt->bindValue(':fileurl', $fileurl, PDO::PARAM_STR);
    $stmt->bindValue(':type', $type, PDO::PARAM_STR);
    $stmt->bindValue(':media_type', $media_type, PDO::PARAM_STR);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':genre', $genre, PDO::PARAM_STR);
    $stmt->bindValue(':quality', $quality, PDO::PARAM_STR);
    $stmt->bindValue(':imdb', $imdb, PDO::PARAM_STR);
    $stmt->bindValue(':director', $director, PDO::PARAM_STR);
    $stmt->bindValue(':cast', $cast, PDO::PARAM_STR);
    $stmt->bindValue(':duration', $duration, PDO::PARAM_STR);
    $stmt->bindValue(':season', $season, PDO::PARAM_INT);
    $stmt->bindValue(':episode', $episode, PDO::PARAM_INT);
    $stmt->bindValue(':poster', $poster, PDO::PARAM_STR);
    $stmt->bindValue(':price', $price, PDO::PARAM_INT);
    $stmt->bindValue(':status', $status, PDO::PARAM_INT);
    $stmt->bindValue(':demo', $demo, PDO::PARAM_STR);
    
    $result = $stmt->execute();
    
    if ($result) {
        $product_id = $telegram->db->lastInsertId();
        
        // پاک کردن فایل موقت
        unlink($temp_file);
        
        // پاک کردن وضعیت
        file_put_contents('users/' . $userid . '.txt', ' ');
        
        $msg = "✅ <b>محصول با موفقیت اضافه شد!</b>\n\n";
        $msg .= "<b>نام:</b> " . htmlspecialchars($name) . "\n";
        $msg .= "<b>شناسه:</b> <code>$product_id</code>\n\n";
        $msg .= "🎉 محصول شما آماده استفاده است.";
        
        $keyboard = [[
            ['text' => '📋 مشاهده محصول', 'callback_data' => 'admin_edit_product#' . $product_id],
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
        $msg = "❌ خطا در ذخیره محصول. لطفاً دوباره تلاش کنید.";
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
}

// لغو افزودن محصول
function cancel_admin_add_product($userid)
{
    global $telegram;
    
    // پاک کردن فایل موقت
    $temp_file = 'temp/product_' . $userid . '.json';
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    
    // پاک کردن وضعیت
    file_put_contents('users/' . $userid . '.txt', ' ');
    
    $msg = "❌ افزودن محصول لغو شد.";
    
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

