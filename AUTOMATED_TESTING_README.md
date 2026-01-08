# 🧪 Automated Testing System

Hệ thống kiểm tra và sửa lỗi tự động với Docker cho dự án LeQuocAnh.

## 📋 Tính năng

### 1. **Automated Test Runner** (`automated_test_runner.php`)
- ✅ Kiểm tra cấu trúc database
- ✅ Kiểm tra indexes
- ✅ Kiểm tra optimization files
- ✅ Kiểm tra Service classes
- ✅ Kiểm tra Cache system
- ✅ Kiểm tra Query Builder
- ✅ Kiểm tra Performance
- ✅ Kiểm tra Security
- 📊 Tạo báo cáo JSON chi tiết

### 2. **Auto-Fix Runner** (`auto_fix_runner.php`)
- 🔧 Tự động sửa lỗi indexes
- 🔧 Tự động tối ưu tables
- 🔧 Tự động sửa lỗi performance
- 📝 Ghi log chi tiết các fixes đã áp dụng

### 3. **Health Monitor** (`health_monitor.php`)
- 🏥 Giám sát database connection
- 🏥 Giám sát database size
- 🏥 Giám sát table health
- 🏥 Giám sát query performance
- 🏥 Giám sát cache health
- 🏥 Giám sát disk space
- 🏥 Giám sát memory usage
- 🚨 Cảnh báo khi có vấn đề

### 4. **Test Dashboard** (`test_dashboard.php`)
- 📊 Hiển thị kết quả tests
- 📊 Hiển thị kết quả auto-fixes
- 📊 Hiển thị health status
- ▶️ Chạy tests từ web interface
- 🔧 Áp dụng fixes từ web interface

## 🚀 Cách sử dụng

### Phương pháp 1: Sử dụng Docker (Khuyến nghị)

#### Bước 1: Cài đặt Docker
- Tải và cài đặt Docker Desktop: https://www.docker.com/products/docker-desktop

#### Bước 2: Chạy tests với Docker
```bash
# Windows
run_docker_tests.bat

# Linux/Mac
chmod +x run_docker_tests.sh
./run_docker_tests.sh
```

#### Bước 3: Xem kết quả
- Mở trình duyệt: `http://localhost/test_dashboard.php`
- Hoặc xem file JSON trong thư mục `test-results/`

### Phương pháp 2: Chạy trực tiếp (Không dùng Docker)

#### Bước 1: Cấu hình database
Tạo file `.env` hoặc set environment variables:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=sales_management
DB_USER=root
DB_PASS=your_password
```

#### Bước 2: Chạy tests
```bash
php automated_test_runner.php
```

#### Bước 3: Áp dụng fixes (nếu có lỗi)
```bash
php auto_fix_runner.php
```

#### Bước 4: Chạy health monitor
```bash
php health_monitor.php
```

### Phương pháp 3: Sử dụng Web Dashboard

1. Mở trình duyệt: `http://localhost/test_dashboard.php`
2. Click nút "▶️ Run Tests" để chạy tests
3. Click nút "🔧 Apply Fixes" để sửa lỗi
4. Click nút "🏥 Health Check" để kiểm tra sức khỏe hệ thống

## 📁 Cấu trúc thư mục

```
project/
├── automated_test_runner.php      # Test runner chính
├── auto_fix_runner.php            # Auto-fix runner
├── health_monitor.php             # Health monitor
├── test_dashboard.php             # Web dashboard
├── docker-compose.test.yml        # Docker compose config
├── Dockerfile.test                # Docker image config
├── run_docker_tests.bat           # Windows script
├── test-results/                  # Kết quả tests (JSON)
│   ├── test-report-*.json
│   └── fix-report-*.json
└── monitoring/                    # Health monitoring data
    └── health-*.json
```

## 🔄 CI/CD Integration

### GitHub Actions
File `.github/workflows/automated-tests.yml` đã được tạo sẵn.

Workflow sẽ tự động:
1. Chạy tests khi push code
2. Áp dụng auto-fixes nếu tests fail
3. Chạy lại tests sau khi fix
4. Upload kết quả tests
5. Chạy health check
6. Gửi thông báo nếu có lỗi

### Chạy tests định kỳ
Workflow sẽ tự động chạy mỗi ngày lúc 2 giờ sáng.

