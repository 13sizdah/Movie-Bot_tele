<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

// ویرایش کیفیت - نمایش فرم
if (isset($_GET['edit_quality'])) {
    $quality_id = intval($_GET['edit_quality']);
    $sql = "SELECT * FROM sp_qualities WHERE id=$quality_id";
    $quality = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (!$quality) {
        echo "<script>alert('کیفیت یافت نشد'); window.location='products.php';</script>";
        exit;
    }
    $file_id = $quality['file_id'];
    
    // دریافت اطلاعات فیلم
    $file_sql = "SELECT * FROM sp_files WHERE id=$file_id";
    $file_info = $db->query($file_sql)->fetch(PDO::FETCH_ASSOC);
?>
<div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
    <div class="flex flex-col flex-wrap sm:flex-row">
        <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
            <div class="py-8">
                <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                    <h2 class="text-2xl leading-tight">ویرایش کیفیت: <?= htmlspecialchars($quality['quality']) ?></h2>
                    <a href="?manage_qualities=<?= $file_id ?>">
                        <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            بازگشت
                        </button>
                    </a>
                </div>
                <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                    <div class="px-4 py-8 sm:px-10">
                        <form method="POST" action="">
                            <input type="hidden" name="quality_id" value="<?= $quality_id ?>">
                            <input type="hidden" name="file_id" value="<?= $file_id ?>">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-gray-700 mb-2">کیفیت (مثلاً: 720p, 1080p, 4K)</label>
                                    <input type="text" name="quality" value="<?= htmlspecialchars($quality['quality']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">لینک دانلود مستقیم (URL)</label>
                                    <input type="url" name="download_link" value="<?= htmlspecialchars($quality['download_link']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" required placeholder="https://example.com/file.mp4">
                                    <p class="text-xs text-gray-500 mt-1">لینک مستقیم دانلود فایل را وارد کنید</p>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">حجم فایل (اختیاری)</label>
                                    <input type="text" name="file_size" value="<?= htmlspecialchars($quality['file_size']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 1.5 GB">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">وضعیت</label>
                                    <select name="status" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                        <option value="1" <?= $quality['status'] == 1 ? 'selected' : '' ?>>فعال</option>
                                        <option value="0" <?= $quality['status'] == 0 ? 'selected' : '' ?>>غیرفعال</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" name="edit_quality_submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        ویرایش کیفیت
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    exit;
}

