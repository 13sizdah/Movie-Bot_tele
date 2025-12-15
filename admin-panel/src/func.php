<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}
// TOKEN باید از config.php بیاید، نه از اینجا
// اگر TOKEN تعریف نشده، از config.php استفاده می‌کنیم
if (!defined('TOKEN')) {
    // تلاش برای include کردن config.php
    $config_path = dirname(__DIR__) . '/config.php';
    if (file_exists($config_path)) {
        require_once $config_path;
    } else {
        // اگر config.php پیدا نشد، از مقدار پیش‌فرض استفاده کن
        define('TOKEN', '7753447307:AAGfdK7B3ZUb5brapdgWo5zcg65mjBpk8qA');   //توکن ربات
    }
}

$products_per_page = 15; // تعداد نمایش فیلم و سریال در هر صفحه
$orders_per_page = 10; // تعداد نمایش سفارشات در هر صفحه
$users_per_page = 100; // تعداد نمایش کاربران در هر صفحه
$tickets_per_page = 10; // تعداد نمایش تیکت ها در هر صفحه
$cats_per_page = 10; // تعداد نمایش دسته بندی ها در هر صفحه
$options_per_page = 50; // تعداد نمایش تنظیمات در هر صفحه

function list_cats()
{
    global $db, $cats, $cats_per_page;
    if (!isset($db)) {
        $cats = [];
        return;
    }
    try {
        $sql = "select * from sp_cats ORDER BY id DESC LIMIT $cats_per_page";
        $cats = $db->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in list_cats: " . $e->getMessage());
        $cats = [];
    }
}
function fetch_cat_info()
{
    global $db, $cat_name, $cat_id;
    if (!isset($db)) {
        $cat_name = '';
        return;
    }
    try {
        $cat_id = intval($_GET['edit_cat']);
        $sql = "select * from sp_cats where id='$cat_id'";
        $cat = $db->query($sql)->fetch();
        $cat_name = $cat ? $cat['name'] : '';
    } catch (PDOException $e) {
        error_log("Error in fetch_cat_info: " . $e->getMessage());
        $cat_name = '';
    }
}
function list_users()
{
    global $db, $users, $users_per_page;
    if (!isset($db)) {
        $users = [];
        return;
    }
    try {
        $sql = "select * from sp_users ORDER BY id DESC LIMIT $users_per_page";
        $users = $db->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in list_users: " . $e->getMessage());
        $users = [];
    }
}
function list_admins()
{
    global $db, $admins;
    if (!isset($db)) {
        $admins = [];
        return;
    }
    try {
        $sql = "select * from sp_admins";
        $admins = $db->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in list_admins: " . $e->getMessage());
        $admins = [];
    }
}
function users_count()
{
    global $db, $users;
    $sql = "select * from sp_users";
    $users = $db->query($sql)->fetchAll();
    return count($users);
}
function user_info($userid)
{
    global $db, $user, $name, $username, $phone, $vip_date;
    $sql = "select * from sp_users where userid=$userid";
    $user = $db->query($sql)->fetch();
    $name = $user['name'];
    $userid = $user['userid'];
    $username = $user['username'];
    $phone = $user['phone'];
    $vip_date = $user['vip_date'];
}
function admin_info($id)
{
    global $db, $username;
    $sql = "select * from sp_admins where id=$id";
    $admin = $db->query($sql)->fetch();
    $username = $admin['username'];
}
function user_orders($userid)
{
    global $db, $orders;
    $sql = "select * from sp_orders where userid=$userid";
    $orders = $db->query($sql)->fetchAll();
}
function add_vip_days($userid, $days)
{
    global $vip_date, $db;
    user_info($userid);
    $current_vip_date = $vip_date;
    $days = $days * 86400;
    $new_date = $current_vip_date + $days;
    $sql = "UPDATE sp_users set vip_date='$new_date' where userid='$userid'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function remove_not_verified_users(){
    global $db;
    $sql = "DELETE FROM sp_users where phone=0";
    $result = $db->query($sql);
    if ($result) {
        header("Location:users.php");
    }
}
function is_vip($userid)
{
    // Check if User is VIP or not - If user is vip returns remaining days 
    global $userid, $db, $vip_days;
    $sql = "select * from sp_users WHERE userid='$userid'";
    $res = $db->query($sql);
    $user = $res->fetch(PDO::FETCH_ASSOC);
    $vip_date = $user['vip_date'];
    $time = time();
    $now = date($time);
    $day = $vip_date - $now;
    if ($day > 0) {
        $vip_days = number_format($day / 60 / 60 / 24);
        return true;
    } else {
        return false;
    }
}

