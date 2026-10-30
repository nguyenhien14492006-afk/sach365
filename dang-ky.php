1. Phần Xử Lý Logic PHP (Kiểm tra dữ liệu, chống SQL Injection, Mã hóa mật khẩu)

<?php
// Nhúng file đầu trang (bao gồm kết nối CSDL $conn, thanh menu, thẻ <head>,...)
include 'dung-chung/dau-trang.php';

// Khởi tạo biến thông báo lỗi ban đầu bằng chuỗi rỗng
$thong_bao = "";

// Kiểm tra xem form có được gửi lên bằng phương thức POST hay không
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ các ô input và loại bỏ khoảng trắng thừa ở đầu/cuối
    $hoTen          = trim($_POST['hoTen'] ?? '');
    $sdt            = trim($_POST['sdt'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $matKhau        = $_POST['matKhau'] ?? '';
    $xacNhanMatKhau = $_POST['xacNhanMatKhau'] ?? '';

    // 1. Kiểm tra tính hợp lệ dữ liệu đầu vào (bắt buộc nhập và khớp mật khẩu)
    if (empty($hoTen)) {
        $thong_bao = "Vui lòng nhập Họ và tên!";
    } elseif (empty($sdt)) {
        $thong_bao = "Vui lòng nhập Số điện thoại!";
    } elseif (empty($email)) {
        $thong_bao = "Vui lòng nhập địa chỉ Email!";
    } elseif (empty($matKhau)) {
        $thong_bao = "Vui lòng nhập Mật khẩu!";
    } elseif ($matKhau !== $xacNhanMatKhau) {
        $thong_bao = "Mật khẩu xác nhận không trùng khớp!";
    } else {
        // 2. Chống SQL Injection - Sử dụng Prepared Statement để kiểm tra xem Số điện thoại đã tồn tại chưa
        $stmt_sdt = $conn->prepare("SELECT id FROM khachhang WHERE sdt = ?");
        $stmt_sdt->bind_param("s", $sdt);
        $stmt_sdt->execute();
        $result_sdt = $stmt_sdt->get_result();

        // Nếu số điện thoại đã tồn tại trong CSDL, gán thông báo lỗi
        if ($result_sdt && $result_sdt->num_rows > 0) {
            $thong_bao = "Số điện thoại này đã được đăng ký tài khoản!";
            $stmt_sdt->close();
        } else {
            $stmt_sdt->close();

            // 3. Chống SQL Injection - Sử dụng Prepared Statement để kiểm tra xem Email đã tồn tại chưa
            $stmt_email = $conn->prepare("SELECT id FROM khachhang WHERE email = ?");
            $stmt_email->bind_param("s", $email);
            $stmt_email->execute();
            $result_email = $stmt_email->get_result();

            // Nếu email đã tồn tại trong CSDL, gán thông báo lỗi
            if ($result_email && $result_email->num_rows > 0) {
                $thong_bao = "Email này đã được sử dụng!";
                $stmt_email->close();
            } else {
                $stmt_email->close();

                // 4. Mã hóa mật khẩu bằng thuật toán Bcrypt an toàn (PASSWORD_DEFAULT)
                $matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);
                $trangThai   = 1; // Trạng thái tài khoản kích hoạt (1)

                // 5. Thêm tài khoản mới vào bảng khachhang bằng Prepared Statement để bảo mật
                $stmt_insert = $conn->prepare("INSERT INTO khachhang (hoTen, sdt, email, matKhau, trangThai) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("ssssi", $hoTen, $sdt, $email, $matKhauHash, $trangThai);

                // Nếu thực thi câu lệnh INSERT thành công
                if ($stmt_insert->execute()) {
                    $stmt_insert->close();
                    // Hiển thị hộp thoại thông báo thành công bằng JavaScript và chuyển hướng sang trang đăng nhập
                    echo "<script>alert('Đăng ký tài khoản thành công! Vui lòng đăng nhập.'); window.location.href='dang-nhap.php';</script>";
                    exit();
                } else {
                    // Nếu lỗi CSDL, hiển thị thông báo lỗi chi tiết
                    $thong_bao = "Lỗi CSDL: " . $conn->error;
                    $stmt_insert->close();
                }
            }
        }
    }
}
?>


2. Phần Giao Diện HTML (Khung Form Đăng Ký)

<!-- Thẻ chứa bố cục chính của trang đăng ký, căn giữa và giới hạn độ rộng -->
<main style="max-width: 460px; margin: 48px auto; padding: 0 24px; box-sizing: border-box">
    <!-- Khung hộp trắng chứa form -->
    <div style="background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 36px; box-sizing: border-box; box-shadow: 0 8px 24px rgba(20,32,28,0.06)">
        <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 800; color: #14201C; text-align: center">Tạo tài khoản</h1>
        <p style="margin: 0 0 24px; font-size: 14px; color: #55635D; text-align: center">Đăng ký để trải nghiệm mua sách nhanh chóng tại Sach365</p>

        <!-- Hiển thị khung thông báo lỗi nếu biến $thong_bao có nội dung -->
        <?php if (!empty($thong_bao)): ?>
            <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #B93A12; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px">
                <?php echo htmlspecialchars($thong_bao); ?>
            </div>
        <?php endif; ?>

        <!-- Form gửi dữ liệu đăng ký qua phương thức POST -->
        <form action="dang-ky.php" method="POST" id="form-register" style="display: flex; flex-direction: column; gap: 18px">
            <!-- Ô nhập Họ và tên -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Họ và tên (*)</label>
                <!-- Giữ lại giá trị người dùng đã nhập nếu form bị lỗi (giúp không phải nhập lại từ đầu) -->
                <input type="text" name="hoTen" id="hoTen" placeholder="Nguyễn Văn A" required value="<?php echo isset($_POST['hoTen']) ? htmlspecialchars($_POST['hoTen']) : ''; ?>" style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
            </div>

            <!-- Ô nhập Số điện thoại -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Số điện thoại (*)</label>
                <input type="text" name="sdt" id="sdt" placeholder="0901234567" required value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : ''; ?>" style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
            </div>

            <!-- Ô nhập Email -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Địa chỉ Email (*)</label>
                <input type="email" name="email" id="email" placeholder="nguyenvana@gmail.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
            </div>

            <!-- Ô nhập Mật khẩu kèm icon ẩn/hiện mật khẩu -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Mật khẩu (*)</label>
                <div style="position: relative; width: 100%;">
                    <input type="password" name="matKhau" id="matKhau" placeholder="Nhập mật khẩu" required style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 50px 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
                    <!-- Icon con mắt FontAwesome để ẩn/hiện mật khẩu khi bấm vào -->
                    <i id="btn-toggle-pwd" class="fa-solid fa-eye" onclick="togglePassword('matKhau', 'btn-toggle-pwd')" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #55635D; cursor: pointer; user-select: none;"></i>
                </div>
            </div>

            <!-- Ô nhập lại Mật khẩu xác nhận -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Nhập lại mật khẩu (*)</label>
                <div style="position: relative; width: 100%;">
                    <input type="password" name="xacNhanMatKhau" id="xacNhanMatKhau" placeholder="Nhập lại mật khẩu" required style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 50px 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
                    <!-- Icon con mắt thứ hai cho ô xác nhận mật khẩu -->
                    <i id="btn-toggle-confirm-pwd" class="fa-solid fa-eye" onclick="togglePassword('xacNhanMatKhau', 'btn-toggle-confirm-pwd')" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #55635D; cursor: pointer; user-select: none;"></i>
                </div>
            </div>

            <!-- Nút bấm Đăng ký (mặc định bị vô hiệu hóa `disabled` cho đến khi điền đủ các ô) -->
            <button type="submit" id="btn-submit" disabled style="width: 100%; height: 48px; border: none; background: #0F4C45; color: #FFFFFF; font-weight: 700; font-size: 16px; border-radius: 8px; cursor: not-allowed; opacity: 0.5; margin-top: 6px; transition: all 0.2s ease">Đăng ký</button>
        </form>

        <!-- Đường dẫn chuyển hướng sang trang đăng nhập nếu đã có tài khoản -->
        <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #55635D">
            Đã có tài khoản? <a href="dang-nhap.php" style="color: #0F4C45; font-weight: 700">Đăng nhập ngay</a>
        </div>
    </div>
</main>


3. Phần Xử Lý JavaScript (Ẩn/Hiện mật khẩu & Kiểm tra form trống)

<script>
    // Hàm chuyển đổi trạng thái hiển thị mật khẩu (Ẩn <-> Hiện)
    function togglePassword(inputId, btnId) {
        const pwdInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(btnId);
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash'); // Đổi icon thành mắt gạch chéo
        } else {
            pwdInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye'); // Đổi lại icon mắt bình thường
        }
    }

    // Lấy các phần tử input và nút submit từ DOM
    const hoTen = document.getElementById('hoTen');
    const sdt = document.getElementById('sdt');
    const email = document.getElementById('email');
    const matKhau = document.getElementById('matKhau');
    const xacNhanMatKhau = document.getElementById('xacNhanMatKhau');
    const btnSubmit = document.getElementById('btn-submit');

    // Hàm kiểm tra xem tất cả các ô input đã được điền đầy đủ chưa
    function checkInputs() {
        if (
            hoTen.value.trim() !== '' && 
            sdt.value.trim() !== '' && 
            email.value.trim() !== '' && 
            matKhau.value.trim() !== '' &&
            xacNhanMatKhau.value.trim() !== ''
        ) {
            // Nếu đã điền đủ: Kích hoạt nút Đăng ký, đổi màu đậm lên và cho phép click
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
        } else {
            // Nếu còn ô trống: Vô hiệu hóa nút Đăng ký, làm mờ đi và đổi con trỏ thành cấm click
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
        }
    }

    // Lắng nghe sự kiện người dùng gõ phím (input) vào các ô để kiểm tra liên tục
    hoTen.addEventListener('input', checkInputs);
    sdt.addEventListener('input', checkInputs);
    email.addEventListener('input', checkInputs);
    matKhau.addEventListener('input', checkInputs);
    xacNhanMatKhau.addEventListener('input', checkInputs);

    // Kiểm tra trạng thái ngay khi trang vừa tải xong (phòng trường hợp trình duyệt tự điền sẵn dữ liệu)
    window.onload = checkInputs;
</script>

<?php 
// Nhúng file cuối trang (chứa footer, đóng kết nối cơ sở dữ liệu, các thẻ đóng HTML chung)
include 'dung-chung/cuoi-trang.php'; 
?>