// مدیریت کیفیت‌های یک فیلم
if (isset($_GET['manage_qualities'])) {
    $file_id = intval($_GET['manage_qualities']);
    
    // دریافت اطلاعات فیلم
    $sql = "SELECT * FROM sp_files WHERE id=$file_id";
    $file_info = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if (!$file_info) {
        echo "<script>alert('فیلم یافت نشد'); window.location='products.php';</script>";
        exit;
    }
    
    // دریافت کیفیت‌های موجود
    $qualities_sql = "SELECT * FROM sp_qualities WHERE file_id=$file_id ORDER BY quality ASC";
    $qualities = $db->query($qualities_sql)->fetchAll();
?>

<div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
    <div class="flex flex-col flex-wrap sm:flex-row">
        <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
            <div class="py-8">
                <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                    <h2 class="text-2xl leading-tight">
                        مدیریت کیفیت‌های: <?= htmlspecialchars($file_info['name']) ?>
                    </h2>
                    <a href="products.php">
                        <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            بازگشت
                        </button>
                    </a>
                </div>
                
                <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                    <div class="px-4 py-8 sm:px-10">
                        <h3 class="text-lg font-semibold mb-4">کیفیت‌های موجود:</h3>
                        
                        <?php if (empty($qualities)) { ?>
                            <p class="text-gray-500 mb-4">هیچ کیفیتی ثبت نشده است.</p>
                        <?php } else { ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full leading-normal">
                                    <thead>
                                        <tr>
                                            <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">کیفیت</th>
                                            <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">حجم</th>
                                            <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">لینک دانلود</th>
                                            <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">وضعیت</th>
                                            <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">اقدامات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($qualities as $q) { ?>
                                            <tr>
                                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                    <span class="font-semibold"><?= htmlspecialchars($q['quality']) ?></span>
                                                </td>
                                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                    <?= !empty($q['file_size']) ? htmlspecialchars($q['file_size']) : '-' ?>
                                                </td>
                                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                    <?php if (!empty($q['download_link'])) { ?>
                                                        <a href="<?= htmlspecialchars($q['download_link']) ?>" target="_blank" class="text-blue-500 hover:underline">
                                                            مشاهده لینک
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="text-gray-400">-</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                    <?php if ($q['status'] == 1) { ?>
                                                        <span class="px-3 py-1 text-blue-900 bg-blue-200 rounded-full text-xs">فعال</span>
                                                    } else { ?>
                                                        <span class="px-3 py-1 text-red-900 bg-red-200 rounded-full text-xs">غیرفعال</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                    <a href="?manage_qualities=<?= $file_id ?>&edit_quality=<?= $q['id'] ?>" class="text-yellow-600 hover:text-yellow-800">
                                                        ویرایش
                                                    </a>
                                                    |
                                                    <a href="?delete_quality=<?= $q['id'] ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('آیا مطمئن هستید؟')">
                                                        حذف
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                        
                        <div class="mt-6">
                            <h3 class="text-lg font-semibold mb-4">افزودن کیفیت جدید:</h3>
                            <form method="POST" action="">
                                <input type="hidden" name="file_id" value="<?= $file_id ?>">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-gray-700 mb-2">کیفیت (مثلاً: 720p, 1080p, 4K)</label>
                                        <input type="text" name="quality" class="w-full rounded-lg border border-gray-300 py-2 px-4" required>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">لینک دانلود مستقیم (URL)</label>
                                        <input type="url" name="download_link" class="w-full rounded-lg border border-gray-300 py-2 px-4" required placeholder="https://example.com/file.mp4">
                                        <p class="text-xs text-gray-500 mt-1">لینک مستقیم دانلود فایل را وارد کنید</p>
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 mb-2">حجم فایل (اختیاری)</label>
                                        <input type="text" name="file_size" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 1.5 GB">
                                    </div>
                                    <div>
                                        <button type="submit" name="add_quality" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                            افزودن کیفیت
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
}

// ویرایش کیفیت
if (isset($_POST['edit_quality_submit'])) {
    $quality_id = intval($_POST['quality_id']);
    $quality = trim($_POST['quality']);
    $download_link = trim($_POST['download_link']);
    $file_size = isset($_POST['file_size']) ? trim($_POST['file_size']) : '';
    $status = intval($_POST['status']);
    $file_id = intval($_POST['file_id']);
    
    if (empty($quality) || empty($download_link)) {
        echo "<script>alert('کیفیت و لینک دانلود الزامی است'); window.location='?manage_qualities=$file_id&edit_quality=$quality_id';</script>";
        exit;
    }
    
    // بررسی صحت URL
    if (!filter_var($download_link, FILTER_VALIDATE_URL)) {
        echo "<script>alert('لینک دانلود معتبر نیست'); window.location='?manage_qualities=$file_id&edit_quality=$quality_id';</script>";
        exit;
    }
    
    $sql = "UPDATE sp_qualities SET quality=:quality, download_link=:download_link, file_size=:file_size, status=:status WHERE id=:id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $quality_id);
    $stmt->bindParam(':quality', $quality);
    $stmt->bindParam(':download_link', $download_link);
    $stmt->bindParam(':file_size', $file_size);
    $stmt->bindParam(':status', $status);
    
    if ($stmt->execute()) {
        echo "<script>window.location='?manage_qualities=$file_id&quality_edited';</script>";
    } else {
        echo "<script>alert('خطا در ویرایش کیفیت'); window.location='?manage_qualities=$file_id';</script>";
    }
    exit;
}

// افزودن کیفیت جدید
if (isset($_POST['add_quality'])) {
    $file_id = intval($_POST['file_id']);
    $quality = trim($_POST['quality']);
    $download_link = trim($_POST['download_link']);
    $file_size = isset($_POST['file_size']) ? trim($_POST['file_size']) : '';
    
    if (empty($quality) || empty($download_link)) {
        echo "<script>alert('کیفیت و لینک دانلود الزامی است'); window.location='?manage_qualities=$file_id';</script>";
        exit;
    }
    
    // بررسی تکراری نبودن کیفیت
    $check_sql = "SELECT id FROM sp_qualities WHERE file_id=$file_id AND quality=:quality";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindParam(':quality', $quality);
    $check_stmt->execute();
    if ($check_stmt->fetch()) {
        echo "<script>alert('این کیفیت قبلاً ثبت شده است'); window.location='?manage_qualities=$file_id';</script>";
        exit;
    }
    
    // بررسی صحت URL
    if (!filter_var($download_link, FILTER_VALIDATE_URL)) {
        echo "<script>alert('لینک دانلود معتبر نیست'); window.location='?manage_qualities=$file_id';</script>";
        exit;
    }
    
    $sql = "INSERT INTO sp_qualities (file_id, quality, file_url, file_size, download_link, status) 
            VALUES (:file_id, :quality, '', :file_size, :download_link, 1)";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id);
    $stmt->bindParam(':quality', $quality);
    $stmt->bindParam(':file_size', $file_size);
    $stmt->bindParam(':download_link', $download_link);
    
    if ($stmt->execute()) {
        echo "<script>window.location='?manage_qualities=$file_id&quality_added';</script>";
    } else {
        echo "<script>alert('خطا در افزودن کیفیت'); window.location='?manage_qualities=$file_id';</script>";
    }
    exit;
}

// حذف کیفیت
if (isset($_GET['delete_quality'])) {
    $quality_id = intval($_GET['delete_quality']);
    
    $sql = "SELECT file_id FROM sp_qualities WHERE id=$quality_id";
    $quality_info = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if ($quality_info) {
        $delete_sql = "DELETE FROM sp_qualities WHERE id=$quality_id";
        if ($db->query($delete_sql)) {
            echo "<script>window.location='?manage_qualities=" . $quality_info['file_id'] . "&quality_deleted';</script>";
        } else {
            echo "<script>alert('خطا در حذف کیفیت');</script>";
        }
    }
    exit;
}

// نمایش پیام‌های موفقیت
if (isset($_GET['quality_added'])) {
    echo '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        کیفیت با موفقیت افزوده شد
        <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>';
}

if (isset($_GET['quality_deleted'])) {
    echo '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        کیفیت با موفقیت حذف شد
        <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>';
}

if (isset($_GET['quality_edited'])) {
    echo '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        کیفیت با موفقیت ویرایش شد
        <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>';
}
?>