function list_options()
{
    global $db, $options, $options_per_page;
    if (!isset($db)) {
        $options = [];
        return;
    }
    try {
        $sql = "select * from sp_options LIMIT $options_per_page";
        $options = $db->query($sql)->fetchAll();
        // تبدیل encoding برای اطمینان از نمایش صحیح
        foreach ($options as &$option) {
            if (isset($option['value'])) {
                $option['value'] = mb_convert_encoding($option['value'], 'UTF-8', 'auto');
            }
            if (isset($option['description'])) {
                $option['description'] = mb_convert_encoding($option['description'], 'UTF-8', 'auto');
            }
        }
        unset($option);
    } catch (PDOException $e) {
        error_log("Error in list_options: " . $e->getMessage());
        $options = [];
    }
}

function list_plans()
{
    global $db, $plans;
    $sql = "select * from sp_vip_plans";
    $plans = $db->query($sql)->fetchAll();
}
function list_products()
{
    global $db, $products, $products_per_page;
    if (!isset($db)) {
        $products = [];
        return;
    }
    try {
        $sql = "select * from sp_files ORDER BY id DESC LIMIT $products_per_page";
        $products = $db->query($sql)->fetchAll();
    } catch (PDOException $e) {
        error_log("Error in list_products: " . $e->getMessage());
        $products = [];
    }
}

function get_specific_page($page, $type)
{
    global $db, $products, $products_per_page, $orders, $orders_per_page, $users, $users_per_page, $tickets, $tickets_per_page, $cats, $cats_per_page, $options, $options_per_page;
    if ($type == 'products') {
        $page_number = $page;
        $offset =  $products_per_page * ($page_number - 1);
        $sql = "select * FROM sp_files ORDER BY id DESC LIMIT $products_per_page OFFSET $offset";
        $products = $db->query($sql)->fetchAll();
    } elseif ($type == 'orders') {
        $page_number = $page;
        $offset =  $orders_per_page * ($page_number - 1);
        $sql = "select * FROM sp_orders ORDER BY id DESC LIMIT $orders_per_page OFFSET $offset";
        $orders = $db->query($sql)->fetchAll();
    } elseif ($type == 'users') {
        $page_number = $page;
        $offset =  $users_per_page * ($page_number - 1);
        $sql = "select * FROM sp_users ORDER BY id DESC LIMIT $users_per_page OFFSET $offset";
        $users = $db->query($sql)->fetchAll();
    } elseif ($type == 'tickets') {
        $page_number = $page;
        $offset =  $tickets_per_page * ($page_number - 1);
        $sql = "select * FROM sp_tickets ORDER BY id DESC LIMIT $tickets_per_page OFFSET $offset";
        $tickets = $db->query($sql)->fetchAll();
    } elseif ($type == 'cats') {
        $page_number = $page;
        $offset =  $cats_per_page * ($page_number - 1);
        $sql = "select * FROM sp_cats ORDER BY id DESC LIMIT $cats_per_page OFFSET $offset";
        $cats = $db->query($sql)->fetchAll();
    } elseif ($type == 'options') {
        $page_number = $page;
        $offset =  $options_per_page * ($page_number - 1);
        $sql = "select * FROM sp_options LIMIT $options_per_page OFFSET $offset";
        $options = $db->query($sql)->fetchAll();
        // تبدیل encoding برای اطمینان از نمایش صحیح
        foreach ($options as &$option) {
            if (isset($option['value'])) {
                $option['value'] = mb_convert_encoding($option['value'], 'UTF-8', 'auto');
            }
            if (isset($option['description'])) {
                $option['description'] = mb_convert_encoding($option['description'], 'UTF-8', 'auto');
            }
        }
        unset($option);
    }
}

