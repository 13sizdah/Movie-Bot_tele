<?php

$telegram = new telegram(TOKEN, HOST, USERNAME, PASSWORD, DBNAME);
$result = $telegram->getTxt();

// user initializing
$baseuri = BASEURI;
$userid = isset($result->message->from->id) ? $result->message->from->id : (isset($result->callback_query->from->id) ? $result->callback_query->from->id : null);
$text = isset($result->message->text) ? $result->message->text : null;
$fname = isset($result->message->from->first_name) ? $result->message->from->first_name : (isset($result->callback_query->from->first_name) ? $result->callback_query->from->first_name : '');
$lname = isset($result->message->from->last_name) ? $result->message->from->last_name : (isset($result->callback_query->from->last_name) ? $result->callback_query->from->last_name : '');
$username = isset($result->message->from->username) ? $result->message->from->username : (isset($result->callback_query->from->username) ? $result->callback_query->from->username : '');
$date = jdate('Y/m/d');
$contact = isset($result->message->contact->phone_number) ? $result->message->contact->phone_number : '';
$contact = str_replace('+', "", $contact);
$msgid = isset($result->message->message_id) ? $result->message->message_id : null;
$time = time();
$fileid = isset($result->message->document->file_id) ? $result->message->document->file_id : null;



// callbacks
$cid = isset($result->callback_query->id) ? $result->callback_query->id : null;
$cdata = isset($result->callback_query->data) ? $result->callback_query->data : null;
$cmsgid = isset($result->callback_query->message->message_id) ? $result->callback_query->message->message_id : null;
$cuserid = isset($result->callback_query->from->id) ? $result->callback_query->from->id : null;



// upload file
if (isset($result->message)) {
    if (isset($result->message->document->file_id)) {
        $fileid = $result->message->document->file_id;
    } elseif (isset($result->message->audio->file_id)) {
        $fileid = $result->message->audio->file_id;
    } elseif (isset($result->message->video->file_id)) {
        $fileid = $result->message->video->file_id;
    } elseif (isset($result->message->photo) && is_array($result->message->photo) && count($result->message->photo) > 0) {
        if (isset($result->message->photo[2]->file_id)) {
            $fileid = $result->message->photo[2]->file_id;
        } elseif (isset($result->message->photo[1]->file_id)) {
            $fileid = $result->message->photo[1]->file_id;
        } elseif (isset($result->message->photo[0]->file_id)) {
            $fileid = $result->message->photo[0]->file_id;
        }
    } elseif (isset($result->message->voice->file_id)) {
        $fileid = $result->message->voice->file_id;
    }
    if (isset($result->message->message_id)) {
        $msgid = $result->message->message_id;
    }
}

// این بخش برای آپلود فایل استفاده می‌شود - در upload_functions.php پردازش می‌شود
// اگر در حالت آپلود نیستیم، File ID را نمایش می‌دهیم
if ($userid == $admin and $fileid) {
    $status_file = 'users/' . $userid . '.txt';
    $status = file_exists($status_file) ? file_get_contents($status_file) : '';
    // اگر در حالت آپلود نیستیم، File ID را نمایش بده
    if (strpos($status, 'upload_') !== 0) {
        bot('sendMessage', [
            'chat_id' => $userid,
            'text' => "File ID: $fileid",
            'reply_to_message_id' => $msgid
        ]);
    }
}

function fa_num($input)
{
    $en_nums = range(0, 9);
    $fa_nums = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $output = str_replace($en_nums, $fa_nums, $input);
    return $output;
}

function options($name)
{
    // Fetch buttons texts from database
    global $telegram;
    $sql = "select * from sp_options where name='$name'";
    $db = $telegram->db->query($sql);
    $option = $db->fetch();
    return trim($option['value']);
}


function numeric_id()
{
    // Creates a new file by the name of user's id in /users directory
    global $userid;
    $usernumericid = 'users/' . $userid . '.txt';
    if (!file_exists($usernumericid)) {
        $userfile = fopen('users/' . $userid . '.txt', "w");
        fclose($userfile);
    }
}
function check_new_user()
{
    // If new User detected, insert his/her data to sp_users table
    global $userid, $telegram, $fname, $lname, $username;
    $sql = "select * from sp_users where userid=" . $userid;
    $db = $telegram->db->query($sql);
    $count = $db->rowCount();
    if ($count == 0) {
        numeric_id();
        $sql = "INSERT INTO sp_users (id,userid,name,username,phone,vip_date,vip_plan,vip_refid,verified) VALUES (NULL,'$userid','$fname.$lname','$username',0,0,0,0,0)";
        $telegram->db->query($sql);
    }
}

function get_phone()
{
    // Receives phone number if user sends it
    global $contact, $phone_verified, $telegram, $userid, $go_to_home_keyboard;
    if (isset($contact)) {
        if (validate_phone()) {
            update_number();
            // update_number خودش کانال‌ها را بررسی می‌کند
        }
    }
}
function update_number()
{
    // Insert user's phone number to database 
    // Supports both Iranian and international numbers
    global $contact, $userid, $telegram, $phone_verified, $go_to_home_keyboard;
    // Only convert 98 to 0 for Iranian numbers (starting with 98)
    if (preg_match("/^98\d{9}$/", $contact)) {
        $contact = str_replace('98', "0", $contact);
    }
    // For other international numbers, keep as is
    $sql = "UPDATE sp_users SET phone = '$contact', verified = '1' WHERE sp_users.userid = '$userid'";
    $telegram->db->query($sql);
    
    // بعد از تایید شماره، بررسی عضویت در کانال‌ها
    include_once 'channels_system.php';
    $channels_ok = check_channels_after_verification($userid);
    
    if ($channels_ok === true) {
        // همه کانال‌ها عضو شده
        $msg = $phone_verified;
        $telegram->sendMessageCURL($userid, $msg, $go_to_home_keyboard);
    } else {
        // نیاز به عضویت در کانال‌ها
        show_required_channels($userid);
    }
}

function is_vip($userid)
{
    // Check if User is VIP or not - If user is vip returns remaining days 
    global $userid, $telegram, $time, $vip_days, $day;
    $sql = "select * from sp_users WHERE userid='$userid'";
    $db = $telegram->db->query($sql);
    $user = $db->fetch(PDO::FETCH_ASSOC);
    $vip_date = $user['vip_date'];
    $now = date($time);
    $day = $vip_date - $now;
    if ($day > 0) {
        $vip_days = number_format($day / 60 / 60 / 24);
        return true;
    } else {
        return false;
    }
}


function is_verified($userid)
{
    // Check if user's Phone is Verified or Not
    check_new_user();
    global $telegram;
    global $verified;
    $sql = "select * from sp_users WHERE userid='$userid'";
    $db = $telegram->db->query($sql);
    $user = $db->fetch(PDO::FETCH_ASSOC);
    $verified = $user['verified'];
    if ($verified == 0) {
        return false;
    } elseif ($verified == 1) {
        return true;
    } else {
        return false;
    }
}
function request_phone()
{
    global $requst_phone_msg, $telegram, $userid, $phone_send_keyboard;
    $msg = $requst_phone_msg;
    $telegram->sendMessageCURL($userid, $msg, $phone_send_keyboard);
}

function validate_phone()
{
    // check if the phone number is owned by sender or not. Prevents cheating (Share Contact);
    // check if the number format is valid (accepts international numbers)
    global $result, $contact, $phone_cheating, $telegram, $userid, $wrong_format, $phone_send_keyboard;
    if (isset($contact)) {
        if (isset($result->message->contact)) {
            if (isset($result->message->contact->user_id) && $result->message->contact->user_id == $result->message->from->id) {
                // Accept any phone number format (international numbers included)
                // Minimum 7 digits, maximum 15 digits (E.164 standard)
                if (preg_match("/^\d{7,15}$/", $contact)) {
                    return true;
                } else {
                    $msg = $wrong_format;
                    $telegram->sendMessageCURL($userid, $msg, $phone_send_keyboard);
                    exit;
                }
            } else {
                $msg = $phone_cheating;
                $telegram->sendMessageCURL($userid, $msg, $phone_send_keyboard);
            }
        }
    }
}

