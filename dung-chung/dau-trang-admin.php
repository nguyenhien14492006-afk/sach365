<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieu_de_trang ?? 'Quản trị') ?> - Sach365</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Link file CSS Admin dùng chung -->
    <link rel="stylesheet" href="../tai-nguyen/css/style-admin.css">
</head>
<body>
<div class="thanh-thong-bao-admin">
    <span>Hệ thống Quản trị Sach365 · Giao diện chuẩn Quản lý</span>
    <div class="goc-phai">
        <span>Xin chào, <strong class="ten-user"><?= e($_SESSION['hoTenQL'] ?? $_SESSION['admin'] ?? 'Admin') ?></strong></span>
        <a href="dang-xuat.php" class="nut-dang-xuat">Đăng xuất</a>
    </div>
</div>
<div class="khung-admin">
    <aside class="thanh-ben">
        <a href="trang-quan-tri.php" class="logo">Sach<span>365</span></a>
        <ul class="menu-ben">
            <li><a href="trang-quan-tri.php" class="<?= ($trang_hien_tai ?? '') === 'trang-chu' ? 'kich-hoat' : '' ?>">📊 Tổng quan</a></li>
            <li><a href="quan-ly-sach.php" class="<?= ($trang_hien_tai ?? '') === 'sach' ? 'kich-hoat' : '' ?>">📚 Quản lý sách</a></li>
            <li><a href="quan-ly-danh-muc-sach.php" class="<?= ($trang_hien_tai ?? '') === 'danh-muc' ? 'kich-hoat' : '' ?>">📁 Quản lý danh mục</a></li>
            <li><a href="quan-ly-tac-gia-nxb.php" class="<?= ($trang_hien_tai ?? '') === 'tac-gia-nxb' ? 'kich-hoat' : '' ?>">✍️ Quản lý tác giả &amp; NXB</a></li>
            <li><a href="quan-ly-don-hang.php" class="<?= ($trang_hien_tai ?? '') === 'don-hang' ? 'kich-hoat' : '' ?>">📦 Quản lý đơn hàng</a></li>
            <li><a href="quan-ly-don-vi-van-chuyen.php" class="<?= ($trang_hien_tai ?? '') === 'don-vi-van-chuyen' ? 'kich-hoat' : '' ?>">🚚 Đơn vị vận chuyển</a></li>
            <li><a href="quan-ly-khuyen-mai.php" class="<?= ($trang_hien_tai ?? '') === 'khuyen-mai' ? 'kich-hoat' : '' ?>">🏷️ Quản lý khuyến mãi</a></li>
            <li><a href="quan-ly-khach-hang.php" class="<?= ($trang_hien_tai ?? '') === 'khach-hang' ? 'kich-hoat' : '' ?>">👤 Quản lý khách hàng</a></li>
        </ul>
    </aside>
    <main class="noi-dung-chinh">