function fetch_pages_count($type)
{
    global $db, $pages_number, $products_per_page, $orders_per_page, $users_per_page, $tickets_per_page, $cats_per_page, $options_per_page;
    if ($type == 'products') {
        $sql = "select * from sp_files";
        $products = $db->query($sql)->fetchAll();
        $products_count = count($products);
        $pages_number = ceil($products_count / $products_per_page);
    } elseif ($type == 'orders') {
        $sql = "select * from sp_orders";
        $orders = $db->query($sql)->fetchAll();
        $orders_count = count($orders);
        $pages_number = ceil($orders_count / $orders_per_page);
    } elseif ($type == 'users') {
        $sql = "select * from sp_users";
        $users = $db->query($sql)->fetchAll();
        $users_count = count($users);
        $pages_number = ceil($users_count / $users_per_page);
    } elseif ($type == 'tickets') {
        $sql = "select * from sp_tickets";
        $tickets = $db->query($sql)->fetchAll();
        $tickets_count = count($tickets);
        $pages_number = ceil($tickets_count / $tickets_per_page);
    } elseif ($type == 'cats') {
        $sql = "select * from sp_cats";
        $cats = $db->query($sql)->fetchAll();
        $cats_count = count($cats);
        $pages_number = ceil($cats_count / $cats_per_page);
    } elseif ($type == 'options') {
        $sql = "select * from sp_options";
        $options = $db->query($sql)->fetchAll();
        $options_count = count($options);
        $pages_number = ceil($options_count / $options_per_page);
    }
}

function products_count()
{
    global $db, $products;
    $sql = "select * from sp_files";
    $products = $db->query($sql)->fetchAll();
    return count($products);
}
function cat_name($cat_id)
{
    global $db;
    $sql = "select * from sp_cats where id='$cat_id'";
    $cat = $db->query($sql)->fetch();
    $cat_name = $cat['name'];
    return $cat_name;
}
function list_tickets()
{
    global $db, $tickets, $tickets_per_page;
    $sql = "select * from sp_tickets ORDER BY id DESC LIMIT $tickets_per_page";
    $tickets = $db->query($sql)->fetchAll();
}
function list_tickets_limit5()
{
    global $db, $tickets;
    $sql = "select * from sp_tickets ORDER BY id DESC LIMIT 5";
    $tickets = $db->query($sql)->fetchAll();
}
function sendMessage($userid, $msg)
{
    $msg = urlencode($msg);
    $url = 'https://api.telegram.org/bot' . TOKEN . '/sendMessage?chat_id=' . $userid . '&text=' . $msg . '&parse_mode=HTML';
    $res = file_get_contents($url);
    if ($res) {
        return true;
    } else {
        return false;
    }
}