function inline_close_btn()
{
    global $userid, $telegram, $cdata;
    if (preg_match('/exit/', $cdata)) {
        $input = explode('#', $cdata);
        $msgid = $input[1];
        $userid = $input[2];
        $msgid = $msgid + 1;
        $telegram->deleteMessage($userid, $msgid);
    }
    if (preg_match('/close/', $cdata)) {
        $input = explode('#', $cdata);
        $msgid = $input[1];
        $userid = $input[2];
        $telegram->deleteMessage($userid, $msgid);
    }
}
// نمایش لیست فیلم‌ها (دسته‌بندی‌ها)
function show_movies_list()
{
    global $telegram, $userid, $main_keyboard, $back_to_cats, $cat_column_number, $cats_msg, $empty_cats;
    
    // دریافت دسته‌بندی‌هایی که فیلم دارند
    $sql = "SELECT DISTINCT c.id, c.name FROM sp_cats c 
            INNER JOIN sp_files f ON f.catid = c.id 
            WHERE f.media_type='movie' AND f.status=1 
            ORDER BY c.name ASC";
    $db = $telegram->db->query($sql);
    $cats = $db->fetchAll();
    
    if (empty($cats)) {
        $msg = $empty_cats ? $empty_cats : "🎬 هیچ دسته‌بندی برای فیلم‌ها یافت نشد.";
        $telegram->sendMessageCURL($userid, $msg, $main_keyboard);
        return;
    }
    
    $keyboard = [];
    foreach ($cats as $cat) {
        $cat_id = $cat['id'];
        $cat_name = $cat['name'];
        $keyboard[] = ['text' => "$cat_name", 'callback_data' => "cat#movie#$cat_id"];
    }
    $keyboard = array_chunk($keyboard, $cat_column_number);
    $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
    array_push($keyboard, $back_btn);
    
    $msg = $cats_msg ? $cats_msg : "🎬 <b>فیلم‌ها</b>\n\nلطفاً دسته‌بندی مورد نظر خود را انتخاب کنید:";
    $result = bot('sendMessage', [
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text' => $msg,
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    if (!$result || (isset($result->ok) && !$result->ok)) {
        error_log("Error sending movies categories list: " . (isset($result->description) ? $result->description : 'Unknown error'));
    }
}

// نمایش لیست سریال‌ها (دسته‌بندی‌ها)
function show_series_list()
{
    global $telegram, $userid, $main_keyboard, $back_to_cats, $cat_column_number, $cats_msg, $empty_cats;
    
    // دریافت دسته‌بندی‌هایی که سریال دارند
    $sql = "SELECT DISTINCT c.id, c.name FROM sp_cats c 
            INNER JOIN sp_files f ON f.catid = c.id 
            WHERE f.media_type IN ('series', 'animation', 'anime') AND f.status=1 
            ORDER BY c.name ASC";
    $db = $telegram->db->query($sql);
    $cats = $db->fetchAll();
    
    if (empty($cats)) {
        $msg = $empty_cats ? $empty_cats : "📺 هیچ دسته‌بندی برای سریال‌ها یافت نشد.";
        $telegram->sendMessageCURL($userid, $msg, $main_keyboard);
        return;
    }
    
    $keyboard = [];
    foreach ($cats as $cat) {
        $cat_id = $cat['id'];
        $cat_name = $cat['name'];
        $keyboard[] = ['text' => "$cat_name", 'callback_data' => "cat#series#$cat_id"];
    }
    $keyboard = array_chunk($keyboard, $cat_column_number);
    $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
    array_push($keyboard, $back_btn);
    
    $msg = $cats_msg ? $cats_msg : "📺 <b>سریال‌ها</b>\n\nلطفاً دسته‌بندی مورد نظر خود را انتخاب کنید:";
    $result = bot('sendMessage', [
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text' => $msg,
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    if (!$result || (isset($result->ok) && !$result->ok)) {
        error_log("Error sending series categories list: " . (isset($result->description) ? $result->description : 'Unknown error'));
    }
}



function show_selected_category_products()
{
    pagination();
    global $telegram, $cdata, $cid, $empty_cat, $products_list_waiting, $cuserid, $cmsgid, $choose_product, $products_column_number, $pages, $back_to_cats;
    
    // نمایش محصولات بر اساس دسته‌بندی
    if (isset($cdata) && !empty($cdata) && preg_match('/^cat#/', $cdata)) {
        $input = explode('#', $cdata);
        
        // فرمت جدید: cat#media_type#cat_id
        // فرمت قدیمی (سازگاری): cat#cat_id
        if (count($input) == 3) {
            // فرمت جدید
            $media_type_filter = $input[1]; // movie یا series
            $cat_id = intval($input[2]);
        } elseif (count($input) == 2) {
            // فرمت قدیمی - برای سازگاری
            $cat_id = intval($input[1]);
            $media_type_filter = null; // فیلتر نشود
        } else {
            return; // فرمت نامعتبر
        }
        
        if ($cat_id <= 0) {
            return; // اگر cat_id نامعتبر بود، خروج
        }
        
        // ساخت کوئری با فیلتر media_type
        if ($media_type_filter == 'movie') {
            $sql = "select * from sp_files WHERE catid='$cat_id' AND media_type='movie' AND status=1 ORDER BY id DESC LIMIT 5";
        } elseif ($media_type_filter == 'series') {
            $sql = "select * from sp_files WHERE catid='$cat_id' AND media_type IN ('series', 'animation', 'anime') AND status=1 ORDER BY id DESC LIMIT 5";
        } else {
            // فرمت قدیمی - بدون فیلتر media_type
            $sql = "select * from sp_files WHERE catid='$cat_id' and status=1 ORDER BY id DESC LIMIT 5";
        }
        
        $db = $telegram->db->query($sql);
        $products = $db->fetchAll();



        if (empty($products)) {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => $empty_cat,
                'show_alert' => false
            ]);
        } else {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => $products_list_waiting,
                'show_alert' => false
            ]);
            $keyboard = [];
            foreach ($products as $product) {
                $id = $product['id'];
                $name = $product['name'];
                $name = fa_num($name);
                $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
            }

            // ساخت کوئری برای pagination با فیلتر media_type
            if ($media_type_filter == 'movie') {
                $sql2 = "select * from sp_files WHERE catid='$cat_id' AND media_type='movie' AND status=1";
            } elseif ($media_type_filter == 'series') {
                $sql2 = "select * from sp_files WHERE catid='$cat_id' AND media_type IN ('series', 'animation', 'anime') AND status=1";
            } else {
                // فرمت قدیمی
                $sql2 = "select * from sp_files WHERE catid='$cat_id' and status=1";
            }
            $db2 = $telegram->db->query($sql2);
            $products2 = $db2->fetchAll();

            $page = 1;
            $count = count($products2);
            $pages = ceil($count / 5);
            if ($pages <= 1) {
                $pagination = [];
            } else {
                $pagination = [];
                while ($page <= $pages) {
                    $pagenumber = ['text' => fa_num($page), 'callback_data' => "page#$page#$cat_id"];
                    array_push($pagination, $pagenumber);
                    $page++;
                }
            }

            $keyboard = array_chunk($keyboard, 1);
            $pagination_keyboard = array(
                $pagination
            );
            $keyboar_with_pagination = array_merge($keyboard, $pagination_keyboard);

            // تعیین دکمه بازگشت بر اساس نوع محصول
            $back_callback = 'back_to_cats';
            $back_text = '◀️ بازگشت به منو';
            if (!empty($products)) {
                $first_product = $products[0];
                $media_type = isset($first_product['media_type']) ? $first_product['media_type'] : '';
                if ($media_type == 'movie') {
                    $back_callback = 'back_to_movies';
                    $back_text = '◀️ بازگشت به فیلم‌ها';
                } elseif (in_array($media_type, ['series', 'animation', 'anime'])) {
                    $back_callback = 'back_to_series';
                    $back_text = '◀️ بازگشت به سریال‌ها';
                }
            }
            $back_btn = [['text' => $back_text, 'callback_data' => $back_callback]];
            array_push($keyboar_with_pagination, $back_btn);
            bot('editMessageText', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'text' => $choose_product,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboar_with_pagination
                ])
            ]);
        }
    }
}

function pagination()
{
    global $telegram, $cdata, $cid, $empty_cat, $products_list_waiting, $pages, $products_column_number, $cuserid, $cmsgid, $choose_product, $main_keyboard, $back_to_cats;
    // بررسی دقیق‌تر برای pagination - باید با page# شروع شود
    if (isset($cdata) && !empty($cdata) && preg_match('/^page#/', $cdata)) {
        $input = explode('#', $cdata);
        $current_page = $input[1];
        
        // فرمت جدید: page#page_number#media_type#cat_id
        // فرمت قدیمی (سازگاری): page#page_number#cat_id
        if (count($input) == 4) {
            // فرمت جدید
            $media_type_filter = $input[2];
            $cat_id = intval($input[3]);
        } elseif (count($input) == 3) {
            // فرمت قدیمی
            $cat_id = intval($input[2]);
            $media_type_filter = null;
        } else {
            return; // فرمت نامعتبر
        }
        
        $product_per_page = 5;
        $offset = ($current_page - 1) * $product_per_page;
        
        // ساخت کوئری با فیلتر media_type
        if ($media_type_filter == 'movie') {
            $sql = "select * from sp_files WHERE catid='$cat_id' AND media_type='movie' AND status=1 ORDER BY id DESC LIMIT 5 OFFSET $offset";
        } elseif ($media_type_filter == 'series') {
            $sql = "select * from sp_files WHERE catid='$cat_id' AND media_type IN ('series', 'animation', 'anime') AND status=1 ORDER BY id DESC LIMIT 5 OFFSET $offset";
        } else {
            // فرمت قدیمی - بدون فیلتر media_type
            $sql = "select * from sp_files WHERE catid='$cat_id' and status=1 ORDER BY id DESC LIMIT 5 OFFSET $offset";
        }
        
        $db = $telegram->db->query($sql);
        $products = $db->fetchAll();


        $keyboard = [];
        foreach ($products as $product) {
            $id = $product['id'];
            $name = $product['name'];
            $name = fa_num($name);
            $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
        }

        // ساخت کوئری برای شمارش کل با فیلتر media_type
        if ($media_type_filter == 'movie') {
            $sql2 = "select * from sp_files WHERE catid='$cat_id' AND media_type='movie' AND status=1";
        } elseif ($media_type_filter == 'series') {
            $sql2 = "select * from sp_files WHERE catid='$cat_id' AND media_type IN ('series', 'animation', 'anime') AND status=1";
        } else {
            // فرمت قدیمی
            $sql2 = "select * from sp_files WHERE catid='$cat_id' and status=1";
        }
        $db2 = $telegram->db->query($sql2);
        $products2 = $db2->fetchAll();

        $page = 1;
        $count = count($products2);
        $pages = ceil($count / 5);

        $pagination = [];
        while ($page <= $pages) {
            // اضافه کردن media_type به callback_data برای pagination
            if ($media_type_filter) {
                if ($current_page == $page) {
                    $pagenumber = ['text' => "✅ " . fa_num($page), 'callback_data' => "page#$page#$media_type_filter#$cat_id"];
                } else {
                    $pagenumber = ['text' => fa_num($page), 'callback_data' => "page#$page#$media_type_filter#$cat_id"];
                }
            } else {
                // فرمت قدیمی
                if ($current_page == $page) {
                    $pagenumber = ['text' => "✅ " . fa_num($page), 'callback_data' => "page#$page#$cat_id"];
                } else {
                    $pagenumber = ['text' => fa_num($page), 'callback_data' => "page#$page#$cat_id"];
                }
            }
            array_push($pagination, $pagenumber);
            $page++;
        }


        $keyboard = array_chunk($keyboard, 1);
        $pagination_keyboard = array(
            $pagination
        );
        $keyboar_with_pagination = array_merge($keyboard, $pagination_keyboard);

        // تعیین دکمه بازگشت بر اساس نوع محصول
        $back_callback = 'back_to_cats';
        $back_text = '◀️ بازگشت به منو';
        if (!empty($products)) {
            $first_product = $products[0];
            $media_type = isset($first_product['media_type']) ? $first_product['media_type'] : '';
            if ($media_type == 'movie') {
                $back_callback = 'back_to_movies';
                $back_text = '◀️ بازگشت به فیلم‌ها';
            } elseif (in_array($media_type, ['series', 'animation', 'anime'])) {
                $back_callback = 'back_to_series';
                $back_text = '◀️ بازگشت به سریال‌ها';
            }
        }
        $back_btn = [['text' => $back_text, 'callback_data' => $back_callback]];
        array_push($keyboar_with_pagination, $back_btn);
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'text' => $choose_product,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboar_with_pagination
            ])
        ]);
    }
}


