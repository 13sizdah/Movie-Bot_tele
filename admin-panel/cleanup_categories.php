<?php
// ============================================================
// پاکسازی دسته‌بندی‌ها: نگه داشتن 3-4 محصول در هر دسته‌بندی
// ============================================================
// این فایل برای هر دسته‌بندی، فقط 4 محصول جدیدترین را نگه می‌دارد
// و catid بقیه محصولات را به 0 تنظیم می‌کند (حذف نمی‌کند)
// ============================================================

session_start();
// تعریف INDEX قبل از include کردن func.php
if (!defined('INDEX')) {
    define('INDEX', true);
}
require_once 'db.php';
require_once 'src/func.php';

// بررسی لاگین بودن کاربر
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';

if (isset($_POST['cleanup'])) {
    try {
        // شروع تراکنش
        $db->beginTransaction();
        
        // دریافت تمام دسته‌بندی‌ها
        $cats_sql = "SELECT id FROM sp_cats";
        $cats_stmt = $db->query($cats_sql);
        $categories = $cats_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_updated = 0;
        
        foreach ($categories as $category) {
            $cat_id = $category['id'];
            
            // شمارش محصولات این دسته‌بندی
            $count_sql = "SELECT COUNT(*) as count FROM sp_files WHERE catid = :catid AND status = 1";
            $count_stmt = $db->prepare($count_sql);
            $count_stmt->bindValue(':catid', $cat_id, PDO::PARAM_INT);
            $count_stmt->execute();
            $product_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // اگر بیشتر از 4 محصول وجود دارد
            if ($product_count > 4) {
                // دریافت 4 محصول جدیدترین
                $keep_sql = "SELECT id FROM sp_files 
                            WHERE catid = :catid AND status = 1 
                            ORDER BY id DESC 
                            LIMIT 4";
                $keep_stmt = $db->prepare($keep_sql);
                $keep_stmt->bindValue(':catid', $cat_id, PDO::PARAM_INT);
                $keep_stmt->execute();
                $keep_ids = $keep_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($keep_ids)) {
                    // تبدیل آرایه به رشته برای استفاده در IN
                    $placeholders = implode(',', array_fill(0, count($keep_ids), '?'));
                    
                    // تنظیم catid به 0 برای محصولات اضافی
                    $update_sql = "UPDATE sp_files 
                                  SET catid = 0 
                                  WHERE catid = ? 
                                  AND status = 1 
                                  AND id NOT IN ($placeholders)";
                    
                    $update_stmt = $db->prepare($update_sql);
                    $params = array_merge([$cat_id], $keep_ids);
                    $update_stmt->execute($params);
                    
                    $total_updated += $update_stmt->rowCount();
                }
            }
        }
        
        // تایید تراکنش
        $db->commit();
        
        $message = "✅ پاکسازی با موفقیت انجام شد. تعداد $total_updated محصول از دسته‌بندی‌ها خارج شدند.";
        $message_type = 'success';
        
    } catch (Exception $e) {
        // برگشت تراکنش در صورت خطا
        $db->rollBack();
        $message = "❌ خطا در پاکسازی: " . $e->getMessage();
        $message_type = 'error';
    }
}

// دریافت آمار دسته‌بندی‌ها
$stats_sql = "SELECT 
                c.id AS category_id,
                c.name AS category_name,
                COUNT(f.id) AS product_count
              FROM sp_cats c
              LEFT JOIN sp_files f ON f.catid = c.id AND f.status = 1
              GROUP BY c.id, c.name
              HAVING product_count > 0
              ORDER BY c.name";
$stats_stmt = $db->query($stats_sql);
$stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پاکسازی دسته‌بندی‌ها</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        .info-box ul {
            margin-right: 20px;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .btn {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-danger {
            background: #f44336;
        }
        .btn-danger:hover {
            background: #da190b;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #4CAF50;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .count-badge {
            display: inline-block;
            background: #2196F3;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
        }
        .count-badge.warning {
            background: #ff9800;
        }
        .count-badge.danger {
            background: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 پاکسازی دسته‌بندی‌ها</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>ℹ️ اطلاعات</h3>
            <ul>
                <li>این عملیات برای هر دسته‌بندی، فقط <strong>4 محصول جدیدترین</strong> را نگه می‌دارد</li>
                <li>محصولات اضافی <strong>حذف نمی‌شوند</strong>، فقط <code>catid</code> آنها به <code>0</code> تنظیم می‌شود</li>
                <li>می‌توانید بعداً محصولات را به دسته‌بندی دیگری اختصاص دهید</li>
                <li>قبل از اجرا، حتماً از دیتابیس خود بکاپ بگیرید</li>
            </ul>
        </div>
        
        <form method="POST" onsubmit="return confirm('⚠️ آیا مطمئن هستید که می‌خواهید پاکسازی را انجام دهید؟');">
            <button type="submit" name="cleanup" class="btn btn-danger">
                🧹 شروع پاکسازی
            </button>
        </form>
        
        <h2>📊 آمار دسته‌بندی‌ها</h2>
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام دسته‌بندی</th>
                    <th>تعداد محصولات</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stats)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            هیچ دسته‌بندی با محصول یافت نشد.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stats as $stat): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stat['category_id']); ?></td>
                            <td><?php echo htmlspecialchars($stat['category_name']); ?></td>
                            <td>
                                <span class="count-badge <?php 
                                    echo $stat['product_count'] > 4 ? 'danger' : ($stat['product_count'] > 3 ? 'warning' : ''); 
                                ?>">
                                    <?php echo $stat['product_count']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($stat['product_count'] > 4): ?>
                                    <span style="color: #f44336;">⚠️ نیاز به پاکسازی</span>
                                <?php elseif ($stat['product_count'] > 3): ?>
                                    <span style="color: #ff9800;">⚠️ نزدیک به حد</span>
                                <?php else: ?>
                                    <span style="color: #4CAF50;">✅ مناسب</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php" style="color: #4CAF50; text-decoration: none;">← بازگشت به پنل مدیریت</a>
        </div>
    </div>
</body>
</html>

