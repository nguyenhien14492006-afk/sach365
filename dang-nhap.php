<?php
require_once 'dung-chung/dau-trang.php';

$thong_bao = "";

if (isset($_POST['btn_dang_nhap'])) {
    $sdt = trim($_POST['sdt']);
    $matKhau = trim($_POST['matKhau']);

    if (!empty($sdt) && !empty($matKhau)) {
        $stmt = $conn->prepare("SELECT * FROM khachhang WHERE sdt = ? AND trangThai = 1 LIMIT 1");
        $stmt->bind_param("s", $sdt);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($matKhau === $user['matKhau'] || password_verify($matKhau, $user['matKhau'])) {
                $_SESSION['maKH'] = $user['maKH'];
                $_SESSION['hoTen'] = $user['hoTen'];
                echo "<script>alert('Đăng nhập thành công!'); window.location.href='index.php';</script>";
                exit();
            } else {
                $thong_bao = "Mật khẩu không chính xác!";
            }
        } else {
            $thong_bao = "Số điện thoại không tồn tại hoặc tài khoản đã bị khóa!";
        }
    } else {
        $thong_bao = "Vui lòng nhập đầy đủ thông tin!";
    }
}
?>

<main style="max-width: 440px; margin: 48px auto; padding: 0 24px; box-sizing: border-box">
    <div style="background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 36px; box-sizing: border-box; box-shadow: 0 8px 24px rgba(20,32,28,0.06)">
        <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 800; color: #14201C; text-align: center">Đăng nhập</h1>
        <p style="margin: 0 0 24px; font-size: 14px; color: #55635D; text-align: center">Chào mừng bạn quay trở lại với Sach365</p>

        <?php if (!empty($thong_bao)): ?>
            <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #B93A12; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px">
                <?php echo $thong_bao; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="form-login" style="display: flex; flex-direction: column; gap: 18px">
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Số điện thoại (*)</label>
                <input type="text" name="sdt" id="sdt" placeholder="0901234567" required value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : ''; ?>" style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
            </div>

            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Mật khẩu (*)</label>
                <div style="position: relative; width: 100%;">
                    <input type="password" name="matKhau" id="matKhau" placeholder="Nhập mật khẩu" required style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 50px 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
                    <i id="btn-toggle-pwd" class="fa-solid fa-eye" onclick="togglePassword()" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #55635D; cursor: pointer; user-select: none;"></i>
                </div>
            </div>

            <button type="submit" name="btn_dang_nhap" id="btn-submit" disabled style="width: 100%; height: 48px; border: none; background: #0F4C45; color: #FFFFFF; font-weight: 700; font-size: 16px; border-radius: 8px; cursor: not-allowed; opacity: 0.5; margin-top: 6px; transition: all 0.2s ease">Đăng nhập</button>
        </form>

        <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #55635D">
            Chưa có tài khoản? <a href="dang-ky.php" style="color: #0F4C45; font-weight: 700">Đăng ký ngay</a>
        </div>
    </div>
</main>

<script>
    function togglePassword() {
        const pwdInput = document.getElementById('matKhau');
        const toggleIcon = document.getElementById('btn-toggle-pwd');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            pwdInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    const sdt = document.getElementById('sdt');
    const matKhau = document.getElementById('matKhau');
    const btnSubmit = document.getElementById('btn-submit');

    function checkInputs() {
        if (sdt.value.trim() !== '' && matKhau.value.trim() !== '') {
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
        } else {
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
        }
    }

    sdt.addEventListener('input', checkInputs);
    matKhau.addEventListener('input', checkInputs);

    window.onload = checkInputs;
</script>

<?php
require_once 'dung-chung/cuoi-trang.php';
?>