<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

function url_quay_lai($mac_dinh)
{
    $u = (string)($_POST['quay_lai'] ?? '');
    return preg_match('/^quan-ly-don-hang\.php(\?[A-Za-z0-9_=&%.\-]*)?$/', $u) ? $u : $mac_dinh;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('quan-ly-don-hang.php', 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'loi');
    }
    $hanhDong = $_POST['hanh_dong'] ?? '';
    $ma = (int)($_POST['maDonHang'] ?? 0);
    $veChiTiet = url_quay_lai('quan-ly-don-hang.php?xem=' . $ma);

    $don = truy_van($conn, "SELECT trangThaiDonHang, phuongThucTT FROM donhang WHERE maDonHang = ?", 'i', [$ma])->fetch_assoc();
    if (!$don) {
        chuyen_huong('quan-ly-don-hang.php', 'Không tìm thấy đơn hàng.', 'loi');
    }
    $cu = (int)$don['trangThaiDonHang'];

    if ($hanhDong === 'cap-nhat') {
        $moi = (int)($_POST['trangThai'] ?? -1);
        if ($cu >= 3) {
            chuyen_huong($veChiTiet, 'Đơn đã giao hoặc đã hủy nên không thể thay đổi trạng thái.', 'loi');
        }
        if (!isset(TRANG_THAI_DON[$moi]) || ($moi !== 4 && $moi <= $cu)) {
            chuyen_huong($veChiTiet, 'Trạng thái mới không hợp lệ.', 'loi');
        }

        $maVanDon = trim($_POST['maVanDon'] ?? '');
        $maDVVC = (int)($_POST['maDVVC'] ?? 0);
        $ghiChu = trim($_POST['ghiChu'] ?? '');
        if ($ghiChu === '') {
            $ghiChu = 'Quản trị viên cập nhật: ' . TRANG_THAI_DON[$moi][0];
        }

        $loiDB = null;
        try {
            $conn->begin_transaction();
            $stmt = $conn->prepare("UPDATE donhang SET trangThaiDonHang = ?, trangThaiTT = IF(? = 3 AND phuongThucTT = 'COD', 1, trangThaiTT), maVanDon = COALESCE(NULLIF(?, ''), maVanDon), maDVVC = COALESCE(NULLIF(?, 0), maDVVC) WHERE maDonHang = ?");
            $stmt->bind_param('iisii', $moi, $moi, $maVanDon, $maDVVC, $ma);
            $stmt->execute();

            if ($moi === 4) {
                $stmtKho = $conn->prepare("UPDATE sach s JOIN chitietdonhang ct ON ct.maSach = s.maSach SET s.soLuongTon = s.soLuongTon + ct.soLuong WHERE ct.maDonHang = ?");
                $stmtKho->bind_param('i', $ma);
                $stmtKho->execute();

                $stmtKM = $conn->prepare("UPDATE khuyenmai km JOIN donhang d ON d.maKM = km.maKM SET km.soLuong = km.soLuong + 1 WHERE d.maDonHang = ? AND km.soLuong IS NOT NULL");
                $stmtKM->bind_param('i', $ma);
                $stmtKM->execute();
            }

            $stmtLS = $conn->prepare("INSERT INTO lichsutrangthai (maDonHang, trangThai, ghiChu) VALUES (?, ?, ?)");
            $stmtLS->bind_param('iis', $ma, $moi, $ghiChu);
            $stmtLS->execute();
            $conn->commit();
        } catch (mysqli_sql_exception $ex) {
            $conn->rollback();
            $loiDB = $ex->getMessage();
        }
        if ($loiDB !== null) {
            chuyen_huong($veChiTiet, 'Không cập nhật được đơn hàng: ' . $loiDB, 'loi');
        }
        chuyen_huong($veChiTiet, 'Đã cập nhật đơn #' . $ma . ' sang trạng thái "' . TRANG_THAI_DON[$moi][0] . '".');
    }

    if ($hanhDong === 'xac-nhan-thanh-toan') {
        if ($cu === 4) {
            chuyen_huong($veChiTiet, 'Đơn đã hủy, không thể ghi nhận thanh toán.', 'loi');
        }
        $stmt = $conn->prepare("UPDATE donhang SET trangThaiTT = 1 WHERE maDonHang = ?");
        $stmt->bind_param('i', $ma);
        $stmt->execute();
        chuyen_huong($veChiTiet, 'Đã ghi nhận đơn #' . $ma . ' là đã thanh toán.');
    }

    chuyen_huong('quan-ly-don-hang.php', 'Thao tác không hợp lệ.', 'loi');
}

