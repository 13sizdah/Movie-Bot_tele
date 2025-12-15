<?php
require_once 'config.php';

if (!is_logged_in()) {
    if (isset($_GET['tgWebAppStartParam']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'telegram') !== false)) {
        redirect('login.php');
    } else {
        redirect('login.php');
    }
}

$page_title = 'صفحه اصلی';
include 'includes/header.php';

// بررسی فعال بودن صفحه ساز اختصاصی
$enable_custom_html = false;
$custom_html = '';

// دریافت تنظیمات صفحه اصلی از دیتابیس
$show_popular = true;
$show_latest = true;
$show_categories = true;
$show_series = false;
$show_movies = false;
$show_korean = false;
$show_turkish = false;
$show_anime = false;
$show_animation = false;

// بررسی اتصال به دیتابیس قبل از فراخوانی توابع
if (isset($pdo)) {
    try {
        $enable_custom_html = get_webapp_setting('enable_custom_html', '0') == '1';
        $custom_html = get_webapp_setting('homepage_custom_html', '');
        $show_popular = is_filter_enabled('show_popular');
        $show_latest = is_filter_enabled('show_latest');
        $show_categories = is_filter_enabled('show_categories');
        $show_series = is_filter_enabled('show_series');
        $show_movies = is_filter_enabled('show_movies');
        $show_korean = is_filter_enabled('show_korean');
        $show_turkish = is_filter_enabled('show_turkish');
        $show_anime = is_filter_enabled('show_anime');
        $show_animation = is_filter_enabled('show_animation');
    } catch (Exception $e) {
        error_log("Error loading homepage settings: " . $e->getMessage());
    }
}

// دریافت محدودیت‌های فیلترها
$popular_limit = 20;
$latest_limit = 20;
$most_viewed_limit = 20;

// دریافت محبوب‌ترین فیلم/سریال‌ها (فقط اگر فعال باشد)
$popular_files = [];
$most_viewed_files = [];
$latest_files = [];
$categories = [];
$series_files = [];
$movies_files = [];
$korean_files = [];
$turkish_files = [];
$anime_files = [];
$animation_files = [];

