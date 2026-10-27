<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

$loi = [];
$che_do = 'danh-sach';
$sach = [
    'maSach' => 0, 'tenSach' => '', 'maDanhMuc' => 0, 'maNXB' => 0, 'nhaCungCap' => '', 'nguoiDich' => '',
    'namXuatBan' => '', 'hinhThuc' => 'Bìa mềm', 'trongLuong' => '', 'soTrang' => '', 'giaBan' => '',
    'giaNhap' => '', 'soLuongTon' => 0, 'hinhAnh' => '', 'moTa' => '', 'trangThai' => 1, 'tacGia' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('quan-ly-sach.php', 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'loi');
    }
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'xoa') {
        $ma = (int)($_POST['maSach'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM sach WHERE maSach = ?");
            $stmt->bind_param('i', $ma);
            $stmt->execute();
        } catch (mysqli_sql_exception $ex) {
            $stmt = $conn->prepare("UPDATE sach SET trangThai = 0 WHERE maSach = ?");
            $stmt->bind_param('i', $ma);
            $stmt->execute();
        }
        chuyen_huong('quan-ly-sach.php', 'Đã xử lý xóa/ẩn sách thành công.');
    }

    if ($hanhDong === 'luu') {
        $maGoc = (int)($_POST['maSach'] ?? 0);
        $sach = [
            'maSach' => $maGoc,
            'tenSach' => trim($_POST['tenSach'] ?? ''),
            'maDanhMuc' => (int)($_POST['maDanhMuc'] ?? 0),
            'maNXB' => (int)($_POST['maNXB'] ?? 0),
            'nhaCungCap' => trim($_POST['nhaCungCap'] ?? ''),
            'nguoiDich' => trim($_POST['nguoiDich'] ?? ''),
            'namXuatBan' => trim($_POST['namXuatBan'] ?? ''),
            'hinhThuc' => trim($_POST['hinhThuc'] ?? 'Bìa mềm'),
            'trongLuong' => trim($_POST['trongLuong'] ?? ''),
            'soTrang' => trim($_POST['soTrang'] ?? ''),
            'giaBan' => trim($_POST['giaBan'] ?? ''),
            'giaNhap' => trim($_POST['giaNhap'] ?? ''),
            'soLuongTon' => trim($_POST['soLuongTon'] ?? '0'),
            'moTa' => trim($_POST['moTa'] ?? ''),
            'trangThai' => ((int)($_POST['trangThai'] ?? 1) === 1) ? 1 : 0,
            'tacGia' => trim($_POST['tacGia'] ?? ''),
            'hinhAnh' => '',
        ];

        $anhCu = '';
        if ($maGoc > 0) {
            $cu = truy_van($conn, "SELECT hinhAnh FROM sach WHERE maSach = ?", 'i', [$maGoc])->fetch_assoc();
            $anhCu = $cu ? (string)$cu['hinhAnh'] : '';
        }
        $sach['hinhAnh'] = $anhCu;

        if ($sach['tenSach'] === '') { $loi[] = 'Vui lòng nhập tên đầu sách / giáo trình.'; }
        if ($sach['maDanhMuc'] <= 0) { $loi[] = 'Vui lòng chọn danh mục sách.'; }
        if ($sach['maNXB'] <= 0) { $loi[] = 'Vui lòng chọn nhà xuất bản.'; }
        if ($sach['tacGia'] === '') { $loi[] = 'Vui lòng nhập tác giả / nhóm biên soạn.'; }
        if (!is_numeric($sach['giaBan']) || (float)$sach['giaBan'] <= 0) { $loi[] = 'Giá bán phải lớn hơn 0.'; }
        if (!ctype_digit($sach['soLuongTon'])) { $loi[] = 'Số lượng tồn kho phải là số nguyên.'; }

        if ($sach['tenSach'] !== '') {
            $namXBVal = $sach['namXuatBan'] === '' ? null : (int)$sach['namXuatBan'];
            $hinhThucVal = $sach['hinhThuc'] === '' ? null : $sach['hinhThuc'];

            if ($namXBVal === null) {
                if ($maGoc > 0) {
                    $sqlCheck = "SELECT maSach FROM sach WHERE tenSach = ? AND namXuatBan IS NULL AND hinhThuc <=> ? AND maSach != ?";
                    $resCheck = truy_van($conn, $sqlCheck, 'ssi', [$sach['tenSach'], $hinhThucVal, $maGoc]);
                } else {
                    $sqlCheck = "SELECT maSach FROM sach WHERE tenSach = ? AND namXuatBan IS NULL AND hinhThuc <=> ?";
                    $resCheck = truy_van($conn, $sqlCheck, 'ss', [$sach['tenSach'], $hinhThucVal]);
                }
            } else {
                if ($maGoc > 0) {
                    $sqlCheck = "SELECT maSach FROM sach WHERE tenSach = ? AND namXuatBan = ? AND hinhThuc <=> ? AND maSach != ?";
                    $resCheck = truy_van($conn, $sqlCheck, 'sisi', [$sach['tenSach'], $namXBVal, $hinhThucVal, $maGoc]);
                } else {
                    $sqlCheck = "SELECT maSach FROM sach WHERE tenSach = ? AND namXuatBan = ? AND hinhThuc <=> ?";
                    $resCheck = truy_van($conn, $sqlCheck, 'sis', [$sach['tenSach'], $namXBVal, $hinhThucVal]);
                }
            }

            if ($rTrung = $resCheck->fetch_assoc()) {
                $idCu = (int)$rTrung['maSach'];
                $namHienThi = $sach['namXuatBan'] !== '' ? $sach['namXuatBan'] : 'Chưa xác định';
                $loi[] = 'Phiên bản sách này (' . e($sach['tenSach']) . ' - Năm XB: ' . e($namHienThi) . ' - ' . e($sach['hinhThuc']) . ') đã tồn tại trong hệ thống (Mã sách: #' . $idCu . '). <a href="quan-ly-sach.php?action=sua&id=' . $idCu . '" style="font-weight:bold; color:#0F4C45; text-decoration:underline">Bấm vào đây để chuyển sang cập nhật sách đó.</a>';
            }
        }

        $anhMoi = null;
        $f = $_FILES['hinhAnh'] ?? null;
        if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $loi[] = 'Tải ảnh bìa thất bại.';
            } elseif ($f['size'] > 2 * 1024 * 1024) {
                $loi[] = 'Ảnh bìa tối đa 2MB.';
            } else {
                $thongTin = @getimagesize($f['tmp_name']);
                $duoiFile = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
                if (!$thongTin || !isset($duoiFile[$thongTin[2]])) {
                    $loi[] = 'Ảnh bìa phải thuộc định dạng .jpg, .png hoặc .webp.';
                } else {
                    $anhMoi = ['tmp' => $f['tmp_name'], 'duoi' => $duoiFile[$thongTin[2]]];
                }
            }
        }

        $tenFileMoi = null;
        if (!$loi && $anhMoi) {
            if (!is_dir(THU_MUC_ANH)) { @mkdir(THU_MUC_ANH, 0755, true); }
            $tenFileMoi = 'sach_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $anhMoi['duoi'];
            if (!move_uploaded_file($anhMoi['tmp'], THU_MUC_ANH . $tenFileMoi)) {
                $loi[] = 'Không lưu được tệp tin ảnh bìa.';
                $tenFileMoi = null;
            }
        }

        if (!$loi) {
            $hinhAnhLuu = $tenFileMoi ?? ($anhCu !== '' ? $anhCu : null);
            $rongThanhNull = function ($v) { return $v === '' ? null : $v; };

            $maDanhMuc  = $sach['maDanhMuc'];
            $maNXB      = $sach['maNXB'];
            $nhaCC      = $rongThanhNull($sach['nhaCungCap']);
            $nguoiDich  = $rongThanhNull($sach['nguoiDich']);
            $namXB      = $sach['namXuatBan'] === '' ? null : (int)$sach['namXuatBan'];
            $tenSach    = $sach['tenSach'];
            $trongLuong = $sach['trongLuong'] === '' ? null : (float)$sach['trongLuong'];
            $soTrang    = $sach['soTrang'] === '' ? null : (int)$sach['soTrang'];
            $hinhThuc   = $rongThanhNull($sach['hinhThuc']);
            $giaBan     = (float)$sach['giaBan'];
            $giaNhap    = $sach['giaNhap'] !== '' ? (float)$sach['giaNhap'] : 0;
            $ton        = (int)$sach['soLuongTon'];
            $moTa       = $rongThanhNull($sach['moTa']);
            $trangThai  = $sach['trangThai'];
            $tacGiaTxt  = $sach['tacGia'];
            $ma         = $maGoc;

            try {
                $conn->begin_transaction();
                if ($maGoc > 0) {
                    $stmt = $conn->prepare("UPDATE sach SET maDanhMuc=?, maNXB=?, nhaCungCap=?, nguoiDich=?, namXuatBan=?, tenSach=?, trongLuong=?, soTrang=?, hinhThuc=?, giaBan=?, giaNhap=?, soLuongTon=?, hinhAnh=?, moTa=?, trangThai=? WHERE maSach=?");
                    $stmt->bind_param('iissisdisddissii', $maDanhMuc, $maNXB, $nhaCC, $nguoiDich, $namXB, $tenSach, $trongLuong, $soTrang, $hinhThuc, $giaBan, $giaNhap, $ton, $hinhAnhLuu, $moTa, $trangThai, $ma);
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("INSERT INTO sach (maDanhMuc, maNXB, nhaCungCap, nguoiDich, namXuatBan, tenSach, trongLuong, soTrang, hinhThuc, giaBan, giaNhap, soLuongTon, hinhAnh, moTa, trangThai) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('iissisdisddissi', $maDanhMuc, $maNXB, $nhaCC, $nguoiDich, $namXB, $tenSach, $trongLuong, $soTrang, $hinhThuc, $giaBan, $giaNhap, $ton, $hinhAnhLuu, $moTa, $trangThai);
                    $stmt->execute();
                    $ma = $conn->insert_id;
                }

                $stmtTGXoa = $conn->prepare("DELETE FROM sachtacgia WHERE maSach = ?");
                $stmtTGXoa->bind_param('i', $ma);
                $stmtTGXoa->execute();

                $stmtTimTG = $conn->prepare("SELECT maTacGia FROM tacgia WHERE tenTacGia = ?");
                $stmtTimTG->bind_param('s', $tacGiaTxt);
                $stmtTimTG->execute();
                $resTG = $stmtTimTG->get_result();

                if ($rTG = $resTG->fetch_assoc()) {
                    $maTG = (int)$rTG['maTacGia'];
                } else {
                    $stmtInsTG = $conn->prepare("INSERT INTO tacgia (tenTacGia) VALUES (?)");
                    $stmtInsTG->bind_param('s', $tacGiaTxt);
                    $stmtInsTG->execute();
                    $maTG = $conn->insert_id;
                }

                $stmtLuuSTG = $conn->prepare("INSERT INTO sachtacgia (maSach, maTacGia) VALUES (?, ?)");
                $stmtLuuSTG->bind_param('ii', $ma, $maTG);
                $stmtLuuSTG->execute();

                $conn->commit();
            } catch (mysqli_sql_exception $ex) {
                $conn->rollback();
                if ($tenFileMoi) { @unlink(THU_MUC_ANH . $tenFileMoi); }
                $loi[] = 'Lỗi cơ sở dữ liệu: ' . $ex->getMessage();
            }

            if (!$loi) {
                chuyen_huong('quan-ly-sach.php', $maGoc > 0 ? 'Đã cập nhật sách.' : 'Đã thêm sách mới thành công.');
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
        $row = truy_van($conn, "SELECT * FROM sach WHERE maSach = ?", 'i', [$id])->fetch_assoc();
        if (!$row) {
            chuyen_huong('quan-ly-sach.php', 'Không tìm thấy sách.', 'loi');
        }
        $sach = $row;
        $che_do = 'sua';
        
        $resTG = truy_van($conn, "SELECT tg.tenTacGia FROM sachtacgia st JOIN tacgia tg ON tg.maTacGia = st.maTacGia WHERE st.maSach = ?", 'i', [$id]);
        $arrTG = [];
        while ($r = $resTG->fetch_assoc()) { $arrTG[] = $r['tenTacGia']; }
        $sach['tacGia'] = implode(', ', $arrTG);
    }
}

$dsDanhMucMang = $conn->query("SELECT maDanhMuc, tenDanhMuc FROM danhmuc ORDER BY tenDanhMuc")->fetch_all(MYSQLI_ASSOC);
$dsNXBMang     = $conn->query("SELECT maNXB, tenNXB FROM nhaxuatban ORDER BY tenNXB")->fetch_all(MYSQLI_ASSOC);

if ($che_do === 'danh-sach') {
    $q = trim($_GET['q'] ?? '');
    $locDanhMuc = (int)($_GET['maDanhMuc'] ?? 0);
    $trang = max(1, (int)($_GET['trang'] ?? 1));
    $moiTrang = 10;

    $dieuKien = [];
    $kieu = '';
    $tham = [];
    if ($q !== '') {
        $dieuKien[] = "(s.tenSach LIKE ? OR s.maSach = ?)";
        $kieu .= 'si';
        $tham[] = '%' . $q . '%';
        $tham[] = (int)$q;
    }
    if ($locDanhMuc > 0) {
        $dieuKien[] = "s.maDanhMuc = ?";
        $kieu .= 'i';
        $tham[] = $locDanhMuc;
    }
    $where = $dieuKien ? ' WHERE ' . implode(' AND ', $dieuKien) : '';

    $tong = (int)truy_van($conn, "SELECT COUNT(*) FROM sach s" . $where, $kieu, $tham)->fetch_row()[0];
    $offset = ($trang - 1) * $moiTrang;
    $dsSach = truy_van($conn,
        "SELECT s.maSach, s.tenSach, s.giaBan, s.soLuongTon, s.hinhAnh, s.trangThai, dm.tenDanhMuc,
                (SELECT GROUP_CONCAT(tg.tenTacGia SEPARATOR ', ') FROM sachtacgia st JOIN tacgia tg ON tg.maTacGia = st.maTacGia WHERE st.maSach = s.maSach) AS tacGia
         FROM sach s LEFT JOIN danhmuc dm ON dm.maDanhMuc = s.maDanhMuc" . $where . "
         ORDER BY s.maSach DESC LIMIT " . $moiTrang . " OFFSET " . $offset, $kieu, $tham);
    $thamSoLoc = array_filter(['q' => $q, 'maDanhMuc' => $locDanhMuc ?: ''], function ($v) { return $v !== '' && $v !== null; });
}

$trang_hien_tai = 'sach';
$tieu_de_trang = 'Quản lý sách';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<?php if ($che_do === 'danh-sach'): ?>

    <div class="dau-trang">
        <h1>Quản lý sách <span class="phu" style="font-weight:500">(<?= $tong ?> cuốn)</span></h1>
        <a href="quan-ly-sach.php?action=them" class="nut nut-chinh">+ Thêm sách mới</a>
    </div>

    <div class="card">
        <form method="GET" action="quan-ly-sach.php" class="bo-loc">
            <div class="tim">
                <label class="nhan-truong" for="q">Tìm kiếm</label>
                <input class="o-nhap" type="search" id="q" name="q" placeholder="Tên sách hoặc mã sách" value="<?= e($q) ?>">
            </div>
            <div>
                <label class="nhan-truong" for="maDanhMuc">Danh mục</label>
                <select class="o-chon" id="maDanhMuc" name="maDanhMuc">
                    <option value="0">Tất cả</option>
                    <?php foreach ($dsDanhMucMang as $dm): ?>
                        <option value="<?= (int)$dm['maDanhMuc'] ?>" <?= $locDanhMuc === (int)$dm['maDanhMuc'] ? 'selected' : '' ?>><?= e($dm['tenDanhMuc']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="nut nut-chinh" type="submit">Lọc</button>
            <?php if ($thamSoLoc): ?><a class="nut" href="quan-ly-sach.php">Xóa lọc</a><?php endif; ?>
        </form>

        <?php if ($dsSach->num_rows > 0): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr><th>Ảnh</th><th>Tên sách</th><th>Tác giả</th><th class="phai-so">Giá bán</th><th class="phai-so">Tồn</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($s = $dsSach->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($s['hinhAnh'])): ?>
                                    <img class="anh-nho" src="../tai-nguyen/hinh-anh/<?= e($s['hinhAnh']) ?>" alt="" onerror="this.style.visibility='hidden'">
                                <?php else: ?>
                                    <div class="anh-nho"></div>
                                <?php endif; ?>
                            </td>
                            <td style="min-width:200px">
                                <strong><?= e($s['tenSach']) ?></strong>
                                <div class="phu">MS-<?= (int)$s['maSach'] ?> · <?= e($s['tenDanhMuc'] ?? 'Chưa phân loại') ?></div>
                            </td>
                            <td><?= e($s['tacGia'] ?? '—') ?></td>
                            <td class="phai-so"><strong><?= vnd($s['giaBan']) ?></strong></td>
                            <td class="phai-so">
                                <span style="<?= (int)$s['soLuongTon'] <= NGUONG_SAP_HET ? 'color:#B93A12;font-weight:700' : '' ?>"><?= (int)$s['soLuongTon'] ?></span>
                            </td>
                            <td>
                                <?php if ((int)$s['trangThai'] === 1): ?>
                                    <span class="nhan-tt" style="color:#0F4C45;background:#DCEFE8">Đang bán</span>
                                <?php else: ?>
                                    <span class="nhan-tt" style="color:#55635D;background:#EAEFEA">Đã ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="hanh-dong">
                                    <a class="nut nut-nho" href="quan-ly-sach.php?action=sua&id=<?= (int)$s['maSach'] ?>">Sửa</a>
                                    <form method="POST" action="quan-ly-sach.php" onsubmit="return confirm('Xóa sách này?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="xoa">
                                        <input type="hidden" name="maSach" value="<?= (int)$s['maSach'] ?>">
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
            <div class="trong">Không tìm thấy cuốn sách nào.</div>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="dau-trang">
        <h1>Quản trị (<?= $che_do === 'sua' ? 'Sửa sách #' . (int)$sach['maSach'] : 'Thêm sách' ?>)</h1>
        <a class="nut" href="quan-ly-sach.php">← Quay lại</a>
    </div>

    <?php if ($loi): ?>
        <div class="thong-bao thong-bao-loi" style="margin-bottom:20px">
            <ul style="margin:0;padding-left:20px"><?php foreach ($loi as $l): ?><li><?= $l ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form class="card" method="POST" action="quan-ly-sach.php" enctype="multipart/form-data">
        <h2 style="font-size:15px;color:var(--phu);margin-bottom:20px;border-bottom:1px solid var(--vien);padding-bottom:10px">Thông tin cuốn sách</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="hanh_dong" value="luu">
        <input type="hidden" name="maSach" value="<?= (int)$sach['maSach'] ?>">

        <div class="luoi-form">
            <div class="full">
                <label class="nhan-truong" for="tenSach">Tên đầu sách / giáo trình: (*)</label>
                <input class="o-nhap" type="text" id="tenSach" name="tenSach" maxlength="150" required value="<?= e($sach['tenSach']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="maDanhMuc">Danh mục sách: (*)</label>
                <select class="o-chon" id="maDanhMuc" name="maDanhMuc" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($dsDanhMucMang as $dm): ?>
                        <option value="<?= (int)$dm['maDanhMuc'] ?>" <?= (int)$sach['maDanhMuc'] === (int)$dm['maDanhMuc'] ? 'selected' : '' ?>><?= e($dm['tenDanhMuc']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="nhan-truong" for="maNXB">Nhà xuất bản: (*)</label>
                <select class="o-chon" id="maNXB" name="maNXB" required>
                    <option value="">-- Chọn nhà xuất bản --</option>
                    <?php foreach ($dsNXBMang as $nxb): ?>
                        <option value="<?= (int)$nxb['maNXB'] ?>" <?= (int)$sach['maNXB'] === (int)$nxb['maNXB'] ? 'selected' : '' ?>><?= e($nxb['tenNXB']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="nhan-truong" for="tacGia">Tác giả / Nhóm biên soạn: (*)</label>
                <input class="o-nhap" type="text" id="tacGia" name="tacGia" placeholder="Ví dụ TS. Nguyễn Minh Tuấn" required value="<?= e($sach['tacGia']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="nguoiDich">Người dịch:</label>
                <input class="o-nhap" type="text" id="nguoiDich" name="nguoiDich" placeholder="Nếu có người dịch" value="<?= e($sach['nguoiDich']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="nhaCungCap">Nhà cung cấp / Đối tác:</label>
                <input class="o-nhap" type="text" id="nhaCungCap" name="nhaCungCap" placeholder="Tên đơn vị cung cấp" value="<?= e($sach['nhaCungCap']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="giaBan">Đơn giá bán (VNĐ): (*)</label>
                <input class="o-nhap" type="number" id="giaBan" name="giaBan" min="0" step="500" placeholder="Ví dụ: 95000" required value="<?= e($sach['giaBan'] !== '' ? (float)$sach['giaBan'] : '') ?>">
            </div>

            <div>
                <label class="nhan-truong" for="giaNhap">Đơn giá nhập (VNĐ):</label>
                <input class="o-nhap" type="number" id="giaNhap" name="giaNhap" min="0" step="500" placeholder="Ví dụ: 70000" value="<?= e($sach['giaNhap'] !== '' ? (float)$sach['giaNhap'] : '') ?>">
            </div>

            <div>
                <label class="nhan-truong" for="soLuongTon">Số lượng nhập kho: (*)</label>
                <input class="o-nhap" type="number" id="soLuongTon" name="soLuongTon" min="0" step="1" placeholder="50" required value="<?= e($sach['soLuongTon']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="hinhThuc">Hình thức bìa:</label>
                <select class="o-chon" id="hinhThuc" name="hinhThuc">
                    <option value="Bìa mềm" <?= $sach['hinhThuc'] === 'Bìa mềm' ? 'selected' : '' ?>>Bìa mềm</option>
                    <option value="Bìa cứng" <?= $sach['hinhThuc'] === 'Bìa cứng' ? 'selected' : '' ?>>Bìa cứng</option>
                </select>
            </div>

            <div>
                <label class="nhan-truong" for="namXuatBan">Năm xuất bản:</label>
                <input class="o-nhap" type="number" id="namXuatBan" name="namXuatBan" min="1900" max="<?= (int)date('Y') + 1 ?>" placeholder="Ví dụ: 2024" value="<?= e($sach['namXuatBan']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="soTrang">Số trang:</label>
                <input class="o-nhap" type="number" id="soTrang" name="soTrang" min="1" placeholder="Ví dụ: 350" value="<?= e($sach['soTrang']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="trongLuong">Trọng lượng (gram):</label>
                <input class="o-nhap" type="number" id="trongLuong" name="trongLuong" min="0" step="1" placeholder="Ví dụ: 450" value="<?= e($sach['trongLuong'] !== '' && $sach['trongLuong'] !== null ? (float)$sach['trongLuong'] : '') ?>">
            </div>

            <div>
                <label class="nhan-truong" for="trangThai">Trạng thái hiển thị:</label>
                <select class="o-chon" id="trangThai" name="trangThai">
                    <option value="1" <?= (int)$sach['trangThai'] === 1 ? 'selected' : '' ?>>Đang bán</option>
                    <option value="0" <?= (int)$sach['trangThai'] === 0 ? 'selected' : '' ?>>Ẩn khỏi cửa hàng</option>
                </select>
            </div>

            <div class="full">
                <label class="nhan-truong" for="hinhAnh">Tập tin ảnh bìa sách (.jpg, .png, .webp): (*)</label>
                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 10px; width: 100%;">
                    <input type="file" id="hinhAnh" name="hinhAnh" accept="image/jpeg,image/png,image/webp">
                    <img id="xem-truoc" alt="Xem trước ảnh bìa" src="<?= !empty($sach['hinhAnh']) ? '../tai-nguyen/hinh-anh/' . e($sach['hinhAnh']) : '' ?>"
                         style="width:96px;aspect-ratio:3/4;object-fit:cover;border-radius:6px;background:#EAEFEA;<?= empty($sach['hinhAnh']) ? 'display:none' : '' ?>"
                         onerror="this.style.display='none'">
                </div>
            </div>

            <div class="full">
                <label class="nhan-truong" for="moTa">Mô tả nội dung tóm tắt & mục lục: (*)</label>
                <textarea class="o-nhap" id="moTa" name="moTa" placeholder="Tóm tắt nội dung giáo trình, đối tượng học viên..."><?= e($sach['moTa']) ?></textarea>
            </div>
        </div>

        <div class="hanh-dong" style="margin-top:24px;justify-content:flex-start">
            <button class="nut nut-chinh" type="submit"><?= $che_do === 'sua' ? 'Lưu thay đổi' : 'Lưu vào Cơ Sở Dữ Liệu' ?></button>
            <a class="nut" href="quan-ly-sach.php">Hủy bỏ</a>
        </div>
    </form>

    <script>
        document.getElementById('hinhAnh').addEventListener('change', function () {
            var file = this.files && this.files[0];
            var img = document.getElementById('xem-truoc');
            if (!file) { return; }
            img.src = URL.createObjectURL(file);
            img.style.display = 'block';
        });
    </script>

<?php endif; ?>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>