<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

$loi = [];
$che_do = 'danh-sach';
$km = [
    'maKM' => 0,
    'tenKM' => '',
    'maCode' => '',
    'phanTramGiam' => '',
    'giamToiDa' => '',
    'donToiThieu' => '',
    'ngayBatDau' => date('Y-m-d\TH:i'),
    'ngayKetThuc' => date('Y-m-d\TH:i', strtotime('+7 days')),
    'soLuong' => '',
    'trangThai' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('quan-ly-khuyen-mai.php', 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'loi');
    }
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'doi-trang-thai') {
        $ma = (int)($_POST['maKM'] ?? 0);
        $ttMoi = (int)($_POST['trangThaiMoi'] ?? 1);
        try {
            $stmt = $conn->prepare("UPDATE khuyenmai SET trangThai = ? WHERE maKM = ?");
            $stmt->bind_param('ii', $ttMoi, $ma);
            $stmt->execute();
            chuyen_huong('quan-ly-khuyen-mai.php', $ttMoi === 1 ? 'Đã kích hoạt chương trình khuyến mãi.' : 'Đã ẩn chương trình khuyến mãi.');
        } catch (mysqli_sql_exception $ex) {
            chuyen_huong('quan-ly-khuyen-mai.php', 'Lỗi cập nhật trạng thái: ' . $ex->getMessage(), 'loi');
        }
    }

    if ($hanhDong === 'xoa') {
        $ma = (int)($_POST['maKM'] ?? 0);
        $donHangLienQuan = (int)truy_van($conn, "SELECT COUNT(*) FROM donhang WHERE maKM = ?", 'i', [$ma])->fetch_row()[0];
        if ($donHangLienQuan > 0) {
            try {
                $stmt = $conn->prepare("UPDATE khuyenmai SET trangThai = 0 WHERE maKM = ?");
                $stmt->bind_param('i', $ma);
                $stmt->execute();
                chuyen_huong('quan-ly-khuyen-mai.php', 'Mã khuyến mãi đã từng được sử dụng cho ' . $donHangLienQuan . ' đơn hàng nên hệ thống đã tự động chuyển sang trạng thái Ẩn.');
            } catch (mysqli_sql_exception $ex) {
                chuyen_huong('quan-ly-khuyen-mai.php', 'Lỗi không thể ẩn khuyến mãi: ' . $ex->getMessage(), 'loi');
            }
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM khuyenmai WHERE maKM = ?");
                $stmt->bind_param('i', $ma);
                $stmt->execute();
                chuyen_huong('quan-ly-khuyen-mai.php', 'Đã xóa hoàn toàn khuyến mãi thành công.');
            } catch (mysqli_sql_exception $ex) {
                chuyen_huong('quan-ly-khuyen-mai.php', 'Không thể xóa khuyến mãi này: ' . $ex->getMessage(), 'loi');
            }
        }
    }

    if ($hanhDong === 'luu') {
        $maGoc = (int)($_POST['maKM'] ?? 0);
        $km = [
            'maKM' => $maGoc,
            'tenKM' => trim($_POST['tenKM'] ?? ''),
            'maCode' => strtoupper(trim($_POST['maCode'] ?? '')),
            'phanTramGiam' => trim($_POST['phanTramGiam'] ?? ''),
            'giamToiDa' => trim($_POST['giamToiDa'] ?? ''),
            'donToiThieu' => trim($_POST['donToiThieu'] ?? '0'),
            'ngayBatDau' => trim($_POST['ngayBatDau'] ?? ''),
            'ngayKetThuc' => trim($_POST['ngayKetThuc'] ?? ''),
            'soLuong' => trim($_POST['soLuong'] ?? ''),
            'trangThai' => ((int)($_POST['trangThai'] ?? 1) === 1) ? 1 : 0,
        ];

        if ($km['tenKM'] === '') { $loi[] = 'Vui lòng nhập tên chương trình khuyến mãi.'; }
        if ($km['maCode'] === '') { $loi[] = 'Vui lòng nhập mã Voucher / Code giảm giá.'; }
        if (!is_numeric($km['phanTramGiam']) || (float)$km['phanTramGiam'] <= 0 || (float)$km['phanTramGiam'] > 100) {
            $loi[] = 'Phần trăm giảm giá phải từ 1% đến 100%.';
        }
        if ($km['ngayBatDau'] === '' || $km['ngayKetThuc'] === '') {
            $loi[] = 'Vui lòng chọn thời gian bắt đầu và kết thúc.';
        } elseif (strtotime($km['ngayKetThuc']) <= strtotime($km['ngayBatDau'])) {
            $loi[] = 'Thời gian kết thúc phải lớn hơn thời gian bắt đầu.';
        }

        if (!$loi) {
            $check = truy_van($conn, "SELECT maKM FROM khuyenmai WHERE maCode = ? AND maKM != ?", 'si', [$km['maCode'], $maGoc])->fetch_assoc();
            if ($check) {
                $loi[] = 'Mã Code giảm giá này đã tồn tại trên hệ thống.';
            }
        }

        if (!$loi) {
            $ten          = $km['tenKM'];
            $code         = $km['maCode'];
            $ptGiam       = (float)$km['phanTramGiam'];
            $giamTD       = $km['giamToiDa'] !== '' ? (float)$km['giamToiDa'] : null;
            $donTT        = $km['donToiThieu'] !== '' ? (float)$km['donToiThieu'] : 0;
            $ngayBD       = date('Y-m-d H:i:s', strtotime($km['ngayBatDau']));
            $ngayKT       = date('Y-m-d H:i:s', strtotime($km['ngayKetThuc']));
            $soLuong      = $km['soLuong'] !== '' ? (int)$km['soLuong'] : null;
            $trangThai    = $km['trangThai'];
            $ma           = $maGoc;

            try {
                if ($maGoc > 0) {
                    $stmt = $conn->prepare("UPDATE khuyenmai SET tenKM=?, maCode=?, phanTramGiam=?, giamToiDa=?, donToiThieu=?, ngayBatDau=?, ngayKetThuc=?, soLuong=?, trangThai=? WHERE maKM=?");
                    $stmt->bind_param('ssdddssiii', $ten, $code, $ptGiam, $giamTD, $donTT, $ngayBD, $ngayKT, $soLuong, $trangThai, $ma);
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("INSERT INTO khuyenmai (tenKM, maCode, phanTramGiam, giamToiDa, donToiThieu, ngayBatDau, ngayKetThuc, soLuong, trangThai) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('ssdddssii', $ten, $code, $ptGiam, $giamTD, $donTT, $ngayBD, $ngayKT, $soLuong, $trangThai);
                    $stmt->execute();
                }
                chuyen_huong('quan-ly-khuyen-mai.php', $maGoc > 0 ? 'Đã cập nhật chương trình khuyến mãi.' : 'Đã thêm khuyến mãi mới thành công.');
            } catch (mysqli_sql_exception $ex) {
                $loi[] = 'Lỗi cơ sở dữ liệu: ' . $ex->getMessage();
            }
        }
        $che_do = ($maGoc > 0) ? 'sua' : 'them';
    }
}

