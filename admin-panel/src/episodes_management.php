<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

// مدیریت فصل‌ها و قسمت‌های سریال
if (isset($_GET['manage_episodes'])) {
    $file_id = intval($_GET['manage_episodes']);
    
    // دریافت اطلاعات سریال
    $sql = "SELECT * FROM sp_files WHERE id=$file_id";
    $series = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    
    if (!$series) {
        echo "<script>alert('سریال یافت نشد'); window.location='products.php';</script>";
        exit;
    }
    
    // دریافت فصل‌ها و قسمت‌ها
    $episodes_sql = "SELECT * FROM sp_series_episodes WHERE file_id=$file_id ORDER BY season ASC, episode ASC, order_num ASC";
    $episodes = $db->query($episodes_sql)->fetchAll();
    
    // گروه‌بندی بر اساس فصل
    $episodes_by_season = [];
    foreach ($episodes as $episode) {
        $season = $episode['season'];
        if (!isset($episodes_by_season[$season])) {
            $episodes_by_season[$season] = [];
        }
        $episodes_by_season[$season][] = $episode;
    }
    
    // افزودن قسمت جدید
    if (isset($_POST['add_episode'])) {
        $season = intval($_POST['season']);
        $episode = intval($_POST['episode']);
        $episode_title = trim($_POST['episode_title']);
        $order_num = intval($_POST['order_num']);
        
        // بررسی تکراری نبودن فصل و قسمت
        $check_sql = "SELECT id FROM sp_series_episodes WHERE file_id=$file_id AND season=$season AND episode=$episode";
        $exists = $db->query($check_sql)->fetch();
        
        if ($exists) {
            echo "<script>alert('این فصل و قسمت قبلاً اضافه شده است'); window.location='?manage_episodes=$file_id';</script>";
            exit;
        }
        
        // افزودن قسمت (بدون کیفیت)
        $sql = "INSERT INTO sp_series_episodes (file_id, season, episode, episode_title, status, order_num) 
                VALUES (:file_id, :season, :episode, :title, 1, :order)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_id', $file_id);
        $stmt->bindParam(':season', $season);
        $stmt->bindParam(':episode', $episode);
        $stmt->bindParam(':title', $episode_title);
        $stmt->bindParam(':order', $order_num);
        
        if ($stmt->execute()) {
            $episode_id = $db->lastInsertId();
            
            // افزودن کیفیت‌ها
            $qualities_added = 0;
            
            // بررسی و پردازش آرایه qualities
            if (isset($_POST['qualities']) && is_array($_POST['qualities']) && count($_POST['qualities']) > 0) {
                $qualities_sql = "INSERT INTO sp_episode_qualities (episode_id, quality, download_link, file_size, status, order_num) 
                                  VALUES (:episode_id, :quality, :link, :size, 1, :order)";
                
                $order_index = 0;
                
                foreach ($_POST['qualities'] as $quality_data) {
                    if (is_array($quality_data)) {
                        $quality = isset($quality_data['quality']) ? trim($quality_data['quality']) : '';
                        $download_link = isset($quality_data['download_link']) ? trim($quality_data['download_link']) : '';
                        $file_size = isset($quality_data['file_size']) ? trim($quality_data['file_size']) : '';
                        
                        // بررسی اینکه آیا داده‌ها خالی نیستند
                        if (!empty($quality) && !empty($download_link)) {
                            try {
                                $qualities_stmt = $db->prepare($qualities_sql);
                                $qualities_stmt->bindValue(':episode_id', $episode_id, PDO::PARAM_INT);
                                $qualities_stmt->bindValue(':quality', $quality, PDO::PARAM_STR);
                                $qualities_stmt->bindValue(':link', $download_link, PDO::PARAM_STR);
                                $file_size_value = !empty($file_size) ? $file_size : null;
                                $qualities_stmt->bindValue(':size', $file_size_value, $file_size_value !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                                $qualities_stmt->bindValue(':order', $order_index, PDO::PARAM_INT);
                                
                                if ($qualities_stmt->execute()) {
                                    $qualities_added++;
                                } else {
                                    $error_info = $qualities_stmt->errorInfo();
                                    error_log("خطا در افزودن کیفیت: " . print_r($error_info, true));
                                }
                                $order_index++;
                            } catch (Exception $e) {
                                error_log("خطا در افزودن کیفیت (Exception): " . $e->getMessage());
                            }
                        }
                    }
                }
            }
            
            if ($qualities_added == 0) {
                echo "<script>alert('خطا: هیچ کیفیتی اضافه نشد. لطفاً مطمئن شوید که:\n1. حداقل یک کیفیت با نام وارد کرده‌اید\n2. لینک دانلود برای هر کیفیت وارد کرده‌اید\n3. فیلدها را خالی نگذاشته‌اید'); window.location='?manage_episodes=$file_id';</script>";
                exit;
            }
            
            echo "<script>window.location='?manage_episodes=$file_id&episode_added';</script>";
            exit;
        } else {
            echo "<script>alert('خطا در افزودن قسمت'); window.location='?manage_episodes=$file_id';</script>";
            exit;
        }
    }
    
    // ویرایش قسمت
    if (isset($_POST['edit_episode'])) {
        $episode_id = intval($_POST['episode_id']);
        $season = intval($_POST['season']);
        $episode = intval($_POST['episode']);
        $episode_title = trim($_POST['episode_title']);
        $status = intval($_POST['status']);
        $order_num = intval($_POST['order_num']);
        
        // بررسی تکراری نبودن (به جز خودش)
        $check_sql = "SELECT id FROM sp_series_episodes WHERE file_id=$file_id AND season=$season AND episode=$episode AND id!=$episode_id";
        $exists = $db->query($check_sql)->fetch();
        
        if ($exists) {
            echo "<script>alert('این فصل و قسمت قبلاً اضافه شده است'); window.location='?manage_episodes=$file_id';</script>";
            exit;
        }
        
        // به‌روزرسانی اطلاعات قسمت
        $sql = "UPDATE sp_series_episodes SET season=:season, episode=:episode, episode_title=:title, 
                status=:status, order_num=:order 
                WHERE id=:id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $episode_id, PDO::PARAM_INT);
        $stmt->bindParam(':season', $season, PDO::PARAM_INT);
        $stmt->bindParam(':episode', $episode, PDO::PARAM_INT);
        $stmt->bindParam(':title', $episode_title, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':order', $order_num, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // حذف کیفیت‌های قدیمی
            $delete_qualities = "DELETE FROM sp_episode_qualities WHERE episode_id=$episode_id";
            $db->query($delete_qualities);
            
            // افزودن کیفیت‌های جدید
            $qualities_added = 0;
            
            // پردازش آرایه qualities
            if (isset($_POST['qualities']) && is_array($_POST['qualities']) && count($_POST['qualities']) > 0) {
                $qualities_sql = "INSERT INTO sp_episode_qualities (episode_id, quality, download_link, file_size, status, order_num) 
                                  VALUES (:episode_id, :quality, :link, :size, 1, :order)";
                
                $order_index = 0;
                
                foreach ($_POST['qualities'] as $quality_data) {
                    // بررسی ساختار داده
                    if (is_array($quality_data)) {
                        $quality = isset($quality_data['quality']) ? trim($quality_data['quality']) : '';
                        $download_link = isset($quality_data['download_link']) ? trim($quality_data['download_link']) : '';
                        $file_size = isset($quality_data['file_size']) ? trim($quality_data['file_size']) : '';
                    } else {
                        continue; // اگر آرایه نیست، رد می‌شود
                    }
                    
                    if (!empty($quality) && !empty($download_link)) {
                        try {
                            $qualities_stmt = $db->prepare($qualities_sql);
                            $qualities_stmt->bindValue(':episode_id', $episode_id, PDO::PARAM_INT);
                            $qualities_stmt->bindValue(':quality', $quality, PDO::PARAM_STR);
                            $qualities_stmt->bindValue(':link', $download_link, PDO::PARAM_STR);
                            $file_size_value = !empty($file_size) ? $file_size : null;
                            $qualities_stmt->bindValue(':size', $file_size_value, $file_size_value !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $qualities_stmt->bindValue(':order', $order_index, PDO::PARAM_INT);
                            
                            if ($qualities_stmt->execute()) {
                                $qualities_added++;
                            } else {
                                $error_info = $qualities_stmt->errorInfo();
                                error_log("خطا در افزودن کیفیت: " . print_r($error_info, true));
                            }
                            $order_index++;
                        } catch (Exception $e) {
                            error_log("خطا در افزودن کیفیت (Exception): " . $e->getMessage());
                        }
                    }
                }
            }
            
            if ($qualities_added == 0) {
                echo "<script>alert('خطا: هیچ کیفیتی اضافه نشد. لطفاً مطمئن شوید که حداقل یک کیفیت با نام و لینک دانلود وارد کرده‌اید.'); window.location='?manage_episodes=$file_id';</script>";
                exit;
            }
            
            echo "<script>window.location='?manage_episodes=$file_id&episode_edited';</script>";
            exit;
        } else {
            echo "<script>alert('خطا در ویرایش قسمت'); window.location='?manage_episodes=$file_id';</script>";
            exit;
        }
    }
    
    // حذف قسمت
    if (isset($_GET['delete_episode'])) {
        $episode_id = intval($_GET['delete_episode']);
        // حذف کیفیت‌های قسمت
        $delete_qualities = "DELETE FROM sp_episode_qualities WHERE episode_id=$episode_id";
        $db->query($delete_qualities);
        // حذف قسمت
        $sql = "DELETE FROM sp_series_episodes WHERE id=$episode_id";
        if ($db->query($sql)) {
            echo "<script>window.location='?manage_episodes=$file_id&episode_deleted';</script>";
            exit;
        }
    }
    
    // نمایش فرم افزودن/ویرایش
    $editing_episode = null;
    $editing_qualities = [];
    if (isset($_GET['edit_episode'])) {
        $episode_id = intval($_GET['edit_episode']);
        $sql = "SELECT * FROM sp_series_episodes WHERE id=$episode_id";
        $editing_episode = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
        
        // دریافت کیفیت‌های قسمت
        if ($editing_episode) {
            $qualities_sql = "SELECT * FROM sp_episode_qualities WHERE episode_id=$episode_id ORDER BY order_num ASC";
            $editing_qualities = $db->query($qualities_sql)->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // نمایش پیام‌های موفقیت
    $success_msg = '';
    if (isset($_GET['episode_added'])) {
        $success_msg = '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
            قسمت با موفقیت افزوده شد
            <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
                <span>×</span>
            </button>
        </div>';
    }
    if (isset($_GET['episode_edited'])) {
        $success_msg = '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
            قسمت با موفقیت ویرایش شد
            <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
                <span>×</span>
            </button>
        </div>';
    }
    if (isset($_GET['episode_deleted'])) {
        $success_msg = '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
            قسمت با موفقیت حذف شد
            <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
                <span>×</span>
            </button>
        </div>';
    }
?>

<div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
    <div class="flex flex-col flex-wrap sm:flex-row">
        <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
            <div class="py-8">
                <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                    <h2 class="text-2xl leading-tight">
                        مدیریت فصل‌ها و قسمت‌ها: <?= htmlspecialchars($series['name']) ?>
                    </h2>
                    <a href="products.php">
                        <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            بازگشت
                        </button>
                    </a>
                </div>
                
                <?= $success_msg ?>
                
                <!-- فرم افزودن/ویرایش قسمت -->
                <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5 mb-5">
                    <div class="px-4 py-8 sm:px-10">
                        <h3 class="text-xl mb-4"><?= $editing_episode ? 'ویرایش قسمت' : 'افزودن قسمت جدید' ?></h3>
                        <form method="POST" action="" id="episode_form">
                            <input type="hidden" name="episode_id" value="<?= $editing_episode ? $editing_episode['id'] : '' ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div>
                                    <label class="block text-gray-700 mb-2">فصل *</label>
                                    <input type="number" name="season" min="1" value="<?= $editing_episode ? $editing_episode['season'] : '1' ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">قسمت *</label>
                                    <input type="number" name="episode" min="1" value="<?= $editing_episode ? $editing_episode['episode'] : '' ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 mb-2">عنوان قسمت (اختیاری)</label>
                                    <input type="text" name="episode_title" value="<?= $editing_episode ? htmlspecialchars($editing_episode['episode_title']) : '' ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: قسمت اول">
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2">ترتیب نمایش</label>
                                    <input type="number" name="order_num" value="<?= $editing_episode ? $editing_episode['order_num'] : '0' ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                </div>
                                <?php if ($editing_episode) { ?>
                                <div>
                                    <label class="block text-gray-700 mb-2">وضعیت</label>
                                    <select name="status" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                        <option value="1" <?= $editing_episode['status'] == 1 ? 'selected' : '' ?>>فعال</option>
                                        <option value="0" <?= $editing_episode['status'] == 0 ? 'selected' : '' ?>>غیرفعال</option>
                                    </select>
                                </div>
                                <?php } ?>
                            </div>
                            
                            <!-- بخش کیفیت‌ها -->
                            <div class="border-t border-gray-200 pt-6 mt-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-lg font-semibold">کیفیت‌های قسمت</h4>
                                    <button type="button" onclick="addQualityRow()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        + افزودن کیفیت
                                    </button>
                                </div>
                                <div id="qualities_container">
                                    <?php if (!empty($editing_qualities)) { ?>
                                        <?php foreach ($editing_qualities as $index => $q) { ?>
                                            <div class="quality-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 border border-gray-200 rounded-lg">
                                                <div>
                                                    <label class="block text-gray-700 mb-2 text-sm">کیفیت *</label>
                                                    <input type="text" name="qualities[<?= $index ?>][quality]" value="<?= htmlspecialchars($q['quality']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 720p" required>
                                                </div>
                                                <div>
                                                    <label class="block text-gray-700 mb-2 text-sm">لینک دانلود *</label>
                                                    <input type="url" name="qualities[<?= $index ?>][download_link]" value="<?= htmlspecialchars($q['download_link']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="https://example.com/episode.mp4" required>
                                                </div>
                                                <div>
                                                    <label class="block text-gray-700 mb-2 text-sm">حجم فایل (اختیاری)</label>
                                                    <input type="text" name="qualities[<?= $index ?>][file_size]" value="<?= htmlspecialchars($q['file_size']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 500MB">
                                                </div>
                                                <div class="flex items-end">
                                                    <button type="button" onclick="removeQualityRow(this)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full">
                                                        حذف
                                                    </button>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <div class="quality-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 border border-gray-200 rounded-lg">
                                            <div>
                                                <label class="block text-gray-700 mb-2 text-sm">کیفیت *</label>
                                                <input type="text" name="qualities[0][quality]" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 720p" required>
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2 text-sm">لینک دانلود *</label>
                                                <input type="url" name="qualities[0][download_link]" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="https://example.com/episode.mp4" required>
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2 text-sm">حجم فایل (اختیاری)</label>
                                                <input type="text" name="qualities[0][file_size]" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 500MB">
                                            </div>
                                            <div class="flex items-end">
                                                <button type="button" onclick="removeQualityRow(this)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full">
                                                    حذف
                                                </button>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="<?= $editing_episode ? 'edit_episode' : 'add_episode' ?>" class="bg-<?= $editing_episode ? 'blue' : 'green' ?>-500 hover:bg-<?= $editing_episode ? 'blue' : 'green' ?>-700 text-white font-bold py-2 px-4 rounded">
                                    <?= $editing_episode ? 'ویرایش' : 'افزودن' ?>
                                </button>
                                <?php if ($editing_episode) { ?>
                                <a href="?manage_episodes=<?= $file_id ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded inline-block">
                                    انصراف
                                </a>
                                <?php } ?>
                            </div>
                        </form>
                        
                        <script>
                        let qualityIndex = <?= !empty($editing_qualities) ? count($editing_qualities) : 1 ?>;
                        
                        function addQualityRow() {
                            const container = document.getElementById('qualities_container');
                            const newRow = document.createElement('div');
                            newRow.className = 'quality-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 border border-gray-200 rounded-lg';
                            newRow.innerHTML = `
                                <div>
                                    <label class="block text-gray-700 mb-2 text-sm">کیفیت *</label>
                                    <input type="text" name="qualities[${qualityIndex}][quality]" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 720p" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2 text-sm">لینک دانلود *</label>
                                    <input type="url" name="qualities[${qualityIndex}][download_link]" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="https://example.com/episode.mp4" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2 text-sm">حجم فایل (اختیاری)</label>
                                    <input type="text" name="qualities[${qualityIndex}][file_size]" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="مثلاً: 500MB">
                                </div>
                                <div class="flex items-end">
                                    <button type="button" onclick="removeQualityRow(this)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full">
                                        حذف
                                    </button>
                                </div>
                            `;
                            container.appendChild(newRow);
                            qualityIndex++;
                        }
                        
                        function removeQualityRow(button) {
                            const container = document.getElementById('qualities_container');
                            if (container.children.length > 1) {
                                button.closest('.quality-row').remove();
                                // بازسازی index ها
                                reindexQualityRows();
                            } else {
                                alert('حداقل یک کیفیت باید وجود داشته باشد');
                            }
                        }
                        
                        function reindexQualityRows() {
                            const container = document.getElementById('qualities_container');
                            const rows = container.querySelectorAll('.quality-row');
                            rows.forEach((row, index) => {
                                const qualityInput = row.querySelector('input[name*="[quality]"]');
                                const linkInput = row.querySelector('input[name*="[download_link]"]');
                                const sizeInput = row.querySelector('input[name*="[file_size]"]');
                                
                                if (qualityInput) qualityInput.name = `qualities[${index}][quality]`;
                                if (linkInput) linkInput.name = `qualities[${index}][download_link]`;
                                if (sizeInput) sizeInput.name = `qualities[${index}][file_size]`;
                            });
                            qualityIndex = rows.length;
                        }
                        
                        // بازسازی index ها در هنگام بارگذاری صفحه (برای ویرایش)
                        document.addEventListener('DOMContentLoaded', function() {
                            reindexQualityRows();
                        });
                        </script>
                    </div>
                </div>
                
                <!-- لیست فصل‌ها و قسمت‌ها -->
                <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden">
                    <div class="px-4 py-8 sm:px-10">
                        <h3 class="text-xl mb-4">فصل‌ها و قسمت‌های موجود</h3>
                        <?php if (empty($episodes_by_season)) { ?>
                            <p class="text-gray-500 text-center py-8">هنوز قسمتی اضافه نشده است</p>
                        <?php } else { ?>
                            <?php foreach ($episodes_by_season as $season => $season_episodes) { ?>
                                <div class="mb-6 border-b border-gray-200 pb-4">
                                    <h4 class="text-lg font-semibold mb-3">فصل <?= $season ?></h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full leading-normal">
                                            <thead>
                                                <tr>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">قسمت</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">عنوان</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">کیفیت</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">حجم</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">لینک</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">وضعیت</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">اقدامات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($season_episodes as $ep) { 
                                                    // شمارش کیفیت‌های این قسمت
                                                    $qualities_count_sql = "SELECT COUNT(*) as count FROM sp_episode_qualities WHERE episode_id={$ep['id']} AND status=1";
                                                    $qualities_count = $db->query($qualities_count_sql)->fetch(PDO::FETCH_ASSOC);
                                                ?>
                                                    <tr>
                                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                            <?= $ep['episode'] ?>
                                                        </td>
                                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                            <?= htmlspecialchars($ep['episode_title'] ?: '-') ?>
                                                        </td>
                                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                            <?= $qualities_count['count'] ?> کیفیت
                                                        </td>
                                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                            <?php if ($ep['status'] == 1) { ?>
                                                                <span class="px-3 py-1 text-blue-900 bg-blue-200 rounded-full text-xs">فعال</span>
                                                            <?php } else { ?>
                                                                <span class="px-3 py-1 text-red-900 bg-red-200 rounded-full text-xs">غیرفعال</span>
                                                            <?php } ?>
                                                        </td>
                                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                            <a href="?manage_episodes=<?= $file_id ?>&edit_episode=<?= $ep['id'] ?>" class="text-yellow-600 hover:text-yellow-800">ویرایش</a>
                                                            |
                                                            <a href="?manage_episodes=<?= $file_id ?>&delete_episode=<?= $ep['id'] ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    exit;
}
?>

