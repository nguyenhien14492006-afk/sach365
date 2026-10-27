<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../cau-hinh/ket-noi-csdl.php';
$conn->set_charset('utf8mb4');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('THU_MUC_ANH', __DIR__ . '/../tai-nguyen/hinh-anh/');
define('URL_ANH_ADMIN', '../tai-nguyen/hinh-anh/');
define('URL_ANH_KHACH', 'tai-nguyen/hinh-anh/');

const NGUONG_SAP_HET = 20;

const TRANG_THAI_DON = [
    0 => ['Chờ xử lý', '#8A4B08', '#FEF3C7'],
    1 => ['Đã xác nhận', '#1E40AF', '#DBEAFE'],
    2 => ['Đang giao', '#5B21B6', '#EDE9FE'],
    3 => ['Đã giao', '#0F4C45', '#DCEFE8'],
    4 => ['Đã hủy', '#9A2F0E', '#FDE8E2'],
];

function e($chuoi)
{
    return htmlspecialchars((string)$chuoi, ENT_QUOTES, 'UTF-8');
}

function vnd($so)
{
    return number_format((float)$so, 0, ',', '.') . 'đ';
}

function yeu_cau_dang_nhap()
{
    if (!isset($_SESSION['admin']) && !isset($_SESSION['maAdmin']) && !isset($_SESSION['maQL'])) {
        header('Location: dang-nhap.php');
        exit();
    }
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_hop_le()
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

function chuyen_huong($url, $noiDung = null, $loai = 'ok')
{
    if ($noiDung !== null) {
        $_SESSION['flash'] = ['loai' => $loai, 'noi_dung' => $noiDung];
    }
    header('Location: ' . $url);
    exit();
}

function lay_flash()
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function truy_van($conn, $sql, $kieu = '', $thamSo = [])
{
    $stmt = $conn->prepare($sql);
    if ($kieu !== '') {
        $stmt->bind_param($kieu, ...$thamSo);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function gia_tri_don($conn, $sql)
{
    $r = $conn->query($sql);
    $dong = $r ? $r->fetch_row() : null;
    return $dong[0] ?? 0;
}

function nhan_trang_thai_don($tt)
{
    $t = TRANG_THAI_DON[(int)$tt] ?? ['Không rõ', '#55635D', '#EAEFEA'];
    return '<span class="nhan-tt" style="color:' . $t[1] . ';background:' . $t[2] . '">' . e($t[0]) . '</span>';
}

function phan_trang_html($tong, $trang, $moiTrang, $thamSo = [])
{
    $soTrang = (int)ceil($tong / $moiTrang);
    if ($soTrang <= 1) {
        return '';
    }
    $html = '<nav class="phan-trang" aria-label="Phân trang">';
    for ($i = 1; $i <= $soTrang; $i++) {
        if ($i === $trang) {
            $html .= '<span class="dang" aria-current="page">' . $i . '</span>';
        } else {
            $url = '?' . http_build_query(array_merge($thamSo, ['trang' => $i]));
            $html .= '<a href="' . e($url) . '">' . $i . '</a>';
        }
    }
    return $html . '</nav>';
}

// BỔ SUNG CÁC HÀM TÍNH PHÍ VÀ VOUCHER DÙNG CHUNG
function tinh_phi_ship($diaChi, $tienHang, $mienPhiTu = 250000)
{
    if ($tienHang >= $mienPhiTu) {
        return 0;
    }
    $dc = mb_strtolower((string)$diaChi, 'UTF-8');
    if ($dc === '') {
        return 25000;
    }
    if (
        mb_strpos($dc, 'hồ chí minh') !== false || 
        mb_strpos($dc, 'hcm') !== false || 
        mb_strpos($dc, 'sài gòn') !== false || 
        mb_strpos($dc, 'hà nội') !== false || 
        mb_strpos($dc, 'cần thơ') !== false || 
        mb_strpos($dc, 'đà nẵng') !== false
    ) {
        return 15000;
    }
    return 35000;
}

/**
 * Hàm kiểm tra và tính toán Voucher dựa trên đúng cấu trúc CSDL khuyenmai
 */
function kiem_tra_voucher($conn, $maNhap, $maKH, $tienHang, $phiShip)
{
    $ma = strtoupper(trim((string)$maNhap));
    if ($ma === '') {
        return [null, 'Vui lòng nhập mã khuyến mãi.'];
    }

    // 1. Truy vấn đúng cột maVoucher từ bảng khuyenmai
    $stmt = $conn->prepare("
        SELECT *, 
            (ngayBatDau IS NOT NULL AND ngayBatDau > NOW()) AS chua_bat_dau, 
            (ngayKetThuc IS NOT NULL AND ngayKetThuc < NOW()) AS da_het_han 
        FROM khuyenmai 
        WHERE maVoucher = ? LIMIT 1
    ");
    $stmt->bind_param('s', $ma);
    $stmt->execute();
    $km = $stmt->get_result()->fetch_assoc();

    // 2. Kiểm tra sự tồn tại và điều kiện kích hoạt
    if (!$km) {
        return [null, 'Mã khuyến mãi không tồn tại trên hệ thống.'];
    }
    if ((int)$km['trangThai'] !== 1) {
        return [null, 'Mã khuyến mãi hiện đang ngưng áp dụng.'];
    }
    if ((int)$km['chua_bat_dau'] === 1) {
        return [null, 'Chương trình khuyến mãi này chưa bắt đầu.'];
    }
    if ((int)$km['da_het_han'] === 1) {
        return [null, 'Mã khuyến mãi này đã hết hạn sử dụng.'];
    }
    if ($km['soLuong'] !== null && (int)$km['soLuong'] <= 0) {
        return [null, 'Mã khuyến mãi này đã hết lượt sử dụng.'];
    }
    if ($km['donHangToiThieu'] !== null && $tienHang < (float)$km['donHangToiThieu']) {
        return [null, 'Đơn hàng tối thiểu phải từ ' . vnd($km['donHangToiThieu']) . ' mới đủ điều kiện áp dụng mã này.'];
    }

    // 3. Kiểm tra điều kiện đơn hàng đầu tiên (áp dụng cho các mã như WELCOME20)
    if (mb_stripos((string)$km['tenKM'], 'đầu tiên') !== false) {
        $st = $conn->prepare("SELECT COUNT(*) FROM donhang WHERE maKH = ? AND trangThaiDonHang <> 4");
        $st->bind_param('i', $maKH);
        $st->execute();
        if ((int)$st->get_result()->fetch_row()[0] > 0) {
            return [null, 'Mã này chỉ dành cho tài khoản chưa từng mua hàng.'];
        }
    }

    // 4. Tính toán tiền giảm giá theo loại giảm giá (loaiGiamGia)
    $giam = 0;
    $loaiGiam = (int)$km['loaiGiamGia'];
    $giamToiDa = $km['giamToiDa'] !== null ? (float)$km['giamToiDa'] : null;

    if ($loaiGiam === 1) {
        // Loại 1: Giảm theo % -> Tự động trích xuất con số % từ tên khuyến mãi (VD: "20%" -> 20)
        $phanTram = 0;
        if (preg_match('/(\d{1,3})\s*%/u', (string)$km['tenKM'], $m)) {
            $phanTram = (int)$m[1];
        }

        if ($phanTram > 0) {
            $giam = ($tienHang * $phanTram) / 100;
            if ($giamToiDa !== null && $giamToiDa > 0) {
                $giam = min($giam, $giamToiDa);
            }
        } else {
            // Nếu không tìm thấy % trong tên, dùng thẳng giá trị giamToiDa
            $giam = $giamToiDa ?? 0;
        }
    } else {
        // Loại 2: Giảm số tiền cố định (như 10.000đ) hoặc Miễn phí vận chuyển (Freeship)
        $laFreeship = (mb_stripos((string)$km['tenKM'], 'vận chuyển') !== false || stripos((string)$km['maVoucher'], 'FREESHIP') === 0);
        
        if ($laFreeship) {
            $giam = min($giamToiDa ?? $phiShip, $phiShip);
        } else {
            $giam = min($giamToiDa ?? 0, $tienHang);
        }
    }

    $giam = floor($giam);

    if ($giam <= 0) {
        return [null, 'Mã này không giảm thêm được cho đơn hàng hiện tại.'];
    }

    // 5. Trả về thông tin voucher thành công
    return [[
        'maKM'      => (int)$km['maKM'],
        'maVoucher' => $km['maVoucher'],
        'tenKM'     => $km['tenKM'],
        'giam'      => $giam,
        'gioi_han'  => ($km['soLuong'] !== null),
    ], null];
}