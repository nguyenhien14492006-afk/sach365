<?php
require_once 'dung-chung/dau-trang.php';
require_once 'dung-chung/khoi-tao.php';

class LoiDatHang extends Exception {}

if (!isset($_SESSION['maKH'])) {
    echo "<script>alert('Vui lòng đăng nhập để tiến hành đặt hàng!'); window.location.href='dang-nhap.php';</script>";
    exit();
}

if (empty($_SESSION['gio_hang']) || !is_array($_SESSION['gio_hang'])) {
    echo "<script>alert('Giỏ hàng của bạn đang trống!'); window.location.href='gio-hang.php';</script>";
    exit();
}

$maKH = (int)$_SESSION['maKH'];
$mien_phi_tu = 250000;

$ids = [];
foreach (array_keys($_SESSION['gio_hang']) as $k) {
    if ((int)$k > 0) {
        $ids[(int)$k] = true;
    }
}
$ids = array_keys($_SESSION['gio_hang'] ?? []);

$tu_db = [];
if (!empty($ids)) {
    $dau_hoi = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $rs = truy_van($conn, "SELECT maSach, tenSach, giaBan, hinhAnh, soLuongTon, trangThai FROM sach WHERE maSach IN ($dau_hoi)", $types, array_values($ids));
    while ($r = $rs->fetch_assoc()) {
        $tu_db[(int)$r['maSach']] = $r;
    }
}

$san_pham = [];
$canh_bao = [];
$khong_dat_duoc = false;
foreach ($ids as $id) {
    $sl = max(1, (int)($_SESSION['gio_hang'][$id]['so_luong'] ?? 1));
    if (!isset($tu_db[$id]) || (int)$tu_db[$id]['trangThai'] !== 1) {
        $canh_bao[] = 'Một sản phẩm trong giỏ không còn được bán nên đã được bỏ khỏi đơn hàng.';
        unset($_SESSION['gio_hang'][$id]);
        continue;
    }
    $s = $tu_db[$id];
    $ton = (int)$s['soLuongTon'];
    if ($ton < $sl) {
        $khong_dat_duoc = true;
        $canh_bao[] = $ton <= 0
            ? '"' . $s['tenSach'] . '" đã hết hàng.'
            : '"' . $s['tenSach'] . '" chỉ còn ' . $ton . ' cuốn (bạn đang chọn ' . $sl . ').';
    }
    $san_pham[] = [
        'maSach' => $id,
        'tenSach' => $s['tenSach'],
        'hinhAnh' => $s['hinhAnh'],
        'giaBan' => (float)$s['giaBan'],
        'so_luong' => $sl,
    ];
}

if (!$san_pham) {
    echo "<script>alert('Giỏ hàng của bạn đang trống!'); window.location.href='gio-hang.php';</script>";
    exit();
}

$tong_tien_hang = 0;
$tong_so_luong = 0;
foreach ($san_pham as $sp) {
    $tong_tien_hang += $sp['giaBan'] * $sp['so_luong'];
    $tong_so_luong += $sp['so_luong'];
}

$stmt = $conn->prepare("SELECT hoTen, sdt, diaChi, email FROM khachhang WHERE maKH = ? LIMIT 1");
$stmt->bind_param('i', $maKH);
$stmt->execute();
$khach_hang = $stmt->get_result()->fetch_assoc() ?: [];
if (!$khach_hang) {
    echo "<script>alert('Không tìm thấy tài khoản. Vui lòng đăng nhập lại!'); window.location.href='dang-xuat.php';</script>";
    exit();
}

$stmt = $conn->prepare("SELECT maDiaChi, tenNguoiNhan, sdtNguoiNhan, diaChiChiTiet, laMacDinh FROM diachikhachhang WHERE maKH = ? ORDER BY laMacDinh DESC, maDiaChi ASC");
$stmt->bind_param('i', $maKH);
$stmt->execute();
$dia_chi_da_luu = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$form = [
    'hoTenNhan' => (string)$khach_hang['hoTen'],
    'sdtNhan' => (string)($khach_hang['sdt'] ?? ''),
    'diaChiGiao' => (string)($khach_hang['diaChi'] ?? ''),
    'ghiChu' => '',
    'phuongThucTT' => 'COD',
    'maVoucher' => '',
    'chon_dia_chi' => 'tk',
    'luu_dia_chi' => true,
];