## 📊 Đọc kết quả

### Test Report (`test-report-*.json`)
```json
{
  "timestamp": "2025-12-22 14:30:00",
  "duration": 5.23,
  "summary": {
    "total": 50,
    "passed": 48,
    "failed": 2,
    "percentage": 96
  },
  "results": [...],
  "errors": [...],
  "fixes_needed": [...]
}
```

### Fix Report (`fix-report-*.json`)
```json
{
  "timestamp": "2025-12-22 14:35:00",
  "total_fixes": 2,
  "applied": [
    "Created index idx_hanghoa_price on hanghoa.giathamkhao"
  ],
  "failed": []
}
```

### Health Report (`health-*.json`)
```json
{
  "timestamp": "2025-12-22 14:40:00",
  "health": "HEALTHY",
  "checks": [...],
  "alerts": []
}
```

## 🔧 Tùy chỉnh

### Thêm test mới
Chỉnh sửa `automated_test_runner.php`:
```php
private function testMyFeature() {
    $this->log("\n🔍 Testing My Feature...");
    
    // Your test logic here
    if ($condition) {
        $this->pass("Feature working");
    } else {
        $this->fail("Feature broken");
    }
}
```

### Thêm auto-fix mới
Chỉnh sửa `auto_fix_runner.php`:
```php
private function fixMyIssue($fix) {
    $this->log("🔨 Fixing my issue");
    
    try {
        // Your fix logic here
        $this->applied[] = "Fixed my issue";
        $this->log("✅ Fixed successfully");
    } catch (Exception $e) {
        $this->failed[] = "Failed to fix: " . $e->getMessage();
    }
}
```

### Thêm health check mới
Chỉnh sửa `health_monitor.php`:
```php
private function checkMyMetric() {
    try {
        // Your check logic here
        $this->pass("Metric is healthy");
    } catch (Exception $e) {
        $this->fail("Metric has issues");
        $this->alert("Critical issue detected");
    }
}
```

## 🚨 Troubleshooting

### Docker không khởi động được
```bash
# Kiểm tra Docker đang chạy
docker ps

# Xem logs
docker-compose -f docker-compose.test.yml logs

# Dọn dẹp và thử lại
docker-compose -f docker-compose.test.yml down -v
docker-compose -f docker-compose.test.yml up --build
```

### Tests fail liên tục
1. Kiểm tra database connection
2. Kiểm tra file permissions
3. Xem chi tiết lỗi trong `test-results/`
4. Chạy `auto_fix_runner.php` để sửa tự động

### Health monitor báo lỗi
1. Kiểm tra disk space
2. Kiểm tra memory usage
3. Kiểm tra database size
4. Xem chi tiết trong `monitoring/`

## 📚 Tài liệu tham khảo

- Docker Documentation: https://docs.docker.com/
- GitHub Actions: https://docs.github.com/en/actions
- PHP PDO: https://www.php.net/manual/en/book.pdo.php

## 🎯 Best Practices

1. **Chạy tests trước khi deploy**
   ```bash
   php automated_test_runner.php
   ```

2. **Kiểm tra health định kỳ**
   - Mỗi ngày: Xem dashboard
   - Mỗi tuần: Review health reports
   - Mỗi tháng: Optimize database

3. **Backup trước khi auto-fix**
   ```bash
   # Backup database
   mysqldump -u root -p sales_management > backup.sql
   
   # Run auto-fix
   php auto_fix_runner.php
   ```

4. **Monitor performance**
   - Theo dõi query time
   - Theo dõi cache hit rate
   - Theo dõi memory usage

## 🤝 Đóng góp

Nếu bạn muốn thêm tests hoặc fixes mới:
1. Fork repository
2. Tạo branch mới
3. Thêm tests/fixes
4. Tạo Pull Request

## 📝 License

MIT License - Xem file LICENSE để biết thêm chi tiết.

## 📞 Liên hệ

Nếu có vấn đề hoặc câu hỏi, vui lòng tạo issue trên GitHub.

---

**Lưu ý:** Hệ thống này được thiết kế để tự động phát hiện và sửa lỗi. Tuy nhiên, một số lỗi phức tạp có thể cần can thiệp thủ công.