function create_cat()
{
    global $db;
    $cat_name = $_POST['cat_name'];
    $sql = "INSERT INTO sp_cats VALUES(NULL,'$cat_name')";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function create_admin()
{
    global $db;
    $user = $_POST['username'];
    $pass = $_POST['pass'];
    if (!empty($user) && !empty($pass)) {
        $hashed_pwd = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO sp_admins VALUES(NULL,'$user','$hashed_pwd')";
        $result = $db->query($sql);
        if ($result) {
            return true;
        }
    }
}
function change_admin_pass($id)
{
    global $db;
    $pass = $_POST['pass'];
    if (!empty($pass)) {
        $hashed_pwd = password_hash($pass, PASSWORD_DEFAULT);
        $sql = "UPDATE sp_admins SET password='$hashed_pwd' WHERE id=$id";
        $result = $db->query($sql);
        if ($result) {
            return true;
        }
    }
}
function edit_cat()
{
    global $db;
    $cat_name = $_POST['cat_name'];
    $cat_id = $_POST['cat_id'];
    $sql = "UPDATE sp_cats SET name='$cat_name' where id='$cat_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function delete_cat()
{
    global $db;
    $cat_id = intval($_GET['del_cat']);
    $sql = "DELETE FROM sp_cats where id='$cat_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}

function delete_admin()
{
    global $db;
    $admin_id = intval($_GET['del_admin']);
    $sql = "DELETE FROM sp_admins where id='$admin_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function delete_product()
{
    global $db;
    $product_id = intval($_GET['del_prd']);
    $sql = "DELETE FROM sp_files where id='$product_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function delete_ticket()
{
    global $db;
    $ticket_id = intval($_GET['del_ticket']);
    $sql = "DELETE FROM sp_tickets where id='$ticket_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function delete_order()
{
    global $db;
    $order_id = intval($_GET['del_order']);
    $sql = "DELETE FROM sp_orders where id='$order_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function delete_user()
{
    global $db;
    $user_id = intval($_GET['del_user']);
    $sql = "DELETE FROM sp_users where id='$user_id'";
    $result = $db->query($sql);
    if ($result) {
        return true;
    }
}
function insert_product()
{
    global $db, $empty_inputs;
    $product_name = $_POST['prd_name'];
    $product_name_en = isset($_POST['prd_name_en']) ? trim($_POST['prd_name_en']) : '';
    $product_description = $_POST['prd_desc'];
    // دریافت دسته‌بندی از فرم
    $product_category = isset($_POST['prd_cat']) && !empty($_POST['prd_cat']) ? intval($_POST['prd_cat']) : 0;
    $price = 0; // قیمت حذف شده - همیشه 0
    $product_type = $_POST['prd_type'];
    $product_status = $_POST['prd_status'];
    $product_demo = isset($_POST['demo']) ? $_POST['demo'] : '';
    
    // فیلدهای جدید برای فیلم و سریال
    $media_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'movie';
    $year = isset($_POST['year']) ? intval($_POST['year']) : 'NULL';
    // ژانر حذف شده - دیگر استفاده نمی‌شود
    $genre = '';
    $quality = isset($_POST['quality']) ? $_POST['quality'] : '';
    $imdb = isset($_POST['imdb']) ? $_POST['imdb'] : '';
    $director = isset($_POST['director']) ? $_POST['director'] : '';
    $cast = isset($_POST['cast']) ? $_POST['cast'] : '';
    $duration = isset($_POST['duration']) ? $_POST['duration'] : '';
    $season = isset($_POST['season']) && !empty($_POST['season']) ? intval($_POST['season']) : null;
    $episode = isset($_POST['episode']) && !empty($_POST['episode']) ? intval($_POST['episode']) : null;
    
    // پردازش عکس پوستر
    $poster = '';
    if (isset($_POST['poster']) && !empty($_POST['poster'])) {
        $poster = $_POST['poster'];
    } elseif (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] == 0) {
        // آپلود فایل عکس
        $upload_dir = '../uploads/posters/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $upload_path)) {
                $poster = 'uploads/posters/' . $new_filename;
            }
        }
    }
    
    if (isset($product_name) && isset($product_description)) {
        // استفاده از prepared statement برای امنیت بیشتر
        $sql = "INSERT INTO sp_files (name, name_en, description, catid, fileurl, type, media_type, year, genre, quality, imdb, director, cast, duration, season, episode, poster, price, status, demo, views) 
                VALUES (:name, :name_en, :desc, :catid, '', :type, :media_type, :year, :genre, :quality, :imdb, :director, :cast, :duration, :season, :episode, :poster, :price, :status, :demo, 0)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $product_name);
        $stmt->bindValue(':name_en', empty($product_name_en) ? null : $product_name_en, PDO::PARAM_STR);
        $stmt->bindParam(':desc', $product_description);
        $stmt->bindParam(':catid', $product_category);
        $stmt->bindParam(':type', $product_type);
        $stmt->bindParam(':media_type', $media_type);
        $stmt->bindValue(':year', $year === 'NULL' ? null : $year, PDO::PARAM_INT);
        $stmt->bindParam(':genre', $genre);
        $stmt->bindParam(':quality', $quality);
        $stmt->bindParam(':imdb', $imdb);
        $stmt->bindParam(':director', $director);
        $stmt->bindParam(':cast', $cast);
        $stmt->bindParam(':duration', $duration);
        $stmt->bindValue(':season', $season, PDO::PARAM_INT);
        $stmt->bindValue(':episode', $episode, PDO::PARAM_INT);
        $stmt->bindParam(':poster', $poster);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':status', $product_status);
        $stmt->bindParam(':demo', $product_demo);
        
        $result = $stmt->execute();
        if ($result) {
            return true;
        } else {
            return false;
        }
    } else {
        $empty_inputs = 1;
    }
}

function fetch_product_info($id)
{
    global $db, $product, $product_name, $product_description, $product_category, $file_id, $price, $product_type, $product_status, $product_demo;
    global $media_type, $year, $quality, $imdb, $director, $cast, $duration, $season, $episode, $poster, $product_name_en;
    $sql = "select * from sp_files where id='$id'";
    $product = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    $product_name = $product['name'];
    $product_name_en = isset($product['name_en']) ? $product['name_en'] : '';
    $product_description = $product['description'];
    $product_category = $product['catid'];
    $file_id = $product['fileurl'];
    $price = $product['price'];
    $product_type = $product['type'];
    $product_status = $product['status'];
    $product_demo = $product['demo'];
    
    // فیلدهای جدید
    $media_type = isset($product['media_type']) ? $product['media_type'] : 'movie';
    $year = isset($product['year']) ? $product['year'] : '';
    $quality = isset($product['quality']) ? $product['quality'] : '';
    $imdb = isset($product['imdb']) ? $product['imdb'] : '';
    $director = isset($product['director']) ? $product['director'] : '';
    $cast = isset($product['cast']) ? $product['cast'] : '';
    $duration = isset($product['duration']) ? $product['duration'] : '';
    $season = isset($product['season']) ? $product['season'] : '';
    $episode = isset($product['episode']) ? $product['episode'] : '';
    $poster = isset($product['poster']) ? $product['poster'] : '';
}

function update_product($id)
{
    global $db;
    $product_id = $_POST['id'];
    $product_name = $_POST['prd_name'];
    $product_name_en = isset($_POST['prd_name_en']) ? trim($_POST['prd_name_en']) : '';
    $product_description = $_POST['prd_desc'];
    // دریافت دسته‌بندی از فرم
    $product_category = isset($_POST['prd_cat']) && !empty($_POST['prd_cat']) ? intval($_POST['prd_cat']) : 0;
    $price = 0; // قیمت حذف شده - همیشه 0
    // نوع محصول و وضعیت محصول حذف شده - همه محصولات به صورت پیش‌فرض فعال و رایگان هستند
    $product_type = 'free'; // همیشه رایگان
    $product_status = 1; // همیشه فعال
    $product_demo = isset($_POST['demo']) ? $_POST['demo'] : '';
    
    // فیلدهای جدید برای فیلم و سریال
    $media_type = isset($_POST['media_type']) ? $_POST['media_type'] : 'movie';
    $year = isset($_POST['year']) && !empty($_POST['year']) ? intval($_POST['year']) : null;
    // ژانر حذف شده - دیگر استفاده نمی‌شود
    $genre = '';
    $quality = isset($_POST['quality']) ? $_POST['quality'] : '';
    $imdb = isset($_POST['imdb']) ? $_POST['imdb'] : '';
    $director = isset($_POST['director']) ? $_POST['director'] : '';
    $cast = isset($_POST['cast']) ? $_POST['cast'] : '';
    $duration = isset($_POST['duration']) ? $_POST['duration'] : '';
    $season = isset($_POST['season']) && !empty($_POST['season']) ? intval($_POST['season']) : null;
    $episode = isset($_POST['episode']) && !empty($_POST['episode']) ? intval($_POST['episode']) : null;
    
    // پردازش عکس پوستر
    $poster = isset($_POST['poster']) ? $_POST['poster'] : '';
    if (isset($_FILES['poster_file']) && $_FILES['poster_file']['error'] == 0) {
        // آپلود فایل عکس جدید
        $upload_dir = '../uploads/posters/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['poster_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['poster_file']['tmp_name'], $upload_path)) {
                $poster = 'uploads/posters/' . $new_filename;
            }
        }
    }
    
    $sql = "UPDATE sp_files SET 
            name=:name, 
            name_en=:name_en,
            description=:desc, 
            catid=:catid, 
            fileurl=:fileurl, 
            type=:type, 
            media_type=:media_type,
            year=:year,
            genre=:genre,
            quality=:quality,
            imdb=:imdb,
            director=:director,
            cast=:cast,
            duration=:duration,
            season=:season,
            episode=:episode,
            poster=:poster,
            price=:price, 
            status=:status, 
            demo=:demo 
            WHERE id=:id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $product_name);
    $stmt->bindValue(':name_en', empty($product_name_en) ? null : $product_name_en, PDO::PARAM_STR);
    $stmt->bindParam(':desc', $product_description);
    $stmt->bindParam(':catid', $product_category);
    $empty_fileurl = '';
    $stmt->bindParam(':fileurl', $empty_fileurl);
    $stmt->bindParam(':type', $product_type);
    $stmt->bindParam(':media_type', $media_type);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindParam(':genre', $genre);
    $stmt->bindParam(':quality', $quality);
    $stmt->bindParam(':imdb', $imdb);
    $stmt->bindParam(':director', $director);
    $stmt->bindParam(':cast', $cast);
    $stmt->bindParam(':duration', $duration);
    $stmt->bindValue(':season', $season, PDO::PARAM_INT);
    $stmt->bindValue(':episode', $episode, PDO::PARAM_INT);
    $stmt->bindParam(':poster', $poster);
    $stmt->bindParam(':price', $price);
    $stmt->bindParam(':status', $product_status);
    $stmt->bindParam(':demo', $product_demo);
    $stmt->bindParam(':id', $product_id);
    
    $result = $stmt->execute();
    if ($result) {
        return true;
    }
}

