# PTConnect Assignment Demo

## Tài khoản test

- Admin: `admin@ptconnect.test` / `password`
- Teacher: `teacher@ptconnect.test` / `password`
- Assistant: `assistant@ptconnect.test` / `password`
- Parent HS100001: `parent.hs100001@ptconnect.test` / `12345678`

## Dữ liệu demo

- Lớp: `10A1`
- Bài tập demo: `Bài tập demo Sinh học 10A1`
- File đính kèm: `demo-sinh-hoc-10A1.txt`
- Bài nộp demo: `demo-nop-bai-hs100001.txt`

## Cách test nhanh

1. Đăng nhập teacher hoặc admin.
2. Mở menu `Bài tập`.
3. Bấm tải file đính kèm của bài tập demo.
4. Đăng nhập parent của `HS100001`.
5. Vào `Bài tập` để thấy bài tập của con.
6. Tải bài nộp demo để kiểm tra luồng download.

## Ghi chú

- Demo dùng file text nhỏ để bạn test nhanh upload/download.
- Nếu đã chạy seed trước đó, có thể chạy lại seeder để cập nhật demo data:

```bash
docker exec -it ptconnect-backend php artisan db:seed
```
