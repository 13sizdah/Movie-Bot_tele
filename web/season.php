<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$file_id = isset($_GET['file_id']) ? intval($_GET['file_id']) : 0;
$season = isset($_GET['season']) ? intval($_GET['season']) : 0;

if ($file_id <= 0 || $season <= 0) {
    redirect('index.php');
}

// دریافت اطلاعات سریال
$series_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE id = :id AND status = 1 LIMIT 1");
$series_stmt->bindValue(':id', $file_id, PDO::PARAM_INT);
$series_stmt->execute();
$series = $series_stmt->fetch(PDO::FETCH_ASSOC);

if (!$series) {
    redirect('index.php');
}

// دریافت قسمت‌های این فصل
$episodes_stmt = $pdo->prepare("SELECT se.*, COUNT(eq.id) as qualities_count 
                                 FROM sp_series_episodes se 
                                 LEFT JOIN sp_episode_qualities eq ON se.id = eq.episode_id AND eq.status = 1
                                 WHERE se.file_id = :file_id AND se.season = :season AND se.status = 1 
                                 GROUP BY se.id
                                 ORDER BY se.episode ASC, se.order_num ASC");
$episodes_stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
$episodes_stmt->bindValue(':season', $season, PDO::PARAM_INT);
$episodes_stmt->execute();
$episodes = $episodes_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = htmlspecialchars($series['name']) . ' - 📁 فصل ' . $season;
include 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 32px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h1 class="section-title" style="margin: 0 0 8px 0;">
                    <?= htmlspecialchars($series['name']) ?>
                </h1>
                <h2 style="font-size: 20px; font-weight: 500; color: var(--text-secondary);">📁 فصل <span class="fa-num"><?= $season ?></span></h2>
            </div>
            <a href="movie.php?id=<?= $file_id ?>" class="btn">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>
        </div>
    </div>
    
    <?php if (!empty($episodes)): ?>
        <section>
            <h2 class="section-title">🔗 قسمت‌ها</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                <?php foreach ($episodes as $episode): ?>
                    <a href="episode.php?id=<?= $episode['id'] ?>" class="card" style="text-decoration: none; color: inherit;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                            <h3 style="font-weight: 600; color: var(--text-primary); font-size: 18px;">🔗 قسمت <span class="fa-num"><?= $episode['episode'] ?></span></h3>
                            <span class="quality-badge">
                                <i class="fas fa-layer-group"></i> <span class="fa-num"><?= $episode['qualities_count'] ?></span> کیفیت
                            </span>
                        </div>
                        <?php if (!empty($episode['episode_title'])): ?>
                            <p style="color: var(--text-secondary); font-size: 14px; line-height: 1.6;"><?= htmlspecialchars($episode['episode_title']) ?></p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 64px 32px;">
            <i class="fas fa-exclamation-triangle" style="font-size: 64px; color: var(--text-secondary); margin-bottom: 16px;"></i>
            <p style="font-size: 18px; color: var(--text-secondary);">قسمتی برای این فصل تعریف نشده است.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
