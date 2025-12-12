<?php
require_once __DIR__ . '/config/local_config.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/NewsManager.php';

SessionManager::start();

$newsManager = new NewsManager();
$allNews = $newsManager->getPublishedNews(100); // Lấy tất cả tin đã xuất bản
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin tức - Cửa Hàng Điện Thoại</title>
    <base href="/lequocanh/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public_files/mycss.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item active">Tin tức</li>
            </ol>
        </nav>
        
        <h1 class="mb-4">
            <i class="fas fa-newspaper text-primary"></i> Tin tức
        </h1>
        
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($allNews as $news): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <?php if ($news['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($news['image_url']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>"
                         style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
                        <p class="card-text text-muted">
                            <?php 
                            $content = strip_tags($news['content']);
                            echo htmlspecialchars(mb_substr($content, 0, 150)) . '...'; 
                            ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">
                                <i class="far fa-user"></i> <?php echo htmlspecialchars($news['author']); ?>
                            </small>
                            <small class="text-muted">
                                <i class="far fa-clock"></i> 
                                <?php echo date('d/m/Y', strtotime($news['published_at'])); ?>
                            </small>
                        </div>
                        <a href="news_detail.php?id=<?php echo $news['id']; ?>" class="btn btn-primary btn-sm">
                            Đọc thêm <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($allNews)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Chưa có tin tức nào được xuất bản.
        </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại trang chủ
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
