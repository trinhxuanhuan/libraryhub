# Nhật ký học LibraryHub
## Khung Laravel và giao diện chung
### Đã thực hiện
- Khởi tạo Laravel 12, cấu hình kết nối MySQL và chạy migration mặc định.
- Thiết lập Git với main, develop và feature/base-layout.
- Tích hợp Bootstrap 5 qua Vite.
- Tạo layout chung có navbar, footer và vùng hiển thị thông báo.
- Tạo HomeController, AboutController và hai trang Blade.
- Khai báo route có tên home, about; truyền dữ liệu mẫu từ controller sang view.
### Đã kiểm tra
- npm run build chạy thành công.
- route:list hiển thị đúng hai route Home và About.
### Cần củng cố
- Luồng request đi qua route, controller và view.
- Cách @extends, @section và @yield ghép nội dung vào layout.
### Kế hoạch tiếp theo
- Kiểm tra giao diện Home/About và gửi PR vào develop.
- Học migration, seeder và Tinker để xây dựng CSDL thư viện.

## Cấu trúc CSDL và dữ liệu mẫu

### Đã thực hiện
- Tạo migration cho categories, authors, books và author_book.
- Liên kết sách với thể loại bằng khóa ngoại.
- Tạo bảng nối sách và tác giả.
- Tạo seeder cho 8 thể loại và 10 tác giả.
- Đăng ký các seeder trong DatabaseSeeder.
### Đã kiểm tra
- Các bảng đã được tạo trong MySQL và xem bằng DBeaver.
- Tinker đếm được 8 thể loại và 10 tác giả.
- Tìm được tên tác giả bằng truy vấn Laravel.
### Cần củng cố
- Khóa ngoại và quan hệ nhiều–nhiều.
- Phân biệt migration với seeder.
### Kế hoạch tiếp theo
- Thực hành Eloquent và CRUD thể loại.
