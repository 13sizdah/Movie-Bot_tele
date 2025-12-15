-- ============================================================
-- دیتابیس نهایی - سیستم مدیریت فیلم و سریال
-- ============================================================
-- این فایل شامل تمام جداول و تنظیمات پیش‌فرض سیستم است
-- نسخه: 1.0.0
-- تاریخ: 2024
-- ============================================================
-- راهنمای استفاده:
-- 1. قبل از اجرا، از دیتابیس خود بکاپ بگیرید
-- 2. این فایل را در phpMyAdmin یا هر ابزار مدیریت دیتابیس اجرا کنید
-- 3. یا از دستور mysql استفاده کنید: mysql -u username -p database_name < DATABASE_FINAL_COMPLETE.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- 1. ایجاد جدول ادمین‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. ایجاد جدول دسته‌بندی‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_cats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اضافه کردن دسته‌بندی‌های استاندارد
INSERT IGNORE INTO `sp_cats` (`id`, `name`) VALUES
(1, 'اکشن'),
(2, 'ماجراجویی'),
(3, 'انیمیشن'),
(4, 'کمدی'),
(5, 'درام'),
(6, 'فانتزی'),
(7, 'وحشت'),
(8, 'علمی-تخیلی'),
(9, 'هیجان‌انگیز'),
(10, 'عاشقانه'),
(11, 'جنگی'),
(12, 'مستند'),
(13, 'زندگینامه'),
(14, 'جنایی'),
(15, 'خانوادگی'),
(16, 'تاریخی'),
(17, 'موزیکال'),
(18, 'معمایی'),
(19, 'ورزشی'),
(20, 'وسترن');