$xem = (int)($_GET['xem'] ?? 0);
if ($xem > 0) {
    $d = truy_van($conn,
        "SELECT d.*, kh.email AS emailKH, dv.tenDVVC, km.tenKM
         FROM donhang d
         LEFT JOIN khachhang kh ON kh.maKH = d.maKH
         LEFT JOIN donvivanchuyen dv ON dv.maDVVC = d.maDVVC
         LEFT JOIN khuyenmai km ON km.maKM = d.maKM
         WHERE d.maDonHang = ?", 'i', [$xem])->fetch_assoc();
    if (!$d) {
        chuyen_huong('quan-ly-don-hang.php', 'Không tìm thấy đơn hàng.', 'loi');
    }
    $dsSanPham = truy_van($conn,
        "SELECT ct.soLuong, ct.donGia, s.maSach, s.tenSach, s.hinhAnh
         FROM chitietdonhang ct JOIN sach s ON s.maSach = ct.maSach WHERE ct.maDonHang = ?", 'i', [$xem]);
    $lichSu = truy_van($conn,
        "SELECT trangThai, thoiGian, ghiChu FROM lichsutrangthai WHERE maDonHang = ? ORDER BY thoiGian DESC, maLichSu DESC", 'i', [$xem]);
    $dsDVVC = $conn->query("SELECT maDVVC, tenDVVC FROM donvivanchuyen WHERE trangThai = 1 ORDER BY tenDVVC")->fetch_all(MYSQLI_ASSOC);
    $ttHienTai = (int)$d['trangThaiDonHang'];
}

if ($xem === 0) {
    $tt = ($_GET['trang_thai'] ?? '') !== '' ? (int)$_GET['trang_thai'] : -1;
    $q = trim($_GET['q'] ?? '');
    $trang = max(1, (int)($_GET['trang'] ?? 1));
    $moiTrang = 10;

    $dieuKien = [];
    $kieu = '';
    $tham = [];
    if (isset(TRANG_THAI_DON[$tt])) {
        $dieuKien[] = "d.trangThaiDonHang = ?";
        $kieu .= 'i';
        $tham[] = $tt;
    }
    if ($q !== '') {
        $dieuKien[] = "(d.maDonHang = ? OR d.tenNguoiNhan LIKE ? OR d.sdtNguoiNhan LIKE ?)";
        $kieu .= 'iss';
        $tham[] = (int)ltrim($q, '#');
        $tham[] = '%' . $q . '%';
        $tham[] = '%' . $q . '%';
    }
    $where = $dieuKien ? ' WHERE ' . implode(' AND ', $dieuKien) : '';

    $tong = (int)truy_van($conn, "SELECT COUNT(*) FROM donhang d" . $where, $kieu, $tham)->fetch_row()[0];
    $offset = ($trang - 1) * $moiTrang;
    $dsDon = truy_van($conn,
        "SELECT d.maDonHang, d.tenNguoiNhan, d.sdtNguoiNhan, d.tongTien, d.phuongThucTT, d.trangThaiTT, d.trangThaiDonHang, d.ngayLap
         FROM donhang d" . $where . " ORDER BY d.ngayLap DESC, d.maDonHang DESC LIMIT " . $moiTrang . " OFFSET " . $offset, $kieu, $tham);

    $soTheoTT = [];
    $tongTatCa = 0;
    $res = $conn->query("SELECT trangThaiDonHang, COUNT(*) AS so FROM donhang GROUP BY trangThaiDonHang");
    while ($r = $res->fetch_assoc()) {
        $soTheoTT[(int)$r['trangThaiDonHang']] = (int)$r['so'];
        $tongTatCa += (int)$r['so'];
    }
    $thamSoLoc = [];
    if (isset(TRANG_THAI_DON[$tt])) { $thamSoLoc['trang_thai'] = $tt; }
    if ($q !== '') { $thamSoLoc['q'] = $q; }
    $urlHienTai = 'quan-ly-don-hang.php' . ($_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
}

$trang_hien_tai = 'don-hang';
$tieu_de_trang = 'Quản lý đơn hàng';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<style>
    .bo-loc { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; }
    .bo-loc .tim { flex: 1 1 200px; }
    .nhan-truong { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: var(--chu); }
    .o-nhap, .o-chon { width: 100%; height: 42px; border: 1.5px solid var(--vien); border-radius: 8px; padding: 0 14px; background: #fff; color: var(--chu); font: inherit; }
    .o-nhap:focus, .o-chon:focus { outline: none; border-color: var(--xanh); }
    .anh-nho { width: 44px; height: 58px; object-fit: cover; border-radius: 4px; background: #EAEFEA; border: 1px solid var(--vien); }
    .hanh-dong { display: flex; gap: 8px; align-items: center; }
    .luoi-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .luoi-form .full { grid-column: 1 / -1; }
    @media (max-width: 768px) { .luoi-form { grid-template-columns: 1fr; } }
    .tab-loc { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .tab-loc a { padding: 8px 14px; border-radius: 6px; text-decoration: none; color: var(--phu); font-size: 13px; font-weight: 600; background: #fff; border: 1px solid var(--vien); transition: all 0.2s; }
    .tab-loc a:hover, .tab-loc a.dang { background: var(--xanh); color: #fff; border-color: var(--xanh); }
</style>

<?php if ($xem > 0): ?>

    <div class="dau-trang">
        <h1>Đơn hàng #<?= (int)$d['maDonHang'] ?> <?= nhan_trang_thai_don($ttHienTai) ?></h1>
        <a class="nut" href="quan-ly-don-hang.php">← Quay lại danh sách</a>
    </div>

    <div class="hai-cot" style="margin-bottom:24px">
        <section class="card">
            <h2>Thông tin giao hàng</h2>
            <div style="display:grid;grid-template-columns:130px minmax(0,1fr);gap:10px 12px;font-size:14px">
                <div class="phu">Người nhận</div><div><strong><?= e($d['tenNguoiNhan']) ?></strong></div>
                <div class="phu">Số điện thoại</div><div><?= e($d['sdtNguoiNhan']) ?></div>
                <div class="phu">Email khách</div><div><?= e($d['emailKH'] ?? '—') ?></div>
                <div class="phu">Địa chỉ giao</div><div><?= e($d['diaChiGiao']) ?></div>
                <div class="phu">Ngày đặt</div><div><?= e(date('d/m/Y H:i', strtotime($d['ngayLap']))) ?></div>
                <div class="phu">Đơn vị vận chuyển</div><div><?= e($d['tenDVVC'] ?? 'Chưa chọn') ?></div>
                <div class="phu">Mã vận đơn</div><div><?= e($d['maVanDon'] ?? '—') ?></div>
            </div>
        </section>

        <section class="card">
            <h2>Thanh toán</h2>
            <div style="display:grid;grid-template-columns:130px minmax(0,1fr);gap:10px 12px;font-size:14px">
                <div class="phu">Phương thức</div><div><strong><?= e($d['phuongThucTT'] ?? '—') ?></strong></div>
                <div class="phu">Tình trạng</div>
                <div>
                    <?php if ((int)$d['trangThaiTT'] === 1): ?>
                        <span class="nhan-tt" style="color:#0F4C45;background:#DCEFE8">Đã thanh toán</span>
                    <?php else: ?>
                        <span class="nhan-tt" style="color:#8A4B08;background:#FEF3C7">Chưa thanh toán</span>
                    <?php endif; ?>
                </div>
                <div class="phu">Tiền hàng</div><div><?= vnd($d['tongTienHang']) ?></div>
                <div class="phu">Phí vận chuyển</div><div><?= vnd($d['phiVanChuyen']) ?></div>
                <div class="phu">Giảm giá<?= $d['tenKM'] ? ' (' . e($d['tenKM']) . ')' : '' ?></div><div>-<?= vnd($d['tienGiamGia']) ?></div>
                <div class="phu">Tổng thanh toán</div><div style="font-size:18px"><strong style="color:#B93A12"><?= vnd($d['tongTien']) ?></strong></div>
            </div>
            <?php if ((int)$d['trangThaiTT'] !== 1 && $ttHienTai !== 4): ?>
                <form method="POST" action="quan-ly-don-hang.php" style="margin-top:16px" onsubmit="return confirm('Xác nhận đơn này đã nhận đủ tiền?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hanh_dong" value="xac-nhan-thanh-toan">
                    <input type="hidden" name="maDonHang" value="<?= (int)$d['maDonHang'] ?>">
                    <button class="nut nut-nho" type="submit">Đánh dấu đã thanh toán</button>
                </form>
            <?php endif; ?>
        </section>
    </div>

    <section class="card" style="margin-bottom:24px">
        <h2>Sản phẩm trong đơn</h2>
        <div class="cuon-ngang">
            <table class="bang">
                <thead><tr><th>Sách</th><th class="phai-so">Đơn giá</th><th class="phai-so">Số lượng</th><th class="phai-so">Thành tiền</th></tr></thead>
                <tbody>
                <?php while ($sp = $dsSanPham->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:12px">
                                <?php if (!empty($sp['hinhAnh'])): ?>
                                    <img class="anh-nho" src="../tai-nguyen/hinh-anh/<?= e($sp['hinhAnh']) ?>" alt="" onerror="this.style.visibility='hidden'">
                                <?php else: ?>
                                    <div class="anh-nho"></div>
                                <?php endif; ?>
                                <a href="quan-ly-sach.php?action=sua&id=<?= (int)$sp['maSach'] ?>" style="font-weight:600"><?= e($sp['tenSach']) ?></a>
                            </div>
                        </td>
                        <td class="phai-so"><?= vnd($sp['donGia']) ?></td>
                        <td class="phai-so"><?= (int)$sp['soLuong'] ?></td>
                        <td class="phai-so"><strong><?= vnd($sp['donGia'] * $sp['soLuong']) ?></strong></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="hai-cot">
        <section class="card">
            <h2>Cập nhật trạng thái</h2>
            <?php if ($ttHienTai >= 3): ?>
                <p class="phu" style="margin:0">Đơn hàng đã <?= $ttHienTai === 3 ? 'giao thành công' : 'bị hủy' ?>, không thể thay đổi thêm.</p>
            <?php else: ?>
                <form method="POST" action="quan-ly-don-hang.php" onsubmit="return confirmCapNhat(this);">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hanh_dong" value="cap-nhat">
                    <input type="hidden" name="maDonHang" value="<?= (int)$d['maDonHang'] ?>">
                    <div class="luoi-form">
                        <div class="full">
                            <label class="nhan-truong" for="trangThai">Trạng thái mới</label>
                            <select class="o-chon" id="trangThai" name="trangThai" required>
                                <?php foreach (TRANG_THAI_DON as $k => $t): ?>
                                    <?php if ($k === 4 || $k > $ttHienTai): ?>
                                        <option value="<?= $k ?>"><?= e($t[0]) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="nhan-truong" for="maDVVC">Đơn vị vận chuyển</label>
                            <select class="o-chon" id="maDVVC" name="maDVVC">
                                <option value="0">-- Giữ nguyên --</option>
                                <?php foreach ($dsDVVC as $dv): ?>
                                    <option value="<?= (int)$dv['maDVVC'] ?>" <?= (int)$d['maDVVC'] === (int)$dv['maDVVC'] ? 'selected' : '' ?>><?= e($dv['tenDVVC']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="nhan-truong" for="maVanDon">Mã vận đơn</label>
                            <input class="o-nhap" type="text" id="maVanDon" name="maVanDon" maxlength="50" placeholder="<?= e($d['maVanDon'] ?? 'Nhập khi giao cho đơn vị vận chuyển') ?>">
                        </div>
                        <div class="full">
                            <label class="nhan-truong" for="ghiChu">Ghi chú (hiện trong lịch sử đơn)</label>
                            <input class="o-nhap" type="text" id="ghiChu" name="ghiChu" maxlength="200" placeholder="Có thể để trống">
                        </div>
                    </div>
                    <div class="hanh-dong" style="margin-top:20px">
                        <button class="nut nut-chinh" type="submit">Cập nhật trạng thái</button>
                    </div>
                </form>
                <script>
                    function confirmCapNhat(form) {
                        var sel = form.trangThai;
                        if (sel.value === '4') {
                            return confirm('Hủy đơn hàng này? Sách sẽ được hoàn lại kho và thao tác không thể hoàn tác.');
                        }
                        return true;
                    }
                </script>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Lịch sử trạng thái</h2>
            <?php if ($lichSu->num_rows > 0): ?>
                <ol style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px">
                    <?php while ($ls = $lichSu->fetch_assoc()): ?>
                        <li style="display:flex;gap:12px;align-items:flex-start">
                            <div style="flex-shrink:0;min-width:104px"><?= nhan_trang_thai_don($ls['trangThai']) ?></div>
                            <div style="font-size:14px">
                                <?= e($ls['ghiChu'] ?? '') ?>
                                <div class="phu"><?= e(date('d/m/Y H:i', strtotime($ls['thoiGian']))) ?></div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ol>
            <?php else: ?>
                <div class="trong">Chưa có lịch sử.</div>
            <?php endif; ?>
        </section>
    </div>

<?php else: ?>

    <div class="dau-trang">
        <h1>Quản lý đơn hàng <span class="phu" style="font-weight:500">(<?= $tong ?> đơn)</span></h1>
    </div>

    <nav class="tab-loc" aria-label="Lọc theo trạng thái">
        <a href="quan-ly-don-hang.php" class="<?= !isset(TRANG_THAI_DON[$tt]) ? 'dang' : '' ?>">Tất cả <strong><?= $tongTatCa ?></strong></a>
        <?php foreach (TRANG_THAI_DON as $k => $t): ?>
            <a href="quan-ly-don-hang.php?trang_thai=<?= $k ?>" class="<?= $tt === $k ? 'dang' : '' ?>"><?= e($t[0]) ?> <strong><?= $soTheoTT[$k] ?? 0 ?></strong></a>
        <?php endforeach; ?>
    </nav>

    <div class="card">
        <form method="GET" action="quan-ly-don-hang.php" class="bo-loc">
            <?php if (isset(TRANG_THAI_DON[$tt])): ?><input type="hidden" name="trang_thai" value="<?= $tt ?>"><?php endif; ?>
            <div class="tim">
                <label class="nhan-truong" for="q">Tìm đơn hàng</label>
                <input class="o-nhap" type="search" id="q" name="q" placeholder="Mã đơn, tên hoặc số điện thoại người nhận" value="<?= e($q) ?>">
            </div>
            <button class="nut nut-chinh" type="submit">Tìm</button>
            <?php if ($q !== ''): ?>
                <a class="nut" href="quan-ly-don-hang.php<?= isset(TRANG_THAI_DON[$tt]) ? '?trang_thai=' . $tt : '' ?>">Xóa tìm kiếm</a>
            <?php endif; ?>
        </form>

        <?php if ($dsDon->num_rows > 0): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr><th>Mã đơn</th><th>Người nhận</th><th>Ngày đặt</th><th class="phai-so">Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($o = $dsDon->fetch_assoc()): ?>
                        <tr>
                            <td><a href="quan-ly-don-hang.php?xem=<?= (int)$o['maDonHang'] ?>" style="color:var(--xanh);font-weight:700">#<?= (int)$o['maDonHang'] ?></a></td>
                            <td><?= e($o['tenNguoiNhan']) ?><div class="phu"><?= e($o['sdtNguoiNhan']) ?></div></td>
                            <td><?= e(date('d/m/Y H:i', strtotime($o['ngayLap']))) ?></td>
                            <td class="phai-so"><strong><?= vnd($o['tongTien']) ?></strong></td>
                            <td>
                                <?= e($o['phuongThucTT'] ?? '—') ?>
                                <div class="phu"><?= (int)$o['trangThaiTT'] === 1 ? 'Đã thanh toán' : 'Chưa thanh toán' ?></div>
                            </td>
                            <td><?= nhan_trang_thai_don($o['trangThaiDonHang']) ?></td>
                            <td>
                                <div class="hanh-dong">
                                    <a class="nut nut-nho" href="quan-ly-don-hang.php?xem=<?= (int)$o['maDonHang'] ?>">Xem</a>
                                    <?php if ((int)$o['trangThaiDonHang'] === 0): ?>
                                        <form method="POST" action="quan-ly-don-hang.php">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="hanh_dong" value="cap-nhat">
                                            <input type="hidden" name="trangThai" value="1">
                                            <input type="hidden" name="maDonHang" value="<?= (int)$o['maDonHang'] ?>">
                                            <input type="hidden" name="quay_lai" value="<?= e($urlHienTai) ?>">
                                            <button class="nut nut-nho nut-chinh" type="submit">Duyệt đơn</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?= phan_trang_html($tong, $trang, $moiTrang, $thamSoLoc) ?>
        <?php else: ?>
            <div class="trong">Không có đơn hàng nào phù hợp.</div>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>