-- ============================================================
-- به‌روزرسانی نوع و وضعیت محصولات
-- ============================================================
-- این فایل تمام محصولات را به صورت پیش‌فرض فعال و رایگان تنظیم می‌کند
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- به‌روزرسانی تمام محصولات
-- ============================================================
-- تنظیم type به 'free' و status به 1 برای تمام محصولات
-- ============================================================

UPDATE `sp_files` 
SET `type` = 'free', 
    `status` = 1
WHERE `type` != 'free' OR `status` != 1;

-- ============================================================
-- خلاصه:
-- ✅ تمام محصولات به type='free' تنظیم شدند
-- ✅ تمام محصولات به status=1 (فعال) تنظیم شدند
-- ============================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

