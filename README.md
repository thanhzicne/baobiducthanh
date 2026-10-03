# Bao bì Đức Thành

Website giới thiệu và kinh doanh các sản phẩm bao bì của Công ty TNHH Bao bì Đức Thành. Khách hàng có thể xem danh mục, gửi yêu cầu báo giá hoặc đặt hàng trực tuyến; nhân viên quản trị nội dung và xử lý yêu cầu trên trang quản trị.

## Sản phẩm

Website hỗ trợ giới thiệu các nhóm sản phẩm như thùng carton 3 lớp, thùng carton 5 lớp, màng PE và băng keo. Mỗi sản phẩm có trang chi tiết với hình ảnh, mô tả, thông số, giá khởi điểm, đơn vị tính và số lượng đặt tối thiểu.

## Chức năng

- Danh sách sản phẩm, lọc theo danh mục, phân trang và sắp xếp.
- Tìm kiếm sản phẩm và tin tức.
- Giỏ báo giá, cập nhật số lượng, gửi yêu cầu báo giá và theo dõi trạng thái.
- Đặt hàng, nhập thông tin giao hàng và chọn phương thức thanh toán.
- Tài khoản khách hàng: đăng ký, đăng nhập, quản lý thông tin và xem lịch sử đơn hàng.
- Chat trực tuyến giữa khách hàng và quản trị viên.
- Trang quản trị để quản lý sản phẩm, danh mục, báo giá, liên hệ, tin tức, trang nội dung, banner, phản hồi, popup, cài đặt và tài khoản.
- Các trang giới thiệu, liên hệ, tin tức, chính sách và nội dung tĩnh.

## Công nghệ

- PHP 8.1 trở lên.
- MySQL hoặc MariaDB, truy cập bằng PDO.
- Apache có hỗ trợ `mod_rewrite` và `.htaccess`.
- HTML, CSS và JavaScript thuần cho giao diện.

## Chạy trên máy local

1. Cài XAMPP hoặc môi trường tương đương có PHP, Apache và MySQL.
2. Đặt thư mục dự án vào document root, ví dụ `C:\\xampp\\htdocs\\baobiducthanh`.
3. Tạo database MySQL và khôi phục schema cùng dữ liệu từ bản sao lưu của dự án.
4. Tạo file `includes/config.php` cho môi trường local, khai báo các hằng cấu hình database và `SITE_URL` phù hợp. File này được `.gitignore` loại khỏi Git; không đưa mật khẩu thật lên repository.
5. Bật Apache và MySQL, sau đó mở `http://localhost/baobiducthanh`.

> Repository hiện không kèm file dump SQL. Cần có bản sao lưu database riêng để khôi phục các bảng sản phẩm, danh mục, quản trị và nội dung. Một số bảng liên quan khách hàng/chat được ứng dụng khởi tạo khi chạy, nhưng điều đó không thay thế database chính.

## Triển khai

Dự án cần máy chủ chạy PHP và MySQL/MariaDB; có thể dùng hosting PHP/MySQL. Sau khi tạo database trên hosting, nhập bản sao lưu, tải source và thư mục upload lên máy chủ, rồi cấu hình `includes/config.php` với thông tin của môi trường production. Cập nhật `SITE_URL` theo domain thật và bật HTTPS.

Không commit `includes/config.php`, mật khẩu database, log hoặc dữ liệu tải lên. Các đường dẫn `includes/config.php`, `logs/`, `uploads/` và `database.sql` hiện được khai báo trong `.gitignore`. Giữ bản sao lưu database và ảnh upload ở nơi an toàn.

## Đường dẫn chính

| Đường dẫn          | Nội dung                        |
| ------------------ | ------------------------------- |
| `/`                | Trang chủ                       |
| `/san-pham`        | Danh sách sản phẩm              |
| `/danh-muc/{slug}` | Sản phẩm theo danh mục          |
| `/san-pham/{slug}` | Chi tiết sản phẩm               |
| `/gio-bao-gia`     | Giỏ báo giá                     |
| `/thanh-toan`      | Thông tin giao hàng và đặt hàng |
| `/dang-nhap`       | Đăng nhập khách hàng            |
| `/dang-ky`         | Đăng ký khách hàng              |
| `/tin-tuc`         | Danh sách tin tức               |
| `/lien-he`         | Trang liên hệ                   |
| `/admin/`          | Đăng nhập quản trị              |

## Cấu trúc thư mục

- `admin/`: trang quản trị.
- `api/`: xử lý báo giá, chat, liên hệ và đăng ký nhận tin.
- `assets/`: CSS, JavaScript và hình ảnh giao diện.
- `includes/`: cấu hình, kết nối database, helper và thành phần dùng chung.
- `pages/`: trang sản phẩm, tài khoản, giỏ hàng, thanh toán và nội dung.
- `uploads/`: ảnh và tệp do website tải lên; không nên đưa dữ liệu production vào Git.
