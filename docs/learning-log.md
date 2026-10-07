# Nhật ký học LibraryHub

## Khung Laravel và giao diện chung

### Đã thực hiện
- Khởi tạo Laravel 12, cấu hình kết nối MySQL và chạy migration mặc định.
- Thiết lập Git với main, develop và feature/base-layout.
- Tích hợp Bootstrap 5 qua Vite.
- Tạo layout chung có navbar, footer và vùng hiển thị thông báo.
- Tạo HomeController, AboutController và hai trang Blade.
- Khai báo route có tên home, about; truyền dữ liệu mẫu từ controller sang view.
- Push code và tạo PR vào develop để review.

### Đã kiểm tra
- npm run build chạy thành công.
- route:list hiển thị đúng hai route Home và About.

### Cần củng cố
- Luồng request đi qua route, controller và view.
- Cách @extends, @section và @yield ghép nội dung vào layout.

## Cấu trúc CSDL và dữ liệu mẫu

### Đã thực hiện
- Tạo migration cho categories, authors, books và author_book.
- Liên kết sách với thể loại bằng khóa ngoại.
- Tạo bảng nối sách và tác giả.
- Tạo seeder cho 8 thể loại và 10 tác giả.
- Đăng ký các seeder trong DatabaseSeeder.
- Push code và tạo PR phần CSDL để review.

### Đã kiểm tra
- Các bảng đã được tạo trong MySQL và xem bằng DBeaver.
- Tinker đếm được 8 thể loại và 10 tác giả.
- Tìm được tác giả Nam Cao bằng truy vấn Laravel.
- Quiz tuần 1: 19/20 câu đúng.

### Cần củng cố
- Khóa ngoại và quan hệ nhiều–nhiều.
- Phân biệt migration với seeder.
- Cách xây dựng truy vấn CSDL bằng Laravel.

## Kế hoạch tiếp theo
- Thực hành Eloquent và CRUD thể loại.
- Củng cố luồng route–controller–model–view qua chức năng thực tế.
- Xử lý góp ý từ các PR.