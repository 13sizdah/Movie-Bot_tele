<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

// تغییر وضعیت محصول حذف شده - همه محصولات به صورت پیش‌فرض فعال هستند
if (isset($_GET['create_product'])) {
    list_cats();
?>

    <div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
        <div class="flex flex-col  flex-wrap sm:flex-row ">
            <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
                <div class="py-8">
                    <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                        <h2 class="text-2xl leading-tight">
                            افزودن فیلم/سریال
                        </h2>
                    </div>
                    <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                        <div class="px-4 py-8 sm:px-10">
                            <div class="relative mt-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300">
                                    </div>
                                </div>
                                <div class="relative flex justify-center text-sm leading-5">
                                    <span class="px-2 text-gray-500 bg-white">
                                        اطلاعات محصول را وارد کرده و سپس دکمه ثبت را بزنید
                                    </span>
                                </div>
                            </div>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mt-6">
                                    <div class="w-full space-y-10">
                                        <div class="w-full">
                                            <div class=" relative ">

                                                <label for="prd_name" class="text-gray-700">
                                                    نام محصول (فارسی) *
                                                </label>
                                                <input type="text" name="prd_name" id="prd_name" class="mb-3 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required placeholder="مثال: ماتریکس"/>
                                                
                                                <label for="prd_name_en" class="text-gray-700">
                                                    نام محصول (انگلیسی)
                                                </label>
                                                <div class="flex gap-2 mb-2">
                                                    <input type="text" name="prd_name_en" id="prd_name_en" class="flex-1 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثال: The Matrix"/>
                                                    <button type="button" onclick="fetchFromIMDb()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 whitespace-nowrap">
                                                        <i class="fas fa-download"></i> دریافت از IMDb
                                                    </button>
                                                </div>
                                                <p class="text-xs text-gray-500 mb-5">💡 برای جستجوی بهتر، می‌توانید نام انگلیسی را هم وارد کنید. ابتدا نام فیلم/سریال را <strong>به انگلیسی</strong> در فیلد بالا وارد کنید، سپس دکمه "دریافت از IMDb" را بزنید. برای نتیجه بهتر، سال را هم وارد کنید.</p>

                                                <label for="prd_desc" class="text-gray-700">
                                                    توضیحات محصول
                                                </label>
                                                <textarea rows="5" cols="50" name="prd_desc" id="prd_desc" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required></textarea>

                                                <label for="prd_cat" class="text-gray-700">
                                                    دسته‌بندی *
                                                </label>
                                                <select name="prd_cat" id="prd_cat" class="mb-5 rounded-lg border-transparent flex-1 border border-gray-300 w-full py-2 px-4 text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required>
                                                    <option value="">-- انتخاب دسته‌بندی --</option>
                                                    <?php
                                                    global $db;
                                                    $cats_sql = "SELECT * FROM sp_cats ORDER BY name ASC";
                                                    $cats_result = $db->query($cats_sql)->fetchAll();
                                                    foreach ($cats_result as $cat) {
                                                        echo '<option value="' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <p class="text-xs text-gray-500 mb-5">💡 لطفاً دسته‌بندی مناسب را انتخاب کنید. اگر دسته‌بندی وجود ندارد، ابتدا از بخش "دسته‌بندی‌ها" یک دسته‌بندی ایجاد کنید.</p>

                                                <label for="media_type" class="text-gray-700">
                                                    نوع محتوا *
                                                </label>
                                                <select name="media_type" id="media_type_select" class="mb-5 rounded-lg border-transparent flex-1 border border-gray-300 w-full py-2 px-4 text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required onchange="toggleMediaFields()">
                                                    <option value="">-- ابتدا نوع محتوا را انتخاب کنید --</option>
                                                    <option value="movie">🎬 فیلم</option>
                                                    <option value="series">📺 سریال</option>
                                                    <option value="animation">🎨 انیمیشن</option>
                                                    <option value="anime">🌸 انیمه</option>
                                                </select>
                                                <p class="text-xs text-gray-500 mb-2">⚠️ ابتدا نوع محتوا را انتخاب کنید تا فیلدهای مناسب نمایش داده شود</p>

                                                <label for="poster" class="text-gray-700">
                                                    عکس پوستر فیلم/سریال
                                                </label>
                                                <input type="file" name="poster_file" accept="image/*" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                                                <p class="text-xs text-gray-500 mb-2">یا لینک عکس را وارد کنید:</p>
                                                <input type="text" name="poster" id="poster" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="https://example.com/poster.jpg" />

                                                <label for="year" class="text-gray-700">
                                                    سال تولید
                                                </label>
                                                <input type="number" name="year" id="year" min="1900" max="2100" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />


                                                <!-- فیلدهای مخصوص فیلم -->
                                                <div id="movie_fields" style="display: none;">
                                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-5">
                                                        <p class="text-blue-800 font-semibold mb-3">📋 فیلدهای مخصوص فیلم:</p>
                                                        <p class="text-sm text-blue-700 mb-3">برای فیلم، می‌توانید کیفیت‌های مختلف را از بخش "مدیریت کیفیت‌ها" اضافه کنید.</p>
                                                    </div>
                                                </div>
                                                
                                                <!-- فیلدهای مخصوص سریال -->
                                                <div id="series_fields_add" style="display: none;">
                                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-5">
                                                        <p class="text-green-800 font-semibold mb-3">📋 فیلدهای مخصوص سریال/انیمیشن/انیمه:</p>
                                                        <p class="text-sm text-green-700 mb-3">برای سریال، انیمیشن و انیمه، می‌توانید فصل‌ها و قسمت‌ها را از بخش "مدیریت فصل‌ها و قسمت‌ها" اضافه کنید.</p>
                                                    </div>
                                                    
                                                    <label for="season" class="text-gray-700">
                                                        فصل (برای سریال) - اختیاری
                                                    </label>
                                                    <input type="number" name="season" id="season" min="1" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثلاً: 1" />
                                                    
                                                    <label for="episode" class="text-gray-700">
                                                        قسمت (برای سریال) - اختیاری
                                                    </label>
                                                    <input type="number" name="episode" id="episode" min="1" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثلاً: 5" />
                                                    
                                                    <p class="text-xs text-gray-500 mb-2">💡 توجه: برای افزودن فصل‌ها و قسمت‌ها با لینک‌های جداگانه، بعد از ذخیره از بخش "مدیریت فصل‌ها و قسمت‌ها" استفاده کنید.</p>
                                                </div>
                                                
                                                <label for="quality" class="text-gray-700">
                                                    کیفیت (مثلا: 720p, 1080p, 4K) - اختیاری
                                                </label>
                                                <input type="text" name="quality" id="quality" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                                                <p class="text-xs text-gray-500 mb-2">💡 برای فیلم: از بخش "مدیریت کیفیت‌ها" استفاده کنید | برای سریال: کیفیت را در بخش "مدیریت فصل‌ها و قسمت‌ها" تنظیم کنید</p>

                                                <label for="imdb" class="text-gray-700">
                                                    امتیاز IMDb (مثلا: 8.5)
                                                </label>
                                                <input type="text" name="imdb" id="imdb" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="director" class="text-gray-700">
                                                    کارگردان
                                                </label>
                                                <input type="text" name="director" id="director" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="cast" class="text-gray-700">
                                                    بازیگران (با کاما جدا کنید)
                                                </label>
                                                <input type="text" name="cast" id="cast" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="duration" class="text-gray-700">
                                                    مدت زمان (برای فیلم) یا تعداد قسمت (برای سریال)
                                                </label>
                                                <input type="text" name="duration" id="duration" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                                                
                                                <div id="series_fields_add" style="display: none;">
                                                    <label for="season" class="text-gray-700">
                                                        فصل (برای سریال)
                                                    </label>
                                                    <input type="number" name="season" id="season" min="1" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثلاً: 1" />
                                                    
                                                    <label for="episode" class="text-gray-700">
                                                        قسمت (برای سریال)
                                                    </label>
                                                    <input type="number" name="episode" id="episode" min="1" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثلاً: 5" />
                                                </div>
                                                
                                                <script>
                                                function toggleMediaFields() {
                                                    var mediaType = document.getElementById('media_type').value;
                                                    var movieFields = document.getElementById('movie_fields');
                                                    var seriesFields = document.getElementById('series_fields_add');
                                                    
                                                    // مخفی کردن همه
                                                    if (movieFields) movieFields.style.display = 'none';
                                                    if (seriesFields) seriesFields.style.display = 'none';
                                                    
                                                    // نمایش فیلدهای مناسب
                                                    if (mediaType === 'movie' && movieFields) {
                                                        movieFields.style.display = 'block';
                                                    } else if ((mediaType === 'series' || mediaType === 'animation' || mediaType === 'anime') && seriesFields) {
                                                        seriesFields.style.display = 'block';
                                                    }
                                                }
                                                
                                                // تابع دریافت اطلاعات از IMDb
                                                function fetchFromIMDb() {
                                                    // اول نام انگلیسی را چک کن، اگر نبود از نام فارسی استفاده کن
                                                    const titleEn = document.getElementById('prd_name_en') ? document.getElementById('prd_name_en').value.trim() : '';
                                                    const title = titleEn || document.getElementById('prd_name').value.trim();
                                                    const year = document.querySelector('input[name="year"]').value;
                                                    
                                                    if (!title) {
                                                        alert('⚠️ لطفاً ابتدا نام فیلم/سریال را وارد کنید');
                                                        document.getElementById('prd_name').focus();
                                                        return;
                                                    }
                                                    
                                                    // نمایش loading
                                                    const loadingDiv = document.createElement('div');
                                                    loadingDiv.id = 'imdb-loading';
                                                    loadingDiv.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                                                    loadingDiv.innerHTML = '<div class="bg-white rounded-lg p-6 text-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div><p class="text-gray-700">در حال دریافت اطلاعات از IMDb...</p></div>';
                                                    document.body.appendChild(loadingDiv);
                                                    
                                                    // ساخت URL API (استفاده از مسیر مطلق)
                                                    let apiUrl = '/8/web/api/imdb.php?title=' + encodeURIComponent(title);
                                                    if (year && year > 0) {
                                                        apiUrl += '&year=' + year;
                                                    }
                                                    
                                                    // دریافت اطلاعات
                                                    fetch(apiUrl)
                                                        .then(response => {
                                                            // بررسی اینکه آیا پاسخ JSON است یا HTML خطا
                                                            const contentType = response.headers.get('content-type');
                                                            if (!contentType || !contentType.includes('application/json')) {
                                                                return response.text().then(text => {
                                                                    throw new Error('پاسخ از سرور JSON نیست. احتمالاً خطای PHP: ' + text.substring(0, 200));
                                                                });
                                                            }
                                                            return response.json();
                                                        })
                                                        .then(data => {
                                                            document.getElementById('imdb-loading').remove();
                                                            
                                                            if (data.success) {
                                                                // پر کردن فیلدها
                                                                if (data.data.imdb_rating) {
                                                                    document.querySelector('input[name="imdb"]').value = data.data.imdb_rating;
                                                                }
                                                                if (data.data.director) {
                                                                    document.querySelector('input[name="director"]').value = data.data.director;
                                                                }
                                                                if (data.data.actors) {
                                                                    document.querySelector('input[name="cast"]').value = data.data.actors;
                                                                }
                                                                if (data.data.plot) {
                                                                    document.querySelector('textarea[name="prd_desc"]').value = data.data.plot;
                                                                }
                                                                if (data.data.poster && data.data.poster !== 'N/A') {
                                                                    document.querySelector('input[name="poster"]').value = data.data.poster;
                                                                }
                                                                if (data.data.year && data.data.year !== 'N/A') {
                                                                    document.querySelector('input[name="year"]').value = data.data.year;
                                                                }
                                                                if (data.data.runtime && data.data.runtime !== 'N/A') {
                                                                    const runtime = data.data.runtime.replace(' min', '');
                                                                    document.querySelector('input[name="duration"]').value = runtime + ' دقیقه';
                                                                }
                                                                // پر کردن نام انگلیسی از Title
                                                                if (data.data.title && data.data.title !== 'N/A') {
                                                                    const nameEnInput = document.getElementById('prd_name_en');
                                                                    if (nameEnInput) {
                                                                        nameEnInput.value = data.data.title;
                                                                    }
                                                                }
                                                                
                                                                // اگر نوع محتوا انتخاب نشده، بر اساس type از IMDb تنظیم کن
                                                                const mediaTypeSelect = document.getElementById('media_type');
                                                                if (!mediaTypeSelect.value && data.data.type) {
                                                                    if (data.data.type.toLowerCase() === 'series' || data.data.type.toLowerCase() === 'tv series') {
                                                                        mediaTypeSelect.value = 'series';
                                                                        toggleMediaFields();
                                                                    } else {
                                                                        mediaTypeSelect.value = 'movie';
                                                                        toggleMediaFields();
                                                                    }
                                                                }
                                                                
                                                                alert('✅ اطلاعات با موفقیت از IMDb دریافت شد!');
                                                            } else {
                                                                let errorMsg = data.error || 'فیلم/سریال یافت نشد';
                                                                if (errorMsg.includes('not found') || errorMsg.includes('Movie not found')) {
                                                                    alert('❌ فیلم/سریال یافت نشد!\n\n💡 راهنمایی:\n• نام را به انگلیسی و دقیق وارد کنید\n• می‌توانید سال را هم وارد کنید\n• مثال: "The Matrix" یا "The Matrix" با سال 1999\n• برای سریال‌ها: "Breaking Bad" یا "Game of Thrones"');
                                                                } else {
                                                                    alert('❌ خطا: ' + errorMsg);
                                                                }
                                                            }
                                                        })
                                                        .catch(error => {
                                                            document.getElementById('imdb-loading').remove();
                                                            alert('❌ خطا در ارتباط با سرور: ' + error.message);
                                                            console.error('Error:', error);
                                                        });
                                                }
                                                
                                                // اجرای تابع در بارگذاری صفحه
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    toggleMediaFields();
                                                });
                                                </script>

                                                <label for="demo" class="text-gray-700">
                                                    لینک پیش نمایش / تریلر
                                                </label>
                                                <input type="text" name="demo" id="demo" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />


                                                <!-- نوع محصول و وضعیت محصول حذف شده - همه محصولات به صورت پیش‌فرض فعال و رایگان هستند -->
                                              
                                            </div>

                                            <div>
                                                <span class="block w-full rounded-md shadow-sm">
                                                    <button type="submit" name="create_product" class="py-2 px-4  bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 focus:ring-offset-indigo-200 text-white w-full transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2  rounded-lg ">
                                                        ثبت
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

