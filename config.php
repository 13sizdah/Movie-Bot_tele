<?php

if (!defined('TOKEN')) {
    define('TOKEN','7753447307:AAGfdK7B3ZUb5brapdgWo5zcg65mjBpk8qA'); // توکن ربات
}
define('HOST','localhost'); //  سرور دیتابیس (به صورت پیشفرض لوکال هاست)
define('USERNAME','isheresh_8'); // یوزنیم دیتابیس
define('PASSWORD','gJ&!uE9)j;kzFVJ?'); // رمزعبور دیتابیس
define('DBNAME','isheresh_8'); // نام دیتابیس
define('API','ZARINPAL_MERCHANT'); // مرچنت کد زین پال
define('BASEURI','https://13ishere.shop/8'); // آدرس ربات روی سایت شما
define('TELEGRAM_API_ID','34933903'); // API ID تلگرام
define('TELEGRAM_API_HASH','36cc98bc9a21f68f5852aecc7551b5ff'); // API Hash تلگرام
$admin = 7767429880; // آی دی عددی خود را از بات @userinfobot دریافت و جایگزین کنید
$botuser = 'vensumoviebot'; //یوزرنیم ربات بدون @

// تبدیل ژانرهای انگلیسی به فارسی
function translate_genre($genre) {
    if (empty($genre)) {
        return '';
    }
    
    // جدول تبدیل ژانرهای رایج
    $genre_map = [
        // ژانرهای اصلی
        'Action' => 'اکشن',
        'Adventure' => 'ماجراجویی',
        'Animation' => 'انیمیشن',
        'Biography' => 'زندگینامه',
        'Comedy' => 'کمدی',
        'Crime' => 'جنایی',
        'Documentary' => 'مستند',
        'Drama' => 'درام',
        'Family' => 'خانوادگی',
        'Fantasy' => 'فانتزی',
        'Film-Noir' => 'فیلم نوآر',
        'History' => 'تاریخی',
        'Horror' => 'وحشت',
        'Music' => 'موزیکال',
        'Musical' => 'موزیکال',
        'Mystery' => 'معمایی',
        'Romance' => 'عاشقانه',
        'Sci-Fi' => 'علمی-تخیلی',
        'Science Fiction' => 'علمی-تخیلی',
        'Sport' => 'ورزشی',
        'Thriller' => 'هیجان‌انگیز',
        'War' => 'جنگی',
        'Western' => 'وسترن',
        
        // ترکیبی (با کاما جدا شده)
        'Action, Adventure' => 'اکشن، ماجراجویی',
        'Action, Comedy' => 'اکشن، کمدی',
        'Action, Crime' => 'اکشن، جنایی',
        'Action, Drama' => 'اکشن، درام',
        'Action, Sci-Fi' => 'اکشن، علمی-تخیلی',
        'Comedy, Drama' => 'کمدی، درام',
        'Comedy, Romance' => 'کمدی، عاشقانه',
        'Crime, Drama' => 'جنایی، درام',
        'Drama, Romance' => 'درام، عاشقانه',
        'Horror, Thriller' => 'وحشت، هیجان‌انگیز',
        
        // ژانرهای دیگر
        'Short' => 'کوتاه',
        'Talk-Show' => 'تاک‌شو',
        'Game-Show' => 'گیم‌شو',
        'Reality-TV' => 'ریالیته',
        'News' => 'خبری',
        'Adult' => 'بزرگسالان',
    ];
    
    // اگر ژانر شامل کاما باشد (چند ژانر)، هر کدام را جداگانه تبدیل کن
    if (strpos($genre, ',') !== false) {
        $genres = explode(',', $genre);
        $translated_genres = [];
        foreach ($genres as $g) {
            $g = trim($g);
            $translated = isset($genre_map[$g]) ? $genre_map[$g] : $g;
            $translated_genres[] = $translated;
        }
        return implode('، ', $translated_genres);
    }
    
    // تبدیل تک ژانر
    $genre = trim($genre);
    return isset($genre_map[$genre]) ? $genre_map[$genre] : $genre;
}

