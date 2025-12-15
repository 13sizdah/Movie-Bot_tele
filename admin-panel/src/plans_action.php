<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

// پردازش فعالسازی/غیرفعالسازی حالت VIP (فقط هنگام ارسال فرم)
if (isset($_POST['plan_update'])) {
    $enable_vip_mode = isset($_POST['enable_vip_mode']) && $_POST['enable_vip_mode'] == '1' ? '1' : '0';
    
    try {
        // بررسی وجود تنظیمات
        $check_stmt = $db->prepare("SELECT id FROM sp_webapp_settings WHERE setting_key = 'enable_vip_mode' LIMIT 1");
        $check_stmt->execute();
        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // به‌روزرسانی تنظیمات موجود
            $update_stmt = $db->prepare("UPDATE sp_webapp_settings SET setting_value = :value, updated_at = NOW() WHERE setting_key = 'enable_vip_mode'");
            $update_stmt->bindValue(':value', $enable_vip_mode, PDO::PARAM_STR);
            $update_stmt->execute();
        } else {
            // ایجاد تنظیمات جدید
            $insert_stmt = $db->prepare("INSERT INTO sp_webapp_settings (setting_key, setting_value, setting_type, category, description) VALUES ('enable_vip_mode', :value, 'boolean', 'vip', 'فعالسازی حالت VIP')");
            $insert_stmt->bindValue(':value', $enable_vip_mode, PDO::PARAM_STR);
            $insert_stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("Error updating VIP mode: " . $e->getMessage());
    }
}

if(isset($_POST['plan_update'])){
    $all_success = true;
    
    // Iterate over POST values
    foreach ($_POST['name'] as $key => $val) {
        $plan_id = $_POST['id'][$key];
        $plan_name = $_POST['name'][$key];
        $plan_price = ($_POST['price'][$key]);
        $plan_day = ($_POST['day'][$key]);

        $sql = "UPDATE sp_vip_plans SET name='$plan_name',price='$plan_price',days='$plan_day' where id='$plan_id'";
        $result = $db->query($sql);
        if(!$result){
            $all_success = false;
        }
    }
    
    // اگر همه پلن‌ها با موفقیت به‌روزرسانی شدند، هدایت کن
    if($all_success){ ?>
        <script type="text/javascript">
        window.location = "plans.php?plans_updated";
    </script>
<?php } else { ?>
    <script type="text/javascript">
        window.location = "plans.php?plan_update_error";
    </script>
<?php }
}
?>
<?php if (isset($_GET['plans_updated'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        پلن ها با موفقیت به روز رسانی شدند
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>

<?php if (isset($_GET['plan_update_error'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-red-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        مشکلی در بروزرسانی پلن ها بوجود آمده است
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>