function total_income()
{
    global $db;
    if (!isset($db)) {
        return "0 تومان";
    }
    try {
        $sql = "select * from sp_orders where status=1";
        $orders = $db->query($sql)->fetchAll();
        $total_income = 0;
        foreach ($orders as $order) {
            $total_income += $order['price'];
        }
        return number_format($total_income) . " تومان ";
    } catch (PDOException $e) {
        error_log("Error in total_income: " . $e->getMessage());
        return "0 تومان";
    }
}
function list_orders()
{
    global $db, $orders, $orders_per_page;
    $sql = "select * from sp_orders ORDER BY id DESC LIMIT $orders_per_page";
    $orders = $db->query($sql)->fetchAll();
}
function list_orders_limit5()
{
    global $db, $orders;
    $sql = "select * from sp_orders ORDER BY id DESC LIMIT 5";
    $orders = $db->query($sql)->fetchAll();
}
function fetch_product_name($product_id)
{
    global $db;
    $sql = "select * from sp_files where id=$product_id";
    $result = $db->query($sql)->fetch();
    $product_name = $result['name'];
    echo $product_name;
}
function fetch_plan_name($planid)
{
    global $db;
    $sql = "select * from sp_vip_plans where id=$planid";
    $result = $db->query($sql)->fetch();
    $plan_name = $result['name'];
    echo $plan_name;
}
