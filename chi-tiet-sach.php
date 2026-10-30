1. Phần Xử Lý Logic PHP (Kết nối, Truy vấn dữ liệu tối ưu với COALESCE)

<?php
// Nhúng file đầu trang (bao gồm cấu hình kết nối CSDL $conn, bắt đầu session, thanh điều hướng, thẻ <head>,...)
require_once 'dung-chung/dau-trang.php';

// Kiểm tra xem trên URL có truyền tham số 'id' không. Nếu có thì ép kiểu về số nguyên, nếu không có thì mặc định lấy id = 1
$maSach = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Gom nhóm lấy chi tiết sách tối ưu với COALESCE: 
// Dùng COALESCE(s.soLuongTon, s.soLuong, 0) để tự động lấy cột tồn kho có dữ liệu (ưu tiên soLuongTon, nếu không có thì lấy soLuong, nếu không có nữa thì trả về 0)
$sql_detail = "SELECT s.*, dm.tenDanhMuc, nxb.tenNXB, tg.tenTacGia, 
               COALESCE(s.soLuongTon, s.soLuong, 0) as tonKho 
               FROM sach s 
               LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc 
               LEFT JOIN nhaxuatban nxb ON s.maNXB = nxb.maNXB 
               LEFT JOIN sachtacgia stg ON s.maSach = stg.maSach 
               LEFT JOIN tacgia tg ON stg.maTacGia = tg.maTacGia 
               WHERE s.maSach = $maSach LIMIT 1";

// Thực thi câu lệnh truy vấn chi tiết sách
$res_detail = $conn->query($sql_detail);

// Kiểm tra nếu truy vấn lỗi hoặc không tìm thấy sách, chạy truy vấn dự phòng lấy đại diện cuốn sách đầu tiên trong CSDL
if (!$res_detail || $res_detail->num_rows == 0) {
    $res_detail = $conn->query("SELECT s.*, dm.tenDanhMuc, nxb.tenNXB, tg.tenTacGia, COALESCE(s.soLuongTon, s.soLuong, 0) as tonKho FROM sach s LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc LEFT JOIN nhaxuatban nxb ON s.maNXB = nxb.maNXB LEFT JOIN sachtacgia stg ON s.maSach = stg.maSach LEFT JOIN tacgia tg ON stg.maTacGia = tg.maTacGia LIMIT 1");
}

// Chuyển kết quả truy vấn thành mảng kết hợp
$sach = $res_detail->fetch_assoc();
$soLuongTon = (int)$sach['tonKho'];  // Lấy giá trị tồn kho chuẩn hóa từ COALESCE
$id_hien_tai = $sach['maSach'];     // Lưu lại mã sách hiện tại
$id_danh_muc = $sach['maDanhMuc'];   // Lưu lại mã danh mục để truy vấn sách liên quan

// Câu lệnh SQL lấy 4 sản phẩm liên quan (cùng danh mục, khác sách hiện tại, và đang mở bán trangThai = 1)
$sql_lienquan = "SELECT * FROM sach WHERE maDanhMuc = '$id_danh_muc' AND maSach != '$id_hien_tai' AND trangThai = 1 LIMIT 4";
$res_lienquan = $conn->query($sql_lienquan);

// Nếu không tìm thấy sách liên quan cùng danh mục, lấy 4 cuốn sách bất kỳ khác đang mở bán làm dự phòng
if (!$res_lienquan || $res_lienquan->num_rows == 0) {
    $res_lienquan = $conn->query("SELECT * FROM sach WHERE maSach != '$id_hien_tai' AND trangThai = 1 LIMIT 4");
}
?>

2. Phần Giao Diện HTML & Hiển Thị Thông Tin Sách