function product_keyboard($userid)
{
    global $type, $free_msg, $dl_btn, $demo_btn, $demo, $back_to_cats, $telegram, $time, $id, $already_purchased_product, $day, $allowed_vip_msg, $vip_msg, $pay_btn, $baseuri, $footer_msg, $keyboard, $views, $ad, $ad_link;
    //check if is_vip or not ;
    $sql = "select * from sp_users WHERE userid='$userid'";
    $db = $telegram->db->query($sql);
    $user = $db->fetch(PDO::FETCH_ASSOC);
    $vip_date = $user['vip_date'];
    $now = date($time);
    $day = $vip_date - $now;
    //check if is_vip or not ;

    // حذف دکمه دریافت لینک دانلود - دیگر استفاده نمی‌شود
    // کاربران مستقیماً از دکمه‌های کیفیت استفاده می‌کنند
    if ($type == 'free') {
        $footer_msg = $free_msg;
        $keyboard = []; // دکمه دانلود حذف شد
    } elseif ($type == 'vip') {
        if (already_purchased($userid, $id)) {
            $footer_msg = $already_purchased_product;
            $keyboard = []; // دکمه دانلود حذف شد
        } elseif ($day > 0) {
            $footer_msg = $allowed_vip_msg;
            $keyboard = []; // دکمه دانلود حذف شد
        } else {
            $footer_msg = $vip_msg;
            $keyboard = []; // دکمه پرداخت نیز حذف شد (فروشگاه حذف شده)
        }
    }
    if (isset($demo) && !empty($demo)) {
        $demo = [['text' => $demo_btn, 'url' => $demo]];
        array_push($keyboard, $demo);
    }


    // تعیین دکمه بازگشت بر اساس نوع محتوا
    $back_btn_text = '◀️ بازگشت به منو';
    $back_callback = 'back_to_cats';
    
    // برای فیلم‌ها: بازگشت به فیلم‌ها
    global $media_type;
    if (isset($media_type) && $media_type == 'movie') {
        $back_btn_text = '◀️ بازگشت به فیلم‌ها';
        $back_callback = 'back_to_movies';
    } 
    // برای سریال‌ها، انیمیشن و انیمه: بازگشت به سریال‌ها
    elseif (isset($media_type) && in_array($media_type, ['series', 'animation', 'anime'])) {
        $back_btn_text = '◀️ بازگشت به سریال‌ها';
        $back_callback = 'back_to_series';
    }
    
    $back_to_cats_views = [['text' => $back_btn_text, 'callback_data' => $back_callback], ['text' => 'تعداد بازدید: ' . fa_num($views), 'callback_data' => "views"]];
    array_push($keyboard, $back_to_cats_views);
    // if (isset($ad) && !empty($ad)) {
    //     $ads = [['text' => $ad, 'url' => $ad_link]];
    //     array_push($keyboard, $ads);
    // }
}
function back_to_cats()
{
    global $telegram, $exit, $msgid, $userid, $main_menu_msg, $cid, $main_keyboard, $cdata, $cuserid, $cmsgid, $cat_column_number, $cats_msg, $empty_cats;
    
    // back_to_movies - بازگشت به لیست فیلم‌ها
    if (isset($cdata) && $cdata == 'back_to_movies') {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'بازگشت به فیلم‌ها',
            'show_alert' => false
        ]);
        
        // دریافت دسته‌بندی‌هایی که فیلم دارند
        $sql = "SELECT DISTINCT c.id, c.name FROM sp_cats c 
                INNER JOIN sp_files f ON f.catid = c.id 
                WHERE f.media_type='movie' AND f.status=1 
                ORDER BY c.name ASC";
        $db = $telegram->db->query($sql);
        $cats = $db->fetchAll();
        
        if (!empty($cats)) {
            $keyboard = [];
            foreach ($cats as $cat) {
                $cat_id = $cat['id'];
                $cat_name = $cat['name'];
                $keyboard[] = ['text' => "$cat_name", 'callback_data' => "cat#movie#$cat_id"];
            }
            $keyboard = array_chunk($keyboard, $cat_column_number);
            $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
            array_push($keyboard, $back_btn);
            
            $msg = $cats_msg ? $cats_msg : "🎬 <b>فیلم‌ها</b>\n\nلطفاً دسته‌بندی مورد نظر خود را انتخاب کنید:";
            
            $edit_result = bot('editMessageText', [
                'chat_id' => $cuserid,
                'text' => $msg,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
            
            if (isset($edit_result->ok) && !$edit_result->ok) {
                $edit_caption_result = @bot('editMessageCaption', [
                    'chat_id' => $cuserid,
                    'message_id' => $cmsgid,
                    'caption' => $msg,
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                ]);
                
                if (isset($edit_caption_result->ok) && !$edit_caption_result->ok) {
                    bot('sendMessage', [
                        'chat_id' => $cuserid,
                        'text' => $msg,
                        'parse_mode' => "HTML",
                        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                    ]);
                }
            }
        }
        return;
    }
    
    // back_to_series - بازگشت به لیست سریال‌ها
    if (isset($cdata) && $cdata == 'back_to_series') {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'بازگشت به سریال‌ها',
            'show_alert' => false
        ]);
        
        // دریافت دسته‌بندی‌هایی که سریال دارند
        $sql = "SELECT DISTINCT c.id, c.name FROM sp_cats c 
                INNER JOIN sp_files f ON f.catid = c.id 
                WHERE f.media_type IN ('series', 'animation', 'anime') AND f.status=1 
                ORDER BY c.name ASC";
        $db = $telegram->db->query($sql);
        $cats = $db->fetchAll();
        
        if (!empty($cats)) {
            $keyboard = [];
            foreach ($cats as $cat) {
                $cat_id = $cat['id'];
                $cat_name = $cat['name'];
                $keyboard[] = ['text' => "$cat_name", 'callback_data' => "cat#series#$cat_id"];
            }
            $keyboard = array_chunk($keyboard, $cat_column_number);
            $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
            array_push($keyboard, $back_btn);
            
            $msg = $cats_msg ? $cats_msg : "📺 <b>سریال‌ها</b>\n\nلطفاً دسته‌بندی مورد نظر خود را انتخاب کنید:";
            
            $edit_result = bot('editMessageText', [
                'chat_id' => $cuserid,
                'text' => $msg,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
            
            if (isset($edit_result->ok) && !$edit_result->ok) {
                $edit_caption_result = @bot('editMessageCaption', [
                    'chat_id' => $cuserid,
                    'message_id' => $cmsgid,
                    'caption' => $msg,
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                ]);
                
                if (isset($edit_caption_result->ok) && !$edit_caption_result->ok) {
                    bot('sendMessage', [
                        'chat_id' => $cuserid,
                        'text' => $msg,
                        'parse_mode' => "HTML",
                        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                    ]);
                }
            }
        }
        return;
    }
    
    // back_to_cats - بازگشت به منوی اصلی
    if (preg_match('/back_to_cats/', $cdata) || (isset($cdata) && $cdata == 'back_to_cats')) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'بازگشت به منو',
            'show_alert' => false
        ]);
        
        // ویرایش پیام برای نمایش منوی اصلی
        $msg = $main_menu_msg;
        
        // ابتدا سعی کن پیام را ویرایش کنی
        $edit_result = bot('editMessageText', [
            'chat_id' => $cuserid,
            'text' => $msg,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'reply_markup' => json_encode(['keyboard' => $main_keyboard, 'resize_keyboard' => true])
        ]);
        
        // اگر editMessageText موفق نبود، سعی کن با editMessageCaption ویرایش کنی (اگر پیام قبلی عکس بود)
        if (isset($edit_result->ok) && !$edit_result->ok) {
            // اگر پیام قبلی عکس بود، سعی کن با editMessageCaption ویرایش کنی
            $edit_caption_result = @bot('editMessageCaption', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid,
                'caption' => $msg,
                'parse_mode' => "HTML",
                'reply_markup' => json_encode(['keyboard' => $main_keyboard, 'resize_keyboard' => true])
            ]);
            
            // اگر editMessageCaption هم موفق نبود، پیام جدید ارسال می‌کنیم
            if (isset($edit_caption_result->ok) && !$edit_caption_result->ok) {
                bot('sendMessage', [
                    'chat_id' => $cuserid,
                    'text' => $msg,
                    'parse_mode' => "HTML",
                    'reply_markup' => json_encode(['keyboard' => $main_keyboard, 'resize_keyboard' => true])
                ]);
            }
        }
    }
}
function show_product()
{
    global $footer_msg, $keyboard, $id, $cdata, $cid, $product_info_waiting, $cuserid, $cmsgid, $name, $desc, $price, $media_type, $year, $genre, $quality, $imdb, $director, $cast, $duration, $telegram, $poster, $baseuri;
    // بررسی دقیق‌تر برای جلوگیری از تداخل با callback های دیگر
    if (isset($cdata) && !empty($cdata) && preg_match('/^file#/', $cdata) && !preg_match('/season_episodes|episode_qualities/', $cdata)) {

        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => $product_info_waiting,
            'show_alert' => false
        ]);
        $input = explode('#', $cdata);
        $id = $input[1];
        product_info($id);
        
        // بررسی نوع محتوا
        $episodes_keyboard = [];
        
        // اگر سریال، انیمیشن یا انیمه است، ابتدا فصل‌ها را نمایش بده (بهینه‌سازی: فقط فصل‌های منحصر به فرد)
        if ($media_type == 'series' || $media_type == 'animation' || $media_type == 'anime') {
            $episodes_sql = "SELECT DISTINCT season FROM sp_series_episodes WHERE file_id=$id AND status=1 ORDER BY season ASC";
            $episodes = $telegram->db->query($episodes_sql)->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($episodes)) {
                // ساخت دکمه‌ها برای فصل‌ها (بهینه‌سازی: فقط فصل‌های منحصر به فرد)
                foreach ($episodes as $ep) {
                    $season = $ep['season'];
                    // تعیین متن دکمه بر اساس نوع محتوا (همیشه از 📁 برای فصل استفاده می‌کنیم)
                    if ($media_type == 'animation') {
                        $button_text = "📁 فصل $season (انیمیشن)";
                    } elseif ($media_type == 'anime') {
                        $button_text = "📁 فصل $season (انیمه)";
                    } else {
                        $button_text = "📁 فصل $season";
                    }
                    // استفاده از callback_data برای نمایش قسمت‌های فصل
                    $episodes_keyboard[] = [['text' => $button_text, 'callback_data' => "season_episodes#{$id}#{$season}"]];
                }
            }
        }
        
        // بررسی کیفیت‌های موجود (برای فیلم‌ها) (بهینه‌سازی: فقط فیلدهای مورد نیاز)
        $qualities_sql = "SELECT quality, download_link, file_size FROM sp_qualities WHERE file_id=$id AND status=1 ORDER BY quality ASC";
        $qualities = $telegram->db->query($qualities_sql)->fetchAll(PDO::FETCH_ASSOC);
        
        // ساخت کیبورد نهایی
        $final_keyboard = [];
        
        // برای سریال‌ها، انیمیشن و انیمه: فقط فصل‌ها (قسمت‌ها و کیفیت‌ها در مراحل بعدی نمایش داده می‌شوند)
        if (($media_type == 'series' || $media_type == 'animation' || $media_type == 'anime') && !empty($episodes_keyboard)) {
            $final_keyboard = $episodes_keyboard;
        } 
        // برای فیلم‌ها: فقط کیفیت‌ها
        elseif ($media_type == 'movie' && !empty($qualities)) {
            foreach ($qualities as $q) {
                $quality_name = $q['quality'];
                $file_size = !empty($q['file_size']) ? " (" . $q['file_size'] . ")" : "";
                $download_link = !empty($q['download_link']) ? $q['download_link'] : '';
                
                if (!empty($download_link)) {
                    $final_keyboard[] = [['text' => "📥 دریافت $quality_name$file_size", 'url' => $download_link]];
                } else {
                    $final_keyboard[] = [['text' => "📥 $quality_name$file_size (لینک تنظیم نشده)", 'callback_data' => 'no_link']];
                }
            }
        }
        
        // اضافه کردن دکمه بازگشت به دسته‌بندی‌ها
        $back_to_cats_btn = [['text' => '◀️ بازگشت به دسته‌بندی‌ها', 'callback_data' => 'back_to_cats']];
        if (!empty($final_keyboard)) {
            $final_keyboard[] = $back_to_cats_btn;
        } else {
            $final_keyboard = [$back_to_cats_btn];
        }
        
        $keyboard = $final_keyboard;

        // ساخت پیام با اطلاعات کامل فیلم/سریال/انیمیشن/انیمه
        if ($media_type == 'series') {
            $media_label = 'سریال';
        } elseif ($media_type == 'animation') {
            $media_label = 'انیمیشن';
        } elseif ($media_type == 'anime') {
            $media_label = 'انیمه';
        } else {
            $media_label = 'فیلم';
        }
        $msg = "🎬 <b>$media_label</b>: $name\n\n";
        
        if (!empty($year)) {
            $msg .= "📅 سال تولید: $year\n";
        }
        if (!empty($genre)) {
            $msg .= "🎭 ژانر: " . translate_genre($genre) . "\n";
        }
        
        // نمایش کیفیت‌های موجود
        if (!empty($qualities)) {
            $quality_list = [];
            foreach ($qualities as $q) {
                $quality_list[] = $q['quality'];
            }
            $msg .= "📺 کیفیت‌های موجود: " . implode(', ', $quality_list) . "\n";
        } elseif (!empty($quality)) {
            $msg .= "📺 کیفیت: $quality\n";
        }
        
        if (!empty($imdb)) {
            $msg .= "⭐ IMDb: $imdb/10\n";
        }
        if (!empty($director)) {
            $msg .= "🎬 کارگردان: $director\n";
        }
        if (!empty($cast)) {
            $msg .= "👥 بازیگران: $cast\n";
        }
        if (!empty($duration)) {
            if ($media_type == 'series') {
                // شمارش تعداد قسمت‌های واقعی از دیتابیس
                $episodes_count_sql = "SELECT COUNT(*) as total FROM sp_series_episodes WHERE file_id=$id AND status=1";
                $episodes_count = $telegram->db->query($episodes_count_sql)->fetch(PDO::FETCH_ASSOC);
                if ($episodes_count['total'] > 0) {
                    $msg .= "🔗 تعداد قسمت‌ها: " . $episodes_count['total'] . "\n";
                } else {
                    $msg .= "🔗 تعداد قسمت: $duration\n";
                }
            } else {
                $msg .= "⏱ مدت زمان: $duration\n";
            }
        }
        
        // نمایش اطلاعات فصل‌ها و قسمت‌ها برای سریال/انیمیشن/انیمه
        if ($media_type == 'series' || $media_type == 'animation' || $media_type == 'anime') {
            $episodes_info_sql = "SELECT season, COUNT(*) as count FROM sp_series_episodes WHERE file_id=$id AND status=1 GROUP BY season ORDER BY season ASC";
            $seasons_info = $telegram->db->query($episodes_info_sql)->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($seasons_info)) {
                $seasons_text = [];
                foreach ($seasons_info as $s) {
                    $seasons_text[] = "📁 فصل {$s['season']} ({$s['count']} 🔗 قسمت)";
                }
                $msg .= "📚 📁 فصل‌ها: " . implode(' | ', $seasons_text) . "\n";
            }
        }
        
        $msg .= "\n📃 <b>توضیحات:</b>\n$desc\n\n";
        $msg .= $footer_msg;
        
        $msg = fa_num($msg);

        // اگر عکس پوستر وجود دارد، با عکس ارسال کن
        if (!empty($poster)) {
            $photo_url = $poster;
            // اگر لینک نسبی است، BASEURI را اضافه کن
            if (strpos($photo_url, 'http') !== 0) {
                $photo_url = $baseuri . '/' . ltrim($photo_url, '/');
            }
            
            // اگر message_id وجود دارد، پیام را ویرایش کن، در غیر این صورت پیام جدید بفرست
            if (!empty($cmsgid)) {
                $result = bot('editMessageMedia', [
                    'chat_id' => $cuserid,
                    'message_id' => $cmsgid,
                    'media' => json_encode([
                        'type' => 'photo',
                        'media' => $photo_url,
                        'caption' => $msg,
                        'parse_mode' => 'HTML'
                    ]),
                    'reply_markup' => !empty($keyboard) ? json_encode(['inline_keyboard' => $keyboard]) : null
                ]);
                
                // اگر ویرایش موفق نبود، پیام جدید بفرست
                if (!$result || (isset($result->ok) && !$result->ok)) {
                    bot('sendphoto', [
                        'chat_id' => $cuserid,
                        'photo' => $photo_url,
                        'caption' => $msg,
                        'parse_mode' => 'HTML',
                        'reply_markup' => !empty($keyboard) ? json_encode(['inline_keyboard' => $keyboard]) : null
                    ]);
                }
            } else {
                bot('sendphoto', [
                    'chat_id' => $cuserid,
                    'photo' => $photo_url,
                    'caption' => $msg,
                    'parse_mode' => 'HTML',
                    'reply_markup' => !empty($keyboard) ? json_encode(['inline_keyboard' => $keyboard]) : null
                ]);
            }
        } else {
            // اگر message_id وجود دارد، پیام را ویرایش کن، در غیر این صورت پیام جدید بفرست
            if (!empty($cmsgid)) {
                $result = bot('editMessageText', [
                    'chat_id' => $cuserid,
                    'message_id' => $cmsgid,
                    'parse_mode' => "HTML",
                    'text' => $msg,
                    'reply_markup' => !empty($keyboard) ? json_encode(['inline_keyboard' => $keyboard]) : null
                ]);
                
                // اگر ویرایش موفق نبود، پیام جدید بفرست
                if (!$result || (isset($result->ok) && !$result->ok)) {
                    bot('sendMessage', [
                        'chat_id' => $cuserid,
                        'parse_mode' => "HTML",
                        'text' => $msg,
                        'reply_markup' => !empty($keyboard) ? json_encode(['inline_keyboard' => $keyboard]) : null
                    ]);
                }
            } else {
                bot('sendMessage', [
                    'chat_id' => $cuserid,
                    'parse_mode' => "HTML",
                    'text' => $msg,
                    'reply_markup' => !empty($keyboard) ? json_encode(['inline_keyboard' => $keyboard]) : null
                ]);
            }
        }
    }
}

