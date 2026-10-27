<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('quan-ly-khach-hang.php', 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'loi');
    }
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'doi-trang-thai') {
        $ma = (int)($_POST['maKH'] ?? 0);
        $ttMoi = (int)($_POST['trangThaiMoi'] ?? 1);
        try {
            $stmt = $conn->prepare("UPDATE khachhang SET trangThai = ? WHERE maKH = ?");
            $stmt->bind_param('ii', $ttMoi, $ma);
            $stmt->execute();
            chuyen_huong('quan-ly-khach-hang.php', $ttMoi === 1 ? 'Đã mở khóa tài khoản khách hàng.' : 'Đã khóa tài khoản khách hàng thành công.');
        } catch (mysqli_sql_exception $ex) {
            chuyen_huong('quan-ly-khach-hang.php', 'Lỗi cập nhật trạng thái: ' . $ex->getMessage(), 'loi');
        }
    }
}

$xem = (int)($_GET['xem'] ?? 0);
if ($xem > 0) {
    $khach = truy_van($conn, "SELECT * FROM khachhang WHERE maKH = ?", 'i', [$xem])->fetch_assoc();
    if (!$khach) {
        chuyen_huong('quan-ly-khach-hang.php', 'Không tìm thấy thông tin khách hàng.', 'loi');
    }

    $dsDiaChi = truy_van($conn, "SELECT * FROM diachikhachhang WHERE maKH = ? ORDER BY laMacDinh DESC, maDiaChi DESC", 'i', [$xem]);
    $dsDonHang = truy_van($conn, "SELECT maDonHang, ngayLap, tongTien, trangThaiDonHang, trangThaiTT FROM donhang WHERE maKH = ? ORDER BY ngayLap DESC LIMIT 10", 'i', [$xem]);
    
    $thongKe = truy_van($conn, "SELECT COUNT(*) AS tongDon, COALESCE(SUM(tongTien), 0) AS tongChiTieu FROM donhang WHERE maKH = ? AND trangThaiDonHang = 3", 'i', [$xem])->fetch_assoc();
} else {
    $q = trim($_GET['q'] ?? '');
    $locTrangThai = ($_GET['trang_thai'] ?? '') !== '' ? (int)$_GET['trang_thai'] : -1;
    $trang = max(1, (int)($_GET['trang'] ?? 1));
    $moiTrang = 10;

    $dieuKien = [];
    $kieu = '';
    $tham = [];

    if ($q !== '') {
        $dieuKien[] = "(kh.hoTen LIKE ? OR kh.email LIKE ? OR kh.sdt LIKE ? OR kh.maKH = ?)";
        $kieu .= 'sssi';
        $tham[] = '%' . $q . '%';
        $tham[] = '%' . $q . '%';
        $tham[] = '%' . $q . '%';
        $tham[] = (int)$q;
    }
    if ($locTrangThai === 0 || $locTrangThai === 1) {
        $dieuKien[] = "kh.trangThai = ?";
        $kieu .= 'i';
        $tham[] = $locTrangThai;
    }

    $where = $dieuKien ? ' WHERE ' . implode(' AND ', $dieuKien) : '';

    $tong = (int)truy_van($conn, "SELECT COUNT(*) FROM khachhang kh" . $where, $kieu, $tham)->fetch_row()[0];
    $offset = ($trang - 1) * $moiTrang;
    $dsKhachHang = truy_van($conn,
        "SELECT kh.*, 
                (SELECT COUNT(*) FROM donhang d WHERE d.maKH = kh.maKH) AS soDonHang,
                (SELECT COALESCE(SUM(tongTien), 0) FROM donhang d WHERE d.maKH = kh.maKH AND d.trangThaiDonHang = 3) AS tongChiTieu
         FROM khachhang kh" . $where . " ORDER BY kh.maKH DESC LIMIT " . $moiTrang . " OFFSET " . $offset, $kieu, $tham);

    $thamSoLoc = array_filter(['q' => $q, 'trang_thai' => $locTrangThai >= 0 ? $locTrangThai : ''], function ($v) { return $v !== '' && $v !== null; });
}

$trang_hien_tai = 'khach-hang';
$tieu_de_trang = 'Quản lý khách hàng';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<style>
    .bo-loc { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; }
    .bo-loc .tim { flex: 1 1 200px; }
    .nhan-truong { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: var(--chu); }
    .o-nhap, .o-chon { width: 100%; height: 42px; border: 1.5px solid var(--vien); border-radius: 8px; padding: 0 14px; background: #fff; color: var(--chu); font: inherit; }
    .o-nhap:focus, .o-chon:focus { outline: none; border-color: var(--xanh); }
    .hanh-dong { display: flex; gap: 6px; align-items: center; }
    .nut-khoa { background: #FEE2E2; color: #DC2626; }
    .nut-khoa:hover { background: #DC2626; color: #fff; }
    .nut-mo { background: #DCEFE8; color: #0F4C45; }
    .nut-mo:hover { background: #0F4C45; color: #fff; }
    .the-thong-ke { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; }
    .the-thong-ke .item { background: #fff; border: 1px solid var(--vien); border-radius: 8px; padding: 16px; }
    .the-thong-ke .so { font-size: 20px; font-weight: 700; color: var(--xanh); margin-top: 4px; }
</style>

<?php if ($xem > 0): ?>

    <div class="dau-trang">
        <h1>Khách hàng: <?= e($khach['hoTen']) ?> <span class="phu">(KH-<?= (int)$khach['maKH'] ?>)</span></h1>
        <a class="nut" href="quan-ly-khach-hang.php">← Quay lại danh sách</a>
    </div>

    <div class="the-thong-ke">
        <div class="item">
            <div class="phu">Số điện thoại</div>
            <div class="so" style="font-size:16px;color:var(--chu)"><?= e($khach['sdt'] ?? 'Chưa cập nhật') ?></div>
        </div>
        <div class="item">
            <div class="phu">Email</div>
            <div class="so" style="font-size:16px;color:var(--chu)"><?= e($khach['email']) ?></div>
        </div>
        <div class="item">
            <div class="phu">Đơn hoàn thành</div>
            <div class="so"><?= (int)$thongKe['tongDon'] ?> đơn</div>
        </div>
        <div class="item">
            <div class="phu">Tổng chi tiêu thành công</div>
            <div class="so" style="color:#B93A12"><?= vnd($thongKe['tongChiTieu']) ?></div>
        </div>
    </div>

    <div class="hai-cot" style="margin-bottom:24px">
        <section class="card">
            <h2>Sổ địa chỉ giao hàng</h2>
            <?php if ($dsDiaChi->num_rows > 0): ?>
                <div style="display:flex;flex-direction:column;gap:12px">
                <?php while ($dc = $dsDiaChi->fetch_assoc()): ?>
                    <div style="padding:12px;border:1px solid var(--vien);border-radius:6px;background:#FAFBFB">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <strong><?= e($dc['tenNguoiNhan']) ?> (<?= e($dc['sdtNguoiNhan']) ?>)</strong>
                            <?php if ((int)$dc['laMacDinh'] === 1): ?>
                                <span class="nhan-tt" style="color:#0F4C45;background:#DCEFE8">Mặc định</span>
                            <?php endif; ?>
                        </div>
                        <div class="phu"><?= e($dc['diaChiChiTiet']) ?></div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="trong">Chưa có địa chỉ nào lưu trong tài khoản.</div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Đơn hàng gần đây</h2>
            <?php if ($dsDonHang->num_rows > 0): ?>
                <div class="cuon-ngang">
                    <table class="bang">
                        <thead>
                            <tr><th>Mã đơn</th><th>Ngày đặt</th><th class="phai-so">Tổng tiền</th><th>Trạng thái</th></tr>
                        </thead>
                        <tbody>
                        <?php while ($dh = $dsDonHang->fetch_assoc()): ?>
                            <tr>
                                <td><a href="quan-ly-don-hang.php?xem=<?= (int)$dh['maDonHang'] ?>" style="color:var(--xanh);font-weight:700">#<?= (int)$dh['maDonHang'] ?></a></td>
                                <td><?= date('d/m/Y H:i', strtotime($dh['ngayLap'])) ?></td>
                                <td class="phai-so"><strong><?= vnd($dh['tongTien']) ?></strong></td>
                                <td><?= nhan_trang_thai_don($dh['trangThaiDonHang']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="trong">Khách hàng chưa mua đơn hàng nào.</div>
            <?php endif; ?>
        </section>
    </div>

<?php else: ?>

    <div class="dau-trang">
        <h1>Quản lý khách hàng <span class="phu" style="font-weight:500">(<?= $tong ?> tài khoản)</span></h1>
    </div>

    <div class="card">
        <form method="GET" action="quan-ly-khach-hang.php" class="bo-loc">
            <div class="tim">
                <label class="nhan-truong" for="q">Tìm khách hàng</label>
                <input class="o-nhap" type="search" id="q" name="q" placeholder="Họ tên, email, SĐT hoặc mã KH" value="<?= e($q) ?>">
            </div>
            <div>
                <label class="nhan-truong" for="trang_thai">Trạng thái tài khoản</label>
                <select class="o-chon" id="trang_thai" name="trang_thai">
                    <option value="">Tất cả</option>
                    <option value="1" <?= $locTrangThai === 1 ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="0" <?= $locTrangThai === 0 ? 'selected' : '' ?>>Đã bị khóa</option>
                </select>
            </div>
            <button class="nut nut-chinh" type="submit">Lọc</button>
            <?php if ($thamSoLoc): ?><a class="nut" href="quan-ly-khach-hang.php">Xóa lọc</a><?php endif; ?>
        </form>

        <?php if ($dsKhachHang->num_rows > 0): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr><th>Mã KH</th><th>Họ tên & Email</th><th>Số điện thoại</th><th class="phai-so">Tổng đơn</th><th class="phai-so">Tích lũy mua</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($kh = $dsKhachHang->fetch_assoc()): ?>
                        <tr>
                            <td><strong>KH-<?= (int)$kh['maKH'] ?></strong></td>
                            <td style="min-width:200px">
                                <strong><?= e($kh['hoTen']) ?></strong>
                                <div class="phu"><?= e($kh['email']) ?></div>
                            </td>
                            <td><?= e($kh['sdt'] ?? '—') ?></td>
                            <td class="phai-so"><strong><?= (int)$kh['soDonHang'] ?></strong> đơn</td>
                            <td class="phai-so"><strong style="color:#B93A12"><?= vnd($kh['tongChiTieu']) ?></strong></td>
                            <td>
                                <?php if ((int)$kh['trangThai'] === 1): ?>
                                    <span class="nhan-tt" style="color:#0F4C45;background:#DCEFE8">Hoạt động</span>
                                <?php else: ?>
                                    <span class="nhan-tt" style="color:#991B1B;background:#FEE2E2">Bị khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="hanh-dong">
                                    <a class="nut nut-nho" href="quan-ly-khach-hang.php?xem=<?= (int)$kh['maKH'] ?>">Xem</a>
                                    
                                    <form method="POST" action="quan-ly-khach-hang.php" onsubmit="return confirm('<?= (int)$kh['trangThai'] === 1 ? 'Khóa tài khoản khách hàng này?' : 'Mở khóa tài khoản này?' ?>');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="doi-trang-thai">
                                        <input type="hidden" name="maKH" value="<?= (int)$kh['maKH'] ?>">
                                        <input type="hidden" name="trangThaiMoi" value="<?= (int)$kh['trangThai'] === 1 ? 0 : 1 ?>">
                                        <button class="nut nut-nho <?= (int)$kh['trangThai'] === 1 ? 'nut-khoa' : 'nut-mo' ?>" type="submit">
                                            <?= (int)$kh['trangThai'] === 1 ? 'Khóa' : 'Mở' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?= phan_trang_html($tong, $trang, $moiTrang, $thamSoLoc) ?>
        <?php else: ?>
            <div class="trong">Không tìm thấy khách hàng nào.</div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>