// بررسی اتصال به دیتابیس قبل از اجرای کوئری‌ها
if (isset($pdo)) {
    try {
        $popular_limit = get_filter_limit('popular_limit', 20);
        $latest_limit = get_filter_limit('latest_limit', 20);
        $most_viewed_limit = get_filter_limit('most_viewed_limit', 20);

        // دریافت محبوب‌ترین فیلم/سریال‌ها (فقط اگر فعال باشد)
        if ($show_popular && is_filter_enabled('filter_popular_enabled')) {
            $popular_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 ORDER BY views DESC LIMIT :limit");
            $popular_stmt->bindValue(':limit', $popular_limit, PDO::PARAM_INT);
            $popular_stmt->execute();
            $popular_files = $popular_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت پر بازدیدترین‌ها (اگر فعال باشد)
        if (is_filter_enabled('filter_most_viewed_enabled')) {
            $most_viewed_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 ORDER BY views DESC LIMIT :limit");
            $most_viewed_stmt->bindValue(':limit', $most_viewed_limit, PDO::PARAM_INT);
            $most_viewed_stmt->execute();
            $most_viewed_files = $most_viewed_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت آخرین فیلم/سریال‌ها (فقط اگر فعال باشد)
        if ($show_latest && is_filter_enabled('filter_latest_enabled')) {
            $latest_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 ORDER BY id DESC LIMIT :limit");
            $latest_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $latest_stmt->execute();
            $latest_files = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت دسته‌بندی‌ها (فقط اگر فعال باشد)
        if ($show_categories) {
            $categories_stmt = $pdo->query("SELECT * FROM sp_cats ORDER BY name ASC");
            $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت سریال‌ها (فقط اگر فعال باشد)
        if ($show_series) {
            $series_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 AND media_type IN ('series', 'animation', 'anime') ORDER BY id DESC LIMIT :limit");
            $series_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $series_stmt->execute();
            $series_files = $series_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت فیلم‌ها (فقط اگر فعال باشد)
        if ($show_movies) {
            $movies_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 AND media_type='movie' ORDER BY id DESC LIMIT :limit");
            $movies_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $movies_stmt->execute();
            $movies_files = $movies_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت کره‌ای (فقط اگر فعال باشد)
        if ($show_korean) {
            $korean_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 AND (name LIKE '%کره%' OR name LIKE '%korean%' OR name LIKE '%Korean%' OR name_en LIKE '%korean%' OR name_en LIKE '%Korean%') ORDER BY id DESC LIMIT :limit");
            $korean_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $korean_stmt->execute();
            $korean_files = $korean_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت ترکیه‌ای (فقط اگر فعال باشد)
        if ($show_turkish) {
            $turkish_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 AND (name LIKE '%ترک%' OR name LIKE '%turkish%' OR name LIKE '%Turkish%' OR name_en LIKE '%turkish%' OR name_en LIKE '%Turkish%') ORDER BY id DESC LIMIT :limit");
            $turkish_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $turkish_stmt->execute();
            $turkish_files = $turkish_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت انیمه (فقط اگر فعال باشد)
        if ($show_anime) {
            $anime_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 AND media_type='anime' ORDER BY id DESC LIMIT :limit");
            $anime_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $anime_stmt->execute();
            $anime_files = $anime_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // دریافت انیمیشن (فقط اگر فعال باشد)
        if ($show_animation) {
            $animation_stmt = $pdo->prepare("SELECT * FROM sp_files WHERE status=1 AND media_type='animation' ORDER BY id DESC LIMIT :limit");
            $animation_stmt->bindValue(':limit', $latest_limit, PDO::PARAM_INT);
            $animation_stmt->execute();
            $animation_files = $animation_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error loading homepage data: " . $e->getMessage());
    }
}
?>

<!-- صفحه ساز اختصاصی (HTML سفارشی) -->
<?php if ($enable_custom_html && !empty($custom_html)): ?>
    <section style="margin-bottom: 48px;">
        <?= $custom_html ?>
    </section>
<?php endif; ?>

<!-- پر بازدیدترین‌ها -->
<?php if (!empty($most_viewed_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">🔥 پر بازدیدترین‌ها</h2>
        <div class="movies-grid">
            <?php foreach ($most_viewed_files as $file): ?>
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
    </section>
<?php endif; ?>

<!-- محبوب‌ترین‌ها -->
<?php if (!empty($popular_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">محبوب‌ترین‌ها</h2>
        <div class="movies-grid">
            <?php foreach ($popular_files as $file): ?>
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
    </section>
<?php endif; ?>

<!-- جدیدترین‌ها -->
<?php if (!empty($latest_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">جدیدترین‌ها</h2>
        <div class="movies-grid">
            <?php foreach ($latest_files as $file): ?>
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
    </section>
<?php endif; ?>

<!-- سریال‌ها -->
<?php if ($show_series && !empty($series_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">📺 سریال‌ها</h2>
        <div class="movies-grid">
            <?php foreach ($series_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php $poster_url = get_poster_url($file['poster']); ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-tv" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
                    <div class="movie-poster" style="display: none; align-items: center; justify-content: center; background: var(--bg-secondary);">
                        <i class="fas fa-tv" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="movie-meta">
                            <span><i class="fas fa-eye"></i> <span class="fa-num"><?= number_format($file['views']) ?></span></span>
                            <span class="quality-badge"><i class="fas fa-tv"></i> سریال</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- فیلم‌ها -->
<?php if ($show_movies && !empty($movies_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">🎬 فیلم‌ها</h2>
        <div class="movies-grid">
            <?php foreach ($movies_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php $poster_url = get_poster_url($file['poster']); ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
                    <div class="movie-poster" style="display: none; align-items: center; justify-content: center; background: var(--bg-secondary);">
                        <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="movie-meta">
                            <span><i class="fas fa-eye"></i> <span class="fa-num"><?= number_format($file['views']) ?></span></span>
                            <span class="quality-badge"><i class="fas fa-film"></i> فیلم</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- کره‌ای -->
<?php if ($show_korean && !empty($korean_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">🇰🇷 کره‌ای</h2>
        <div class="movies-grid">
            <?php foreach ($korean_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php $poster_url = get_poster_url($file['poster']); ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
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
    </section>
<?php endif; ?>

<!-- ترکیه‌ای -->
<?php if ($show_turkish && !empty($turkish_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">🇹🇷 ترکیه‌ای</h2>
        <div class="movies-grid">
            <?php foreach ($turkish_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php $poster_url = get_poster_url($file['poster']); ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-film" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
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
    </section>
<?php endif; ?>

<!-- انیمه -->
<?php if ($show_anime && !empty($anime_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">🎌 انیمه</h2>
        <div class="movies-grid">
            <?php foreach ($anime_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php $poster_url = get_poster_url($file['poster']); ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-paint-brush" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
                    <div class="movie-poster" style="display: none; align-items: center; justify-content: center; background: var(--bg-secondary);">
                        <i class="fas fa-paint-brush" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="movie-meta">
                            <span><i class="fas fa-eye"></i> <span class="fa-num"><?= number_format($file['views']) ?></span></span>
                            <span class="quality-badge"><i class="fas fa-paint-brush"></i> انیمه</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- انیمیشن -->
<?php if ($show_animation && !empty($animation_files)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">🎨 انیمیشن</h2>
        <div class="movies-grid">
            <?php foreach ($animation_files as $file): ?>
                <a href="movie.php?id=<?= $file['id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                    <?php if (!empty($file['poster'])): ?>
                        <?php $poster_url = get_poster_url($file['poster']); ?>
                        <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($file['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php else: ?>
                        <div class="movie-poster" style="display: flex; align-items: center; justify-content: center; background: var(--bg-secondary);">
                            <i class="fas fa-palette" style="font-size: 64px; color: var(--text-secondary);"></i>
                        </div>
                    <?php endif; ?>
                    <div class="movie-poster" style="display: none; align-items: center; justify-content: center; background: var(--bg-secondary);">
                        <i class="fas fa-palette" style="font-size: 64px; color: var(--text-secondary);"></i>
                    </div>
                    <div class="movie-info">
                        <h3 class="movie-title"><?= htmlspecialchars($file['name']) ?></h3>
                        <div class="movie-meta">
                            <span><i class="fas fa-eye"></i> <span class="fa-num"><?= number_format($file['views']) ?></span></span>
                            <span class="quality-badge"><i class="fas fa-palette"></i> انیمیشن</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<!-- دسته‌بندی‌ها -->
<?php if ($show_categories && !empty($categories)): ?>
    <section style="margin-bottom: 48px;">
        <h2 class="section-title">📁 دسته‌بندی‌ها</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
            <?php foreach ($categories as $cat): ?>
                <a href="category.php?id=<?= $cat['id'] ?>" class="category-card">
                    <i class="fas fa-folder" style="font-size: 32px; margin-bottom: 12px; color: var(--text-secondary);"></i>
                    <h3 style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($cat['name']) ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
