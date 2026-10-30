1. Phần Xử Lý Logic PHP (Kiểm tra tài khoản, xác thực mật khẩu, tạo phiên đăng nhập)

<?php
// Nhúng file đầu trang (bao gồm cấu hình kết nối CSDL $conn, bắt đầu session, thanh menu, thẻ <head>,...)
require_once 'dung-chung/dau-trang.php';

// Khởi tạo biến thông báo lỗi ban đầu bằng chuỗi rỗng
$thong_bao = "";

// Kiểm tra xem người dùng đã bấm nút Đăng nhập (có tên là 'btn_dang_nhap') hay chưa
if (isset($_POST['btn_dang_nhap'])) {
    // Lấy số điện thoại và mật khẩu từ form gửi lên, đồng thời cắt bỏ khoảng trắng thừa
    $sdt = trim($_POST['sdt']);
    $matKhau = trim($_POST['matKhau']);

    // Kiểm tra xem cả 2 ô số điện thoại và mật khẩu có được điền đầy đủ hay không
    if (!empty($sdt) && !empty($matKhau)) {
        // Sử dụng Prepared Statement để tìm thông tin khách hàng dựa vào số điện thoại và tài khoản phải đang hoạt động (trangThai = 1)
        $stmt = $conn->prepare("SELECT * FROM khachhang WHERE sdt = ? AND trangThai = 1 LIMIT 1");
        $stmt->bind_param("s", $sdt);
        $stmt->execute();
        $result = $stmt->get_result();

        // Nếu tìm thấy tài khoản khớp trong cơ sở dữ liệu
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Kiểm tra mật khẩu: Hỗ trợ cả mật khẩu cũ dạng văn bản thuần ($matKhau === $user['matKhau']) 
            // hoặc mật khẩu đã được mã hóa an toàn bằng password_hash (password_verify)
            if ($matKhau === $user['matKhau'] || password_verify($matKhau, $user['matKhau'])) {
                // Đăng nhập thành công: Lưu mã khách hàng và họ tên vào Session để duy trì trạng thái đăng nhập
                $_SESSION['maKH'] = $user['maKH'];
                $_SESSION['hoTen'] = $user['hoTen'];
                // Hiển thị thông báo thành công bằng JavaScript và chuyển hướng về trang chủ index.php
                echo "<script>alert('Đăng nhập thành công!'); window.location.href='index.php';</script>";
                exit();
            } else {
                // Nếu mật khẩu không khớp, gán thông báo lỗi
                $thong_bao = "Mật khẩu không chính xác!";
            }
        } else {
            // Nếu không tìm thấy số điện thoại hoặc tài khoản bị khóa (trangThai != 1)
            $thong_bao = "Số điện thoại không tồn tại hoặc tài khoản đã bị khóa!";
        }
    } else {
        // Nếu để trống một trong các ô
        $thong_bao = "Vui lòng nhập đầy đủ thông tin!";
    }
}
?>


2. Phần Giao Diện HTML (Khung Form Đăng Nhập)