function send_product_by_id()
{
    global $text, $footer_msg, $keyboard, $id, $name, $desc, $price, $userid, $media_type, $year, $genre, $quality, $imdb, $director, $cast, $duration, $telegram, $poster, $baseuri;
    
    if (preg_match('/file/', $text)) {
        if (is_verified($userid)) {

            $input = explode('_', $text);
            $id = $input[1];
            product_info($id);
            
            // بررسی کیفیت‌های موجود
            $qualities_sql = "SELECT * FROM sp_qualities WHERE file_id=$id AND status=1 ORDER BY quality ASC";
            $qualities = $telegram->db->query($qualities_sql)->fetchAll(PDO::FETCH_ASSOC);
            
            // اگر کیفیت وجود دارد، دکمه انتخاب کیفیت را اضافه کن
            if (!empty($qualities)) {
                $quality_keyboard = [];
                foreach ($qualities as $q) {
                    $quality_name = $q['quality'];
                    $file_size = !empty($q['file_size']) ? " (" . $q['file_size'] . ")" : "";
                    $download_link = !empty($q['download_link']) ? $q['download_link'] : '';
                    
                    if (!empty($download_link)) {
                        // اگر لینک وجود دارد، دکمه با لینک بساز
                        $quality_keyboard[] = [['text' => "📥 دریافت $quality_name$file_size", 'url' => $download_link]];
                    } else {
                        // اگر لینک وجود ندارد، فقط نمایش بده
                        $quality_keyboard[] = [['text' => "📥 $quality_name$file_size (لینک تنظیم نشده)", 'callback_data' => 'no_link']];
                    }
                }
                
                // اضافه کردن دکمه‌های اصلی
                product_keyboard($userid);
                if (isset($keyboard) && is_array($keyboard)) {
                    $quality_keyboard = array_merge($quality_keyboard, $keyboard);
                }
                
                $keyboard = $quality_keyboard;
            } else {
                product_keyboard($userid);
            }

            // ساخت پیام با اطلاعات کامل فیلم/سریال
            $media_label = ($media_type == 'series') ? 'سریال' : 'فیلم';
            $msg = "🎬 <b>$media_label</b>: $name\n\n";
            
            if (!empty($year)) {
                $msg .= "📅 سال تولید: $year\n";
            }
            if (!empty($genre)) {
                $msg .= "🎭 ژانر: " . translate_genre($genre) . "\n";
            }
            
            // نمایش کیفیت‌های موجود
            if (!empty($qualities)) {
                $quality_list = [];
                foreach ($qualities as $q) {
                    $quality_list[] = $q['quality'];
                }
                $msg .= "📺 کیفیت‌های موجود: " . implode(', ', $quality_list) . "\n";
            } elseif (!empty($quality)) {
                $msg .= "📺 کیفیت: $quality\n";
            }
            if (!empty($imdb)) {
                $msg .= "⭐ IMDb: $imdb/10\n";
            }
            if (!empty($director)) {
                $msg .= "🎬 کارگردان: $director\n";
            }
            if (!empty($cast)) {
                $msg .= "👥 بازیگران: $cast\n";
            }
            if (!empty($duration)) {
                if ($media_type == 'series') {
                    $msg .= "🔗 تعداد قسمت: $duration\n";
                } else {
                    $msg .= "⏱ مدت زمان: $duration\n";
                }
            }
            
            $msg .= "\n📃 <b>توضیحات:</b>\n$desc\n\n";
            $msg .= "💰 <b>قیمت:</b> $price\n\n";
            $msg .= $footer_msg;
            
            $msg = fa_num($msg);

            // اگر عکس پوستر وجود دارد، با عکس ارسال کن
            if (!empty($poster)) {
                $photo_url = $poster;
                // اگر لینک نسبی است، BASEURI را اضافه کن
                if (strpos($photo_url, 'http') !== 0) {
                    $photo_url = $baseuri . '/' . ltrim($photo_url, '/');
                }
                
                bot('sendphoto', [
                    'chat_id' => $userid,
                    'photo' => $photo_url,
                    'caption' => $msg,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboard
                    ])
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $userid,
                    'parse_mode' => "HTML",
                    'text' => $msg,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboard
                    ])
                ]);
            }
        } else {
            request_phone();
        }
    }
}
function already_purchased($userid, $productid)
{
    global $telegram;
    $sql = "select * from sp_orders where userid='$userid'AND productid='$productid' AND type='file'";
    $order = $telegram->db->query($sql);
    $count = $order->rowCount();
    $order_details = $order->fetch(PDO::FETCH_ASSOC);
    if ($count != 0) {
        if ($order_details['status'] == 1) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
function product_info($product_id, $update_views = true)
{
    global $telegram, $name, $desc, $type, $price, $demo, $views, $media_type, $year, $genre, $quality, $imdb, $director, $cast, $duration, $poster;
    // استفاده از prepared statement برای امنیت و سرعت بیشتر
    $sql = "SELECT * FROM sp_files WHERE id=:id AND status=1 LIMIT 1";
    $stmt = $telegram->db->prepare($sql);
    $stmt->bindValue(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return false;
    }
    
    $name = $product['name'];
    $desc = $product['description'];
    $type = $product['type'];
    $price = number_format($product['price']);
    if ($price == 0) {
        $price = 'رایگان';
    } else {
        $price = number_format($product['price']) . " تومان ";
    }
    $demo = $product['demo'];
    $views = $product['views'];
    
    // فیلدهای جدید برای فیلم و سریال
    $media_type = isset($product['media_type']) ? $product['media_type'] : 'movie';
    $year = isset($product['year']) ? $product['year'] : '';
    $genre = isset($product['genre']) ? $product['genre'] : '';
    $quality = isset($product['quality']) ? $product['quality'] : '';
    $imdb = isset($product['imdb']) ? $product['imdb'] : '';
    $director = isset($product['director']) ? $product['director'] : '';
    $cast = isset($product['cast']) ? $product['cast'] : '';
    $duration = isset($product['duration']) ? $product['duration'] : '';
    $poster = isset($product['poster']) ? $product['poster'] : '';
    
    // Add one view whenever product is shown (فقط اگر نیاز باشد)
    if ($update_views) {
        // افزایش تعداد بازدید کلی فیلم/سریال
        $sql_view = "UPDATE sp_files SET views=views+1 WHERE id=:id";
        $stmt_view = $telegram->db->prepare($sql_view);
        $stmt_view->bindValue(':id', $product_id, PDO::PARAM_INT);
        $stmt_view->execute();
        
        // ذخیره بازدید کاربر در جدول sp_user_views (اگر قبلاً ذخیره نشده باشد)
        global $userid;
        if (!empty($userid)) {
            try {
                // بررسی وجود جدول قبل از استفاده
                $check_table = $telegram->db->query("SHOW TABLES LIKE 'sp_user_views'");
                if ($check_table->rowCount() > 0) {
                    $sql_user_view = "INSERT IGNORE INTO sp_user_views (userid, file_id) VALUES (:userid, :file_id)";
                    $stmt_user_view = $telegram->db->prepare($sql_user_view);
                    $stmt_user_view->bindValue(':userid', $userid, PDO::PARAM_INT);
                    $stmt_user_view->bindValue(':file_id', $product_id, PDO::PARAM_INT);
                    $stmt_user_view->execute();
                }
            } catch (PDOException $e) {
                // اگر جدول وجود نداشت، خطا را نادیده بگیر (برای سازگاری با دیتابیس‌های قدیمی)
                error_log("Warning: sp_user_views table not found: " . $e->getMessage());
            }
        }
    }
    
    return true;
}

function download_file()
{
    global $cdata, $telegram, $cid, $cuserid, $sending_file, $ad, $ad_link;

    if (preg_match('/download/', $cdata)) {
        $input = explode('#', $cdata);
        $id = $input[1];
        $sql = "select * from sp_files WHERE id='$id' and status=1";
        $db = $telegram->db->query($sql);
        $respond = $db->fetch(PDO::FETCH_ASSOC);
        $name = $respond['name'];
        $name = fa_num($name);
        $fileurl = $respond['fileurl'];
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => $sending_file,
            'show_alert' => false
        ]);
        if (isset($ad) && !empty($ad)) {
            $ads[] = [['text' => $ad, 'url' => $ad_link]];
            bot('senddocument', [
                'chat_id' => $cuserid,
                'document' => $fileurl,
                'caption' => $name,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $ads
                ])
            ]);
        } else {
            bot('senddocument', [
                'chat_id' => $cuserid,
                'document' => $fileurl,
                'caption' => $name
            ]);
        }
    }
}

