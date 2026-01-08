<?php

require_once './elements_LQA/mod/database.php';
require_once './elements_LQA/mod/PasswordHelper.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Migration Password từ Plain Text sang Bcrypt</h2>";
echo "<p>Bắt đầu quá trình migration...</p>";

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT iduser, username, password FROM user";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalUsers = count($users);
    $migratedCount = 0;
    $alreadyHashedCount = 0;
    $errorCount = 0;
    
    echo "<p>Tìm thấy <strong>$totalUsers</strong> user trong database.</p>";
    echo "<hr>";
    
    foreach ($users as $user) {
        $iduser = $user['iduser'];
        $username = $user['username'];
        $password = $user['password'];
        
        echo "<p><strong>User:</strong> $username (ID: $iduser)</p>";
        
        if (PasswordHelper::isPlainText($password)) {
            echo "<p style='color: orange;'>→ Password là plain text, đang hash...</p>";
            
            try {

                $hashedPassword = PasswordHelper::hash($password);
                
                $updateSql = "UPDATE user SET password = ? WHERE iduser = ?";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->execute([$hashedPassword, $iduser]);
                
                echo "<p style='color: green;'>✓ Đã hash thành công!</p>";
                $migratedCount++;
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</p>";
                $errorCount++;
            }
        } else {
            echo "<p style='color: blue;'>→ Password đã được hash trước đó.</p>";
            $alreadyHashedCount++;
        }
        
        echo "<hr>";
    }
    
    echo "<h3>Kết quả Migration:</h3>";
    echo "<ul>";
    echo "<li><strong>Tổng số user:</strong> $totalUsers</li>";
    echo "<li style='color: green;'><strong>Đã migration:</strong> $migratedCount</li>";
    echo "<li style='color: blue;'><strong>Đã hash trước đó:</strong> $alreadyHashedCount</li>";
    echo "<li style='color: red;'><strong>Lỗi:</strong> $errorCount</li>";
    echo "</ul>";
    
    if ($migratedCount > 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Migration hoàn tất thành công!</p>";
        echo "<p><strong>Lưu ý:</strong> Tất cả password đã được hash bằng Bcrypt. Người dùng có thể đăng nhập bình thường với password cũ của họ.</p>";
    } else {
        echo "<p style='color: blue;'>Không có password nào cần migration.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Lỗi nghiêm trọng:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Quay lại trang chủ</a></p>";
?>
