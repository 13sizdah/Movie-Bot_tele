<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($file_id <= 0) {
    redirect('index.php');
}

// دریافت اطلاعات فیلم/سریال
$stmt = $pdo->prepare("SELECT * FROM sp_files WHERE id = :id AND status = 1 LIMIT 1");
$stmt->bindValue(':id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    redirect('index.php');
}

// ثبت بازدید
$user_id = get_user_id();
$view_stmt = $pdo->prepare("INSERT IGNORE INTO sp_user_views (userid, file_id) VALUES (:userid, :file_id)");
$view_stmt->bindValue(':userid', $user_id, PDO::PARAM_INT);
$view_stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
$view_stmt->execute();

// افزایش تعداد بازدید
$update_views_stmt = $pdo->prepare("UPDATE sp_files SET views = views + 1 WHERE id = :id");
$update_views_stmt->bindValue(':id', $file_id, PDO::PARAM_INT);
$update_views_stmt->execute();

// دریافت کیفیت‌ها (برای فیلم)
$qualities_stmt = $pdo->prepare("SELECT * FROM sp_qualities WHERE file_id = :file_id AND status = 1 ORDER BY quality ASC");
$qualities_stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
$qualities_stmt->execute();
$qualities = $qualities_stmt->fetchAll(PDO::FETCH_ASSOC);