function vip()
{
    global $userid, $vip_days, $telegram, $main_keyboard;

    if (is_vip($userid)) {
        $msg = options('vip_remaining');
        $msg = str_replace("[vip_days]", $vip_days, $msg);
        $msg = fa_num($msg);
        $telegram->sendMessageCURL($userid, $msg, $main_keyboard);
    } else {
        show_vip_plans();
    }
}
function show_vip_plans()
{
    global $telegram, $userid, $baseuri, $vip_plans;
    $sql = "select * from sp_vip_plans";
    $db = $telegram->db->query($sql);
    $plans = $db->fetchAll();
    $keyboard = [];
    foreach ($plans as $plan) {
        $id = $plan['id'];
        $name = fa_num($plan['name']);
        $price = fa_num(number_format($plan['price']));
        $keyboard[] = ['text' => "$name - $price تومان ", 'url' => $baseuri . "/vip/pay.php?uid=$userid&vip=$id"];
    }
    $keyboard = array_chunk($keyboard, 1);
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $vip_plans,
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
}

function user_purchased_products($userid)
{
    global $telegram, $go_to_home_keyboard, $botuser, $empty_transactions;
    $sql = "select * from sp_orders where userid=$userid AND status=1";
    $db = $telegram->db->query($sql);
    $user_orders = $db->fetchAll();
    if (empty($user_orders)) {
        $msg = $empty_transactions;
        $telegram->sendHTML($userid, $msg, $go_to_home_keyboard);
    } else {
        foreach ($user_orders as $order) {
            $trans_type = $order['type'];
            $order_product_id = $order['productid'];
            $order_transcode = $order['transcode'];
            $order_price = number_format($order['price']);
            $order_date = jdate('Y/m/d-H:i:s', $order['date']);
            if ($trans_type == 'file') {
                $product_name = fetch_product_name($order_product_id);
                $product_link = "https://t.me/$botuser?start=file_$order_product_id";
            } elseif ($trans_type == 'plan') {
                $product_name = fetch_plan_name($order_product_id);
                $product_link = "";
            }
            $msg = options('orders_msg');
            $msg = str_replace("[product_link]", $product_link, $msg);
            $msg = str_replace("[product_name]", fa_num($product_name), $msg);
            $msg = str_replace("[order_price]", fa_num($order_price), $msg);
            $msg = str_replace("[order_transcode]", fa_num($order_transcode), $msg);
            $msg = str_replace("[order_date]", fa_num($order_date), $msg);
            $telegram->sendHTML($userid, $msg, $go_to_home_keyboard);
        }
    }
}