if (empty($_SESSION['csrf_dat_hang'])) {
    $_SESSION['csrf_dat_hang'] = bin2hex(random_bytes(16));
}

$hanh_dong = (string)($_POST['hanh_dong'] ?? '');
$la_post = $_SERVER['REQUEST_METHOD'] === 'POST' && in_array($hanh_dong, ['dat-hang', 'ap-dung', 'go-ma'], true);

$voucher = null;
$loi_voucher = '';
$loi = [];
$loi_truong = [];

if ($la_post) {
    foreach (['hoTenNhan', 'sdtNhan', 'diaChiGiao', 'ghiChu', 'phuongThucTT', 'maVoucher'] as $truong) {
        $form[$truong] = trim((string)($_POST[$truong] ?? ''));
    }
    $form['sdtNhan'] = preg_replace('/[\s.\-]/', '', $form['sdtNhan']);
    $chon = (string)($_POST['chon_dia_chi'] ?? 'tk');
    $form['chon_dia_chi'] = preg_match('/^(tk|moi|dc_\d+)$/', $chon) ? $chon : 'tk';
    $form['luu_dia_chi'] = isset($_POST['luu_dia_chi']);

    if ($hanh_dong === 'go-ma') {
        $form['maVoucher'] = '';
    }
}

$phi_van_chuyen = tinh_phi_ship($form['diaChiGiao'], $tong_tien_hang, $mien_phi_tu);

if ($la_post && $form['maVoucher'] !== '') {
    list($voucher, $loi_voucher) = kiem_tra_voucher($conn, $form['maVoucher'], $maKH, $tong_tien_hang, $phi_van_chuyen);
    if ($voucher === null) {
        $loi_voucher = (string)$loi_voucher;
    }
}

$giam_gia = $voucher ? (float)$voucher['giam'] : 0;
$tong_thanh_toan = max(0, $tong_tien_hang + $phi_van_chuyen - $giam_gia);

