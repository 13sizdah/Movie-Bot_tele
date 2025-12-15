<?php
if (!defined('INDEX')) {
    die('403-Forbidden Access');
}

// Include episodes management
include_once 'episodes_management.php';
if (isset($_GET['page'])) {
    $page_number = $_GET['page'];
    get_specific_page($page_number, 'products');
} else {
    list_products();
}
include 'products_action.php';
include 'qualities_management.php';
?>

<div class="overflow-auto h-screen pb-24 pt-2 pr-2 pl-2 md:pt-0 md:pr-0 md:pl-0">
    <div class="flex flex-col flex-wrap sm:flex-row ">
        <div class="container mx-auto px-4 sm:px-8 max-w-8xl">
            <div class="py-8">
                <div class="flex flex-row mb-1 sm:mb-0 justify-between w-full">
                    <h2 class="text-2xl leading-tight">
                        <?= $title ?>
                    </h2>
                    <a href="?create_product">
                        <button class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            افزودن فیلم/سریال
                        </button>
                    </a>
                </div>
                <div class="-mx-4 sm:-mx-8 px-4 sm:px-8 py-4 overflow-x-auto">
                    <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-5 py-3 bg-white  border-b border-gray-200 text-gray-800  text-right text-sm uppercase font-normal">
                                        شناسه
                                    </th>
                                    <th scope="col" class="px-5 py-3 bg-white  border-b border-gray-200 text-gray-800  text-right text-sm uppercase font-normal">
                                        نام فیلم/سریال
                                    </th>
                                    <th scope="col" class="px-5 py-3 bg-white  border-b border-gray-200 text-gray-800  text-right text-sm uppercase font-normal">
                                        دسته بندی
                                    </th>
                                    <th scope="col" class="px-5 py-3 bg-white  border-b border-gray-200 text-gray-800  text-right text-sm uppercase font-normal">
                                        تعداد بازدید
                                    </th>
                                    <th scope="col" class="px-5 py-3 bg-white  border-b border-gray-200 text-gray-800  text-right text-sm uppercase font-normal">
                                        کیفیت‌ها
                                    </th>
                                    <th scope="col" class="px-5 py-3 bg-white  border-b border-gray-200 text-gray-800  text-right text-sm uppercase font-normal">
                                        اقدامات
                                    </th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product) { ?>

                                    <tr>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">
                                                <?= $product['id']; ?>
                                            </p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap max-w-xs">
                                                <?= $product['name']; ?>
                                            </p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">
                                                <?= cat_name($product['catid']); ?>
                                            </p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <p class="text-gray-900 whitespace-no-wrap">
                                                <?= $product['views'] . " بازدید"; ?>
                                            </p>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <?php
                                            // دریافت کیفیت‌های موجود
                                            $qualities_sql = "SELECT * FROM sp_qualities WHERE file_id=" . $product['id'] . " AND status=1 ORDER BY quality ASC";
                                            $qualities = $db->query($qualities_sql)->fetchAll();
                                            
                                            if (!empty($qualities)) {
                                                echo "<div class='space-y-1'>";
                                                foreach ($qualities as $q) {
                                                    $quality_name = htmlspecialchars($q['quality']);
                                                    $download_link = !empty($q['download_link']) ? htmlspecialchars($q['download_link']) : '-';
                                                    echo "<div class='text-xs'>";
                                                    echo "<span class='font-semibold'>$quality_name</span>";
                                                    if ($download_link != '-') {
                                                        echo "<br><a href='$download_link' target='_blank' class='text-blue-500 hover:underline text-xs'>لینک دانلود</a>";
                                                    }
                                                    echo "</div>";
                                                }
                                                echo "</div>";
                                            } else {
                                                echo "<span class='text-gray-400 text-xs'>بدون کیفیت</span>";
                                            }
                                            ?>
                                        </td>
                                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                            <div class="flex">
                                                <a class="ml-5" href="?edit_prd=<?= $product['id'] ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#F9A602">
                                                        <path d="M0 0h24v24H0V0z" fill="none" />
                                                        <path d="M14.06 9.02l.92.92L5.92 19H5v-.92l9.06-9.06M17.66 3c-.25 0-.51.1-.7.29l-1.83 1.83 3.75 3.75 1.83-1.83c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.2-.2-.45-.29-.71-.29zm-3.6 3.19L3 17.25V21h3.75L17.81 9.94l-3.75-3.75z" />
                                                    </svg>
                                                </a>
                                                <a class="ml-5" href="?manage_qualities=<?= $product['id'] ?>" title="مدیریت کیفیت‌ها">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#3B82F6">
                                                        <path d="M0 0h24v24H0V0z" fill="none"/>
                                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                                    </svg>
                                                </a>
                                                <?php if ($product['media_type'] == 'series') { ?>
                                                <a class="ml-5" href="?manage_episodes=<?= $product['id'] ?>" title="مدیریت فصل‌ها و قسمت‌ها">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#10B981">
                                                        <path d="M0 0h24v24H0V0z" fill="none"/>
                                                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/>
                                                    </svg>
                                                </a>
                                                <?php } ?>
                                                <a href="?del_prd=<?= $product['id'] ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#FF0000">
                                                        <path d="M0 0h24v24H0V0z" fill="none" />
                                                        <path d="M16 9v10H8V9h8m-1.5-6h-5l-1 1H5v2h14V4h-3.5l-1-1zM18 7H6v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>


                                    </tr>
                                <?php } ?>

                            </tbody>
                        </table>
                        <?php
                        fetch_pages_count('products');
                        if (isset($_GET['page']) && $_GET['page'] > $pages_number) { ?>
                            <div class="px-5 bg-white py-5 flex flex-col xs:flex-row items-center xs:justify-between">
                                همچین صفحه ای وجود ندارد
                            </div>
                        <?php } else { ?>
                            <div class="px-5 bg-white py-5 flex flex-col xs:flex-row items-center xs:justify-between">
                                <div class="flex items-center">

                                    <?php
                                    $page = 1;
                                    while ($page <= $pages_number) {
                                        if (isset($_GET['page']) && $_GET['page'] == $page) {
                                    ?>
                                            <a href="?page=<?= $page ?>">
                                                <button type="button" class="w-full px-4 py-2 border border-b text-base text-white bg-blue-600 hover:bg-blue-400 rounded-lg">
                                                    <?= $page;
                                                    $page++; ?>
                                                </button>
                                            </a>
                                        <?php } else { ?>
                                            <a href="?page=<?= $page ?>">
                                                <button type="button" class="w-full px-4 py-2 border border-b text-base text-indigo-500 bg-white hover:bg-gray-100 rounded-lg">
                                                    <?= $page;
                                                    $page++; ?>
                                                </button>
                                            </a>
                                <?php }
                                    }
                                } ?>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function priceCheck() {
       if (document.getElementById('free').checked) {
            document.getElementById("price").value = '0';
            document.getElementById("price").readOnly = true;
        }else if (document.getElementById('vip').checked){
            document.getElementById("price").readOnly = false;
        }

    }
</script>