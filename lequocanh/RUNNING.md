# Hướng Dẫn Chạy Ứng Dụng với Cloudflare Tunnel

## Bước 1: Chạy Docker containers

Chạy lệnh sau trong thư mục dự án (d:/PHP_WS):

```
docker-compose up
```

## Bước 2: Chạy Cloudflare Tunnel

Trong một terminal khác, chạy lệnh sau để kết nối Cloudflare tunnel đến Docker container:

```
.\cloudflared.exe --config cloudflared-config.yml tunnel --url http://localhost:18080
```

## Truy cập ứng dụng

Sau khi cả Docker và Cloudflare tunnel đều chạy:
- Ứng dụng chính: https://superior-downloadable-wayne-buildings.trycloudflare.com
- phpMyAdmin: http://localhost:888
- Prometheus monitoring: http://localhost:9090
- Grafana dashboard: http://localhost:3000

## Chạy với Docker (chế độ nền)

Nếu muốn chạy Docker ở chế độ nền:

```
docker-compose up -d
```

Sau đó chạy tunnel:
```
.\cloudflared.exe --config cloudflared-config.yml tunnel --url http://localhost:18080
```

## Cấu hình hiện tại

Hiện tại, ứng dụng được cấu hình để:
- Chuyển hướng đến Cloudflare tunnel (FORCE_TUNNEL=true)
- URL cơ sở là https://superior-downloadable-wayne-buildings.trycloudflare.com

## Troubleshooting

Nếu vẫn không truy cập được:

1. Kiểm tra các container có đang chạy không:
   ```
   docker-compose ps
   ```

2. Kiểm tra log của các container:
   ```
   docker-compose logs web
   docker-compose logs nginx
   ```

3. Đảm bảo các cổng 18080, 888, 9090, 3000 không bị chiếm dụng

4. Kiểm tra Cloudflare tunnel có đang chạy không