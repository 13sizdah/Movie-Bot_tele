<?php
// ============================================================
// پردازش callback های پنل مدیریت
// ============================================================

// پردازش callback های پنل مدیریت
function handle_admin_panel_callbacks()
{
    global $cdata, $cid, $cuserid, $cmsgid, $admin;
    
    // بررسی دسترسی ادمین
    if ($cuserid != $admin) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '❌ شما دسترسی به این بخش را ندارید',
            'show_alert' => true
        ]);
        return;
    }
    
    // منوی اصلی
    if ($cdata == 'admin_main_menu') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_main_menu($cuserid);
        return;
    }
    
    // داشبورد
    if ($cdata == 'admin_dashboard') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_dashboard($cuserid);
        return;
    }
    
    // محصولات
    if ($cdata == 'admin_products') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_products_list($cuserid, 1);
        return;
    }
    
    if (preg_match('/^admin_products_page#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_products_list($cuserid, intval($matches[1]));
        return;
    }
    
    if (preg_match('/^admin_edit_product#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_product_details($cuserid, intval($matches[1]));
        return;
    }
    
    if (preg_match('/^admin_toggle_product#(\d+)$/', $cdata, $matches)) {
        $GLOBALS['cid'] = $cid;
        toggle_admin_product_status($cuserid, intval($matches[1]));
        return;
    }
    
    // کاربران
    if ($cdata == 'admin_users') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_users_list($cuserid, 1);
        return;
    }
    
    if (preg_match('/^admin_users_page#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_users_list($cuserid, intval($matches[1]));
        return;
    }
    
    // دسته‌بندی‌ها
    if ($cdata == 'admin_categories') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_categories_list($cuserid);
        return;
    }
    
    // افزودن دسته‌بندی
    if ($cdata == 'admin_add_category') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_add_category_menu($cuserid);
        return;
    }
    
    if ($cdata == 'admin_cancel_add_category') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        file_put_contents('users/' . $cuserid . '.txt', ' ');
        bot('sendMessage', [
            'chat_id' => $cuserid,
            'text' => '❌ افزودن دسته‌بندی لغو شد',
            'parse_mode' => 'HTML'
        ]);
        show_admin_categories_list($cuserid);
        return;
    }
    
    // مدیریت دسته‌بندی‌ها (ویرایش/حذف)
    if ($cdata == 'admin_manage_categories') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_manage_categories($cuserid);
        return;
    }
    
    // جزئیات یک دسته‌بندی
    if (preg_match('/^admin_category_details#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_category_details($cuserid, intval($matches[1]));
        return;
    }
    
    // ویرایش دسته‌بندی
    if (preg_match('/^admin_edit_category#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_edit_category_menu($cuserid, intval($matches[1]));
        return;
    }
    
    if ($cdata == 'admin_cancel_edit_category') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        file_put_contents('users/' . $cuserid . '.txt', ' ');
        bot('sendMessage', [
            'chat_id' => $cuserid,
            'text' => '❌ ویرایش دسته‌بندی لغو شد',
            'parse_mode' => 'HTML'
        ]);
        show_admin_categories_list($cuserid);
        return;
    }
    
    // حذف دسته‌بندی
    if (preg_match('/^admin_delete_category_confirm#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_delete_category_confirm($cuserid, intval($matches[1]));
        return;
    }
    
    if (preg_match('/^admin_delete_category_yes#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        delete_admin_category($cuserid, intval($matches[1]));
        return;
    }
    
    // تیکت‌ها
    if ($cdata == 'admin_tickets') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_tickets_list($cuserid, 1);
        return;
    }
    
    if (preg_match('/^admin_tickets_page#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_tickets_list($cuserid, intval($matches[1]));
        return;
    }
    
    // ارسال همگانی
    if ($cdata == 'admin_sendtoall') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_sendtoall_menu($cuserid);
        return;
    }
    
    if ($cdata == 'admin_cancel_sendtoall') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        file_put_contents('users/' . $cuserid . '.txt', ' ');
        bot('sendMessage', [
            'chat_id' => $cuserid,
            'text' => '❌ ارسال همگانی لغو شد',
            'parse_mode' => 'HTML'
        ]);
        show_admin_main_menu($cuserid);
        return;
    }
    
    // تنظیمات
    if ($cdata == 'admin_settings') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_settings_menu($cuserid);
        return;
    }
    
    if (preg_match('/^admin_edit_option#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_edit_option($cuserid, intval($matches[1]), $cmsgid);
        return;
    }
    
    if ($cdata == 'admin_cancel_edit_option') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        cancel_admin_edit_option($cuserid, $cmsgid);
        return;
    }
    
    if ($cdata == 'admin_admins') {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🚧 این بخش به زودی اضافه خواهد شد',
            'show_alert' => true
        ]);
        return;
    }
    
    // تغییر دسته‌بندی محصول
    if (preg_match('/^admin_change_product_category#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_change_product_category($cuserid, intval($matches[1]));
        return;
    }
    
    // تنظیم دسته‌بندی محصول
    if (preg_match('/^admin_set_product_category#(\d+)#(\d+)$/', $cdata, $matches)) {
        $GLOBALS['cid'] = $cid;
        set_admin_product_category($cuserid, intval($matches[1]), intval($matches[2]));
        return;
    }
    
    // حذف دسته‌بندی محصول
    if (preg_match('/^admin_remove_product_category#(\d+)$/', $cdata, $matches)) {
        $GLOBALS['cid'] = $cid;
        remove_admin_product_category($cuserid, intval($matches[1]));
        return;
    }
    
    // حذف محصول
    if (preg_match('/^admin_delete_product_confirm#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_delete_product_confirm($cuserid, intval($matches[1]));
        return;
    }
    
    if (preg_match('/^admin_delete_product_yes#(\d+)$/', $cdata, $matches)) {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        delete_admin_product($cuserid, intval($matches[1]));
        return;
    }
    
    // افزودن محصول
    if ($cdata == 'admin_add_product') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        show_admin_add_product_menu($cuserid);
        return;
    }
    
    // لغو افزودن محصول
    if ($cdata == 'admin_cancel_add_product') {
        bot('answercallbackquery', ['callback_query_id' => $cid]);
        cancel_admin_add_product($cuserid);
        return;
    }
    
}

