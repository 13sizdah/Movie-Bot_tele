-- ============================================================
-- ایجاد کاربر ادمین پیش‌فرض
-- ============================================================
-- این فایل یک کاربر ادمین پیش‌فرض در دیتابیس ایجاد می‌کند
-- ============================================================
-- راهنمای استفاده:
-- 1. این فایل را در phpMyAdmin یا هر ابزار مدیریت دیتابیس اجرا کنید
-- 2. یا از دستور mysql استفاده کنید: mysql -u username -p database_name < CREATE_ADMIN_USER.sql
-- 3. پس از اجرا، می‌توانید با username و password زیر وارد شوید:
--    Username: admin
--    Password: admin123
-- ============================================================
-- ⚠️ هشدار امنیتی:
-- بعد از اولین ورود، حتماً پسورد را تغییر دهید!
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- ایجاد کاربر ادمین پیش‌فرض
-- ============================================================
-- Username: admin
-- Password: admin123
-- Hash: $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy
-- ============================================================
-- ⚠️ توجه: این hash برای پسورد "admin123" تولید شده است
-- اگر می‌خواهید پسورد دیگری استفاده کنید، از فایل
-- admin-panel/generate_admin_hash.php استفاده کنید
-- ============================================================

-- حذف ادمین قبلی با username 'admin' (اگر وجود دارد)
DELETE FROM `sp_admins` WHERE `username` = 'admin';

-- ایجاد ادمین جدید
INSERT INTO `sp_admins` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy');

-- ============================================================
-- اطلاعات ورود:
-- ============================================================
-- Username: admin
-- Password: admin123
-- ============================================================
-- ⚠️ مهم: بعد از اولین ورود، حتماً پسورد را تغییر دهید!
-- ============================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