function fetch_product_name($product_id)
{
    global $telegram;
    $sql = "select * from sp_files where id=$product_id";
    $db = $telegram->db->query($sql)->fetch();
    $product_name = $db['name'];
    return $product_name;
}

function fetch_plan_name($plan_id)
{
    global $telegram;
    $sql = "select * from sp_vip_plans where id=$plan_id";
    $db = $telegram->db->query($sql)->fetch();
    $plan_name = $db['name'];
    return $plan_name;
}

function ticket()
{
    global $ticket_msg, $userid, $telegram, $go_to_home_keyboard;
    $msg = $ticket_msg;
    $telegram->sendHTML($userid, $msg, $go_to_home_keyboard);
    file_put_contents('users/' . $userid . '.txt', 'pending_ticket');
}

function submit_ticket()
{
    global $userid, $telegram, $main_keyboard, $text, $send_ticket, $my_transactions, $my_transactions, $vip_member, $shop, $home, $time, $ticket_sent, $new_ticket, $admin, $search_products;
    $status_file = 'users/' . $userid . '.txt';
    $status = file_exists($status_file) ? file_get_contents($status_file) : '';
    if ($text == $home) {
        file_put_contents('users/' . $userid . '.txt', ' ');
    }
    if ($status == 'pending_ticket' && $text != $send_ticket && $text != $my_transactions && $text != $vip_member && $text != $shop && $text != $shop && $text != $home && $text != $search_products && !(preg_match('/^\/([Ss]tart)/', $text))) {
        $sql = "INSERT INTO sp_tickets VALUES (NULL,'$userid','$text','$time')";
        $telegram->db->query($sql);
        $telegram->sendMessageCURL($userid, $ticket_sent, $main_keyboard);  // Notify user that the ticket is sent;
        $telegram->sendMessageCURL($admin, $new_ticket, $main_keyboard);  // Notify admin that a new ticket is submited;
        file_put_contents('users/' . $userid . '.txt', ' ');
    }
}
function init_search()
{
    global $telegram, $userid, $go_to_home_keyboard, $search_text;
    $msg = $search_text;
    $telegram->sendHTML($userid, $msg, $go_to_home_keyboard);
    file_put_contents('users/' . $userid . '.txt', 'pending_search');
}