// دریافت فصل‌ها و قسمت‌ها (برای سریال، انیمیشن و انیمه)
    $seasons = [];
    if ($file['media_type'] == 'series' || $file['media_type'] == 'animation' || $file['media_type'] == 'anime') {
    $seasons_stmt = $pdo->prepare("SELECT DISTINCT season FROM sp_series_episodes WHERE file_id = :file_id AND status = 1 ORDER BY season ASC");
    $seasons_stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
    $seasons_stmt->execute();
    $seasons = $seasons_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = htmlspecialchars($file['name']);
include 'includes/header.php';

$poster_url = '';
if (!empty($file['poster'])) {
    $poster_url = get_poster_url($file['poster']);
}
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <!-- دکمه بازگشت -->
    <div style="margin-bottom: 16px;">
        <a href="javascript:history.back()" class="btn" style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-arrow-right"></i> بازگشت
        </a>
    </div>
    
    <!-- اطلاعات اصلی فیلم/سریال - سبک IMDb -->
    <div class="card" style="margin-bottom: 32px;">
        <div style="display: flex; flex-direction: column; gap: 32px;" class="movie-detail-grid">
            <!-- پوستر -->
            <div style="width: 100%; max-width: 300px; margin: 0 auto;">
                <?php if (!empty($poster_url)): ?>
                    <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" style="width: 100%; border-radius: 8px; box-shadow: var(--shadow); display: block; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges; image-rendering: high-quality; object-fit: contain;" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="width: 100%; aspect-ratio: 2/3; background: var(--bg-secondary); border-radius: 8px; display: none; align-items: center; justify-content: center;">
                        <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                <?php else: ?>
                    <div style="width: 100%; aspect-ratio: 2/3; background: var(--bg-secondary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- اطلاعات -->
            <div class="movie-detail-info" style="display: flex; flex-direction: column; justify-content: flex-start; direction: rtl; text-align: right; width: 100%;">
                <h1 style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 16px; direction: rtl; text-align: right;">
                    <?= htmlspecialchars($file['name']) ?>
                    <?php if (!empty($file['name_en'])): ?>
                        <span style="font-size: 0.7em; color: var(--text-secondary); font-weight: normal; display: block; margin-top: 8px;"><?= htmlspecialchars($file['name_en']) ?></span>
                    <?php endif; ?>
                </h1>
                
                <div class="movie-detail-badges" style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; direction: rtl; justify-content: flex-end;">
                    <?php if (!empty($file['year'])): ?>
                        <span class="quality-badge"><i class="fas fa-calendar"></i> <?= fa_num($file['year']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($file['imdb'])): ?>
                        <span class="quality-badge" style="background: #fbbf24; color: #000;"><i class="fas fa-star"></i> IMDb: <?= htmlspecialchars($file['imdb']) ?>/10</span>
                    <?php endif; ?>
                    <?php if ($file['media_type'] == 'series'): ?>
                        <span class="quality-badge"><i class="fas fa-tv"></i> سریال</span>
                    <?php elseif ($file['media_type'] == 'animation'): ?>
                        <span class="quality-badge"><i class="fas fa-palette"></i> انیمیشن</span>
                    <?php elseif ($file['media_type'] == 'anime'): ?>
                        <span class="quality-badge"><i class="fas fa-paint-brush"></i> انیمه</span>
                    <?php else: ?>
                        <span class="quality-badge"><i class="fas fa-film"></i> فیلم</span>
                    <?php endif; ?>
                    <span class="quality-badge"><i class="fas fa-eye"></i> <span class="fa-num"><?= number_format($file['views']) ?></span></span>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; direction: rtl; text-align: right;">
                    <?php if (!empty($file['genre'])): ?>
                        <div class="movie-detail-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); direction: rtl; text-align: right;">
                            <i class="fas fa-tags"></i>
                            <span style="direction: rtl; text-align: right;"><strong>ژانر:</strong> <?= htmlspecialchars(translate_genre($file['genre'])) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($file['director'])): ?>
                        <div class="movie-detail-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); direction: rtl; text-align: right;">
                            <i class="fas fa-user-tie"></i>
                            <span style="direction: rtl; text-align: right;"><strong>کارگردان:</strong> <?= htmlspecialchars($file['director']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($file['cast'])): ?>
                        <div class="movie-detail-item" style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); direction: rtl; text-align: right;">
                            <i class="fas fa-users"></i>
                            <span style="direction: rtl; text-align: right;"><strong>بازیگران:</strong> <?= htmlspecialchars($file['cast']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="movie-detail-info" style="background: var(--bg-secondary); border-radius: 8px; padding: 16px; margin-top: 24px; direction: rtl; text-align: right;">
                    <h3 style="font-weight: 600; color: var(--text-primary); margin-bottom: 12px; direction: rtl; text-align: right;">توضیحات</h3>
                    <p style="color: var(--text-secondary); line-height: 1.8; direction: rtl; text-align: right;"><?= nl2br(htmlspecialchars($file['description'])) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- کیفیت‌ها یا فصل‌ها -->
    <?php 
    // دریافت user_id برای بررسی دسترسی دانلود
    $user_id = get_user_id();
    if ($file['media_type'] == 'movie' && !empty($qualities)): 
    ?>
        <section style="margin-bottom: 32px;">
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
                            <?php if (can_user_download($user_id)): ?>
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
    <?php elseif (($file['media_type'] == 'series' || $file['media_type'] == 'animation' || $file['media_type'] == 'anime') && !empty($seasons)): ?>
        <section style="margin-bottom: 32px;">
            <h2 class="section-title">📁 فصل‌ها</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                <?php foreach ($seasons as $season_row): ?>
                    <a href="season.php?file_id=<?= $file_id ?>&season=<?= $season_row['season'] ?>" class="category-card">
                        <i class="fas fa-tv" style="font-size: 32px; margin-bottom: 12px; color: var(--text-secondary);"></i>
                        <h3 style="font-weight: 600; color: var(--text-primary);">📁 فصل <span class="fa-num"><?= $season_row['season'] ?></span></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
// دیباگ: بررسی URL پوستر
console.log('Poster URL:', '<?= !empty($poster_url) ? htmlspecialchars($poster_url, ENT_QUOTES) : "خالی" ?>');
console.log('Poster from DB:', '<?= !empty($file['poster']) ? htmlspecialchars($file['poster'], ENT_QUOTES) : "خالی" ?>');
console.log('BASEURI:', '<?= BASEURI ?>');
</script>

<?php include 'includes/footer.php'; ?>
