<?php
// Nhúng file đầu trang (bao gồm cấu hình kết nối CSDL $conn, thẻ <head>, thanh điều hướng chung,...)
require_once 'dung-chung/dau-trang.php';

// Kiểm tra xem trên URL có truyền tham số 'id' không. Nếu có thì ép kiểu về số nguyên, nếu không có thì mặc định lấy id = 1
$maSach = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Tạo câu lệnh SQL lấy thông tin chi tiết của sách, kết hợp (LEFT JOIN) với các bảng: 
// danh mục (dm), nhà xuất bản (nxb), và bảng trung gian sachtacgia (stg) cùng bảng tác giả (tg)
$sql_detail = "SELECT s.*, dm.tenDanhMuc, nxb.tenNXB, tg.tenTacGia 
               FROM sach s 
               LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc 
               LEFT JOIN nhaxuatban nxb ON s.maNXB = nxb.maNXB 
               LEFT JOIN sachtacgia stg ON s.maSach = stg.maSach 
               LEFT JOIN tacgia tg ON stg.maTacGia = tg.maTacGia 
               WHERE s.maSach = $maSach LIMIT 1";

// Thực thi câu lệnh truy vấn chi tiết sách
$res_detail = $conn->query($sql_detail);

// Kiểm tra nếu truy vấn lỗi hoặc không tìm thấy sách nào theo ID, 
// thì chạy câu truy vấn dự phòng lấy đại diện cuốn sách đầu tiên trong database
if (!$res_detail || $res_detail->num_rows == 0) {
    $res_detail = $conn->query("SELECT s.*, dm.tenDanhMuc, nxb.tenNXB, tg.tenTacGia 
                                FROM sach s 
                                LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc 
                                LEFT JOIN nhaxuatban nxb ON s.maNXB = nxb.maNXB 
                                LEFT JOIN sachtacgia stg ON s.maSach = stg.maSach 
                                LEFT JOIN tacgia tg ON stg.maTacGia = tg.maTacGia 
                                LIMIT 1");
}

// Chuyển kết quả truy vấn thành một mảng kết hợp để dễ dàng hiển thị thông tin sách
$sach = $res_detail->fetch_assoc();
$id_hien_tai = $sach['maSach'];   // Lưu lại mã sách hiện tại
$id_danh_muc = $sach['maDanhMuc']; // Lưu lại mã danh mục để truy vấn sách liên quan

// Xác định số lượng tồn kho của sách (hỗ trợ kiểm tra cả 2 tên cột: soLuong hoặc soLuongTon)
$soLuongTon = isset($sach['soLuong']) ? (int)$sach['soLuong'] : (isset($sach['soLuongTon']) ? (int)$sach['soLuongTon'] : 0);

// Câu lệnh SQL lấy 4 sản phẩm liên quan (cùng danh mục, khác mã sách hiện tại, và sách đang hoạt động trangThai = 1)
$sql_lienquan = "SELECT * FROM sach WHERE maDanhMuc = '$id_danh_muc' AND maSach != '$id_hien_tai' AND trangThai = 1 LIMIT 4";
$res_lienquan = $conn->query($sql_lienquan);

// Nếu không tìm thấy sách liên quan cùng danh mục, lấy 4 cuốn sách bất kỳ khác đang mở bán làm dự phòng
if (!$res_lienquan || $res_lienquan->num_rows == 0) {
    $res_lienquan = $conn->query("SELECT * FROM sach WHERE maSach != '$id_hien_tai' AND trangThai = 1 LIMIT 4");
}
?>
