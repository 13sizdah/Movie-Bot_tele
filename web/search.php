<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$genre = isset($_GET['genre']) ? sanitize($_GET['genre']) : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$media_type = isset($_GET['media_type']) ? sanitize($_GET['media_type']) : '';

$page_title = 'جستجو';
include 'includes/header.php';

// دریافت ژانرهای موجود
$genres_stmt = $pdo->query("SELECT DISTINCT genre FROM sp_files WHERE genre IS NOT NULL AND genre != '' AND status = 1 ORDER BY genre ASC");
$genres = $genres_stmt->fetchAll(PDO::FETCH_ASSOC);

// دریافت سال‌های موجود
$years_stmt = $pdo->query("SELECT DISTINCT year FROM sp_files WHERE year IS NOT NULL AND year > 0 AND status = 1 ORDER BY year DESC");
$years = $years_stmt->fetchAll(PDO::FETCH_ASSOC);

// جستجو
$results = [];
if (!empty($query) || !empty($genre) || $year > 0 || !empty($media_type)) {
    $sql = "SELECT * FROM sp_files WHERE status = 1";
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (name LIKE :query OR name_en LIKE :query OR description LIKE :query)";
        $params[':query'] = '%' . $query . '%';
    }
    
    if (!empty($genre)) {
        $sql .= " AND genre LIKE :genre";
        $params[':genre'] = '%' . $genre . '%';
    }
    
    if ($year > 0) {
        $sql .= " AND year = :year";
        $params[':year'] = $year;
    }
    
    if (!empty($media_type)) {
        $sql .= " AND media_type = :media_type";
        $params[':media_type'] = $media_type;
    }
    
    $sql .= " ORDER BY id DESC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div class="card" style="margin-bottom: 32px;">
        <h1 class="section-title" style="margin-bottom: 24px;">
            <i class="fas fa-search"></i> جستجوی پیشرفته
        </h1>
        
        <form method="GET" action="" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">نام فیلم/سریال</label>
                <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="جستجو کنید..." class="search-bar">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">ژانر</label>
                <select name="genre" class="search-bar">
                    <option value="">همه ژانرها</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= htmlspecialchars($g['genre']) ?>" <?= $genre == $g['genre'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(translate_genre($g['genre'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">سال تولید</label>
                <select name="year" class="search-bar">
                    <option value="0">همه سال‌ها</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y['year'] ?>" <?= $year == $y['year'] ? 'selected' : '' ?>>
                            <span class="fa-num"><?= $y['year'] ?></span>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">نوع محتوا</label>
                <select name="media_type" class="search-bar">
                    <option value="">همه</option>
                    <option value="movie" <?= $media_type == 'movie' ? 'selected' : '' ?>>فیلم</option>
                    <option value="series" <?= $media_type == 'series' ? 'selected' : '' ?>>سریال</option>
                    <option value="animation" <?= $media_type == 'animation' ? 'selected' : '' ?>>انیمیشن</option>
                    <option value="anime" <?= $media_type == 'anime' ? 'selected' : '' ?>>انیمه</option>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-search"></i> جستجو
                </button>
            </div>
        </form>
    </div>
    
    <?php if (!empty($results)): ?>
        <section>
            <h2 class="section-title">نتایج جستجو (<span class="fa-num"><?= count($results) ?></span>)</h2>
            <div class="movies-grid">
                <?php foreach ($results as $file): ?>
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
                            <h3 class="movie-title"><?= htmlspecialchars($file['name']) ?><?= !empty($file['name_en']) ? ' <span style="font-size: 0.85em; color: var(--text-secondary); font-weight: normal;">(' . htmlspecialchars($file['name_en']) . ')</span>' : '' ?></h3>
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
        </section>
    <?php elseif (!empty($query) || !empty($genre) || $year > 0 || !empty($media_type)): ?>
        <div class="card" style="text-align: center; padding: 64px 32px;">
            <i class="fas fa-search" style="font-size: 64px; color: var(--text-secondary); margin-bottom: 16px;"></i>
            <p style="font-size: 18px; color: var(--text-secondary);">نتیجه‌ای یافت نشد.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
