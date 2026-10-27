<?php
require_once 'dung-chung/dau-trang.php';
require_once 'dung-chung/khoi-tao.php';

if (!isset($_SESSION['maKH'])) {
    header('Location: dang-nhap.php');
    exit();
}

$maKH = (int)$_SESSION['maKH'];
$thongBaoOk = '';
$loiHoso = [];
$loiMK = [];

$kh = truy_van($conn, "SELECT * FROM khachhang WHERE maKH = ? LIMIT 1", 'i', [$maKH])->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('tai-khoan.php', 'Phiên làm việc đã hết hạn, vui lòng đăng nhập lại.', 'loi');
    }

    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'cap_nhat_ho_so') {
        $hoTen  = trim($_POST['hoTen'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $sdt    = trim($_POST['sdt'] ?? '');
        $diaChi = trim($_POST['diaChi'] ?? '');

        if ($hoTen === '' || $email === '') {
            $loiHoso[] = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loiHoso[] = 'Địa chỉ email không hợp lệ.';
        }

        if (!$loiHoso) {
            $stmtCheck = $conn->prepare("SELECT maKH FROM khachhang WHERE (email = ? OR (sdt = ? AND sdt <> '')) AND maKH <> ? LIMIT 1");
            $stmtCheck->bind_param('ssi', $email, $sdt, $maKH);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->fetch_assoc()) {
                $loiHoso[] = 'Email hoặc Số điện thoại cập nhật đã tồn tại ở tài khoản khác.';
            }
        }

        if (!$loiHoso) {
            $stmt = $conn->prepare("UPDATE khachhang SET hoTen = ?, email = ?, sdt = ?, diaChi = ? WHERE maKH = ?");
            $stmt->bind_param('ssssi', $hoTen, $email, $sdt, $diaChi, $maKH);
            $stmt->execute();

            $_SESSION['hoTen'] = $hoTen;
            $thongBaoOk = 'Cập nhật thông tin tài khoản thành công!';
            $kh['hoTen'] = $hoTen;
            $kh['email'] = $email;
            $kh['sdt'] = $sdt;
            $kh['diaChi'] = $diaChi;
        }
    }

    if ($hanhDong === 'doi_mat_khau') {
        $mkCu  = (string)($_POST['matKhauCu'] ?? '');
        $mkMoi = (string)($_POST['matKhauMoi'] ?? '');
        $xacNhan = (string)($_POST['xacNhanMatKhau'] ?? '');

        if ($mkCu === '' || $mkMoi === '' || $xacNhan === '') {
            $loiMK[] = 'Vui lòng nhập đầy đủ các trường mật khẩu.';
        } elseif (!password_verify($mkCu, $kh['matKhau'])) {
            $loiMK[] = 'Mật khẩu hiện tại không chính xác';
        } elseif (mb_strlen($mkMoi) < 6) {
            $loiMK[] = 'Mật khẩu mới phải chứa ít nhất 6 ký tự.';
        } elseif ($mkMoi !== $xacNhan) {
            $loiMK[] = 'Mật khẩu xác nhận không trùng khớp';
        }

        if (!$loiMK) {
            $mkHash = password_hash($mkMoi, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE khachhang SET matKhau = ? WHERE maKH = ?");
            $stmt->bind_param('si', $mkHash, $maKH);
            $stmt->execute();
            $kh['matKhau'] = $mkHash;
            $thongBaoOk = 'Đổi mật khẩu tài khoản thành công!';
        }
    }
}

$flash = lay_flash();
?>

<div style="max-width: 960px; margin: 0 auto; padding-bottom: 40px;">
    <h1 style="font-size: 26px; font-weight: 800; margin-bottom: 24px;">Quản lý tài khoản cá nhân</h1>

    <?php if ($flash): ?>
        <div style="padding:14px 18px; border-radius:8px; margin-bottom:20px; font-weight:600; background:<?= $flash['loai'] === 'loi' ? '#FDE8E2; color:#9A2F0E; border:1px solid #F8B4B4;' : '#DCEFE8; color:#0F4C45; border:1px solid #A3D9C9;' ?>">
            <?= e($flash['noi_dung']) ?>
        </div>
    <?php endif; ?>

    <?php if ($thongBaoOk): ?>
        <div style="background:#DCEFE8; color:#0F4C45; border:1px solid #A3D9C9; padding:14px 18px; border-radius:8px; margin-bottom:24px; font-weight:600;">
            <?= e($thongBaoOk) ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
        <div class="dh-the">
            <h2 style="font-size: 18px; font-weight: 700; color: #0F4C45; margin-bottom: 16px;">Cập nhật thông tin hồ sơ</h2>
            
            <?php if ($loiHoso): ?>
                <div style="color:#9A2F0E; background:#FDE8E2; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; border:1px solid #F8B4B4;">
                    <?php foreach ($loiHoso as $l): ?><div>• <?= e($l) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="tai-khoan.php">
                <?= csrf_field() ?>
                <input type="hidden" name="hanh_dong" value="cap_nhat_ho_so">

                <div style="margin-bottom: 16px;">
                    <label class="dh-nhan" for="hoTen">Họ và tên: (*)</label>
                    <input class="dh-nhap" type="text" id="hoTen" name="hoTen" value="<?= e($kh['hoTen']) ?>" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="dh-nhan" for="email">Địa chỉ Email: (*)</label>
                    <input class="dh-nhap" type="email" id="email" name="email" value="<?= e($kh['email']) ?>" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="dh-nhan" for="sdt">Số điện thoại:</label>
                    <input class="dh-nhap" type="tel" id="sdt" name="sdt" value="<?= e($kh['sdt'] ?? '') ?>" placeholder="Nhập số điện thoại...">
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="dh-nhan" for="diaChi">Địa chỉ giao hàng mặc định:</label>
                    <textarea class="dh-nhap" id="diaChi" name="diaChi" rows="3" placeholder="Nhập địa chỉ nhận hàng mặc định..."><?= e($kh['diaChi'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="dh-nut-phu" style="width:100%; border-radius:8px;">Lưu thay đổi hồ sơ</button>
            </form>
        </div>

        <div class="dh-the">
            <h2 style="font-size: 18px; font-weight: 700; color: #0F4C45; margin-bottom: 16px;">Thiết lập đổi mật khẩu</h2>

            <?php if ($loiMK): ?>
                <div style="color:#9A2F0E; background:#FDE8E2; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px; border:1px solid #F8B4B4;">
                    <?php foreach ($loiMK as $l): ?><div>• <?= e($l) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="tai-khoan.php">
                <?= csrf_field() ?>
                <input type="hidden" name="hanh_dong" value="doi_mat_khau">

                <div style="margin-bottom: 16px;">
                    <label class="dh-nhan" for="matKhauCu">Mật khẩu hiện tại: (*)</label>
                    <input class="dh-nhap" type="password" id="matKhauCu" name="matKhauCu" required placeholder="Nhập mật khẩu hiện tại...">
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="dh-nhan" for="matKhauMoi">Mật khẩu mới: (*)</label>
                    <input class="dh-nhap" type="password" id="matKhauMoi" name="matKhauMoi" required placeholder="Nhập mật khẩu mới...">
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="dh-nhan" for="xacNhanMatKhau">Xác nhận mật khẩu mới: (*)</label>
                    <input class="dh-nhap" type="password" id="xacNhanMatKhau" name="xacNhanMatKhau" required placeholder="Nhập lại mật khẩu mới...">
                </div>

                <button type="submit" class="dh-nut-phu" style="width:100%; border-radius:8px; background:#0F4C45;">Cập nhật mật khẩu mới</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'dung-chung/cuoi-trang.php'; ?>