if ($la_post && $hanh_dong === 'dat-hang') {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf_dat_hang'], (string)$_POST['csrf'])) {
        $loi[] = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
    }
    if ($form['hoTenNhan'] === '') {
        $loi[] = 'Vui lòng nhập Họ và tên người nhận!';
        $loi_truong['hoTenNhan'] = true;
    } elseif (mb_strlen($form['hoTenNhan']) > 50) {
        $loi[] = 'Họ và tên người nhận tối đa 50 ký tự.';
        $loi_truong['hoTenNhan'] = true;
    }
    if ($form['sdtNhan'] === '') {
        $loi[] = 'Vui lòng nhập Số điện thoại nhận hàng!';
        $loi_truong['sdtNhan'] = true;
    } elseif (!preg_match('/^(\+84|0)\d{9,10}$/', $form['sdtNhan'])) {
        $loi[] = 'Số điện thoại nhận hàng không hợp lệ.';
        $loi_truong['sdtNhan'] = true;
    }
    if ($form['diaChiGiao'] === '') {
        $loi[] = 'Vui lòng nhập Địa chỉ giao hàng!';
        $loi_truong['diaChiGiao'] = true;
    } elseif (mb_strlen($form['diaChiGiao']) < 10) {
        $loi[] = 'Địa chỉ giao hàng quá ngắn, vui lòng nhập chi tiết (số nhà, đường, phường/xã, quận/huyện, tỉnh/thành).';
        $loi_truong['diaChiGiao'] = true;
    } elseif (mb_strlen($form['diaChiGiao']) > 200) {
        $loi[] = 'Địa chỉ giao hàng tối đa 200 ký tự.';
        $loi_truong['diaChiGiao'] = true;
    }
    if (!in_array($form['phuongThucTT'], ['COD', 'Banking'], true)) {
        $loi[] = 'Phương thức thanh toán không hợp lệ.';
    }
    if ($form['maVoucher'] !== '' && $voucher === null) {
        $loi[] = 'Mã khuyến mãi: ' . $loi_voucher;
    }
    if ($khong_dat_duoc) {
        $loi[] = 'Có sách không đủ số lượng trong kho. Vui lòng quay lại giỏ hàng để điều chỉnh.';
    }

    if (!$loi) {
        try {
            $conn->begin_transaction();

// Nếu không có voucher, gán bằng 0 để kết hợp với NULLIF trong SQL
$maKM = ($voucher && !empty($voucher['maKM'])) ? (int)$voucher['maKM'] : 0;

$stmt_dh = $conn->prepare("INSERT INTO donhang (maKH, maKM, tenNguoiNhan, sdtNguoiNhan, diaChiGiao, tongSoLuongSanPham, tongTienHang, phiVanChuyen, tienGiamGia, tongTien, phuongThucTT, trangThaiTT, trangThaiDonHang) VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)");

// Chuỗi types 'iisssidddds': 11 tham số tương ứng đúng 11 dấu '?'
$stmt_dh->bind_param('iisssidddds', $maKH, $maKM, $form['hoTenNhan'], $form['sdtNhan'], $form['diaChiGiao'], $tong_so_luong, $tong_tien_hang, $phi_van_chuyen, $giam_gia, $tong_thanh_toan, $form['phuongThucTT']);
$stmt_dh->execute();
$maDonHang = $conn->insert_id;

$stmt_ct = $conn->prepare("INSERT INTO chitietdonhang (maDonHang, maSach, soLuong, donGia) VALUES (?, ?, ?, ?)");
            $stmt_check_col = $conn->query("SHOW COLUMNS FROM sach LIKE 'soLuongTon'");
            $cotTon = ($stmt_check_col && $stmt_check_col->num_rows > 0) ? 'soLuongTon' : 'soLuong';

            $stmt_kho = $conn->prepare("UPDATE sach SET {$cotTon} = {$cotTon} - ? WHERE maSach = ? AND {$cotTon} >= ? AND trangThai = 1");
            foreach ($san_pham as $sp) {
                $maSach = $sp['maSach'];
                $soLuong = $sp['so_luong'];
                $donGia = $sp['giaBan'];
                $stmt_ct->bind_param('iiid', $maDonHang, $maSach, $soLuong, $donGia);
                $stmt_ct->execute();

                $stmt_kho->bind_param('iii', $soLuong, $maSach, $soLuong);
                $stmt_kho->execute();
                if ($stmt_kho->affected_rows < 1) {
                    throw new LoiDatHang('"' . $sp['tenSach'] . '" vừa hết hàng hoặc không đủ số lượng. Vui lòng kiểm tra lại giỏ hàng.');
                }
            }

            if ($voucher && !empty($voucher['gioi_han'])) {
                $stmt_km = $conn->prepare("UPDATE khuyenmai SET soLuong = soLuong - 1 WHERE maKM = ? AND soLuong > 0");
                $stmt_km->bind_param('i', $maKM);
                $stmt_km->execute();
                if ($stmt_km->affected_rows < 1) {
                    throw new LoiDatHang('Mã khuyến mãi vừa hết lượt sử dụng. Vui lòng bỏ mã và đặt lại.');
                }
            }

            if ($form['chon_dia_chi'] === 'moi' && $form['luu_dia_chi']) {
                $stmt_tr = $conn->prepare("SELECT 1 FROM diachikhachhang WHERE maKH = ? AND sdtNguoiNhan = ? AND diaChiChiTiet = ? LIMIT 1");
                $stmt_tr->bind_param('iss', $maKH, $form['sdtNhan'], $form['diaChiGiao']);
                $stmt_tr->execute();
                if ($stmt_tr->get_result()->num_rows === 0) {
                    $stmt_dc = $conn->prepare("INSERT INTO diachikhachhang (maKH, tenNguoiNhan, sdtNguoiNhan, diaChiChiTiet, laMacDinh) VALUES (?, ?, ?, ?, 0)");
                    $stmt_dc->bind_param('isss', $maKH, $form['hoTenNhan'], $form['sdtNhan'], $form['diaChiGiao']);
                    $stmt_dc->execute();
                }
            }

            $ghi_chu_ls = 'Đặt hàng thành công';
            if ($form['ghiChu'] !== '') {
                $ghi_chu_ls .= ' · Khách ghi chú: ' . $form['ghiChu'];
            }
            $ghi_chu_ls = mb_substr($ghi_chu_ls, 0, 200);
            $stmt_ls = $conn->prepare("INSERT INTO lichsutrangthai (maDonHang, trangThai, ghiChu) VALUES (?, 0, ?)");
            $stmt_ls->bind_param('is', $maDonHang, $ghi_chu_ls);
            $stmt_ls->execute();

            $conn->commit();

            if (!empty($khach_hang['email'])) {
                $to = $khach_hang['email'];
                $subject = "=?UTF-8?B?" . base64_encode("Xác nhận đơn hàng #" . $maDonHang . " - Sach365") . "?=";
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Sach365 <cskh@sach365.com>\r\n";

                $msg = "<h2>Cảm ơn bạn đã đặt hàng tại Sach365!</h2>";
                $msg .= "<p>Mã đơn hàng: <strong>#" . (int)$maDonHang . "</strong></p>";
                $msg .= "<p>Người nhận: " . e($form['hoTenNhan']) . " (" . e($form['sdtNhan']) . ")</p>";
                $msg .= "<p>Địa chỉ giao hàng: " . e($form['diaChiGiao']) . "</p>";
                $msg .= "<h3>Sản phẩm đã đặt:</h3><ul>";
                foreach ($san_pham as $sp) {
                    $msg .= "<li>" . e($sp['tenSach']) . " x " . $sp['so_luong'] . " - " . vnd($sp['giaBan'] * $sp['so_luong']) . "</li>";
                }
                $msg .= "</ul>";
                $msg .= "<p>Tổng tiền hàng: " . vnd($tong_tien_hang) . "</p>";
                $msg .= "<p>Phí vận chuyển: " . ($phi_van_chuyen == 0 ? "Miễn phí" : vnd($phi_van_chuyen)) . "</p>";
                if ($giam_gia > 0) {
                    $msg .= "<p>Giảm giá: -" . vnd($giam_gia) . "</p>";
                }
                $msg .= "<p><strong>Tổng tiền thanh toán: " . vnd($tong_thanh_toan) . "</strong></p>";

                @mail($to, $subject, $msg, $headers);
            }

            unset($_SESSION['gio_hang']);
            $_SESSION['csrf_dat_hang'] = bin2hex(random_bytes(16));

            echo "<script>alert('Đặt hàng thành công! Mã đơn hàng của bạn: #" . (int)$maDonHang . "'); window.location.href='lich-su-don-hang.php';</script>";
            exit();
        } catch (LoiDatHang $ex) {
            $conn->rollback();
            $loi[] = $ex->getMessage();
        } catch (Throwable $ex) {
            $conn->rollback();
            error_log('dat-hang.php: ' . $ex->getMessage());
            $loi[] = 'Có lỗi xảy ra trong quá trình xử lý đơn hàng. Vui lòng thử lại!';
        }
    }
}