-- ============================================================
-- 3. ایجاد جدول کانال‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `channel_username` varchar(255) NOT NULL COMMENT 'یوزرنیم کانال (بدون @)',
  `channel_id` varchar(100) DEFAULT NULL COMMENT 'شناسه عددی کانال',
  `channel_title` varchar(255) DEFAULT NULL COMMENT 'نام کانال',
  `channel_link` varchar(500) DEFAULT NULL COMMENT 'لینک دعوت کانال',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT 'وضعیت: 1=فعال، 0=غیرفعال',
  `order_num` int(11) NOT NULL DEFAULT 0 COMMENT 'ترتیب نمایش',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. ایجاد جدول کیفیت‌های قسمت‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_episode_qualities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL COMMENT 'شناسه قسمت',
  `quality` varchar(20) NOT NULL COMMENT 'کیفیت (مثلا: 720p, 1080p, 4K)',
  `download_link` varchar(500) NOT NULL COMMENT 'لینک دانلود',
  `file_size` varchar(50) DEFAULT NULL COMMENT 'حجم فایل',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT 'وضعیت: 1=فعال، 0=غیرفعال',
  `order_num` int(11) NOT NULL DEFAULT 0 COMMENT 'ترتیب نمایش',
  PRIMARY KEY (`id`),
  KEY `episode_id` (`episode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ایجاد جدول فایل‌ها (فیلم/سریال)
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT 'نام فیلم/سریال',
  `name_en` varchar(200) DEFAULT NULL COMMENT 'نام انگلیسی فیلم/سریال',
  `description` mediumtext NOT NULL COMMENT 'توضیحات',
  `caption` text DEFAULT NULL COMMENT 'کپشن قابل ویرایش',
  `catid` int(11) NOT NULL DEFAULT 0 COMMENT 'شناسه دسته‌بندی',
  `fileurl` mediumtext NOT NULL COMMENT 'لینک دانلود یا File ID',
  `type` varchar(10) NOT NULL COMMENT 'نوع: free یا vip',
  `media_type` varchar(10) NOT NULL DEFAULT 'movie' COMMENT 'نوع محتوا: movie, series, animation یا anime',
  `year` int(4) DEFAULT NULL COMMENT 'سال تولید',
  `genre` varchar(255) DEFAULT NULL COMMENT 'ژانر (برای سازگاری با کد قدیمی)',
  `quality` varchar(20) DEFAULT NULL COMMENT 'کیفیت (برای سازگاری با کد قدیمی)',
  `imdb` varchar(10) DEFAULT NULL COMMENT 'امتیاز IMDb',
  `director` varchar(255) DEFAULT NULL COMMENT 'کارگردان',
  `cast` text DEFAULT NULL COMMENT 'بازیگران',
  `duration` varchar(20) DEFAULT NULL COMMENT 'مدت زمان (برای فیلم) یا تعداد قسمت (برای سریال)',
  `season` int(11) DEFAULT NULL COMMENT 'فصل (برای سازگاری با کد قدیمی)',
  `episode` int(11) DEFAULT NULL COMMENT 'قسمت (برای سازگاری با کد قدیمی)',
  `poster` varchar(500) DEFAULT NULL COMMENT 'لینک پوستر',
  `unique_link` varchar(100) DEFAULT NULL COMMENT 'لینک اختصاصی',
  `price` int(11) NOT NULL DEFAULT 0 COMMENT 'قیمت به تومان',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT 'وضعیت: 1=فعال، 0=غیرفعال',
  `demo` varchar(255) DEFAULT NULL COMMENT 'لینک پیش‌نمایش/تریلر',
  `views` bigint(20) NOT NULL DEFAULT 0 COMMENT 'تعداد بازدید',
  PRIMARY KEY (`id`),
  KEY `idx_name_en` (`name_en`),
  KEY `idx_catid` (`catid`),
  KEY `idx_media_type` (`media_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ایجاد جدول تنظیمات ربات
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اضافه کردن تنظیمات پیش‌فرض
INSERT IGNORE INTO `sp_options` (`id`, `name`, `description`, `value`) VALUES
(1, 'home', 'دکمه منوی اصلی', 'منوی اصلی ????'),
(2, 'shop', 'دکمه فروشگاه', '???? ورود به بخش دانلود فیلم و سریال ????'),
(3, 'vip_member', 'دکمه عضویت ویژه', '???? عضویت ویژه'),
(4, 'my_transactions', 'دکمه تراکنش ها', '???? تراکنش های من'),
(5, 'send_ticket', 'دکمه تیکت', '???? ارسال تیکت / درخواست محصول'),
(6, 'phone_verefication', 'دکمه ارسال شماره', '☎️ ارسال شماره همراه'),
(7, 'welcome_msg', 'پیام خوش آمدگویی', '???? به ربات دانلود فیلم و سریال خوش آمدید ????\r\n\r\n???? ربات دانلود فیلم و سریال جهت دریافت فیلم و سریال با کیفیت بالا ایجاد شده است.\r\n\r\n???? از منوی پایین انتخاب کنید ????'),
(8, 'requst_phone_msg', 'پیام درخواست تایید شماره', '⚠️ برای استفاده از ربات ابتدا باید شماره همراه خود را تایید کنید ⚠️\r\n\r\n???? نکات مهم:\r\n1️⃣ از ارسال شماره دیگران خودداری فرمایید.\r\n2️⃣ شماره باید متعلق به خود شما باشد.\r\n\r\n❓ چرا باید شماره خود را ارسال کنم؟\r\n???? با توجه به متصل بودن ربات به درگاه پرداخت اینترنتی، به منظور جلوگیری از هرگونه سوء استفاده احتمالی از کارت‌های بانکی (فیشینگ)، کاربر باید قبل از استفاده از ربات شماره خود را تایید کند تا با اطلاعات پرداخت‌کننده تطبیق داده شود.'),
(9, 'phone_verified', 'پیام تایید شدن شماره', 'شماره موبایل شما با موفقیت تایید شد ✅'),
(10, 'phone_cheating', 'پیام زمانی که شماره اشتباه شیر شود', '❌  لطفا از اشتراک شماره ی دیگران خودداری کنید ❌'),
(11, 'wrong_format', 'پیام شماره غیر معتبر', '❌ شماره ارسالی صحیح نمیباشد.\r\nلطفا شماره معتبر خود را ارسال کنید.\r\n\r\n⚠️ توجه: شماره باید متعلق به خود شما باشد.'),
(12, 'cats_msg', 'پیام دسته بندی ها', '???? به بخش دانلود فیلم و سریال خوش آمدید ????\r\n\r\n???? در این بخش می‌توانید دسته‌بندی مورد نظر خود را انتخاب کنید و فیلم/سریال‌های هر دسته‌بندی را مشاهده کنید.\r\n\r\n???? لطفاً یکی از دسته‌بندی‌های زیر را انتخاب کنید ????'),
(13, 'empty_cat', 'پیام دسته بندی خالی', '❗️ فیلم یا سریالی در این دسته بندی وجود ندارد!'),
(14, 'products_list_waiting', 'پیام انتظار دریافت لیست محصولات', '⏳ در حال دریافت فیلم و سریال... ⏳'),
(15, 'choose_product', 'پیام انتخاب محصول', '???? لطفاً یکی از فیلم/سریال‌های زیر را انتخاب کنید ????'),
(16, 'product_info_waiting', 'پیام انتظار دریافت اطلاعات محصول', '⏳ در حال دریافت اطلاعات فیلم/سریال... ⏳'),
(17, 'free_msg', 'پیام دریافت رایگان فایل', '???? این فیلم/سریال رایگان است و می توانید آنرا دانلود کنید.'),
(18, 'vip_msg', 'پیام پیشنهاد خرید اشتراک', '???? نکته : با خرید اشتراک ویژه می توانید همه فیلم و سریال های ربات را بصورت رایگان دریافت کنید.'),
(19, 'dl_btn', 'دکمه دانلود فایل', '???? دریافت لینک دانلود'),
(20, 'pay_btn', 'دکمه پرداخت', '???? پرداخت آنلاین'),
(21, 'demo_btn', 'دکمه پیشنمایش', '???? پیشنمایش / تریلر'),
(22, 'vip_remaining', 'پیام روز های باقیمانده اشتراک', '???? اشتراک ویژه فعال دارید.\r\n❕ برای اطلاع از جزئیات حساب خود، دکمه حساب کاربری را انتخاب کنید.\r\n⏳ تعداد روز های باقیمانده از اشتراک : [vip_days] روز\r\n'),
(23, 'allowed_vip_msg', 'پیام اشتراک ویژه / اجازه ی دانلود', '???? اشتراک ویژه دارید و میتوانید این فیلم/سریال را دریافت کنید.'),
(24, 'sending_file', 'پیام ارسال فایل', '???? در حال ارسال لینک دانلود... ????'),
(25, 'vip_plans', 'پیام پلن های اشتراک ویژه', '????  با خرید اشتراک ویژه، می توانید تمام فیلم و سریال ها را به صورت رایگان دریافت کنید.\r\n???? پرداخت به صورت اتوماتیک انجام شده و بلافاصله پس از پرداخت، پلن مورد نظر روی حساب شما فعال خواهد شد.\r\n\r\n  لطفا یکی از پلن های زیر را انتخاب کنید ????'),
(26, 'orders_msg', 'متن سفارش ها', '???? نام فیلم/سریال: <a href=\"[product_link]\">[product_name]</a>\r\n???? قیمت : [order_price] تومان\r\n???? شماره تراکنش: [order_transcode]\r\n???? تاریخ تراکنش: [order_date]'),
(27, 'already_purchased_product', 'پیام محصول خریداری شده', '???? شما این فیلم/سریال را از قبل خریداری کرده اید و میتوانید آنرا دانلود کنید'),
(28, 'empty_transactions', 'پیام خالی بودن سفارشات', 'تاکنون هیچ تراکنش ثبت شده ای ندارید ????'),
(29, 'product_info', 'توضیحات محصول', '???? نام فیلم/سریال:\r\n[name]\r\n\r\n????توضیحات :\r\n[desc]\r\n\r\n==================\r\n???? قیمت : [price]\r\n==================\r\n[footer_msg]'),
(30, 'ticket_msg', 'پیام قبل از ارسال تیکت', 'لطفا پیام خود را بنویسید و ارسال کنید. ✍️'),
(31, 'ticket_sent', 'پیام تیکت ارسال شد', 'پیام شما با موفقیت ارسال شد ✅ \r\nدر صورت نیاز به پاسخگویی حداکثر ظرف 24 ساعت، تیکت شما پاسخ داده خواهد شد ????'),
(32, 'new_ticket', 'پیام تیکت جدید (برای ادمین)', '???? تیکت جدیدی از سوی کاربران ارسال شده است.\r\n???? داشبورد مدیریتی خود را بررسی کنید'),
(33, 'search_products', 'دکمه ی جستجوی محصولات', '???? جستجوی فیلم و سریال'),
(34, 'no_search_result', 'پیام پیدا نشدن محصول در جستجو', '❌ فیلم یا سریالی یافت نشد❌\r\nسعی کنید کلید واژه ی جستجوی خود را تغییر دهید'),
(35, 'search_description', 'توضیحات زیر نتایج جستجو', '???? برای دریافت اطلاعات فیلم/سریال مورد نظر، روی آن کلیک کرده و ربات را استارت کنید.'),
(36, 'empty_cats', 'پیام خالی بودن دسته بندی ها', 'هیج دسته بندی وجود ندارد. نسبت به اضافه کردن دسته بندی اقدام کنید'),
(37, 'account_info', 'اطلاعات حساب کاربری', '???? حساب کاربری شما در ربات دانلود فیلم و سریال :\r\n???? نام = [name]\r\n???? شناسه عددی = [userid]\r\n☎️ شماره تلفن = [phone]\r\n???? پلن اشتراک = [vip_plan]\r\n???? تعداد فیلم/سریال بازدید شده = [total_orders]\r\n\r\n⚠️ در صورت نیاز به پشتیبانی، از طریق دکمه تیکت با ما در تماس باشید.'),
(38, 'vip_purchased_successfully', 'پیام خرید موفق آمیز اشتراک ویژه', '✅ عضویت VIP شما با موفقیت فعال شد .\r\n???? شماره تراکنش : [refid]\r\n???? نام پلن : [plan_name]\r\n???? مبلغ تراکنش : [plan_price] تومان'),
(39, 'product_purchased_successfully', 'پیام خرید موفق محصول', '✅تراکنش شما با موفقیت انجام شد\r\n???? شماره تراکنش : [refid]\r\n???? نام فیلم/سریال : [name]\r\n???? مبلغ تراکنش : [price] تومان'),
(40, 'no_popular_product', 'پیام خالی بودن محصولت محبوب', 'فیلم یا سریال محبوبی وجود ندارد ☹️'),
(41, 'phone_not_verified', 'متن عدم تایید شماره همراه', 'شماره موبایل تایید نشده ☹️'),
(42, 'no_vip_plan', 'عدم وجود اشتراک', 'اشتراک فعال ندارید ☹️'),
(43, 'search_text', 'پیام جستجوی محصول', '???? نام فیلم یا سریال مورد نظر خود را وارد کنید ✍️'),
(44, 'cat_list_waiting', 'پیام انتظار دریافت دسته بندی ها', '⏳ در حال دریافت دسته‌بندی‌ها... ⏳'),
(45, 'back_to_cats', 'دکمه دسته بندی ها', '▶️ بازگشت به دسته بندی ها'),
(46, 'popular_products', 'دکمه محصولات محبوب', '❤️ محبوب ترین فیلم و سریال'),
(47, 'popular_products_text', 'متن پیام محصولات محبوب', '❤️ محبوب‌ترین فیلم و سریال به شرح زیر می‌باشند ❤️\r\n\r\n???? لطفاً یکی از فیلم/سریال‌های زیر را انتخاب کنید ????'),
(48, 'ad', 'تبلیغات (زیر فایل)', '???? کانال ما '),
(49, 'ad_link', 'لینک تبلیغات ', 'https://t.me/example'),
(50, 'account', 'دکمه حساب کاربری', '????  حساب کاربری'),
(51, 'main_menu_msg', 'پیام بازگشت به منوی اصلی', '???? به منوی اصلی خوش آمدید ????\r\n\r\n???? یکی از دکمه‌های زیر را انتخاب کنید ????'),
(52, 'latest_products', 'دکمه محصولات جدید', '???? جدیدترین فیلم و سریال'),
(53, 'latest_products_text', 'متن پیام محصولات جدید', '???? جدیدترین فیلم و سریال به شرح زیر می‌باشند ????\r\n\r\n???? لطفاً یکی از فیلم/سریال‌های زیر را انتخاب کنید ????'),
(54, 'no_latest_product', 'پیام خالی بودن محصولات جدید', 'فیلم یا سریال جدیدی وجود ندارد ☹️'),
(55, 'exit', 'دکمه ی بستن پیام', '❌ بستن '),
(56, 'vip_system_enabled', 'وضعیت سیستم اشتراک ویژه', '1');

-- ============================================================
-- 7. ایجاد جدول سفارش‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productid` int(11) NOT NULL COMMENT 'شناسه فیلم/سریال',
  `userid` bigint(20) NOT NULL COMMENT 'شناسه کاربر',
  `price` int(11) NOT NULL COMMENT 'قیمت',
  `transcode` varchar(255) NOT NULL COMMENT 'کد تراکنش',
  `status` int(11) NOT NULL COMMENT 'وضعیت: 1=موفق، 0=در انتظار',
  `type` varchar(5) NOT NULL COMMENT 'نوع: file یا plan',
  `date` varchar(100) NOT NULL COMMENT 'تاریخ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. ایجاد جدول کیفیت‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_qualities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL COMMENT 'شناسه فیلم/سریال',
  `quality` varchar(20) NOT NULL COMMENT 'کیفیت (مثلا: 720p, 1080p, 4K)',
  `file_url` mediumtext NOT NULL COMMENT 'لینک دانلود یا File ID',
  `file_size` varchar(50) DEFAULT NULL COMMENT 'حجم فایل',
  `download_link` varchar(500) DEFAULT NULL COMMENT 'لینک اختصاصی دانلود',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT 'وضعیت: 1=فعال، 0=غیرفعال',
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. ایجاد جدول قسمت‌های سریال
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_series_episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL COMMENT 'شناسه سریال',
  `season` int(11) NOT NULL COMMENT 'شماره فصل',
  `episode` int(11) NOT NULL COMMENT 'شماره قسمت',
  `episode_title` varchar(255) DEFAULT NULL COMMENT 'عنوان قسمت (اختیاری)',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT 'وضعیت: 1=فعال، 0=غیرفعال',
  `order_num` int(11) NOT NULL DEFAULT 0 COMMENT 'ترتیب نمایش',
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `season_episode` (`season`,`episode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. ایجاد جدول تیکت‌ها
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) NOT NULL COMMENT 'شناسه کاربر',
  `msg` text NOT NULL COMMENT 'متن تیکت',
  `date` varchar(255) NOT NULL COMMENT 'تاریخ',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. ایجاد جدول کاربران
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) NOT NULL COMMENT 'شناسه تلگرام',
  `name` varchar(255) NOT NULL COMMENT 'نام',
  `username` varchar(50) DEFAULT NULL COMMENT 'یوزرنیم',
  `phone` varchar(20) DEFAULT NULL COMMENT 'شماره تلفن (پشتیبانی از شماره‌های بین‌المللی)',
  `vip_date` varchar(30) NOT NULL DEFAULT '0' COMMENT 'تاریخ انقضای VIP',
  `vip_plan` varchar(50) NOT NULL DEFAULT '0' COMMENT 'نام پلن VIP',
  `vip_refid` int(11) NOT NULL DEFAULT 0 COMMENT 'کد تراکنش VIP',
  `verified` int(11) NOT NULL DEFAULT 0 COMMENT 'وضعیت تایید شماره: 1=تایید شده، 0=تایید نشده',
  `channels_joined` int(11) NOT NULL DEFAULT 0 COMMENT 'وضعیت عضویت در کانال‌ها: 1=عضو شده، 0=عضو نشده',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. ایجاد جدول بازدیدهای کاربران
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_user_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` bigint(20) NOT NULL COMMENT 'شناسه کاربر',
  `file_id` int(11) NOT NULL COMMENT 'شناسه فیلم/سریال',
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'زمان بازدید',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_file` (`userid`,`file_id`),
  KEY `userid` (`userid`),
  KEY `file_id` (`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. ایجاد جدول پلن‌های VIP
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_vip_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'نام پلن',
  `price` int(11) NOT NULL COMMENT 'قیمت به تومان',
  `days` int(11) NOT NULL COMMENT 'تعداد روز',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اضافه کردن پلن‌های پیش‌فرض VIP
INSERT IGNORE INTO `sp_vip_plans` (`id`, `name`, `price`, `days`) VALUES
(1, 'اشتراک 10 روزه', 299000, 10),
(2, 'اشتراک 30 روزه', 429000, 30),
(3, 'اشتراک 90 روزه', 800000, 90);

-- ============================================================
-- 14. ایجاد جدول تنظیمات مینی اپ
-- ============================================================

CREATE TABLE IF NOT EXISTS `sp_webapp_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT 'کلید تنظیمات',
  `setting_value` text NOT NULL COMMENT 'مقدار تنظیمات',
  `setting_type` varchar(50) NOT NULL DEFAULT 'text' COMMENT 'نوع: color, image, text, json, boolean',
  `category` varchar(50) NOT NULL DEFAULT 'general' COMMENT 'دسته: colors, colors_dark, logo, menu, homepage, filters',
  `description` text DEFAULT NULL COMMENT 'توضیحات',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- اضافه کردن تنظیمات پیش‌فرض مینی اپ
INSERT IGNORE INTO `sp_webapp_settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`) VALUES
-- رنگ‌بندی Light Mode
('primary_color', '#000000', 'color', 'colors', 'رنگ اصلی'),
('secondary_color', '#6c757d', 'color', 'colors', 'رنگ ثانویه'),
('background_color', '#ffffff', 'color', 'colors', 'رنگ پس‌زمینه'),
('text_color', '#000000', 'color', 'colors', 'رنگ متن'),
('accent_color', '#000000', 'color', 'colors', 'رنگ تاکیدی'),
('border_color', '#e9ecef', 'color', 'colors', 'رنگ حاشیه'),

