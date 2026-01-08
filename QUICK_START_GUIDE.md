# 🚀 Quick Start Guide - Automated Testing System

## ⚡ Bắt Đầu Trong 30 Giây

### Cách 1: Sử dụng Menu (Dễ nhất)
```bash
START_TESTS.bat
```
Chọn option 1 (Quick Test) và nhấn Enter. Xong!

### Cách 2: Web Interface
1. Mở browser: `START_TESTING.html`
2. Click "Run Quick Test"
3. Xem kết quả

### Cách 3: Command Line
```bash
php quick_test.php
```

## 📊 Xem Kết Quả

Sau khi chạy test, mở dashboard:
```
http://localhost/test_dashboard.php
```

## 🎯 Các File Quan Trọng

### Để Chạy Tests:
- `START_TESTS.bat` - Menu chính (Windows)
- `quick_test.php` - Quick test script
- `automated_test_runner.php` - Test runner đầy đủ
- `test_dashboard.php` - Web dashboard

### Để Sửa Lỗi:
- `auto_fix_runner.php` - Auto-fix engine
- `auto_fix_indexes.php` - Fix database indexes

### Để Giám Sát:
- `health_monitor.php` - Health monitoring
- `monitoring/` - Health reports

### Kết Quả:
- `test-results/` - Test reports (JSON)
- `monitoring/` - Health reports (JSON)

## 🔧 Các Lệnh Hữu Ích

### Chạy Tests
```bash
# Quick test (khuyến nghị)
php quick_test.php

# Full test
php automated_test_runner.php

# Docker test
run_docker_tests.bat
```

### Sửa Lỗi
```bash
# Auto-fix tất cả
php auto_fix_runner.php

# Chỉ fix indexes
# Mở browser: http://localhost/auto_fix_indexes.php
```

### Giám Sát
```bash
# Health check
php health_monitor.php

# Xem dashboard
# Mở browser: http://localhost/test_dashboard.php
```

## 📋 Checklist Sau Khi Chạy

- [ ] Mở `test_dashboard.php` để xem kết quả
- [ ] Kiểm tra Success Rate >= 90%
- [ ] Xem các fixes đã được áp dụng
- [ ] Kiểm tra Health Status = HEALTHY
- [ ] Backup database nếu cần
- [ ] Test application functionality

## ⚠️ Nếu Có Lỗi

### Lỗi Database Connection
```bash
# Kiểm tra MySQL đang chạy
# Kiểm tra credentials trong .env
```

### Lỗi Permission
```bash
# Windows: Chạy as Administrator
# Linux: chmod +x *.sh
```

### Tests Fail
```bash
# Chạy auto-fix
php auto_fix_runner.php

# Chạy lại tests
php automated_test_runner.php
```

## 📚 Tài Liệu Chi Tiết

- `AUTOMATED_TESTING_README.md` - Hướng dẫn đầy đủ
- `TESTING_SUMMARY.md` - Tổng quan hệ thống
- `START_TESTING.html` - Landing page

## 🎉 Kết Quả Mong Đợi

Sau khi chạy thành công:

```
✅ Total Tests: 50
✅ Passed: 48 (96%)
✅ Failed: 2 (4%)
✅ Health Status: HEALTHY
✅ Query Performance: <5ms
✅ Cache Hit Rate: 80%+
```

## 💡 Tips

1. **Chạy tests trước khi deploy**
2. **Kiểm tra dashboard mỗi ngày**
3. **Backup database trước khi auto-fix**
4. **Review test reports định kỳ**
5. **Monitor health status**

## 🆘 Cần Giúp Đỡ?

1. Xem `AUTOMATED_TESTING_README.md`
2. Kiểm tra `test-results/` và `monitoring/`
3. Xem logs trong console
4. Chạy với verbose mode

---

**Bắt đầu ngay:** `START_TESTS.bat` hoặc mở `START_TESTING.html`