$action = $_GET['action'] ?? '';
if ($che_do === 'danh-sach') {
    if ($action === 'them') {
        $che_do = 'them';
    } elseif ($action === 'sua') {
        $id = (int)($_GET['id'] ?? 0);
        $row = truy_van($conn, "SELECT * FROM khuyenmai WHERE maKM = ?", 'i', [$id])->fetch_assoc();
        if (!$row) {
            chuyen_huong('quan-ly-khuyen-mai.php', 'Không tìm thấy chương trình khuyến mãi.', 'loi');
        }
        $km = $row;
        $km['ngayBatDau'] = date('Y-m-d\TH:i', strtotime($km['ngayBatDau']));
        $km['ngayKetThuc'] = date('Y-m-d\TH:i', strtotime($km['ngayKetThuc']));
        $che_do = 'sua';
    }
}

if ($che_do === 'danh-sach') {
    $q = trim($_GET['q'] ?? '');
    $locTrangThai = ($_GET['trang_thai'] ?? '') !== '' ? (int)$_GET['trang_thai'] : -1;
    $trang = max(1, (int)($_GET['trang'] ?? 1));
    $moiTrang = 10;

    $dieuKien = [];
    $kieu = '';
    $tham = [];

    if ($q !== '') {
        $dieuKien[] = "(km.tenKM LIKE ? OR km.maCode LIKE ? OR km.maKM = ?)";
        $kieu .= 'ssi';
        $tham[] = '%' . $q . '%';
        $tham[] = '%' . $q . '%';
        $tham[] = (int)$q;
    }
    if ($locTrangThai === 0 || $locTrangThai === 1) {
        $dieuKien[] = "km.trangThai = ?";
        $kieu .= 'i';
        $tham[] = $locTrangThai;
    }

    $where = $dieuKien ? ' WHERE ' . implode(' AND ', $dieuKien) : '';

    $tong = (int)truy_van($conn, "SELECT COUNT(*) FROM khuyenmai km" . $where, $kieu, $tham)->fetch_row()[0];
    $offset = ($trang - 1) * $moiTrang;
    $dsKhuyenMai = truy_van($conn,
        "SELECT km.* FROM khuyenmai km" . $where . " ORDER BY km.maKM DESC LIMIT " . $moiTrang . " OFFSET " . $offset, $kieu, $tham);

    $thamSoLoc = array_filter(['q' => $q, 'trang_thai' => $locTrangThai >= 0 ? $locTrangThai : ''], function ($v) { return $v !== '' && $v !== null; });
}

