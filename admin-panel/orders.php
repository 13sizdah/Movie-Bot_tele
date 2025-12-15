<?php
$title = 'مدیریت کانال‌های اجباری';
include 'src/head.php'; ?>

<body dir="rtl" class="bg-gray-100 dark:bg-gray-800 rounded-2xl h-screen overflow-hidden relative font-body">
    <div class="flex items-start justify-between">
        <?php include 'src/nav.php'; ?>

        <div class="flex flex-col w-full pl-0 md:p-4 md:space-y-4">
            <?php include 'src/header.php'; ?>
            
            <?php
            // INDEX قبلاً در head.php تعریف شده است
            // db.php و func.php هم قبلاً در head.php include شده‌اند
            
            // دریافت لیست کانال‌ها از دیتابیس
            function list_channels()
            {
                global $db, $channels;
                $sql = "SELECT * FROM sp_channels ORDER BY order_num ASC, id DESC";
                $channels = $db->query($sql)->fetchAll();
            }
            
            // حذف کانال
            if (isset($_GET['delete_channel'])) {
                $channel_id = intval($_GET['delete_channel']);
                $sql = "DELETE FROM sp_channels WHERE id=$channel_id";
                if ($db->query($sql)) {
                    echo "<script>window.location='orders.php?channel_deleted';</script>";
                    exit;
                }
            }
            
            // افزودن کانال
            if (isset($_POST['add_channel']) && !empty($_POST['channel_username'])) {
                $channel_username = trim($_POST['channel_username']);
                $channel_id = isset($_POST['channel_id']) ? trim($_POST['channel_id']) : '';
                $channel_title = isset($_POST['channel_title']) ? trim($_POST['channel_title']) : '';
                $channel_link = isset($_POST['channel_link']) ? trim($_POST['channel_link']) : '';
                $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
                
                $channel_username = ltrim($channel_username, '@');
                
                if (empty($channel_link) && !empty($channel_username)) {
                    $channel_link = 'https://t.me/' . $channel_username;
                }
                
                $sql = "INSERT INTO sp_channels (channel_username, channel_id, channel_title, channel_link, status, order_num) 
                        VALUES (:username, :channel_id, :title, :link, 1, :order_num)";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':username', $channel_username);
                $stmt->bindParam(':channel_id', $channel_id);
                $stmt->bindParam(':title', $channel_title);
                $stmt->bindParam(':link', $channel_link);
                $stmt->bindParam(':order_num', $order_num);
                
                if ($stmt->execute()) {
                    echo "<script>window.location='orders.php?channel_added';</script>";
                    exit;
                }
            }
            
            // ویرایش کانال
            if (isset($_POST['edit_channel'])) {
                $channel_id = intval($_POST['id']);
                $channel_username = trim($_POST['channel_username']);
                $channel_id_db = isset($_POST['channel_id']) ? trim($_POST['channel_id']) : '';
                $channel_title = isset($_POST['channel_title']) ? trim($_POST['channel_title']) : '';
                $channel_link = isset($_POST['channel_link']) ? trim($_POST['channel_link']) : '';
                $status = intval($_POST['status']);
                $order_num = isset($_POST['order_num']) ? intval($_POST['order_num']) : 0;
                
                $channel_username = ltrim($channel_username, '@');
                
                if (empty($channel_link) && !empty($channel_username)) {
                    $channel_link = 'https://t.me/' . $channel_username;
                }
                
                $sql = "UPDATE sp_channels SET channel_username=:username, channel_id=:channel_id, channel_title=:title, 
                        channel_link=:link, status=:status, order_num=:order_num WHERE id=:id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':id', $channel_id);
                $stmt->bindParam(':username', $channel_username);
                $stmt->bindParam(':channel_id', $channel_id_db);
                $stmt->bindParam(':title', $channel_title);
                $stmt->bindParam(':link', $channel_link);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':order_num', $order_num);
                
                if ($stmt->execute()) {
                    echo "<script>window.location='orders.php?channel_edited';</script>";
                    exit;
                }
            }
            
            list_channels();
            ?>

            <?php if (isset($_GET['add_channel'])) { ?>
            <div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
                <div class="flex flex-col flex-wrap sm:flex-row">
                    <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
                        <div class="py-8">
                            <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                                <h2 class="text-2xl leading-tight">افزودن کانال جدید</h2>
                                <a href="orders.php">
                                    <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                        بازگشت
                                    </button>
                                </a>
                            </div>
                            <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                                <div class="px-4 py-8 sm:px-10">
                                    <form method="POST" action="">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-gray-700 mb-2">یوزرنیم کانال (بدون @)</label>
                                                <input type="text" name="channel_username" class="w-full rounded-lg border border-gray-300 py-2 px-4" required placeholder="مثلاً: mychannel">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">شناسه عددی کانال (اختیاری)</label>
                                                <input type="text" name="channel_id" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="-1001234567890">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">نام کانال (اختیاری)</label>
                                                <input type="text" name="channel_title" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="نام نمایشی کانال">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">لینک دعوت کانال (اختیاری)</label>
                                                <input type="text" name="channel_link" class="w-full rounded-lg border border-gray-300 py-2 px-4" placeholder="https://t.me/joinchat/...">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">ترتیب نمایش</label>
                                                <input type="number" name="order_num" value="0" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                            </div>
                                            <div>
                                                <button type="submit" name="add_channel" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                    افزودن کانال
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
            <?php } elseif (isset($_GET['edit_channel'])) { 
                $channel_id = intval($_GET['edit_channel']);
                $sql = "SELECT * FROM sp_channels WHERE id=$channel_id";
                $channel = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
                if (!$channel) {
                    echo "<script>alert('کانال یافت نشد'); window.location='orders.php';</script>";
                    exit;
                }
            ?>
            <div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
                <div class="flex flex-col flex-wrap sm:flex-row">
                    <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
                        <div class="py-8">
                            <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                                <h2 class="text-2xl leading-tight">ویرایش کانال</h2>
                                <a href="orders.php">
                                    <button class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                        بازگشت
                                    </button>
                                </a>
                            </div>
                            <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                                <div class="px-4 py-8 sm:px-10">
                                    <form method="POST" action="">
                                        <input type="hidden" name="id" value="<?= $channel['id'] ?>">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-gray-700 mb-2">یوزرنیم کانال (بدون @)</label>
                                                <input type="text" name="channel_username" value="<?= htmlspecialchars($channel['channel_username']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4" required>
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">شناسه عددی کانال (اختیاری)</label>
                                                <input type="text" name="channel_id" value="<?= htmlspecialchars($channel['channel_id']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">نام کانال (اختیاری)</label>
                                                <input type="text" name="channel_title" value="<?= htmlspecialchars($channel['channel_title']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">لینک دعوت کانال</label>
                                                <input type="text" name="channel_link" value="<?= htmlspecialchars($channel['channel_link']) ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">ترتیب نمایش</label>
                                                <input type="number" name="order_num" value="<?= $channel['order_num'] ?>" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                            </div>
                                            <div>
                                                <label class="block text-gray-700 mb-2">وضعیت</label>
                                                <select name="status" class="w-full rounded-lg border border-gray-300 py-2 px-4">
                                                    <option value="1" <?= $channel['status'] == 1 ? 'selected' : '' ?>>فعال</option>
                                                    <option value="0" <?= $channel['status'] == 0 ? 'selected' : '' ?>>غیرفعال</option>
                                                </select>
                                            </div>
                                            <div>
                                                <button type="submit" name="edit_channel" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                    ویرایش کانال
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
            <?php } else { ?>
            <div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
                <div class="flex flex-col flex-wrap sm:flex-row">
                    <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
                        <div class="py-8">
                            <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                                <h2 class="text-2xl leading-tight"><?= $title ?></h2>
                                <a href="?add_channel">
                                    <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        افزودن کانال
                                    </button>
                                </a>
                            </div>
                            <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                                <div class="px-4 py-8 sm:px-10">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full leading-normal">
                                            <thead>
                                                <tr>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">یوزرنیم</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">نام</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">لینک</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">ترتیب</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">وضعیت</th>
                                                    <th class="px-5 py-3 bg-gray-100 border-b border-gray-200 text-right text-sm uppercase font-normal">اقدامات</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($channels)) { ?>
                                                    <tr>
                                                        <td colspan="6" class="px-5 py-5 text-center text-gray-500">هیچ کانالی ثبت نشده است</td>
                                                    </tr>
                                                <?php } else { ?>
                                                    <?php foreach ($channels as $channel) { ?>
                                                        <tr>
                                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                                @<?= htmlspecialchars($channel['channel_username']) ?>
                                                            </td>
                                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                                <?= htmlspecialchars($channel['channel_title'] ?: $channel['channel_username']) ?>
                                                            </td>
                                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                                <a href="<?= htmlspecialchars($channel['channel_link']) ?>" target="_blank" class="text-blue-500 hover:underline">
                                                                    مشاهده
                                                                </a>
                                                            </td>
                                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                                <?= $channel['order_num'] ?>
                                                            </td>
                                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                                <?php if ($channel['status'] == 1) { ?>
                                                                    <span class="px-3 py-1 text-blue-900 bg-blue-200 rounded-full text-xs">فعال</span>
                                                                <?php } else { ?>
                                                                    <span class="px-3 py-1 text-red-900 bg-red-200 rounded-full text-xs">غیرفعال</span>
                                                                <?php } ?>
                                                            </td>
                                                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                                                <a href="?edit_channel=<?= $channel['id'] ?>" class="text-yellow-600 hover:text-yellow-800">ویرایش</a>
                                                                |
                                                                <a href="?delete_channel=<?= $channel['id'] ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('آیا مطمئن هستید؟')">حذف</a>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>

            <?php
            // نمایش پیام‌های موفقیت
            if (isset($_GET['channel_added'])) {
                echo '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
                    کانال با موفقیت افزوده شد
                    <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
                        <span>×</span>
                    </button>
                </div>';
            }

            if (isset($_GET['channel_edited'])) {
                echo '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
                    کانال با موفقیت ویرایش شد
                    <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
                        <span>×</span>
                    </button>
                </div>';
            }

            if (isset($_GET['channel_deleted'])) {
                echo '<div class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
                    کانال با موفقیت حذف شد
                    <button onclick="this.parentElement.remove()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
                        <span>×</span>
                    </button>
                </div>';
            }
            ?>
        </div>
    </div>
<?php include 'src/footer.php'; ?>
