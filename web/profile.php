<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = get_user_id();

// دریافت اطلاعات کاربر
$user_stmt = $pdo->prepare("SELECT * FROM sp_users WHERE userid = :userid LIMIT 1");
$user_stmt->bindValue(':userid', $user_id, PDO::PARAM_INT);
$user_stmt->execute();
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('logout.php');
}

// تعداد فیلم/سریال‌های بازدید شده
$viewed_stmt = $pdo->prepare("SELECT COUNT(DISTINCT uv.file_id) as viewed_count 
                              FROM sp_user_views uv 
                              INNER JOIN sp_files f ON uv.file_id = f.id 
                              WHERE uv.userid = :userid AND f.status = 1");
$viewed_stmt->bindValue(':userid', $user_id, PDO::PARAM_INT);
$viewed_stmt->execute();
$viewed_result = $viewed_stmt->fetch(PDO::FETCH_ASSOC);
$viewed_count = isset($viewed_result['viewed_count']) ? intval($viewed_result['viewed_count']) : 0;

// دریافت آخرین بازدیدها
$recent_views_stmt = $pdo->prepare("SELECT uv.*, f.name, f.poster, f.media_type, f.id as file_id
                                     FROM sp_user_views uv
                                     INNER JOIN sp_files f ON uv.file_id = f.id
                                     WHERE uv.userid = :userid AND f.status = 1
                                     ORDER BY uv.viewed_at DESC
                                     LIMIT 20");
$recent_views_stmt->bindValue(':userid', $user_id, PDO::PARAM_INT);
$recent_views_stmt->execute();
$recent_views = $recent_views_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'پروفایل کاربری';
include 'includes/header.php';
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <div class="profile-card">
        <h1 class="section-title" style="margin-bottom: 32px;">
            <i class="fas fa-user"></i> پروفایل کاربری
        </h1>
        
        <!-- عکس کاربر -->
        <div style="display: flex; justify-content: center; margin-bottom: 32px;">
            <div id="user-avatar-container" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid var(--border-color); background: var(--bg-secondary); display: flex; align-items: center; justify-content: center;">
                <img id="user-avatar" src="" alt="عکس کاربر" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                <i class="fas fa-user" id="user-avatar-icon" style="font-size: 64px; color: var(--text-secondary);"></i>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 32px;">
            <div class="card">
                <h3 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--border-color);">اطلاعات حساب کاربری</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="profile-info-item">
                        <span class="profile-info-label"><i class="fas fa-user"></i> نام:</span>
                        <span class="profile-info-value"><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                    <div class="profile-info-item">
                        <span class="profile-info-label"><i class="fas fa-id-card"></i> شناسه:</span>
                        <span class="profile-info-value"><span class="fa-num"><?= $user['userid'] ?></span></span>
                    </div>
                    <?php if (!empty($user['phone']) && $user['verified'] == 1): ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label"><i class="fas fa-phone"></i> شماره تلفن:</span>
                            <span class="profile-info-value"><?= htmlspecialchars($user['phone']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label"><i class="fas fa-phone"></i> شماره تلفن:</span>
                            <span class="profile-info-value" style="color: var(--text-secondary);">تایید نشده</span>
                        </div>
                    <?php endif; ?>
                    <?php if (is_vip($user_id)): ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label"><i class="fas fa-crown"></i> اشتراک ویژه:</span>
                            <span class="profile-info-value" style="color: #fbbf24;"><?= htmlspecialchars($user['vip_plan']) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="profile-info-item">
                            <span class="profile-info-label"><i class="fas fa-crown"></i> اشتراک ویژه:</span>
                            <span class="profile-info-value" style="color: var(--text-secondary);">غیرفعال</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3 style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid var(--border-color);">آمار</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="profile-info-item">
                        <span class="profile-info-label"><i class="fas fa-film"></i> فیلم/سریال بازدید شده:</span>
                        <span class="profile-info-value"><span class="fa-num"><?= $viewed_count ?></span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($recent_views)): ?>
        <section style="margin-top: 48px;">
            <h2 class="section-title">
                <i class="fas fa-history"></i> آخرین بازدیدها
            </h2>
            <div class="movies-grid">
                <?php foreach ($recent_views as $view): ?>
                    <a href="movie.php?id=<?= $view['file_id'] ?>" class="movie-card" style="text-decoration: none; color: inherit;">
                        <?php if (!empty($view['poster'])): ?>
                            <?php 
                            $poster_url = get_poster_url($view['poster']);
                            ?>
                            <img src="<?= htmlspecialchars($poster_url) ?>" alt="<?= htmlspecialchars($view['name']) ?>" class="movie-poster" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
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
                            <h3 class="movie-title"><?= htmlspecialchars($view['name']) ?></h3>
                            <div class="movie-meta">
                                <?php if ($view['media_type'] == 'series'): ?>
                                    <span class="quality-badge"><i class="fas fa-tv"></i> سریال</span>
                                <?php elseif ($view['media_type'] == 'animation'): ?>
                                    <span class="quality-badge"><i class="fas fa-palette"></i> انیمیشن</span>
                                <?php elseif ($view['media_type'] == 'anime'): ?>
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
</div>

<script>
// دریافت عکس کاربر از Telegram Web App
if (typeof Telegram !== 'undefined' && Telegram.WebApp) {
    const tg = Telegram.WebApp;
    tg.ready();
    
    // دریافت اطلاعات کاربر از initDataUnsafe (دسترسی مستقیم به داده‌ها)
    const initDataUnsafe = tg.initDataUnsafe;
    if (initDataUnsafe && initDataUnsafe.user) {
        const user = initDataUnsafe.user;
        
        // اگر عکس کاربر موجود باشد
        if (user.photo_url) {
            const avatarImg = document.getElementById('user-avatar');
            const avatarIcon = document.getElementById('user-avatar-icon');
            
            if (avatarImg && avatarIcon) {
                avatarImg.src = user.photo_url;
                avatarImg.style.display = 'block';
                avatarIcon.style.display = 'none';
            }
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>