function submit_search()
{
    global $userid, $telegram, $main_keyboard, $text, $send_ticket, $my_transactions, $my_transactions, $vip_member, $shop, $home, $search_products, $botuser, $no_search_result, $search_description;
    $status_file = 'users/' . $userid . '.txt';
    $status = file_exists($status_file) ? file_get_contents($status_file) : '';
    if ($text == $home) {
        file_put_contents('users/' . $userid . '.txt', ' ');
    }
    if ($status == 'pending_search' && $text != $send_ticket && $text != $my_transactions && $text != $vip_member && $text != $shop && $text != $shop && $text != $home && $text != $search_products &&  !(preg_match('/^\/([Ss]tart|search)/i', $text))) {
        // جستجو در فیلم‌ها (بدون در نظر گیری کیفیت - فقط نام فیلم)
        $sql = "SELECT DISTINCT f.* FROM sp_files f WHERE (f.name like '%$text%' or f.description like '%$text%') AND f.status=1 GROUP BY f.id";

        $products = $telegram->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if ($products) {
            $keyboard = [];
            foreach ($products as $product) {
                $product_id = $product['id'];
                $product_name = $product['name'];
                // بررسی تعداد کیفیت‌های موجود
                $qualities_sql = "SELECT COUNT(*) as count FROM sp_qualities WHERE file_id=$product_id AND status=1";
                $qualities_count = $telegram->db->query($qualities_sql)->fetch(PDO::FETCH_ASSOC);
                $qty_count = $qualities_count['count'];
                
                if ($qty_count > 0) {
                    $keyboard[] = [['text' => fa_num($product_name) . " ($qty_count کیفیت)", 'callback_data' => "search_file#$product_id"]];
                } else {
                    $keyboard[] = [['text' => fa_num($product_name), 'callback_data' => "file#$product_id"]];
                }
            }
            
            $msg = "🔽 نتیجه ی جستجوی شما: \n\n";
            $msg .= "لطفاً فیلم/سریال مورد نظر را انتخاب کنید:\n";
            $msg .= "(عدد در پرانتز نشان‌دهنده تعداد کیفیت‌های موجود است)";
            
            bot('sendMessage', [
                'chat_id' => $userid,
                'parse_mode' => 'HTML',
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        } else {
            $msg = $no_search_result;
            $telegram->sendMessageCURL($userid, $msg, $main_keyboard);
        }
        file_put_contents('users/' . $userid . '.txt', ' ');
    }
}

// نمایش کیفیت‌های مختلف برای فیلم انتخاب شده از جستجو
function show_search_qualities()
{
    global $cdata, $cid, $cuserid, $cmsgid, $telegram, $botuser, $baseuri, $footer_msg, $keyboard;
    
    if (preg_match('/search_file/', $cdata)) {
        $input = explode('#', $cdata);
        $file_id = intval($input[1]);
        
        // دریافت اطلاعات فیلم
        $sql = "SELECT * FROM sp_files WHERE id=$file_id AND status=1";
        $file_info = $telegram->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        if (!$file_info) {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'فیلم یافت نشد',
                'show_alert' => false
            ]);
            return;
        }
        
        // دریافت کیفیت‌های موجود
        $qualities_sql = "SELECT * FROM sp_qualities WHERE file_id=$file_id AND status=1 ORDER BY quality ASC";
        $qualities = $telegram->db->query($qualities_sql)->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($qualities)) {
            // اگر کیفیتی وجود ندارد، مستقیماً به صفحه فیلم برو
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'در حال بارگذاری...',
                'show_alert' => false
            ]);
            
            // استفاده از تابع موجود show_product
            $GLOBALS['cdata'] = "file#$file_id";
            show_product();
            return;
        }
        
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'کیفیت‌های موجود',
            'show_alert' => false
        ]);
        
        // دریافت اطلاعات کامل فیلم
        product_info($file_id);
        
        // استفاده از متغیرهای global که توسط product_info تنظیم شده‌اند
        global $name, $desc, $type, $price, $media_type, $year, $genre, $imdb, $director, $cast, $duration, $poster, $id;
        $id = $file_id; // تنظیم id برای product_keyboard
        
        // ساخت پیام با اطلاعات کامل فیلم/سریال/انیمیشن/انیمه
        if ($media_type == 'series') {
            $media_label = 'سریال';
        } elseif ($media_type == 'animation') {
            $media_label = 'انیمیشن';
        } elseif ($media_type == 'anime') {
            $media_label = 'انیمه';
        } else {
            $media_label = 'فیلم';
        }
        
        $msg = "🎬 <b>$media_label</b>: $name\n\n";
        
        if (!empty($year)) {
            $msg .= "📅 سال تولید: $year\n";
        }
        if (!empty($genre)) {
            $msg .= "🎭 ژانر: " . translate_genre($genre) . "\n";
        }
        
        // نمایش کیفیت‌های موجود
        $quality_list = [];
        foreach ($qualities as $q) {
            $quality_list[] = $q['quality'];
        }
        $msg .= "📺 کیفیت‌های موجود: " . implode(', ', $quality_list) . "\n";
        
        if (!empty($imdb)) {
            $msg .= "⭐ IMDb: $imdb/10\n";
        }
        if (!empty($director)) {
            $msg .= "🎬 کارگردان: $director\n";
        }
        if (!empty($cast)) {
            $msg .= "👥 بازیگران: $cast\n";
        }
        if (!empty($duration)) {
            if ($media_type == 'series' || $media_type == 'animation' || $media_type == 'anime') {
                $msg .= "📺 تعداد قسمت: $duration\n";
            } else {
                $msg .= "⏱ مدت زمان: $duration\n";
            }
        }
        
        $msg .= "\n📃 <b>توضیحات:</b>\n$desc\n\n";
        
        $msg = fa_num($msg);
        
        // ساخت دکمه‌های کیفیت
        $quality_keyboard = [];
        foreach ($qualities as $quality) {
            $quality_name = $quality['quality'];
            $file_size = !empty($quality['file_size']) ? " (" . $quality['file_size'] . ")" : "";
            $download_link = !empty($quality['download_link']) ? $quality['download_link'] : '';
            
            if (!empty($download_link)) {
                // اگر لینک وجود دارد، دکمه با لینک بساز
                $quality_keyboard[] = [['text' => "📥 دریافت $quality_name$file_size", 'url' => $download_link]];
            } else {
                // اگر لینک وجود ندارد، فقط نمایش بده
                $quality_keyboard[] = [['text' => "📥 $quality_name$file_size (لینک تنظیم نشده)", 'callback_data' => 'no_link']];
            }
        }
        
        // اضافه کردن دکمه‌های اصلی
        product_keyboard($cuserid);
        if (isset($keyboard) && is_array($keyboard) && !empty($keyboard)) {
            $quality_keyboard = array_merge($quality_keyboard, $keyboard);
        }
        $keyboard = $quality_keyboard;
        
        // اگر عکس پوستر وجود دارد، با عکس ارسال کن
        if (!empty($poster)) {
            $photo_url = $poster;
            // اگر لینک نسبی است، BASEURI را اضافه کن
            if (strpos($photo_url, 'http') !== 0) {
                $photo_url = $baseuri . '/' . ltrim($photo_url, '/');
            }
            
            bot('editMessageMedia', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid,
                'media' => json_encode([
                    'type' => 'photo',
                    'media' => $photo_url,
                    'caption' => $msg,
                    'parse_mode' => 'HTML'
                ]),
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        } else {
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
}

// این تابع دیگر استفاده نمی‌شود - کیفیت‌ها مستقیماً با لینک نمایش داده می‌شوند

function most_popular_products()
{
    global $telegram, $userid, $main_keyboard, $cdata, $cuserid, $cid, $cmsgid, $populars_count, $back_to_cats, $popular_products_text, $no_popular_product, $text, $popular_products;
    // اگر از message text فراخوانی شده (نه callback)
    if (isset($text) && $text == $popular_products) {
        $sql = "SELECT * FROM sp_files WHERE status=1 ORDER BY views DESC limit $populars_count";
        $db = $telegram->db->query($sql);
        $products = $db->fetchAll();
        if (empty($products)) {
            $telegram->sendMessageCURL($userid, $no_popular_product, $main_keyboard);
        } else {
            $keyboard = [];
            foreach ($products as $product) {
                $id = $product['id'];
                $name = $product['name'];
                $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
            }
            $keyboard = array_chunk($keyboard, 1);
            $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
            array_push($keyboard, $back_btn);
            
            bot('sendMessage', [
                'chat_id' => $userid,
                'text' => $popular_products_text,
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
        }
        return;
    }
    // اگر از callback فراخوانی شده
    if (preg_match('/populars/', $cdata) || (isset($cdata) && $cdata == 'populars')) {

        $sql = "SELECT * FROM sp_files WHERE status=1 ORDER BY views DESC limit $populars_count";
        $db = $telegram->db->query($sql);
        $products = $db->fetchAll();
        if (empty($products)) {
            if (!empty($cid)) {
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => $no_popular_product,
                    'show_alert' => false
                ]);
            } else {
                $telegram->sendMessageCURL($cuserid ? $cuserid : $userid, $no_popular_product, $main_keyboard);
            }
        } else {
            $keyboard = [];
            foreach ($products as $product) {
                $id = $product['id'];
                $name = $product['name'];
                $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
            }
            $keyboard = array_chunk($keyboard, 1);
            $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
            array_push($keyboard, $back_btn);
            
            // اگر از callback است، editMessageText استفاده کن، در غیر این صورت sendMessage
            if (!empty($cmsgid) && !empty($cuserid)) {
                bot('editMessageText', [
                    'chat_id' => $cuserid,
                    'message_id' => $cmsgid,
                    'parse_mode' => "HTML",
                    'text' => $popular_products_text,
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cuserid ? $cuserid : $userid,
                    'parse_mode' => "HTML",
                    'text' => $popular_products_text,
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                ]);
            }
        }
    }
}
function latest_products()
{
    global $telegram, $userid, $main_keyboard, $cdata, $cuserid, $cid, $cmsgid, $latests_count, $back_to_cats, $latest_products_text, $no_latest_product, $text, $latest_products;
    // اگر از message text فراخوانی شده (نه callback)
    if (isset($text) && $text == $latest_products) {
        $sql = "SELECT * FROM sp_files WHERE status=1 ORDER BY id DESC limit $latests_count";
        $db = $telegram->db->query($sql);
        $products = $db->fetchAll();
        if (empty($products)) {
            $telegram->sendMessageCURL($userid, $no_latest_product, $main_keyboard);
        } else {
            $keyboard = [];
            foreach ($products as $product) {
                $id = $product['id'];
                $name = $product['name'];
                $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
            }
            $keyboard = array_chunk($keyboard, 1);
            $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
            array_push($keyboard, $back_btn);
            
            bot('sendMessage', [
                'chat_id' => $userid,
                'text' => $latest_products_text,
                'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
            ]);
        }
        return;
    }
    // اگر از callback فراخوانی شده
    if (preg_match('/latests/', $cdata) || (isset($cdata) && $cdata == 'latests')) {

        $sql = "SELECT * FROM sp_files WHERE status=1 ORDER BY id DESC limit $latests_count";
        $db = $telegram->db->query($sql);
        $products = $db->fetchAll();
        if (empty($products)) {
            if (!empty($cid)) {
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => $no_latest_product,
                    'show_alert' => false
                ]);
            } else {
                $telegram->sendMessageCURL($cuserid ? $cuserid : $userid, $no_latest_product, $main_keyboard);
            }
        } else {
            $keyboard = [];
            foreach ($products as $product) {
                $id = $product['id'];
                $name = $product['name'];
                $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
            }
            $keyboard = array_chunk($keyboard, 1);
            $back_btn = [['text' => '◀️ بازگشت به منو', 'callback_data' => "back_to_cats"]];
            array_push($keyboard, $back_btn);
            
            // اگر از callback است، editMessageText استفاده کن، در غیر این صورت sendMessage
            if (!empty($cmsgid) && !empty($cuserid)) {
                bot('editMessageText', [
                    'chat_id' => $cuserid,
                    'message_id' => $cmsgid,
                    'parse_mode' => "HTML",
                    'text' => $latest_products_text,
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                ]);
            } else {
                bot('sendMessage', [
                    'chat_id' => $cuserid ? $cuserid : $userid,
                    'parse_mode' => "HTML",
                    'text' => $latest_products_text,
                    'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
                ]);
            }
        }
    }
}