$trang_hien_tai = 'khuyen-mai';
$tieu_de_trang = 'Quản lý khuyến mãi';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<style>
    .bo-loc { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; }
    .bo-loc .tim { flex: 1 1 200px; }
    .nhan-truong { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: var(--chu); }
    .o-nhap, .o-chon { width: 100%; height: 42px; border: 1.5px solid var(--vien); border-radius: 8px; padding: 0 14px; background: #fff; color: var(--chu); font: inherit; }
    .o-nhap:focus, .o-chon:focus { outline: none; border-color: var(--xanh); }
    .hanh-dong { display: flex; gap: 6px; align-items: center; }
    .nut-nguy { background: #FEE2E2; color: #DC2626; }
    .nut-nguy:hover { background: #DC2626; color: #fff; }
    .nut-an { background: #F3F4F6; color: #4B5563; }
    .nut-an:hover { background: #E5E7EB; color: #111827; }
    .nut-hien { background: #E0E7FF; color: #3730A3; }
    .nut-hien:hover { background: #C7D2FE; color: #312E81; }
    .ma-code { display: inline-block; background: #FEF3C7; color: #92400E; font-weight: 700; padding: 2px 8px; border-radius: 4px; font-family: monospace; font-size: 14px; }
    .luoi-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .luoi-form .full { grid-column: 1 / -1; }
    @media (max-width: 768px) { .luoi-form { grid-template-columns: 1fr; } }
</style>

<?php if ($che_do === 'danh-sach'): ?>

    <div class="dau-trang">
        <h1>Quản lý khuyến mãi <span class="phu" style="font-weight:500">(<?= $tong ?> chương trình)</span></h1>
        <a href="quan-ly-khuyen-mai.php?action=them" class="nut nut-chinh">+ Thêm khuyến mãi mới</a>
    </div>

    <div class="card">
        <form method="GET" action="quan-ly-khuyen-mai.php" class="bo-loc">
            <div class="tim">
                <label class="nhan-truong" for="q">Tìm khuyến mãi</label>
                <input class="o-nhap" type="search" id="q" name="q" placeholder="Tên chương trình hoặc mã Code" value="<?= e($q) ?>">
            </div>
            <div>
                <label class="nhan-truong" for="trang_thai">Trạng thái</label>
                <select class="o-chon" id="trang_thai" name="trang_thai">
                    <option value="">Tất cả</option>
                    <option value="1" <?= $locTrangThai === 1 ? 'selected' : '' ?>>Kích hoạt</option>
                    <option value="0" <?= $locTrangThai === 0 ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </div>
            <button class="nut nut-chinh" type="submit">Lọc</button>
            <?php if ($thamSoLoc): ?><a class="nut" href="quan-ly-khuyen-mai.php">Xóa lọc</a><?php endif; ?>
        </form>

        <?php if ($dsKhuyenMai->num_rows > 0): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr><th>Mã Code</th><th>Tên chương trình</th><th class="phai-so">Mức giảm</th><th>Thời gian hiệu lực</th><th class="phai-so">Còn lại</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($k = $dsKhuyenMai->fetch_assoc()): 
                        $now = time();
                        $bd = strtotime($k['ngayBatDau']);
                        $kt = strtotime($k['ngayKetThuc']);
                        $hetHan = $now > $kt;
                        $chuaDen = $now < $bd;
                    ?>
                        <tr>
                            <td><span class="ma-code"><?= e($k['maCode']) ?></span></td>
                            <td style="min-width:180px">
                                <strong><?= e($k['tenKM']) ?></strong>
                                <div class="phu">Đơn tối thiểu: <?= vnd($k['donToiThieu']) ?></div>
                            </td>
                            <td class="phai-so">
                                <strong style="color:#B93A12">-<?= (float)$k['phanTramGiam'] ?>%</strong>
                                <?php if ($k['giamToiDa']): ?>
                                    <div class="phu">Tối đa <?= vnd($k['giamToiDa']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= date('d/m/Y H:i', $bd) ?></div>
                                <div class="phu">đến <?= date('d/m/Y H:i', $kt) ?></div>
                            </td>
                            <td class="phai-so">
                                <?= $k['soLuong'] !== null ? '<strong>' . (int)$k['soLuong'] . '</strong> lượt' : 'Không giới hạn' ?>
                            </td>
                            <td>
                                <?php if ((int)$k['trangThai'] === 0): ?>
                                    <span class="nhan-tt" style="color:#55635D;background:#EAEFEA">Đã ẩn</span>
                                <?php elseif ($hetHan): ?>
                                    <span class="nhan-tt" style="color:#991B1B;background:#FEE2E2">Đã hết hạn</span>
                                <?php elseif ($chuaDen): ?>
                                    <span class="nhan-tt" style="color:#8A4B08;background:#FEF3C7">Sắp diễn ra</span>
                                <?php else: ?>
                                    <span class="nhan-tt" style="color:#0F4C45;background:#DCEFE8">Đang chạy</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="hanh-dong">
                                    <a class="nut nut-nho" href="quan-ly-khuyen-mai.php?action=sua&id=<?= (int)$k['maKM'] ?>">Sửa</a>
                                    
                                    <form method="POST" action="quan-ly-khuyen-mai.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="doi-trang-thai">
                                        <input type="hidden" name="maKM" value="<?= (int)$k['maKM'] ?>">
                                        <input type="hidden" name="trangThaiMoi" value="<?= (int)$k['trangThai'] === 1 ? 0 : 1 ?>">
                                        <button class="nut nut-nho <?= (int)$k['trangThai'] === 1 ? 'nut-an' : 'nut-hien' ?>" type="submit">
                                            <?= (int)$k['trangThai'] === 1 ? 'Ẩn' : 'Hiện' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="quan-ly-khuyen-mai.php" onsubmit="return confirm('Xóa chương trình khuyến mãi này?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="xoa">
                                        <input type="hidden" name="maKM" value="<?= (int)$k['maKM'] ?>">
                                        <button class="nut nut-nho nut-nguy" type="submit">Xóa</button>
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
            <div class="trong">Không tìm thấy chương trình khuyến mãi nào.</div>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="dau-trang">
        <h1>Quản trị (<?= $che_do === 'sua' ? 'Sửa khuyến mãi' : 'Thêm khuyến mãi' ?>)</h1>
        <a class="nut" href="quan-ly-khuyen-mai.php">← Quay lại</a>
    </div>

    <?php if ($loi): ?>
        <div class="thong-bao thong-bao-loi" style="margin-bottom:20px">
            <ul style="margin:0;padding-left:20px"><?php foreach ($loi as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form class="card" method="POST" action="quan-ly-khuyen-mai.php">
        <h2 style="font-size:15px;color:var(--phu);margin-bottom:20px;border-bottom:1px solid var(--vien);padding-bottom:10px">Thông tin chương trình khuyến mãi</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="hanh_dong" value="luu">
        <input type="hidden" name="maKM" value="<?= (int)$km['maKM'] ?>">

        <div class="luoi-form">
            <div>
                <label class="nhan-truong" for="tenKM">Tên chương trình khuyến mãi: (*)</label>
                <input class="o-nhap" type="text" id="tenKM" name="tenKM" maxlength="150" required placeholder="Ví dụ: Giảm giá Chào Mừng Khai Trương" value="<?= e($km['tenKM']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="maCode">Mã Code (Voucher): (*)</label>
                <input class="o-nhap" type="text" id="maCode" name="maCode" maxlength="20" required placeholder="Ví dụ: SACH365NEW" style="text-transform:uppercase;font-weight:700" value="<?= e($km['maCode']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="phanTramGiam">Tỷ lệ giảm (%): (*)</label>
                <input class="o-nhap" type="number" id="phanTramGiam" name="phanTramGiam" min="1" max="100" step="0.1" required placeholder="Ví dụ: 15" value="<?= e($km['phanTramGiam']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="giamToiDa">Số tiền giảm tối đa (VNĐ):</label>
                <input class="o-nhap" type="number" id="giamToiDa" name="giamToiDa" min="0" step="1000" placeholder="Để trống nếu không giới hạn" value="<?= e($km['giamToiDa']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="donToiThieu">Đơn hàng tối thiểu (VNĐ):</label>
                <input class="o-nhap" type="number" id="donToiThieu" name="donToiThieu" min="0" step="1000" placeholder="Ví dụ: 200000" value="<?= e($km['donToiThieu']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="soLuong">Số lượng lượt dùng:</label>
                <input class="o-nhap" type="number" id="soLuong" name="soLuong" min="1" placeholder="Để trống nếu không giới hạn" value="<?= e($km['soLuong']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="ngayBatDau">Ngày & giờ bắt đầu: (*)</label>
                <input class="o-nhap" type="datetime-local" id="ngayBatDau" name="ngayBatDau" required value="<?= e($km['ngayBatDau']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="ngayKetThuc">Ngày & giờ kết thúc: (*)</label>
                <input class="o-nhap" type="datetime-local" id="ngayKetThuc" name="ngayKetThuc" required value="<?= e($km['ngayKetThuc']) ?>">
            </div>

            <div class="full">
                <label class="nhan-truong" for="trangThai">Trạng thái phát hành:</label>
                <select class="o-chon" id="trangThai" name="trangThai">
                    <option value="1" <?= (int)$km['trangThai'] === 1 ? 'selected' : '' ?>>Kích hoạt (Cho phép sử dụng)</option>
                    <option value="0" <?= (int)$km['trangThai'] === 0 ? 'selected' : '' ?>>Tạm ẩn (Không cho phép áp dụng)</option>
                </select>
            </div>
        </div>

        <div class="hanh-dong" style="margin-top:24px;justify-content:flex-start">
            <button class="nut nut-chinh" type="submit"><?= $che_do === 'sua' ? 'Lưu thay đổi' : 'Tạo mã khuyến mãi' ?></button>
            <a class="nut" href="quan-ly-khuyen-mai.php">Hủy bỏ</a>
        </div>
    </form>

<?php endif; ?>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>