$hien_o_luu_dia_chi = ($form['chon_dia_chi'] === 'moi');
?>

<?php if ($loi): ?>
    <div id="khung-loi" role="alert" style="background: #FDF2F2; border: 1.5px solid #E58A8A; color: #B42318; padding: 14px 18px; border-radius: 8px; font-size: 14px; margin-bottom: 24px; font-weight: 600">
        <?php foreach ($loi as $l): ?><div><?= e($l) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<nav style="font-size: 13px; color: #55635D; margin-bottom: 24px">
    <a href="index.php" style="text-decoration: none;">Trang chủ</a> /
    <a href="gio-hang.php" style="text-decoration: none;">Giỏ hàng</a> /
    <span style="color: #14201C; font-weight: 600;">Đặt hàng &amp; Thanh toán</span>
</nav>

<h1 style="margin: 0 0 24px; font-size: 28px; font-weight: 800; color: #14201C">Đặt Hàng &amp; Thanh Toán</h1>

<?php if ($canh_bao && !$loi): ?>
    <div role="status" style="background: #FEF3C7; border: 1px solid #F3D88A; color: #6B3A06; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 24px">
        <?php foreach ($canh_bao as $c): ?><div><?= e($c) ?></div><?php endforeach; ?>
        <?php if ($khong_dat_duoc): ?><div style="margin-top:6px"><a href="gio-hang.php" style="font-weight:700; color:#0F4C45">← Quay lại giỏ hàng để điều chỉnh</a></div><?php endif; ?>
    </div>
<?php endif; ?>