<!-- Thanh điều hướng breadcrumb (đường dẫn phân cấp trang) -->
    <nav style="font-size: 13px; color: #55635D; display: flex; flex-wrap: wrap; gap: 8px; padding-bottom: 24px">
      <a href="index.php">Trang chủ</a> <span>/</span>
      <!-- Link dẫn tới danh sách sách theo danh mục hiện tại -->
      <a href="danh-sach-sach.php?maDanhMuc=<?php echo $sach['maDanhMuc']; ?>"><?php echo htmlspecialchars($sach['tenDanhMuc'] ? $sach['tenDanhMuc'] : 'Sách chọn lọc'); ?></a> <span>/</span>
      <!-- Tên sách hiện tại -->
      <span style="color: #14201C; font-weight: 600"><?php echo htmlspecialchars($sach['tenSach']); ?></span>
    </nav>

    <!-- Khung nội dung chính chia làm 2 cột (Ảnh bên trái, thông tin bên phải) -->
    <div style="display: flex; flex-wrap: wrap; gap: 48px; align-items: flex-start">
      
      <!-- Cột ảnh bìa sách -->
      <div style="flex: 1 1 360px; min-width: 0; display: flex; flex-direction: column; gap: 16px; max-width: 480px">
        <div style="aspect-ratio: 3/4; border-radius: 8px; overflow: hidden; background: #eee; box-shadow: 0 18px 40px rgba(20,32,28,0.2)">
            <!-- Hiển thị hình ảnh sách kèm ảnh dự phòng (onerror) nếu file ảnh không tồn tại -->
            <img src="tai-nguyen/hinh-anh/<?php echo $sach['hinhAnh']; ?>" alt="<?php echo htmlspecialchars($sach['tenSach']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://via.placeholder.com/400x533?text=Sach365'">
        </div>
      </div>

      <!-- Cột thông tin chi tiết và đặt mua -->
      <div style="flex: 1 1 400px; min-width: 0; display: flex; flex-direction: column; gap: 18px">
        <!-- Tên danh mục (nhãn nhỏ phía trên) -->
        <span style="font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #0F4C45"><?php echo htmlspecialchars($sach['tenDanhMuc'] ? $sach['tenDanhMuc'] : 'Sách chọn lọc'); ?></span>
        <!-- Tiêu đề tên sách lớn -->
        <h1 style="margin: 0; font-size: 38px; line-height: 1.1; letter-spacing: -0.03em; font-weight: 800"><?php echo htmlspecialchars($sach['tenSach']); ?></h1>
        
        <!-- Tác giả và nhà xuất bản -->
        <div style="color: #34443E; font-size: 15px">
          Tác giả: <a href="danh-sach-sach.php" style="color: #0F4C45; font-weight: 600"><?php echo htmlspecialchars($sach['tenTacGia'] ? $sach['tenTacGia'] : 'Nhiều tác giả'); ?></a> · Nhà xuất bản: <strong><?php echo htmlspecialchars($sach['tenNXB'] ? $sach['tenNXB'] : 'NXB Tổng Hợp'); ?></strong>
        </div>

        <!-- Đánh giá sao giả lập và số lượng đã bán -->
        <div style="display: flex; align-items: center; gap: 10px; font-size: 14px">
          <span style="font-weight: 700; color: #14201C">★ 4.8</span>
          <span style="color: #55635D">(128 đánh giá) · Đã bán 2.4k</span>
        </div>

        <!-- Giá tiền và Trạng thái tồn kho -->
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-top: 1px solid #DCE3DE; border-bottom: 1px solid #DCE3DE">
          <!-- Định dạng giá tiền theo chuẩn Việt Nam -->
          <span id="price-display" style="font-size: 34px; font-weight: 800; color: #0F4C45"><?php echo number_format($sach['giaBan'], 0, ',', '.'); ?>đ</span>
          
          <!-- Kiểm tra nếu số lượng tồn > 0: hiển thị nhãn Còn hàng; ngược lại hiển thị Tạm hết hàng -->
          <?php if ($soLuongTon > 0): ?>
            <span style="background: #E6F0EC; color: #0F4C45; font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px">
              <span style="width: 8px; height: 8px; background: #0F4C45; border-radius: 50%; display: inline-block"></span>
              Còn hàng (<?php echo $soLuongTon; ?> sản phẩm)
            </span>
          <?php else: ?>
            <span style="background: #FDF2F2; color: #B93A12; font-size: 13px; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px">
              <span style="width: 8px; height: 8px; background: #B93A12; border-radius: 50%; display: inline-block"></span>
              Tạm hết hàng
            </span>
          <?php endif; ?>
        </div>

        <!-- Mô tả ngắn gọn -->
        <p style="margin: 0; color: #34443E; line-height: 1.6"><?php echo nl2br(htmlspecialchars($sach['moTa'] ? $sach['moTa'] : 'Chưa có mô tả cho cuốn sách này.')); ?></p>

        <!-- Hình thức bìa -->
        <div>
          <div style="font-weight: 700; margin-bottom: 8px; font-size: 14px">Hình thức bìa</div>
          <div style="display: flex; flex-wrap: wrap; gap: 10px">
            <button type="button" class="btn-format active"><?php echo htmlspecialchars($sach['hinhThuc'] ? $sach['hinhThuc'] : 'Bìa mềm'); ?></button>
          </div>
        </div>

        <!-- Form đặt mua / thêm vào giỏ hàng -->
        <form action="gio-hang.php" method="POST" id="form-buy">
            <input type="hidden" name="maSach" value="<?php echo $sach['maSach']; ?>">
            <input type="hidden" name="soLuong" id="form-qty" value="1">

            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-top: 8px">
              <!-- Bộ điều khiển tăng giảm số lượng -->
              <div style="display: flex; align-items: center; border: 1.5px solid #DCE3DE; border-radius: 8px; background: #FFFFFF">
                <button type="button" onclick="changeQty(-1)" style="width: 44px; height: 48px; border: none; background: transparent; font-size: 20px; cursor: pointer; color: #14201C" <?php echo $soLuongTon <= 0 ? 'disabled' : ''; ?>>−</button>
                <span id="qty-val" style="min-width: 36px; text-align: center; font-weight: 700"><?php echo $soLuongTon > 0 ? 1 : 0; ?></span>
                <button type="button" onclick="changeQty(1)" style="width: 44px; height: 48px; border: none; background: transparent; font-size: 20px; cursor: pointer; color: #14201C" <?php echo $soLuongTon <= 0 ? 'disabled' : ''; ?>>+</button>
              </div>

              <!-- Nút Thêm vào giỏ & Mua ngay (hiển thị khi còn hàng), nút Hết hàng (nếu hết) -->
              <?php if ($soLuongTon > 0): ?>
                <button type="submit" name="btn_them_gio_hang" style="min-height: 52px; padding: 0 28px; border: 1.5px solid #0F4C45; background: #FFFFFF; color: #0F4C45; font-size: 15px; font-weight: 700; border-radius: 8px; cursor: pointer">Thêm vào giỏ</button>
                <button type="submit" name="btn_them_gio_hang" style="min-height: 52px; padding: 0 32px; background: #0F4C45; color: #FFFFFF; font-weight: 700; border-radius: 8px; border: none; cursor: pointer">Mua ngay</button>
              <?php else: ?>
                <button type="button" disabled style="min-height: 52px; padding: 0 32px; background: #DCE3DE; color: #55635D; font-weight: 700; border-radius: 8px; border: none; cursor: not-allowed">Hết hàng</button>
              <?php endif; ?>
            </div>
        </form>

        <!-- Khung chính sách giao hàng và đổi trả -->
        <div style="display: flex; flex-direction: column; gap: 10px; background: #E6F0EC; border-radius: 10px; padding: 18px 20px; font-size: 14px; margin-top: 8px">
          <div style="display: flex; gap: 10px; align-items: center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F4C45" stroke-width="2"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>
            <span>Giao trong 2 giờ nội thành, 1–3 ngày toàn quốc</span>
          </div>
          <div style="display: flex; gap: 10px; align-items: center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0F4C45" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
            <span>Đổi trả miễn phí trong 7 ngày nếu có lỗi in ấn</span>
          </div>
        </div>

      </div>
    </div>

    <!-- Phần Giới thiệu chi tiết và Thông số kỹ thuật -->
    <div style="display: flex; flex-wrap: wrap; gap: 32px; margin-top: 56px">
      <!-- Khung giới thiệu nội dung -->
      <section style="flex: 2 1 420px; min-width: 0; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 32px; box-sizing: border-box">
        <h2 style="margin: 0 0 16px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em">Giới thiệu sách</h2>
        <div style="color: #34443E; line-height: 1.7">
            <?php echo nl2br(htmlspecialchars($sach['moTa'] ? $sach['moTa'] : 'Nội dung giới thiệu chi tiết cho cuốn sách đang được cập nhật.')); ?>
        </div>
      </section>

      <!-- Bảng thông tin chi tiết kỹ thuật -->
      <section style="flex: 1 1 300px; min-width: 0; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 32px; box-sizing: border-box">
        <h2 style="margin: 0 0 16px; font-size: 22px; font-weight: 800; letter-spacing: -0.02em">Thông tin chi tiết</h2>
        <div style="display: grid; grid-template-columns: 120px minmax(0, 1fr); gap: 12px 16px; font-size: 14px">
          <div style="color: #55635D">Mã sách</div><div>MS-<?php echo $sach['maSach']; ?></div>
          <div style="color: #55635D">Tình trạng</div>
          <div>
            <?php echo $soLuongTon > 0 ? '<strong style="color: #0F4C45">Còn hàng</strong> (' . $soLuongTon . ' sản phẩm)' : '<strong style="color: #B93A12">Hết hàng</strong>'; ?>
          </div>
          <div style="color: #55635D">Tác giả</div><div><?php echo htmlspecialchars($sach['tenTacGia'] ? $sach['tenTacGia'] : 'Nhiều tác giả'); ?></div>
          <div style="color: #55635D">Nhà xuất bản</div><div><?php echo htmlspecialchars($sach['tenNXB'] ? $sach['tenNXB'] : 'NXB Tổng Hợp'); ?></div>
          <div style="color: #55635D">Năm xuất bản</div><div><?php echo $sach['namXuatBan'] ? $sach['namXuatBan'] : '2023'; ?></div>
          <div style="color: #55635D">Số trang</div><div><?php echo $sach['soTrang'] ? $sach['soTrang'] . ' trang' : 'Đang cập nhật'; ?></div>
          <div style="color: #55635D">Trọng lượng</div><div><?php echo $sach['trongLuong'] ? $sach['trongLuong'] . ' gram' : '300 gram'; ?></div>
          <div style="color: #55635D">Ngôn ngữ</div><div>Tiếng Việt</div>
        </div>
      </section>
    </div>

    <!-- Phần sản phẩm liên quan ("Có thể bạn cũng thích") -->
    <section style="margin-top: 56px">
      <h2 style="margin: 0 0 24px; font-size: 26px; font-weight: 800; letter-spacing: -0.02em">Có thể bạn cũng thích</h2>
      <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 28px 24px">
        
        <?php if ($res_lienquan && $res_lienquan->num_rows > 0): ?>
            <?php while ($lq = $res_lienquan->fetch_assoc()): ?>
                <div class="book-card" style="display: flex; flex-direction: column; gap: 8px;">
                  <a href="chi-tiet-sach.php?id=<?php echo $lq['maSach']; ?>">
                    <div style="aspect-ratio: 3/4; border-radius: 6px; overflow: hidden; background: #eee; box-shadow: 0 8px 18px rgba(20,32,28,0.16)">
                        <img src="tai-nguyen/hinh-anh/<?php echo $lq['hinhAnh']; ?>" alt="<?php echo htmlspecialchars($lq['tenSach']); ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://via.placeholder.com/200x266?text=Sach365'">
                    </div>
                  </a>
                  <div style="font-weight: 600; line-height: 1.3; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($lq['tenSach']); ?></div>
                  <div style="font-weight: 800; font-size: 17px; color: #0F4C45"><?php echo number_format($lq['giaBan'], 0, ',', '.'); ?>đ</div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

      </div>
    </section>

3. Phần Xử Lý JavaScript (Tăng giảm số lượng sản phẩm)

<script>
    // Khởi tạo biến số lượng mua ban đầu (mặc định là 1 nếu còn hàng, 0 nếu hết hàng)
    let qty = <?php echo $soLuongTon > 0 ? 1 : 0; ?>;
    // Lưu số lượng tồn kho tối đa
    let maxQty = <?php echo $soLuongTon; ?>;

    // Hàm tăng giảm số lượng khi người dùng click nút cộng/trừ
    function changeQty(delta) {
      if (maxQty <= 0) return;
      qty = Math.max(1, Math.min(maxQty, qty + delta));
      document.getElementById('qty-val').innerText = qty;
      document.getElementById('form-qty').value = qty;
    }
  </script>

<?php
// Nhúng file cuối trang (chứa footer, đóng kết nối CSDL, các thẻ đóng HTML chung)
require_once 'dung-chung/cuoi-trang.php';
?>
