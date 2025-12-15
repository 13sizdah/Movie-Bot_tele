<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$episode_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($episode_id <= 0) {
    redirect('index.php');
}

// دریافت اطلاعات قسمت
$episode_stmt = $pdo->prepare("SELECT se.*, f.name as series_name, f.id as file_id
                                FROM sp_series_episodes se 
                                INNER JOIN sp_files f ON se.file_id = f.id 
                                WHERE se.id = :episode_id AND se.status = 1 AND f.status = 1 LIMIT 1");
$episode_stmt->bindValue(':episode_id', $episode_id, PDO::PARAM_INT);
$episode_stmt->execute();
$episode = $episode_stmt->fetch(PDO::FETCH_ASSOC);

if (!$episode) {
    redirect('index.php');
}

// دریافت کیفیت‌های این قسمت
$qualities_stmt = $pdo->prepare("SELECT * FROM sp_episode_qualities WHERE episode_id = :episode_id AND status = 1 ORDER BY order_num ASC, quality ASC");
$qualities_stmt->bindValue(':episode_id', $episode_id, PDO::PARAM_INT);
$qualities_stmt->execute();
$qualities = $qualities_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = htmlspecialchars($episode['series_name']) . ' - 📁 فصل ' . $episode['season'] . ' 🔗 قسمت ' . $episode['episode'];
include 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 32px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h1 class="section-title" style="margin: 0 0 8px 0;">
                    <?= htmlspecialchars($episode['series_name']) ?>
                </h1>
                <h2 style="font-size: 20px; font-weight: 500; color: var(--text-secondary);">
                    📁 فصل <span class="fa-num"><?= $episode['season'] ?></span> - 🔗 قسمت <span class="fa-num"><?= $episode['episode'] ?></span>
                    <?php if (!empty($episode['episode_title'])): ?>
                        - <?= htmlspecialchars($episode['episode_title']) ?>
                    <?php endif; ?>
                </h2>
            </div>
            <a href="season.php?file_id=<?= $episode['file_id'] ?>&season=<?= $episode['season'] ?>" class="btn">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>
        </div>
    </div>
    
    <?php if (!empty($qualities)): ?>
        <section>
            <h2 class="section-title">کیفیت‌های موجود</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px;">
                <?php foreach ($qualities as $quality): ?>
                    <div class="card">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                            <span style="font-weight: 600; color: var(--text-primary); font-size: 18px;"><?= htmlspecialchars($quality['quality']) ?></span>
                            <?php if (!empty($quality['file_size'])): ?>
                                <span style="font-size: 14px; color: var(--text-secondary);"><?= htmlspecialchars($quality['file_size']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($quality['download_link'])): ?>
                            <?php 
                            $user_id = get_user_id();
                            if (can_user_download($user_id)): 
                            ?>
                                <a href="<?= htmlspecialchars($quality['download_link']) ?>" target="_blank" class="btn btn-primary" style="width: 100%; text-align: center;">
                                    <i class="fas fa-download"></i> دانلود
                                </a>
                            <?php else: ?>
                                <button disabled class="btn" style="width: 100%; opacity: 0.5; cursor: not-allowed; background: #fbbf24; color: #000;">
                                    <i class="fas fa-crown"></i> نیاز به اشتراک VIP
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <button disabled class="btn" style="width: 100%; opacity: 0.5; cursor: not-allowed;">
                                لینک تنظیم نشده
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 64px 32px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 64px; color: var(--text-secondary); margin-bottom: 16px;"></i>
            <p style="font-size: 18px; color: var(--text-secondary);">کیفیتی برای این قسمت تعریف نشده است.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