<form action="dat-hang.php" method="POST" id="form-dat-hang" novalidate style="display: flex; flex-direction: column; gap: 24px">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf_dat_hang']) ?>">
    <button type="submit" name="hanh_dong" value="dat-hang" class="dh-an" tabindex="-1" aria-hidden="true">Đặt hàng</button>

    <div class="dh-the">
        <h2>1. Sản phẩm đặt mua</h2>
        <div style="display: flex; flex-direction: column; gap: 12px">
            <?php foreach ($san_pham as $sp): ?>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-bottom: 12px; border-bottom: 1px solid #F0F3F0">
                    <div style="display: flex; align-items: center; gap: 12px; min-width: 0">
                        <img src="tai-nguyen/hinh-anh/<?= e($sp['hinhAnh']) ?>" alt="<?= e($sp['tenSach']) ?>" style="width: 48px; height: 64px; object-fit: cover; border-radius: 6px; background: #F7F8F5; flex-shrink: 0" onerror="this.src='https://via.placeholder.com/48x64?text=Sach365'">
                        <div style="min-width: 0">
                            <div style="font-weight: 700; font-size: 15px; color: #14201C"><?= e($sp['tenSach']) ?></div>
                            <div style="font-size: 13px; color: #55635D"><?= vnd($sp['giaBan']) ?> × <?= (int)$sp['so_luong'] ?></div>
                        </div>
                    </div>
                    <div style="font-weight: 700; font-size: 15px; color: #0F4C45; white-space: nowrap">
                        <?= vnd($sp['giaBan'] * $sp['so_luong']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dh-the">
        <h2>2. Thông tin giao hàng</h2>

        <div style="display: flex; flex-direction: column; gap: 16px">
            <div>
                <label class="dh-nhan" for="chon-dia-chi">Địa chỉ nhận hàng</label>
                <select id="chon-dia-chi" name="chon_dia_chi" class="dh-nhap">
                    <option value="tk" data-ten="<?= e($khach_hang['hoTen']) ?>" data-sdt="<?= e($khach_hang['sdt'] ?? '') ?>" data-dc="<?= e($khach_hang['diaChi'] ?? '') ?>" <?= $form['chon_dia_chi'] === 'tk' ? 'selected' : '' ?>>
                        Địa chỉ mặc định tài khoản<?= !empty($khach_hang['diaChi']) ? ' - ' . e($khach_hang['diaChi']) : '' ?>
                    </option>
                    <?php foreach ($dia_chi_da_luu as $dc): ?>
                        <?php $gt = 'dc_' . (int)$dc['maDiaChi']; ?>
                        <option value="<?= $gt ?>" data-ten="<?= e($dc['tenNguoiNhan']) ?>" data-sdt="<?= e($dc['sdtNguoiNhan']) ?>" data-dc="<?= e($dc['diaChiChiTiet']) ?>" <?= $form['chon_dia_chi'] === $gt ? 'selected' : '' ?>>
                            <?= e($dc['tenNguoiNhan']) ?> - <?= e($dc['diaChiChiTiet']) ?><?= (int)$dc['laMacDinh'] === 1 ? ' (Mặc định)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="moi" <?= $form['chon_dia_chi'] === 'moi' ? 'selected' : '' ?>>+ Nhập địa chỉ khác...</option>
                </select>
            </div>

            <div>
                <label class="dh-nhan" for="hoTenNhan">Họ và tên người nhận (*)</label>
                <input class="dh-nhap <?= isset($loi_truong['hoTenNhan']) ? 'loi-o' : '' ?>" type="text" id="hoTenNhan" name="hoTenNhan" maxlength="50" value="<?= e($form['hoTenNhan']) ?>">
            </div>

            <div>
                <label class="dh-nhan" for="sdtNhan">Số điện thoại nhận hàng (*)</label>
                <input class="dh-nhap <?= isset($loi_truong['sdtNhan']) ? 'loi-o' : '' ?>" type="tel" id="sdtNhan" name="sdtNhan" maxlength="15" placeholder="0901234567" value="<?= e($form['sdtNhan']) ?>">
            </div>

            <div>
                <label class="dh-nhan" for="diaChiGiao">Địa chỉ giao hàng (*)</label>
                <textarea class="dh-nhap <?= isset($loi_truong['diaChiGiao']) ? 'loi-o' : '' ?>" id="diaChiGiao" name="diaChiGiao" maxlength="200" placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= e($form['diaChiGiao']) ?></textarea>
            </div>

            <label id="o-luu-dia-chi" style="display: <?= $hien_o_luu_dia_chi ? 'flex' : 'none' ?>; align-items: center; gap: 10px; font-size: 14px; cursor: pointer; min-height: 32px">
                <input type="checkbox" name="luu_dia_chi" value="1" <?= $form['luu_dia_chi'] ? 'checked' : '' ?> style="accent-color: #0F4C45; width: 18px; height: 18px">
                Lưu địa chỉ này vào sổ địa chỉ để sử dụng cho lần sau
            </label>

            <div>
                <label class="dh-nhan" for="ghiChu">Ghi chú đơn hàng (không bắt buộc)</label>
                <input class="dh-nhap" type="text" id="ghiChu" name="ghiChu" maxlength="120" placeholder="Ví dụ: Giao giờ hành chính..." value="<?= e($form['ghiChu']) ?>">
            </div>
        </div>
    </div>

    <div class="dh-the" id="thanh-toan">
        <h2>3. Thanh toán</h2>

        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px">
            <label class="dh-tt <?= $form['phuongThucTT'] === 'COD' ? 'chon' : '' ?>">
                <input type="radio" name="phuongThucTT" value="COD" <?= $form['phuongThucTT'] === 'COD' ? 'checked' : '' ?> style="accent-color: #0F4C45; width: 18px; height: 18px">
                <span style="font-weight: 600; font-size: 14px; color: #14201C">Thanh toán khi nhận hàng (COD)</span>
            </label>

            <label class="dh-tt <?= $form['phuongThucTT'] === 'Banking' ? 'chon' : '' ?>">
                <input type="radio" name="phuongThucTT" value="Banking" <?= $form['phuongThucTT'] === 'Banking' ? 'checked' : '' ?> style="accent-color: #0F4C45; width: 18px; height: 18px">
                <span style="font-weight: 600; font-size: 14px; color: #14201C">Chuyển khoản ngân hàng</span>
            </label>
        </div>

        <div id="ghi-chu-banking" style="display: <?= $form['phuongThucTT'] === 'Banking' ? 'block' : 'none' ?>; background: #E6F0EC; border-radius: 8px; padding: 14px 16px; font-size: 14px; color: #14201C; margin-bottom: 16px; line-height: 1.6">
            Sau khi đặt hàng, vui lòng chuyển khoản đúng số tiền với nội dung <strong>SACH365 + mã đơn hàng</strong>.<br>
            Ngân hàng: Vietcombank · Số tài khoản: 1234567890 · Chủ tài khoản: NHÀ SÁCH SACH365<br>
            Đơn sẽ được xử lý sau khi cửa hàng xác nhận đã nhận tiền.
        </div>

        <div style="padding: 16px 0; border-top: 1px solid #EAEFEA; margin-bottom: 4px">
            <label class="dh-nhan" for="maVoucher">Mã khuyến mãi</label>
            <div style="display: flex">
                <input class="dh-nhap" style="border-radius: 8px 0 0 8px; border-right: none; text-transform: uppercase" type="text" id="maVoucher" name="maVoucher" maxlength="20" placeholder="Nhập mã khuyến mãi" value="<?= e($form['maVoucher']) ?>" autocomplete="off">
                <button type="submit" name="hanh_dong" value="ap-dung" formaction="dat-hang.php#thanh-toan" id="btn-ap-dung" class="dh-nut-phu">Áp dụng</button>
            </div>
            <?php if ($voucher): ?>
                <div role="status" style="margin-top: 10px; background: #E6F0EC; border-radius: 8px; padding: 10px 14px; font-size: 14px; color: #0F4C45; display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap">
                    <span>Đã áp dụng mã <strong><?= e($voucher['maVoucher']) ?></strong>: <?= e($voucher['tenKM']) ?> (giảm <?= vnd($voucher['giam']) ?>)</span>
                    <button type="submit" name="hanh_dong" value="go-ma" formaction="dat-hang.php#thanh-toan" style="border: none; background: transparent; color: #B93A12; font: inherit; font-weight: 700; cursor: pointer; min-height: 32px; padding: 0">Gỡ mã</button>
                </div>
            <?php elseif ($loi_voucher !== ''): ?>
                <div role="alert" style="margin-top: 10px; color: #B42318; font-size: 14px; font-weight: 600"><?= e($loi_voucher) ?></div>
            <?php endif; ?>
        </div>

        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 14px; color: #55635D; padding-top: 16px; border-top: 1px solid #EAEFEA">
            <div style="display: flex; justify-content: space-between">
                <span>Tạm tính (<?= (int)$tong_so_luong ?> sản phẩm):</span>
                <strong style="color: #14201C"><?= vnd($tong_tien_hang) ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between">
                <span>Phí vận chuyển:</span>
                <strong style="color: #14201C"><?= ($phi_van_chuyen == 0) ? 'Miễn phí' : vnd($phi_van_chuyen) ?></strong>
            </div>
            <?php if ($voucher): ?>
                <div style="display: flex; justify-content: space-between">
                    <span>Giảm giá (mã <?= e($voucher['maVoucher']) ?>):</span>
                    <strong style="color: #0F4C45">-<?= vnd($giam_gia) ?></strong>
                </div>
            <?php endif; ?>
            <?php if ($phi_van_chuyen > 0 && $tong_tien_hang < $mien_phi_tu): ?>
                <div style="font-size: 13px; color: #0F4C45">Mua thêm <?= vnd($mien_phi_tu - $tong_tien_hang) ?> để được miễn phí vận chuyển.</div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 18px; font-weight: 800; color: #14201C; margin-top: 8px; padding-top: 12px; border-top: 1.5px solid #DCE3DE">
                <span>Tổng tiền thanh toán:</span>
                <span style="color: #B93A12"><?= vnd($tong_thanh_toan) ?></span>
            </div>
        </div>

        <button type="submit" name="hanh_dong" value="dat-hang" id="btn-dat-hang" <?= $khong_dat_duoc ? 'disabled' : '' ?> style="width: 100%; height: 48px; background: #0F4C45; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; font-size: 16px; cursor: pointer; margin-top: 20px; <?= $khong_dat_duoc ? 'opacity:0.5; cursor:not-allowed;' : '' ?>">Xác Nhận Đặt Hàng</button>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('form-dat-hang');
    var nut = document.getElementById('btn-dat-hang');
    var ghiChuBanking = document.getElementById('ghi-chu-banking');
    var radios = form.querySelectorAll('input[name="phuongThucTT"]');
    var oLuu = document.getElementById('o-luu-dia-chi');
    var chon = document.getElementById('chon-dia-chi');
    var oTen = document.getElementById('hoTenNhan');
    var oSdt = document.getElementById('sdtNhan');
    var oDc = document.getElementById('diaChiGiao');

    function capNhatThanhToan() {
        radios.forEach(function (r) {
            r.closest('.dh-tt').classList.toggle('chon', r.checked);
            if (r.checked) { ghiChuBanking.style.display = (r.value === 'Banking') ? 'block' : 'none'; }
        });
    }
    radios.forEach(function (r) { r.addEventListener('change', capNhatThanhToan); });

    chon.addEventListener('change', function () {
        var o = chon.options[chon.selectedIndex];
        if (o.value === 'moi') {
            oTen.value = '';
            oSdt.value = '';
            oDc.value = '';
            oLuu.style.display = 'flex';
            oTen.focus();
        } else {
            oTen.value = o.getAttribute('data-ten') || '';
            oSdt.value = o.getAttribute('data-sdt') || '';
            oDc.value = o.getAttribute('data-dc') || '';
            oLuu.style.display = 'none';
        }
        [oTen, oSdt, oDc].forEach(function (x) { x.classList.remove('loi-o'); });
    });

    document.getElementById('maVoucher').addEventListener('keydown', function (ev) {
        if (ev.key === 'Enter') {
            ev.preventDefault();
            document.getElementById('btn-ap-dung').click();
        }
    });

    var daGui = false;
    form.addEventListener('submit', function (ev) {
        var la_dat_hang = !ev.submitter || ev.submitter.value === 'dat-hang';
        if (!la_dat_hang) { return; }
        if (daGui) { ev.preventDefault(); return; }
        daGui = true;
        nut.textContent = 'Đang xử lý...';
        nut.style.opacity = '0.7';
    });
})();
</script>

<?php
require_once 'dung-chung/cuoi-trang.php';
?>