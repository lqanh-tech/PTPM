<?php
require_once __DIR__ . '/config/local_config.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/sessionManager.php';
require_once __DIR__ . '/administrator/elements_LQA/mod/NewsManager.php';

SessionManager::start();

$newsManager = new NewsManager();
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$news = $newsManager->getNewsById($newsId);

if (!$news) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - Cửa Hàng Điện Thoại</title>
    <base href="/lequocanh/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="public_files/mycss.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                        <li class="breadcrumb-item active">Tin tức</li>
                    </ol>
                </nav>
                
                <article class="card shadow-sm">
                    <?php if ($news['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($news['image_url']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>">
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h1 class="card-title mb-3"><?php echo htmlspecialchars($news['title']); ?></h1>
                        
                        <div class="text-muted mb-4">
                            <i class="far fa-user"></i> <?php echo htmlspecialchars($news['author']); ?>
                            <span class="mx-2">|</span>
                            <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($news['published_at'])); ?>
                        </div>
                        
                        <div class="news-content">
                            <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                        </div>
                    </div>
                </article>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Quay lại trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
