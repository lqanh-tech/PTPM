<?php

if (ob_get_level() == 0) {
    ob_start();
}

require_once __DIR__ . '/../mod/sessionManager.php';
SessionManager::start();

require_once __DIR__ . '/../mod/phanquyenCls.php';
$phanQuyen = new PhanQuyen();
$username = isset($_SESSION['USER']) ? $_SESSION['USER'] : (isset($_SESSION['ADMIN']) ? $_SESSION['ADMIN'] : '');

if (!$phanQuyen->checkAccess('marketing_content', $username)) {
    return;
}

require_once __DIR__ . '/../mod/BannerManager.php';
require_once __DIR__ . '/../mod/NewsManager.php';
require_once __DIR__ . '/../mod/PromotionManager.php';
require_once __DIR__ . '/../mod/PageManager.php';

$bannerManager = new BannerManager();
$newsManager = new NewsManager();
$promotionManager = new PromotionManager();
$pageManager = new PageManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_marketing_handler'])) {

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $action = $_POST['action'] ?? '';
    $redirect_tab = $_POST['tab'] ?? 'banners';

    switch ($action) {

        case 'add_banner':
            $title = trim($_POST['banner_title'] ?? '');
            $description = trim($_POST['banner_description'] ?? '');
            $link_url = trim($_POST['banner_link_url'] ?? '');
            $position = (int)($_POST['banner_position'] ?? 0);
            $is_active = isset($_POST['banner_is_active']) ? 1 : 0;

            $image_url = '';
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                $image_url = $bannerManager->uploadBannerImage($_FILES['banner_image']);
            }

            if ($image_url && $bannerManager->addBanner($title, $description, $image_url, $link_url, $position, $is_active)) {
                $_SESSION['marketing_message'] = 'Thêm banner thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi thêm banner';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'edit_banner':
            $id = (int)($_POST['banner_id'] ?? 0);
            $title = trim($_POST['banner_title'] ?? '');
            $description = trim($_POST['banner_description'] ?? '');
            $link_url = trim($_POST['banner_link_url'] ?? '');
            $position = (int)($_POST['banner_position'] ?? 0);
            $is_active = isset($_POST['banner_is_active']) ? 1 : 0;

            $banner = $bannerManager->getBannerById($id);
            if (!$banner) {
                $_SESSION['marketing_message'] = 'Banner không tồn tại';
                $_SESSION['marketing_success'] = false;
            } else {
                $image_url = $banner['image_url'];
                if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
                    $new_image_url = $bannerManager->uploadBannerImage($_FILES['banner_image']);
                    if ($new_image_url) {
                        $image_url = $new_image_url;
                    }
                }

                if ($bannerManager->updateBanner($id, $title, $description, $image_url, $link_url, $position, $is_active)) {
                    $_SESSION['marketing_message'] = 'Cập nhật banner thành công';
                    $_SESSION['marketing_success'] = true;
                    $_SESSION['highlight_banner_id'] = $id;
                } else {
                    $_SESSION['marketing_message'] = 'Lỗi khi cập nhật banner';
                    $_SESSION['marketing_success'] = false;
                }
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'delete_banner':
            $id = (int)($_POST['banner_id'] ?? 0);
            if ($bannerManager->deleteBanner($id)) {
                $_SESSION['marketing_message'] = 'Xóa banner thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi xóa banner';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'add_news':
            $title = trim($_POST['news_title'] ?? '');
            $content = trim($_POST['news_content'] ?? '');
            $author = trim($_POST['news_author'] ?? 'Admin');
            $is_published = isset($_POST['news_is_published']) ? 1 : 0;

            if (empty($title) || empty($content)) {
                $_SESSION['marketing_message'] = 'Tiêu đề và nội dung không được để trống';
                $_SESSION['marketing_success'] = false;
            } else {
                $image_url = '';
                if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
                    $image_url = $newsManager->uploadNewsImage($_FILES['news_image']);
                }

                if ($newsManager->addNews($title, $content, $image_url, $author, $is_published)) {
                    $_SESSION['marketing_message'] = 'Thêm tin tức thành công';
                    $_SESSION['marketing_success'] = true;
                } else {
                    $_SESSION['marketing_message'] = 'Lỗi khi thêm tin tức';
                    $_SESSION['marketing_success'] = false;
                }
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'edit_news':
            $id = (int)($_POST['news_id'] ?? 0);
            $title = trim($_POST['news_title'] ?? '');
            $content = trim($_POST['news_content'] ?? '');
            $author = trim($_POST['news_author'] ?? 'Admin');
            $is_published = isset($_POST['news_is_published']) ? 1 : 0;

            $news = $newsManager->getNewsById($id);
            if (!$news) {
                $_SESSION['marketing_message'] = 'Tin tức không tồn tại';
                $_SESSION['marketing_success'] = false;
            } else {
                $image_url = $news['featured_image'];

                if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] === UPLOAD_ERR_OK) {
                    $new_image_url = $newsManager->uploadNewsImage($_FILES['news_image']);
                    if ($new_image_url) {
                        $old_image = $news['featured_image'];
                        if (
                            $old_image !== $new_image_url &&
                            file_exists(__DIR__ . '/../../../../administrator/uploads/' . basename($old_image))
                        ) {
                            @unlink(__DIR__ . '/../../../../administrator/uploads/' . basename($old_image));
                        }
                        $image_url = $new_image_url;
                    }
                }

                if ($newsManager->updateNews($id, $title, $content, $image_url, $author, $is_published)) {
                    $_SESSION['marketing_message'] = 'Cập nhật tin tức thành công';
                    $_SESSION['marketing_success'] = true;
                    $_SESSION['highlight_news_id'] = $id;
                } else {
                    $_SESSION['marketing_message'] = 'Lỗi khi cập nhật tin tức';
                    $_SESSION['marketing_success'] = false;
                }
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'delete_news':
            $id = (int)($_POST['news_id'] ?? 0);
            if ($newsManager->deleteNews($id)) {
                $_SESSION['marketing_message'] = 'Xóa tin tức thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi xóa tin tức';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'add_promotion':
            $title = trim($_POST['promotion_title'] ?? '');
            $description = trim($_POST['promotion_description'] ?? '');
            $discount_percent = floatval($_POST['promotion_discount_percent'] ?? 0);
            $start_date = $_POST['promotion_start_date'] ?? date('Y-m-d');
            $end_date = $_POST['promotion_end_date'] ?? date('Y-m-d', strtotime('+1 month'));
            $is_active = isset($_POST['promotion_is_active']) ? 1 : 0;

            if ($promotionManager->addPromotion($title, $description, $discount_percent, $start_date, $end_date, $is_active)) {
                $_SESSION['marketing_message'] = 'Thêm chương trình ưu đãi thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi thêm chương trình ưu đãi';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'edit_promotion':
            $id = (int)($_POST['promotion_id'] ?? 0);
            $title = trim($_POST['promotion_title'] ?? '');
            $description = trim($_POST['promotion_description'] ?? '');
            $discount_percent = floatval($_POST['promotion_discount_percent'] ?? 0);
            $start_date = $_POST['promotion_start_date'] ?? date('Y-m-d');
            $end_date = $_POST['promotion_end_date'] ?? date('Y-m-d', strtotime('+1 month'));
            $is_active = isset($_POST['promotion_is_active']) ? 1 : 0;

            if ($promotionManager->updatePromotion($id, $title, $description, $discount_percent, $start_date, $end_date, $is_active)) {
                $_SESSION['marketing_message'] = 'Cập nhật chương trình ưu đãi thành công';
                $_SESSION['marketing_success'] = true;
                $_SESSION['highlight_promotion_id'] = $id;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi cập nhật chương trình ưu đãi';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'delete_promotion':
            $id = (int)($_POST['promotion_id'] ?? 0);
            if ($promotionManager->deletePromotion($id)) {
                $_SESSION['marketing_message'] = 'Xóa chương trình ưu đãi thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi xóa chương trình ưu đãi';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'add_blog':
            $title = trim($_POST['blog_title'] ?? '');
            $excerpt = trim($_POST['blog_excerpt'] ?? '');
            $content = trim($_POST['blog_content'] ?? '');
            $status = $_POST['blog_status'] ?? 'draft';

            if (empty($title)) {
                $_SESSION['marketing_message'] = 'Tiêu đề không được để trống';
                $_SESSION['marketing_success'] = false;
            } else {
                $thumbnail = null;
                if (isset($_FILES['blog_thumbnail']) && $_FILES['blog_thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $thumbnail = $pageManager->uploadThumbnail($_FILES['blog_thumbnail']);
                }

                $result = $pageManager->addPage([
                    'type' => 'blog',
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'thumbnail' => $thumbnail,
                    'status' => $status,
                    'author_id' => $username
                ]);

                if ($result) {
                    $_SESSION['marketing_message'] = 'Thêm bài viết thành công';
                    $_SESSION['marketing_success'] = true;
                } else {
                    $_SESSION['marketing_message'] = 'Lỗi khi thêm bài viết';
                    $_SESSION['marketing_success'] = false;
                }
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'edit_blog':
            $id = (int)($_POST['page_id'] ?? 0);
            $title = trim($_POST['blog_title'] ?? '');
            $slug = trim($_POST['blog_slug'] ?? '');
            $excerpt = trim($_POST['blog_excerpt'] ?? '');
            $content = trim($_POST['blog_content'] ?? '');
            $status = $_POST['blog_status'] ?? 'draft';

            $data = [
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $content,
                'status' => $status
            ];

            if (!empty($slug)) {
                $data['slug'] = $slug;
            }

            if (isset($_FILES['blog_thumbnail']) && $_FILES['blog_thumbnail']['error'] === UPLOAD_ERR_OK) {
                $thumbnail = $pageManager->uploadThumbnail($_FILES['blog_thumbnail']);
                if ($thumbnail) {
                    $data['thumbnail'] = $thumbnail;
                }
            }

            if ($pageManager->updatePage($id, $data)) {
                $_SESSION['marketing_message'] = 'Cập nhật bài viết thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi cập nhật bài viết';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'add_page':
            $type = $_POST['page_type'] ?? 'about';
            $title = trim($_POST['page_title'] ?? '');
            $content = trim($_POST['page_content'] ?? '');
            $position = (int)($_POST['page_position'] ?? 0);
            $status = $_POST['page_status'] ?? 'draft';

            if (empty($title)) {
                $_SESSION['marketing_message'] = 'Tiêu đề không được để trống';
                $_SESSION['marketing_success'] = false;
            } else {
                $result = $pageManager->addPage([
                    'type' => $type,
                    'title' => $title,
                    'content' => $content,
                    'position' => $position,
                    'status' => $status,
                    'author_id' => $username
                ]);

                if ($result) {
                    $_SESSION['marketing_message'] = 'Thêm trang thành công';
                    $_SESSION['marketing_success'] = true;
                } else {
                    $_SESSION['marketing_message'] = 'Lỗi khi thêm trang';
                    $_SESSION['marketing_success'] = false;
                }
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'edit_page':
            $id = (int)($_POST['page_id'] ?? 0);
            $type = $_POST['page_type'] ?? 'about';
            $title = trim($_POST['page_title'] ?? '');
            $slug = trim($_POST['page_slug'] ?? '');
            $content = trim($_POST['page_content'] ?? '');
            $position = (int)($_POST['page_position'] ?? 0);
            $status = $_POST['page_status'] ?? 'draft';

            $data = [
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'position' => $position,
                'status' => $status
            ];

            if (!empty($slug)) {
                $data['slug'] = $slug;
            }

            if ($pageManager->updatePage($id, $data)) {
                $_SESSION['marketing_message'] = 'Cập nhật trang thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi cập nhật trang';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();

        case 'delete_page':
            $id = (int)($_POST['page_id'] ?? 0);
            if ($pageManager->deletePage($id)) {
                $_SESSION['marketing_message'] = 'Xóa trang thành công';
                $_SESSION['marketing_success'] = true;
            } else {
                $_SESSION['marketing_message'] = 'Lỗi khi xóa trang';
                $_SESSION['marketing_success'] = false;
            }
            header('Location: ?page=marketing_content&tab=' . $redirect_tab);
            exit();
    }
}
