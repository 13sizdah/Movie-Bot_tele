<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cat_id <= 0) {
    redirect('index.php');
}

// دریافت اطلاعات دسته‌بندی
$cat_stmt = $pdo->prepare("SELECT * FROM sp_cats WHERE id = :id LIMIT 1");
$cat_stmt->bindValue(':id', $cat_id, PDO::PARAM_INT);
$cat_stmt->execute();
$category = $cat_stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    redirect('index.php');
}

// دریافت فیلم/سریال‌های این دسته‌بندی
$files_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE catid = :cat_id AND status = 1 ORDER BY id DESC");
$files_stmt->bindValue(':cat_id', $cat_id, PDO::PARAM_INT);
$files_stmt->execute();
$files = $files_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = htmlspecialchars($category['name']);
include 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 32px;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <h1 class="section-title" style="margin: 0;">
                <i class="fas fa-folder"></i> <?= htmlspecialchars($category['name']) ?>
            </h1>
            <a href="index.php" class="btn">
                <i class="fas fa-arrow-right"></i> بازگشت
            </a>
        </div>
    </div>
    
    <?php if (!empty($files)): ?>
        <div class="movies-grid">
            <?php foreach ($files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php 
                        $poster_url = get_poster_url($file['poster']);
                        ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
                    <!-- Fallback برای عکس که لود نشود -->
                    <div class="movie-poster" style="display: none; align-items: center; justify-content: center; background: var(--bg-secondary);">
                        <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="movie-meta">
                            <span><i class="fas fa-eye"></i> <span class="fa-num"><?= number_format($file['views']) ?></span></span>
                            <?php if ($file['media_type'] == 'series'): ?>
                                <span class="quality-badge"><i class="fas fa-tv"></i> سریال</span>
                            <?php elseif ($file['media_type'] == 'animation'): ?>
                                <span class="quality-badge"><i class="fas fa-palette"></i> انیمیشن</span>
                            <?php elseif ($file['media_type'] == 'anime'): ?>
                                <span class="quality-badge"><i class="fas fa-paint-brush"></i> انیمه</span>
                            <?php else: ?>
                                <span class="quality-badge"><i class="fas fa-film"></i> فیلم</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 64px 32px;">
            <i class="fas fa-folder-open" style="font-size: 64px; color: var(--text-secondary); margin-bottom: 16px;"></i>
            <p style="font-size: 18px; color: var(--text-secondary);">فیلم یا سریالی در این دسته‌بندی وجود ندارد.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
