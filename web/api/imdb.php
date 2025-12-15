<?php
/**
 * API برای دریافت اطلاعات فیلم/سریال از OMDb API
 * OMDb API: http://www.omdbapi.com/
 * 
 * استفاده:
 * GET /web/api/imdb.php?title=Inception&year=2010
 * 
 * نیاز به API Key از OMDb دارد (رایگان با محدودیت 1000 درخواست در روز)
 */

// جلوگیری از نمایش خطاهای PHP در خروجی JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// تنظیم header قبل از هر خروجی
header('Content-Type: application/json; charset=utf-8');

// بارگذاری config.php
$config_path = dirname(__DIR__) . '/config.php';
if (!file_exists($config_path)) {
    echo json_encode([
        'success' => false,
        'error' => 'فایل config.php یافت نشد'
    ]);
    exit;
}

require_once $config_path;

// API Key برای OMDb
define('OMDB_API_KEY', '3ae3e489');

$title = isset($_GET['title']) ? trim($_GET['title']) : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$imdb_id = isset($_GET['imdb_id']) ? trim($_GET['imdb_id']) : '';

// ساخت URL برای OMDb API
if (!empty($imdb_id)) {
    // استفاده از IMDb ID
    $url = 'http://www.omdbapi.com/?apikey=' . OMDB_API_KEY . '&i=' . urlencode($imdb_id) . '&plot=full&r=json';
} elseif (!empty($title)) {
    // استفاده از عنوان
    $url = 'http://www.omdbapi.com/?apikey=' . OMDB_API_KEY . '&t=' . urlencode($title);
    if ($year > 0) {
        $url .= '&y=' . $year;
    }
    $url .= '&plot=full&r=json';
} else {
    echo json_encode([
        'success' => false,
        'error' => 'عنوان فیلم/سریال یا IMDb ID ارسال نشده است'
    ]);
    exit;
}

// دریافت اطلاعات از OMDb
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($response)) {
    echo json_encode([
        'success' => false,
        'error' => 'خطا در دریافت اطلاعات از OMDb'
    ]);
    exit;
}

$data = json_decode($response, true);

if (isset($data['Response']) && $data['Response'] === 'False') {
    echo json_encode([
        'success' => false,
        'error' => $data['Error'] ?? 'فیلم/سریال یافت نشد'
    ]);
    exit;
}

// استخراج اطلاعات مورد نیاز
$result = [
    'success' => true,
    'data' => [
        'title' => $data['Title'] ?? '',
        'year' => $data['Year'] ?? '',
        'rated' => $data['Rated'] ?? '',
        'released' => $data['Released'] ?? '',
        'runtime' => $data['Runtime'] ?? '',
        'genre' => $data['Genre'] ?? '',
        'director' => $data['Director'] ?? '',
        'writer' => $data['Writer'] ?? '',
        'actors' => $data['Actors'] ?? '',
        'plot' => $data['Plot'] ?? '',
        'language' => $data['Language'] ?? '',
        'country' => $data['Country'] ?? '',
        'awards' => $data['Awards'] ?? '',
        'poster' => $data['Poster'] ?? '',
        'imdb_rating' => $data['imdbRating'] ?? '',
        'imdb_votes' => $data['imdbVotes'] ?? '',
        'imdb_id' => $data['imdbID'] ?? '',
        'type' => $data['Type'] ?? '',
        'metascore' => $data['Metascore'] ?? '',
    ]
];

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