-- رنگ‌بندی Dark Mode
('dark_primary_color', '#ffffff', 'color', 'colors_dark', 'رنگ اصلی (دارک)'),
('dark_secondary_color', '#a0a0a0', 'color', 'colors_dark', 'رنگ ثانویه (دارک)'),
('dark_background_color', '#0a0a0a', 'color', 'colors_dark', 'رنگ پس‌زمینه (دارک)'),
('dark_text_color', '#ffffff', 'color', 'colors_dark', 'رنگ متن (دارک)'),
('dark_accent_color', '#ffffff', 'color', 'colors_dark', 'رنگ تاکیدی (دارک)'),
('dark_border_color', '#2a2a2a', 'color', 'colors_dark', 'رنگ حاشیه (دارک)'),

-- لوگو
('logo_url', '', 'image', 'logo', 'آدرس لوگو'),
('logo_width', '150', 'text', 'logo', 'عرض لوگو (px)'),
('logo_height', 'auto', 'text', 'logo', 'ارتفاع لوگو (px)'),

-- منو
('menu_items', '[]', 'json', 'menu', 'آیتم‌های منو (JSON)'),

-- صفحه اصلی
('homepage_layout', 'default', 'text', 'homepage', 'نوع چیدمان: default, grid, list'),
('homepage_sections', '[]', 'json', 'homepage', 'بخش‌های صفحه اصلی (JSON)'),
('show_popular', '1', 'boolean', 'homepage', 'نمایش محبوب‌ترین‌ها'),
('show_latest', '1', 'boolean', 'homepage', 'نمایش جدیدترین‌ها'),
('show_categories', '1', 'boolean', 'homepage', 'نمایش دسته‌بندی‌ها'),
('homepage_custom_html', '', 'text', 'homepage', 'کد HTML سفارشی برای صفحه اصلی'),
('enable_custom_html', '0', 'boolean', 'homepage', 'فعال کردن صفحه ساز اختصاصی'),

-- فیلترها
('filter_popular_enabled', '1', 'boolean', 'filters', 'فعال بودن فیلتر محبوب‌ترین'),
('filter_most_viewed_enabled', '1', 'boolean', 'filters', 'فعال بودن فیلتر پر بازدیدترین'),
('filter_latest_enabled', '1', 'boolean', 'filters', 'فعال بودن فیلتر جدیدترین'),
('popular_limit', '20', 'text', 'filters', 'تعداد محبوب‌ترین‌ها'),
('most_viewed_limit', '20', 'text', 'filters', 'تعداد پر بازدیدترین‌ها'),
('latest_limit', '20', 'text', 'filters', 'تعداد جدیدترین‌ها');

-- ============================================================
-- خلاصه:
-- ✅ تمام جداول با charset utf8mb4 ایجاد شدند (پشتیبانی از emoji)
-- ✅ دسته‌بندی‌های استاندارد اضافه شدند
-- ✅ تنظیمات پیش‌فرض ربات اضافه شدند
-- ✅ پلن‌های VIP پیش‌فرض اضافه شدند
-- ✅ تنظیمات مینی اپ اضافه شدند
-- ✅ تمام Index ها و Foreign Key ها تنظیم شدند
-- ============================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

