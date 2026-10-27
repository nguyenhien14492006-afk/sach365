<?php
require_once 'dung-chung/dau-trang.php';

$res_bestseller = $conn->query("SELECT s.*, dm.tenDanhMuc FROM sach s LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc WHERE s.trangThai = 1 ORDER BY s.maSach ASC LIMIT 5");
$res_moi = $conn->query("SELECT s.*, dm.tenDanhMuc FROM sach s LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc WHERE s.trangThai = 1 ORDER BY s.maSach DESC LIMIT 5");
?>

  <section style="background: #E6F0EC; margin: -24px -24px 0 -24px;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 56px 24px; box-sizing: border-box; display: flex; flex-wrap: wrap; align-items: center; gap: 48px">
      <div style="flex: 1 1 420px; min-width: 0; display: flex; flex-direction: column; gap: 20px; align-items: flex-start">
        <span style="font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #0F4C45">Mùa đọc sách · Sach365</span>
        <h1 style="margin: 0; font-size: 52px; line-height: 1.08; letter-spacing: -0.03em; font-weight: 800">Mỗi cuốn sách là một chuyến đi mới.</h1>
        <p style="margin: 0; font-size: 17px; max-width: 480px; color: #34443E">Hàng nghìn đầu sách văn học, kinh tế, kỹ năng và thiếu nhi, giao tận nhà, bọc sách miễn phí.</p>
        <div style="display: flex; flex-wrap: wrap; gap: 12px; padding-top: 8px">
          <a href="#bestseller" style="background: #0F4C45; color: #FFFFFF; font-weight: 600; padding: 0 28px; min-height: 52px; border-radius: 8px; display: flex; align-items: center">Khám phá ngay</a>
          <a href="#moi" style="border: 1.5px solid #0F4C45; color: #0F4C45; font-weight: 600; padding: 0 28px; min-height: 52px; box-sizing: border-box; border-radius: 8px; display: flex; align-items: center">Xem sách mới</a>
        </div>
      </div>

      <div style="flex: 1 1 360px; min-width: 0; display: flex; justify-content: center; align-items: flex-end; gap: 0; padding: 16px 0">
        <div style="width: 170px; aspect-ratio: 3/4; background-image: url('tai-nguyen/hinh-anh/bonghinhcuagio.jpg'); background-size: cover; background-position: center; color: #FFFFFF; border-radius: 6px; padding: 16px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; transform: rotate(-6deg); box-shadow: 0 14px 28px rgba(20,32,28,0.22); margin-right: -24px; position: relative; z-index: 1;">
          <div style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid currentColor"></div>
          <div style="font-weight: 800; font-size: 20px; line-height: 1.15; text-shadow: 1px 1px 4px rgba(0,0,0,0.8)">Bóng Hình Của Gió</div>
        </div>

        <div style="width: 200px; aspect-ratio: 3/4; background-image: url('tai-nguyen/hinh-anh/chamlaimotchut.jpg'); background-size: cover; background-position: center; color: #FFFFFF; border-radius: 6px; padding: 18px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 18px 36px rgba(20,32,28,0.26); position: relative; z-index: 2;">
          <div style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid currentColor"></div>
          <div style="font-weight: 800; font-size: 24px; line-height: 1.12; text-shadow: 1px 1px 4px rgba(0,0,0,0.8)">Những Ngày Chậm Lại</div>
        </div>

        <div style="width: 170px; aspect-ratio: 3/4; background-image: url('tai-nguyen/hinh-anh/cungchamegiadi.jpg'); background-size: cover; background-position: center; color: #FFFFFF; border-radius: 6px; padding: 16px; box-sizing: border-box; display: flex; flex-direction: column; justify-content: space-between; transform: rotate(6deg); box-shadow: 0 14px 28px rgba(20,32,28,0.22); margin-left: -24px; position: relative; z-index: 1;">
          <div style="width: 28px; height: 28px; border-radius: 50%; border: 2px solid currentColor"></div>
          <div style="font-weight: 800; font-size: 20px; line-height: 1.15; text-shadow: 1px 1px 4px rgba(0,0,0,0.8)">Dấu Ăn Cho Một Người</div>
        </div>
      </div>
    </div>
  </section>

  <section style="max-width: 1200px; margin: 0 auto; padding: 32px 0 0; box-sizing: border-box">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px">
      <div style="display: flex; align-items: center; gap: 14px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px 20px">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F4C45" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v9H3z"></path><path d="M14 10h4l3 3v3h-7"></path><circle cx="7" cy="18" r="1.6"></circle><circle cx="17" cy="18" r="1.6"></circle></svg>
        <div><div style="font-weight: 700">Giao nhanh 2 giờ</div><div style="font-size: 13px; color: #55635D">Nội thành, đơn đặt trước 17h</div></div>
      </div>
      <div style="display: flex; align-items: center; gap: 14px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px 20px">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F4C45" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path></svg>
        <div><div style="font-weight: 700">Đổi trả 7 ngày</div><div style="font-size: 13px; color: #55635D">Nếu sách lỗi hoặc in sai</div></div>
      </div>
      <div style="display: flex; align-items: center; gap: 14px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px 20px">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F4C45" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
        <div><div style="font-weight: 700">Thanh toán an toàn</div><div style="font-size: 13px; color: #55635D">Thẻ, ví điện tử, COD</div></div>
      </div>
      <div style="display: flex; align-items: center; gap: 14px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px 20px">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0F4C45" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12v9H4v-9"></path><path d="M2 7h20v5H2z"></path><path d="M12 21V7"></path><path d="M12 7H7.5a2.5 2.5 0 1 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 1 0 0-5C13 2 12 7 12 7z"></path></svg>
        <div><div style="font-weight: 700">Bọc sách miễn phí</div><div style="font-size: 13px; color: #55635D">Gói quà theo yêu cầu</div></div>
      </div>
    </div>
  </section>

  <section id="bestseller" style="max-width: 1200px; margin: 0 auto; padding: 64px 0 0; box-sizing: border-box">
    <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 24px">
      <h2 style="margin: 0; font-size: 30px; letter-spacing: -0.02em; font-weight: 800">Sách nổi bật</h2>
      <a href="danh-sach-sach.php" style="font-weight: 600; color: #0F4C45; min-height: 44px; display: flex; align-items: center">Xem tất cả →</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 28px 24px">
      <?php if ($res_bestseller && $res_bestseller->num_rows > 0): ?>
        <?php while ($sach = $res_bestseller->fetch_assoc()): ?>
          <?php include 'dung-chung/thanh-phan/the-sach.php'; ?>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

  <section style="max-width: 1200px; margin: 0 auto; padding: 64px 0 0; box-sizing: border-box">
    <div style="display: flex; flex-wrap: wrap; gap: 24px">
      <div style="flex: 1 1 480px; min-width: 0; background: #0F4C45; color: #F2F7F4; border-radius: 14px; padding: 40px; box-sizing: border-box; display: flex; flex-direction: column; gap: 12px; align-items: flex-start">
        <span style="font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #BFE0D6">Ưu đãi hàng tuần</span>
        <div style="font-size: 32px; font-weight: 800; line-height: 1.15; letter-spacing: -0.02em">Giảm đến 30% sách kỹ năng và kinh tế</div>
        <a href="danh-sach-sach.php" style="margin-top: 8px; background: #FFFFFF; color: #14201C; font-weight: 600; padding: 0 24px; min-height: 48px; border-radius: 8px; display: flex; align-items: center">Mua ngay</a>
      </div>
      <div style="flex: 1 1 480px; min-width: 0; background: #F2C14E; color: #2A2108; border-radius: 14px; padding: 40px; box-sizing: border-box; display: flex; flex-direction: column; gap: 12px; align-items: flex-start">
        <span style="font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase">Combo quà tặng</span>
        <div style="font-size: 32px; font-weight: 800; line-height: 1.15; letter-spacing: -0.02em">Mua 3 cuốn thiếu nhi, tặng sổ tay và bookmark</div>
        <a href="danh-sach-sach.php" style="margin-top: 8px; background: #2A2108; color: #FFFFFF; font-weight: 600; padding: 0 24px; min-height: 48px; border-radius: 8px; display: flex; align-items: center">Xem combo</a>
      </div>
    </div>
  </section>

  <section id="moi" style="max-width: 1200px; margin: 0 auto; padding: 64px 0 0; box-sizing: border-box">
    <div style="display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 24px">
      <h2 style="margin: 0; font-size: 30px; letter-spacing: -0.02em; font-weight: 800">Sách mới lên kệ</h2>
      <a href="danh-sach-sach.php" style="font-weight: 600; color: #0F4C45; min-height: 44px; display: flex; align-items: center">Xem tất cả →</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 28px 24px">
      <?php if ($res_moi && $res_moi->num_rows > 0): ?>
        <?php while ($sach = $res_moi->fetch_assoc()): ?>
          <?php include 'dung-chung/thanh-phan/the-sach.php'; ?>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

  <section style="max-width: 1200px; margin: 0 auto; padding: 64px 0 0; box-sizing: border-box">
    <div style="background: #E6F0EC; border-radius: 14px; padding: 40px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 24px">
      <div style="flex: 1 1 320px; min-width: 0">
        <div style="font-size: 26px; font-weight: 800; letter-spacing: -0.02em">Nhận gợi ý sách mỗi tuần</div>
        <div style="color: #34443E; margin-top: 4px">Sách mới, ưu đãi và những cuốn đáng đọc, gửi vào email của bạn.</div>
      </div>
      <form style="flex: 1 1 360px; display: flex; min-width: 0">
        <input type="email" placeholder="Email của bạn" style="flex: 1; min-width: 0; height: 48px; box-sizing: border-box; border: 1.5px solid #B9CCC4; border-right: none; border-radius: 8px 0 0 8px; padding: 0 16px; background: #FFFFFF; color: #14201C">
        <button type="button" style="height: 48px; border: none; border-radius: 0 8px 8px 0; background: #0F4C45; color: #FFFFFF; font-weight: 600; padding: 0 24px; cursor: pointer">Đăng ký</button>
      </form>
    </div>
  </section>

<?php
require_once 'dung-chung/cuoi-trang.php';
?>