function account_info()
{
    global $telegram, $userid, $go_to_home_keyboard, $no_vip_plan, $phone_not_verified;
    $sql = "SELECT * FROM sp_users where userid='$userid'";
    $db = $telegram->db->query($sql);
    $user = $db->fetch();
    $name = $user['name'];
    $verified = $user['verified'];
    $phone = $user['phone'];
    $vip_plan = $user['vip_plan'];

    if (isset($phone) && $phone != 0 && !empty($phone) && $verified == 1) {
        $phone = $user['phone'];
    } else {
        $phone = $phone_not_verified;
    }

    if (is_vip($userid)) {
        $vip_plan = $user['vip_plan'];
    } else {
        $vip_plan = $no_vip_plan;
    }

    // تعداد فیلم/سریال‌های بازدید شده توسط کاربر
    // شمارش تعداد فیلم/سریال‌های منحصر به فرد که کاربر از طریق product_info() دیده
    $viewed_count = 0;
    try {
        // بررسی وجود جدول قبل از استفاده
        $check_table = $telegram->db->query("SHOW TABLES LIKE 'sp_user_views'");
        if ($check_table->rowCount() > 0) {
            $viewed_count_sql = "SELECT COUNT(DISTINCT uv.file_id) as viewed_count 
                                 FROM sp_user_views uv 
                                 INNER JOIN sp_files f ON uv.file_id = f.id 
                                 WHERE uv.userid='$userid' AND f.status=1";
            $viewed_result = $telegram->db->query($viewed_count_sql)->fetch();
            $viewed_count = isset($viewed_result['viewed_count']) ? intval($viewed_result['viewed_count']) : 0;
        }
    } catch (PDOException $e) {
        // اگر جدول وجود نداشت، 0 برگردان (برای سازگاری با دیتابیس‌های قدیمی)
        error_log("Warning: sp_user_views table not found in account_info: " . $e->getMessage());
        $viewed_count = 0;
    }

    $msg = options('account_info');
    $msg = str_replace("[name]", $name, $msg);
    $msg = str_replace("[userid]", $userid, $msg);
    $msg = str_replace("[phone]", $phone, $msg);
    $msg = str_replace("[vip_plan]", $vip_plan, $msg);
    $msg = str_replace("[total_orders]", fa_num($viewed_count), $msg);
    $msg = fa_num($msg);
    $telegram->sendHTML($userid, $msg, $go_to_home_keyboard);
}

// تابع پردازش Inline Query برای جستجوی سریع
function handle_inline_query($inline_query)
{
    global $telegram, $result;
    
    $query_id = $inline_query->id;
    $query_text = isset($inline_query->query) ? trim($inline_query->query) : '';
    $user_id = $inline_query->from->id;
    
    // بررسی اینکه آیا کاربر تایید شده است
    if (!is_verified($user_id)) {
        // اگر کاربر تایید نشده، پیام راهنما بفرست
        $results = [
            [
                'type' => 'article',
                'id' => 'not_verified',
                'title' => '⚠️ ابتدا باید شماره تلفن خود را ثبت کنید',
                'description' => 'برای استفاده از جستجو، ابتدا باید شماره تلفن خود را ثبت کنید',
                'message_text' => '⚠️ برای استفاده از جستجو، ابتدا باید شماره تلفن خود را ثبت کنید. لطفاً /start را بزنید.',
            ]
        ];
        answer_inline_query($query_id, $results);
        return;
    }
    
    // اگر query خالی است یا فقط `/search:` است، پیام راهنما نمایش بده
    if (empty($query_text) || $query_text === '/search:' || $query_text === '/search') {
        $results = [
            [
                'type' => 'article',
                'id' => 'help',
                'title' => '🔍 جستجوی فیلم و سریال',
                'description' => 'نام فیلم یا سریال را وارد کنید (فارسی یا انگلیسی)',
                'message_text' => '🔍 برای جستجو، نام فیلم یا سریال را وارد کنید.\n\nمثال:\n`ماتریکس`\n`The Matrix`',
                'parse_mode' => 'Markdown',
            ]
        ];
        answer_inline_query($query_id, $results);
        return;
    }
    
    // حذف `/search:` از ابتدای query اگر وجود دارد
    $search_query = preg_replace('/^\/search:\s*/i', '', $query_text);
    $search_query = trim($search_query);
    
    if (empty($search_query)) {
        $results = [
            [
                'type' => 'article',
                'id' => 'empty',
                'title' => '🔍 نام فیلم یا سریال را وارد کنید',
                'description' => 'نام را بعد از `/search:` وارد کنید',
                'message_text' => '🔍 لطفاً نام فیلم یا سریال را وارد کنید.',
            ]
        ];
        answer_inline_query($query_id, $results);
        return;
    }
    
    // جستجو در دیتابیس
    $sql = "SELECT DISTINCT f.* FROM sp_files f 
            WHERE (f.name LIKE :query1 OR f.name_en LIKE :query2 OR f.description LIKE :query3) 
            AND f.status=1 
            GROUP BY f.id 
            ORDER BY f.id DESC 
            LIMIT 10";
    
    $stmt = $telegram->db->prepare($sql);
    $search_pattern = '%' . $search_query . '%';
    $stmt->bindValue(':query1', $search_pattern, PDO::PARAM_STR);
    $stmt->bindValue(':query2', $search_pattern, PDO::PARAM_STR);
    $stmt->bindValue(':query3', $search_pattern, PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    
    if ($products && count($products) > 0) {
        foreach ($products as $index => $product) {
            $product_id = $product['id'];
            $product_name = $product['name'];
            $product_name_en = isset($product['name_en']) && !empty($product['name_en']) ? $product['name_en'] : '';
            $product_desc = mb_substr($product['description'], 0, 100);
            if (mb_strlen($product['description']) > 100) {
                $product_desc .= '...';
            }
            $media_type = isset($product['media_type']) ? $product['media_type'] : 'movie';
            
            // بررسی تعداد کیفیت‌ها یا قسمت‌ها
            $qualities_sql = "SELECT COUNT(*) as count FROM sp_qualities WHERE file_id=$product_id AND status=1";
            $qualities_count = $telegram->db->query($qualities_sql)->fetch(PDO::FETCH_ASSOC);
            $qty_count = $qualities_count['count'];
            
            $episodes_sql = "SELECT COUNT(*) as count FROM sp_series_episodes WHERE file_id=$product_id AND status=1";
            $episodes_count = $telegram->db->query($episodes_sql)->fetch(PDO::FETCH_ASSOC);
            $ep_count = $episodes_count['count'];
            
            // ساخت عنوان
            $title = $product_name;
            if (!empty($product_name_en)) {
                $title .= " ($product_name_en)";
            }
            
            // ساخت توضیحات
            $description = '';
            if ($qty_count > 0) {
                $description = "$qty_count کیفیت";
            } elseif ($ep_count > 0) {
                $description = "$ep_count قسمت";
            }
            if ($media_type === 'series') {
                $description = ($description ? $description . ' | ' : '') . '📺 سریال';
            } elseif ($media_type === 'animation') {
                $description = ($description ? $description . ' | ' : '') . '🎨 انیمیشن';
            } elseif ($media_type === 'anime') {
                $description = ($description ? $description . ' | ' : '') . '🌸 انیمه';
            } else {
                $description = ($description ? $description . ' | ' : '') . '🎬 فیلم';
            }
            
            // ساخت متن پیام
            $message_text = "🎬 <b>$product_name</b>\n\n";
            if (!empty($product_name_en)) {
                $message_text .= "🇬🇧 <b>$product_name_en</b>\n\n";
            }
            $message_text .= "📝 " . mb_substr($product['description'], 0, 200);
            if (mb_strlen($product['description']) > 200) {
                $message_text .= '...';
            }
            
            // ساخت callback_data
            $callback_data = "file#$product_id";
            
            $results[] = [
                'type' => 'article',
                'id' => 'product_' . $product_id . '_' . $index,
                'title' => $title,
                'description' => $description,
                'message_text' => $message_text,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[
                        ['text' => '📥 مشاهده و دانلود', 'callback_data' => $callback_data]
                    ]]
                ])
            ];
        }
    } else {
        // اگر نتیجه‌ای پیدا نشد
        $results[] = [
            'type' => 'article',
            'id' => 'no_results',
            'title' => '❌ نتیجه‌ای یافت نشد',
            'description' => "برای: $search_query",
            'message_text' => "❌ متأسفانه برای «$search_query» نتیجه‌ای یافت نشد.\n\n💡 سعی کنید:\n• نام را به انگلیسی یا فارسی وارد کنید\n• از کلمات کلیدی استفاده کنید\n• املای صحیح را بررسی کنید",
        ];
    }
    
    // ارسال نتایج
    answer_inline_query($query_id, $results);
}

// تابع ارسال پاسخ Inline Query
function answer_inline_query($query_id, $results)
{
    $url = "https://api.telegram.org/bot" . TOKEN . "/answerInlineQuery";
    
    $postfields = [
        'inline_query_id' => $query_id,
        'results' => json_encode($results),
        'cache_time' => 300, // 5 دقیقه cache
        'is_personal' => false,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

// تابع نمایش لینک پنل مدیریت
function show_admin_panel_link($userid)
{
    global $telegram, $admin_keyboard, $baseuri, $admin;
    
    // ایجاد توکن موقت (10 دقیقه اعتبار)
    $token = bin2hex(random_bytes(32));
    $expires = time() + (10 * 60); // 10 دقیقه
    
    // ذخیره توکن در فایل (یا می‌توانید در دیتابیس ذخیره کنید)
    $token_file = 'admin_tokens/' . $token . '.txt';
    if (!file_exists('admin_tokens')) {
        mkdir('admin_tokens', 0755, true);
    }
    file_put_contents($token_file, json_encode([
        'userid' => $userid,
        'expires' => $expires,
        'created' => time()
    ]));
    
    // ایجاد لینک پنل مدیریت
    $panel_url = $baseuri . '/admin-panel/auth.php?token=' . $token;
    
    $msg = "⚙️ <b>پنل مدیریت</b>\n\n";
    $msg .= "برای ورود به پنل مدیریت، روی لینک زیر کلیک کنید:\n\n";
    $msg .= "🔗 <a href='$panel_url'>ورود به پنل مدیریت</a>\n\n";
    $msg .= "⚠️ <i>این لینک فقط 10 دقیقه اعتبار دارد.</i>";
    
    $keyboard = [[
        ['text' => '🔗 باز کردن پنل مدیریت', 'url' => $panel_url]
    ]];
    
    // استفاده از bot() برای ارسال پیام با inline keyboard
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