<?php } ?>
<!-- insert product -->
<?php
if (isset($_POST['create_product'])) {
    if (insert_product()) { ?>
        <script type="text/javascript">
            window.location = "products.php?prd_created";
        </script>
        <?php }elseif($empty_inputs == 1){?>
           <script>alert("فیلد های اجباری را پر کنید");</script> 
    <?php } else { ?>
        <script type="text/javascript">
            window.location = "products.php?prd_create_error";
        </script>
<?php }
} ?>
<?php if (isset($_GET['prd_created'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        فیلم/سریال با موفقیت افزوده شد
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>

<?php if (isset($_GET['prd_create_error'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-red-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        مشکلی در افزودن فیلم/سریال بوجود آمده است
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>
<?php if (isset($_GET['prd_create_error'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-red-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        مشکلی در افزودن فیلم/سریال بوجود آمده است
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>


<!-- edit product-->

<?php if (isset($_GET['edit_prd'])) {
    $id = intval($_GET['edit_prd']);
    fetch_product_info($id);
    list_cats();
?>

    <div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
        <div class="flex flex-col  flex-wrap sm:flex-row ">
            <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
                <div class="py-8">
                    <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                        <h2 class="text-2xl leading-tight">
                            افزودن فیلم/سریال
                        </h2>
                    </div>
                    <div class="bg-white rounded-lg shadow min-w-full sm:overflow-hidden mt-5">
                        <div class="px-4 py-8 sm:px-10">
                            <div class="relative mt-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300">
                                    </div>
                                </div>
                                <div class="relative flex justify-center text-sm leading-5">
                                    <span class="px-2 text-gray-500 bg-white">
                                        اطلاعات محصول را وارد کرده و سپس دکمه ثبت را بزنید
                                    </span>
                                </div>
                            </div>
                            <form method="POST" action="" enctype="multipart/form-data">
                                <div class="mt-6">
                                    <div class="w-full space-y-10">
                                        <div class="w-full">
                                            <div class=" relative ">
                                                <input type="hidden" name="id" value="<?= $id ?>" />

                                                <label for="prd_name" class="text-gray-700">
                                                    نام محصول (فارسی) *
                                                </label>
                                                <input type="text" name="prd_name" id="prd_name_edit" value="<?= $product_name ?>" class="mb-3 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required placeholder="مثال: ماتریکس"/>
                                                
                                                <label for="prd_name_en" class="text-gray-700">
                                                    نام محصول (انگلیسی)
                                                </label>
                                                <div class="flex gap-2 mb-2">
                                                    <input type="text" name="prd_name_en" id="prd_name_en_edit" value="<?= isset($product_name_en) ? $product_name_en : '' ?>" class="flex-1 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثال: The Matrix"/>
                                                    <button type="button" onclick="fetchFromIMDbEdit()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg flex items-center gap-2 whitespace-nowrap">
                                                        <i class="fas fa-download"></i> دریافت از IMDb
                                                    </button>
                                                </div>
                                                <p class="text-xs text-gray-500 mb-5">💡 برای جستجوی بهتر، می‌توانید نام انگلیسی را هم وارد کنید. ابتدا نام فیلم/سریال را <strong>به انگلیسی</strong> در فیلد بالا وارد کنید، سپس دکمه "دریافت از IMDb" را بزنید.</p>

                                                <label for="prd_desc" class="text-gray-700">
                                                    توضیحات محصول
                                                </label>
                                                <textarea rows="5" cols="50" name="prd_desc" id="prd_desc" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent"><?= $product_description ?></textarea>

                                                <label for="prd_cat" class="text-gray-700">
                                                    دسته‌بندی *
                                                </label>
                                                <select name="prd_cat" id="prd_cat" class="mb-5 rounded-lg border-transparent flex-1 border border-gray-300 w-full py-2 px-4 text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" required>
                                                    <option value="">-- انتخاب دسته‌بندی --</option>
                                                    <?php
                                                    global $db;
                                                    $cats_sql = "SELECT * FROM sp_cats ORDER BY name ASC";
                                                    $cats_result = $db->query($cats_sql)->fetchAll();
                                                    foreach ($cats_result as $cat) {
                                                        $selected = ($product_category == $cat['id']) ? 'selected' : '';
                                                        echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <p class="text-xs text-gray-500 mb-5">💡 لطفاً دسته‌بندی مناسب را انتخاب کنید.</p>

                                                <label for="media_type" class="text-gray-700">
                                                    نوع محتوا *
                                                </label>
                                                <select name="media_type" id="media_type_select_edit" class="mb-5 rounded-lg border-transparent flex-1 border border-gray-300 w-full py-2 px-4 text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" onchange="toggleMediaFieldsEdit()">
                                                    <option value="">-- انتخاب کنید --</option>
                                                    <option value="movie" <?= ($media_type == 'movie') ? 'selected' : '' ?>>🎬 فیلم</option>
                                                    <option value="series" <?= ($media_type == 'series') ? 'selected' : '' ?>>📺 سریال</option>
                                                    <option value="animation" <?= ($media_type == 'animation') ? 'selected' : '' ?>>🎨 انیمیشن</option>
                                                    <option value="anime" <?= ($media_type == 'anime') ? 'selected' : '' ?>>🌸 انیمه</option>
                                                </select>
                                                <p class="text-xs text-gray-500 mb-2">⚠️ نوع محتوا را انتخاب کنید</p>

                                                <label for="poster" class="text-gray-700">
                                                    عکس پوستر فیلم/سریال
                                                </label>
                                                <?php if (!empty($poster)) { ?>
                                                    <div class="mb-2">
                                                        <img src="<?= htmlspecialchars($poster) ?>" alt="پوستر" class="max-w-xs h-auto rounded-lg border border-gray-300">
                                                    </div>
                                                <?php } ?>
                                                <input type="file" name="poster_file" accept="image/*" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                                                <p class="text-xs text-gray-500 mb-2">یا لینک عکس را وارد کنید:</p>
                                                <input type="text" name="poster" id="poster" value="<?= isset($poster) ? $poster : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="https://example.com/poster.jpg" />

                                                <label for="year" class="text-gray-700">
                                                    سال تولید
                                                </label>
                                                <input type="number" name="year" id="year" min="1900" max="2100" value="<?= isset($year) ? $year : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />


                                                <label for="quality" class="text-gray-700">
                                                    کیفیت (مثلا: 720p, 1080p, 4K)
                                                </label>
                                                <input type="text" name="quality" id="quality" value="<?= isset($quality) ? $quality : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="imdb" class="text-gray-700">
                                                    امتیاز IMDb (مثلا: 8.5)
                                                </label>
                                                <input type="text" name="imdb" id="imdb" value="<?= isset($imdb) ? $imdb : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="director" class="text-gray-700">
                                                    کارگردان
                                                </label>
                                                <input type="text" name="director" id="director" value="<?= isset($director) ? $director : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="cast" class="text-gray-700">
                                                    بازیگران (با کاما جدا کنید)
                                                </label>
                                                <input type="text" name="cast" id="cast" value="<?= isset($cast) ? $cast : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="duration" class="text-gray-700">
                                                    مدت زمان (برای فیلم) یا تعداد قسمت (برای سریال)
                                                </label>
                                                <input type="text" name="duration" id="duration" value="<?= isset($duration) ? $duration : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />
                                                
                                                <div id="series_fields" style="display: <?= ($media_type == 'series' || $media_type == 'animation' || $media_type == 'anime') ? 'block' : 'none' ?>;">
                                                    <label for="season" class="text-gray-700">
                                                        فصل (برای سریال/انیمیشن/انیمه)
                                                    </label>
                                                    <input type="number" name="season" id="season" min="1" value="<?= isset($season) ? $season : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثلاً: 1" />
                                                    
                                                    <label for="episode" class="text-gray-700">
                                                        قسمت (برای سریال)
                                                    </label>
                                                    <input type="number" name="episode" id="episode" min="1" value="<?= isset($episode) ? $episode : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" placeholder="مثلاً: 5" />
                                                </div>
                                                
                                                <script>
                                                function toggleMediaFieldsEdit() {
                                                    var mediaType = document.getElementById('media_type_select_edit').value;
                                                    var seriesFields = document.getElementById('series_fields');
                                                    
                                                    if (mediaType === 'series' || mediaType === 'animation' || mediaType === 'anime') {
                                                        seriesFields.style.display = 'block';
                                                    } else {
                                                        seriesFields.style.display = 'none';
                                                    }
                                                }
                                                
                                                // تابع دریافت اطلاعات از IMDb برای ویرایش
                                                function fetchFromIMDbEdit() {
                                                    // اول نام انگلیسی را چک کن، اگر نبود از نام فارسی استفاده کن
                                                    const titleEn = document.getElementById('prd_name_en_edit') ? document.getElementById('prd_name_en_edit').value.trim() : '';
                                                    const title = titleEn || document.getElementById('prd_name_edit').value.trim();
                                                    const year = document.querySelector('input[name="year"]').value;
                                                    
                                                    if (!title) {
                                                        alert('⚠️ لطفاً ابتدا نام فیلم/سریال را وارد کنید');
                                                        document.getElementById('prd_name_edit').focus();
                                                        return;
                                                    }
                                                    
                                                    // نمایش loading
                                                    const loadingDiv = document.createElement('div');
                                                    loadingDiv.id = 'imdb-loading-edit';
                                                    loadingDiv.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                                                    loadingDiv.innerHTML = '<div class="bg-white rounded-lg p-6 text-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div><p class="text-gray-700">در حال دریافت اطلاعات از IMDb...</p></div>';
                                                    document.body.appendChild(loadingDiv);
                                                    
                                                    // ساخت URL API (استفاده از مسیر مطلق)
                                                    let apiUrl = '/8/web/api/imdb.php?title=' + encodeURIComponent(title);
                                                    if (year && year > 0) {
                                                        apiUrl += '&year=' + year;
                                                    }
                                                    
                                                    // دریافت اطلاعات
                                                    fetch(apiUrl)
                                                        .then(response => {
                                                            // بررسی اینکه آیا پاسخ JSON است یا HTML خطا
                                                            const contentType = response.headers.get('content-type');
                                                            if (!contentType || !contentType.includes('application/json')) {
                                                                return response.text().then(text => {
                                                                    throw new Error('پاسخ از سرور JSON نیست. احتمالاً خطای PHP: ' + text.substring(0, 200));
                                                                });
                                                            }
                                                            return response.json();
                                                        })
                                                        .then(data => {
                                                            document.getElementById('imdb-loading-edit').remove();
                                                            
                                                            if (data.success) {
                                                                // پر کردن فیلدها
                                                                if (data.data.imdb_rating) {
                                                                    document.querySelector('input[name="imdb"]').value = data.data.imdb_rating;
                                                                }
                                                                if (data.data.director) {
                                                                    document.querySelector('input[name="director"]').value = data.data.director;
                                                                }
                                                                if (data.data.actors) {
                                                                    document.querySelector('input[name="cast"]').value = data.data.actors;
                                                                }
                                                                if (data.data.plot) {
                                                                    document.querySelector('textarea[name="prd_desc"]').value = data.data.plot;
                                                                }
                                                                if (data.data.poster && data.data.poster !== 'N/A') {
                                                                    document.querySelector('input[name="poster"]').value = data.data.poster;
                                                                }
                                                                if (data.data.year && data.data.year !== 'N/A') {
                                                                    document.querySelector('input[name="year"]').value = data.data.year;
                                                                }
                                                                if (data.data.runtime && data.data.runtime !== 'N/A') {
                                                                    const runtime = data.data.runtime.replace(' min', '');
                                                                    document.querySelector('input[name="duration"]').value = runtime + ' دقیقه';
                                                                }
                                                                
                                                                alert('✅ اطلاعات با موفقیت از IMDb دریافت شد!');
                                                            } else {
                                                                let errorMsg = data.error || 'فیلم/سریال یافت نشد';
                                                                if (errorMsg.includes('not found') || errorMsg.includes('Movie not found')) {
                                                                    alert('❌ فیلم/سریال یافت نشد!\n\n💡 راهنمایی:\n• نام را به انگلیسی و دقیق وارد کنید\n• می‌توانید سال را هم وارد کنید\n• مثال: "The Matrix" یا "The Matrix" با سال 1999\n• برای سریال‌ها: "Breaking Bad" یا "Game of Thrones"');
                                                                } else {
                                                                    alert('❌ خطا: ' + errorMsg);
                                                                }
                                                            }
                                                        })
                                                        .catch(error => {
                                                            document.getElementById('imdb-loading-edit').remove();
                                                            alert('❌ خطا در ارتباط با سرور: ' + error.message);
                                                            console.error('Error:', error);
                                                        });
                                                }
                                                
                                                document.querySelector('select[name="media_type"]').addEventListener('change', function() {
                                                    toggleMediaFieldsEdit();
                                                });
                                                
                                                // اجرای تابع در بارگذاری صفحه
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    toggleMediaFieldsEdit();
                                                });
                                                </script>

                                                <label for="poster" class="text-gray-700">
                                                    لینک پوستر (اختیاری)
                                                </label>
                                                <input type="text" name="poster" value="<?= isset($poster) ? $poster : '' ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                                <label for="demo" class="text-gray-700">
                                                    لینک پیش نمایش / تریلر
                                                </label>
                                                <input type="text" name="demo" id="demo" value="<?= $product_demo ?>" class="mb-5 rounded-lg border-transparent flex-1 appearance-none border border-gray-300 w-full py-2 px-4 bg-white text-gray-700 placeholder-gray-400 shadow-sm text-base focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-transparent" />

                                            
                                                <!-- نوع محصول و وضعیت محصول حذف شده - همه محصولات به صورت پیش‌فرض فعال و رایگان هستند -->

                                            </div>
                                        </div>

                                        <div>
                                            <span class="block w-full rounded-md shadow-sm">
                                                <button type="submit" name="edit_product" class="py-2 px-4  bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 focus:ring-offset-indigo-200 text-white w-full transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2  rounded-lg ">
                                                    ثبت
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php } ?>
<?php
if (isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    if (update_product($id)) { ?>
        <script type="text/javascript">
            window.location = "products.php?prd_edited";
        </script>
    <?php } else { ?>
        <script type="text/javascript">
            window.location = "products.php?prd_edit_error";
        </script>
<?php }
} ?>

<?php if (isset($_GET['status_toggled'])) { ?>
    <div id="alert1" class="my-3 block text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        وضعیت محصول با موفقیت تغییر یافت.
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>

<?php if (isset($_GET['status_toggle_error'])) { ?>
    <div id="alert1" class="my-3 block text-left text-white bg-red-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        خطا در تغییر وضعیت محصول.
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>

<?php if (isset($_GET['prd_edited'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        فیلم/سریال با موفقیت ویرایش شد
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>

<?php if (isset($_GET['prd_edit_error'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-red-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        مشکلی در ویرایش فیلم/سریال بوجود آمده است
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>



<!-- delete product -->


<?php if (isset($_GET['del_prd'])) {
    if (delete_product()) { ?>
        <script type="text/javascript">
            window.location = "products.php?prd_del";
        </script>
    <?php } else { ?>
        <script type="text/javascript">
            window.location = "products.php?prd_del_error";
        </script>
<?php
    }
} ?>

<?php if (isset($_GET['prd_del'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-green-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        فیلم/سریال با موفقیت حذف شد
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>

<?php if (isset($_GET['prd_del_error'])) { ?>
    <div id="alert1" class="my-3  block  text-left text-white bg-red-500 h-12 flex items-center justify-center p-4 rounded-md relative" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="flex-shrink-0 w-6 h-6 mx-2 stroke-current">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
            </path>
        </svg>
        مشکلی در حذف فیلم/سریال بوجود آمده است
        <button onclick="closeAlert()" class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-3 mr-6 outline-none focus:outline-none">
            <span>×</span>
        </button>
    </div>
<?php } ?>