<!-- Thẻ chứa bố cục chính của trang đăng nhập, căn giữa màn hình và giới hạn độ rộng -->
<main style="max-width: 440px; margin: 48px auto; padding: 0 24px; box-sizing: border-box">
    <!-- Khung hộp trắng chứa form -->
    <div style="background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 36px; box-sizing: border-box; box-shadow: 0 8px 24px rgba(20,32,28,0.06)">
        <h1 style="margin: 0 0 8px; font-size: 28px; font-weight: 800; color: #14201C; text-align: center">Đăng nhập</h1>
        <p style="margin: 0 0 24px; font-size: 14px; color: #55635D; text-align: center">Chào mừng bạn quay trở lại với Sach365</p>

        <!-- Hiển thị khung thông báo lỗi nếu biến $thong_bao có nội dung -->
        <?php if (!empty($thong_bao)): ?>
            <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #B93A12; padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 20px">
                <?php echo $thong_bao; ?>
            </div>
        <?php endif; ?>

        <!-- Form gửi dữ liệu đăng nhập bằng phương thức POST -->
        <form method="POST" action="" id="form-login" style="display: flex; flex-direction: column; gap: 18px">
            <!-- Ô nhập Số điện thoại -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Số điện thoại (*)</label>
                <!-- Giữ lại giá trị sdt nếu nhập sai để người dùng không phải gõ lại -->
                <input type="text" name="sdt" id="sdt" placeholder="0901234567" required value="<?php echo isset($_POST['sdt']) ? htmlspecialchars($_POST['sdt']) : ''; ?>" style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
            </div>

            <!-- Ô nhập Mật khẩu kèm icon ẩn/hiện -->
            <div>
                <label style="display: block; font-weight: 600; font-size: 14px; margin-bottom: 6px; color: #14201C">Mật khẩu (*)</label>
                <div style="position: relative; width: 100%;">
                    <input type="password" name="matKhau" id="matKhau" placeholder="Nhập mật khẩu" required style="width: 100%; height: 46px; box-sizing: border-box; border: 1.5px solid #DCE3DE; border-radius: 8px; padding: 0 50px 0 16px; background: #F7F8F5; color: #14201C; font-size: 15px; outline: none">
                    <!-- Icon con mắt FontAwesome gọi hàm togglePassword() để ẩn/hiện mật khẩu -->
                    <i id="btn-toggle-pwd" class="fa-solid fa-eye" onclick="togglePassword()" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 16px; color: #55635D; cursor: pointer; user-select: none;"></i>
                </div>
            </div>

            <!-- Nút bấm Đăng nhập (mặc định bị vô hiệu hóa `disabled` cho đến khi nhập đủ thông tin) -->
            <button type="submit" name="btn_dang_nhap" id="btn-submit" disabled style="width: 100%; height: 48px; border: none; background: #0F4C45; color: #FFFFFF; font-weight: 700; font-size: 16px; border-radius: 8px; cursor: not-allowed; opacity: 0.5; margin-top: 6px; transition: all 0.2s ease">Đăng nhập</button>
        </form>

        <!-- Đường dẫn chuyển hướng sang trang đăng ký nếu chưa có tài khoản -->
        <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #55635D">
            Chưa có tài khoản? <a href="dang-ky.php" style="color: #0F4C45; font-weight: 700">Đăng ký ngay</a>
        </div>
    </div>
</main>


3. Phần Xử Lý JavaScript (Ẩn/Hiện mật khẩu & Kiểm tra form trống)

<script>
    // Hàm chuyển đổi trạng thái hiển thị mật khẩu (Ẩn <-> Hiện)
    function togglePassword() {
        const pwdInput = document.getElementById('matKhau');
        const toggleIcon = document.getElementById('btn-toggle-pwd');
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash'); // Đổi sang icon mắt gạch chéo
        } else {
            pwdInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye'); // Đổi lại icon mắt bình thường
        }
    }

    // Lấy các phần tử input số điện thoại, mật khẩu và nút submit từ DOM
    const sdt = document.getElementById('sdt');
    const matKhau = document.getElementById('matKhau');
    const btnSubmit = document.getElementById('btn-submit');

    // Hàm kiểm tra điều kiện để bật/tắt nút Đăng nhập
    function checkInputs() {
        if (sdt.value.trim() !== '' && matKhau.value.trim() !== '') {
            // Nếu cả 2 ô đều có dữ liệu: Kích hoạt nút, sáng lên và cho phép click
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
        } else {
            // Nếu có ô trống: Vô hiệu hóa nút, làm mờ đi và đổi con trỏ thành cấm click
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
        }
    }

    // Lắng nghe sự kiện người dùng gõ phím vào ô số điện thoại hoặc mật khẩu
    sdt.addEventListener('input', checkInputs);
    matKhau.addEventListener('input', checkInputs);

    // Kiểm tra ngay lập tức khi trang vừa tải xong (đề phòng trường hợp trình duyệt tự điền sẵn dữ liệu autofill)
    window.onload = checkInputs;
</script>

<?php
// Nhúng file cuối trang (chứa footer, đóng kết nối CSDL, các thẻ đóng HTML chung)
require_once 'dung-chung/cuoi-trang.php';
?>
