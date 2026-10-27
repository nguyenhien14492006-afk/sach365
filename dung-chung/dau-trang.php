<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../cau-hinh/ket-noi-csdl.php';

$tong_so_luong_gio = 0;
if (isset($_SESSION['gio_hang']) && is_array($_SESSION['gio_hang'])) {
    foreach ($_SESSION['gio_hang'] as $sp) {
        $tong_so_luong_gio += $sp['so_luong'];
    }
}

$res_danhmuc_header = $conn->query("SELECT * FROM danhmuc WHERE trangThai = 1 LIMIT 8");
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sach365 - Nhà Sách Trực Tuyến</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="tai-nguyen/css/style-khach.css">
</head>
<body>

  <header style="background: #0F4C45; color: #FFFFFF">
    <div style="max-width: 1200px; margin: 0 auto; padding: 16px 24px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; gap: 24px">
      
      <a href="index.php" style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 26px; letter-spacing: -0.02em; color: #FFFFFF; flex-shrink: 0">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#FBBF24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v17H6.5A2.5 2.5 0 0 0 4 21.5z"></path>
          <path d="M4 21.5A2.5 2.5 0 0 0 6.5 24H20v-5"></path>
          <path d="M9 7h6"></path>
        </svg>
        <span>Sach<span style="color: #FBBF24">365</span></span>
      </a>

      <form action="danh-sach-sach.php" method="GET" style="flex: 1; display: flex; min-width: 0; max-width: 560px; position: relative">
        <input type="text" name="tu_khoa" placeholder="Nhập tên sách, tác giả..." value="<?php echo isset($_GET['tu_khoa']) ? htmlspecialchars($_GET['tu_khoa']) : ''; ?>" style="width: 100%; height: 44px; box-sizing: border-box; border: none; border-radius: 8px; padding: 0 48px 0 16px; background: #FFFFFF; color: #14201C; font-size: 14px; outline: none">
        <button type="submit" style="position: absolute; right: 4px; top: 4px; width: 36px; height: 36px; border: none; border-radius: 6px; background: transparent; color: #0F4C45; cursor: pointer; display: flex; align-items: center; justify-content: center">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
        </button>
      </form>

      <div style="display: flex; align-items: center; gap: 14px; flex-shrink: 0">
        <a href="gio-hang.php" style="width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #FFFFFF; background: rgba(255,255,255,0.12); position: relative">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.5"></circle><circle cx="18" cy="20" r="1.5"></circle><path d="M2 3h3l2.7 12.4a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 8H6"></path></svg>
          <?php if ($tong_so_luong_gio > 0): ?>
            <span style="position: absolute; top: -4px; right: -4px; background: #EF4444; color: #FFFFFF; border-radius: 999px; min-width: 18px; height: 18px; padding: 0 4px; box-sizing: border-box; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; line-height: 1">
              <?php echo $tong_so_luong_gio; ?>
            </span>
          <?php endif; ?>
        </a>

        <div class="user-menu-wrapper">
          <a href="<?php echo (isset($_SESSION['hoTen']) || isset($_SESSION['maKH'])) ? 'tai-khoan.php' : 'dang-nhap.php'; ?>" style="width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #FFFFFF; background: rgba(255,255,255,0.12)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </a>

          <div class="dropdown-box">
            <div class="dropdown-content">
              <?php if (isset($_SESSION['hoTen']) || isset($_SESSION['maKH'])): ?>
                <div style="padding: 10px 16px; border-bottom: 1px solid #EAEFEA; font-size: 13px; color: #55635D">
                  Tài khoản:<br>
                  <strong style="color: #0F4C45; font-size: 14px"><?php echo htmlspecialchars($_SESSION['hoTen'] ?? 'Khách hàng'); ?></strong>
                </div>
                <a href="tai-khoan.php" class="dropdown-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                  <span>Tài khoản cá nhân</span>
                </a>
                <a href="lich-su-don-hang.php" class="dropdown-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                  <span>Lịch sử đơn hàng</span>
                </a>
                <a href="dang-xuat.php" class="dropdown-item" style="color: #B93A12">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                  <span>Đăng xuất</span>
                </a>
              <?php else: ?>
                <a href="dang-nhap.php" class="dropdown-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
                  <span>Đăng nhập</span>
                </a>
                <a href="dang-ky.php" class="dropdown-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                  <span>Đăng ký</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div style="background: #0A3A34; border-top: 1px solid rgba(255,255,255,0.1)">
      <nav style="max-width: 1200px; margin: 0 auto; padding: 0 24px; box-sizing: border-box; display: flex; flex-wrap: wrap; gap: 0 28px; font-size: 14px; font-weight: 500">
        <?php if ($res_danhmuc_header && $res_danhmuc_header->num_rows > 0): ?>
            <?php while ($dm = $res_danhmuc_header->fetch_assoc()): ?>
                <a href="danh-sach-sach.php?maDanhMuc=<?php echo $dm['maDanhMuc']; ?>" style="min-height: 42px; display: flex; align-items: center; color: #E2E8F0"><?php echo htmlspecialchars($dm['tenDanhMuc']); ?></a>
            <?php endwhile; ?>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <main style="max-width: 1200px; margin: 0 auto; padding: 24px; box-sizing: border-box; min-height: 80vh">