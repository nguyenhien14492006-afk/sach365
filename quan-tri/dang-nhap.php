<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';

if (isset($_SESSION['maQL']) || isset($_SESSION['admin']) || isset($_SESSION['maAdmin'])) {
    header('Location: trang-quan-tri.php');
    exit();
}

$thong_bao = '';
$taiKhoan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taiKhoan = trim($_POST['taiKhoan'] ?? '');
    $matKhau = (string)($_POST['matKhau'] ?? '');

    if ($taiKhoan === '' || $matKhau === '') {
        $thong_bao = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu!';
    } else {
        $ql = truy_van($conn, "SELECT maQL, taiKhoan, matKhau, hoTen FROM quanly WHERE taiKhoan = ? LIMIT 1", 's', [$taiKhoan])->fetch_assoc();

        if ($ql && (hash_equals((string)$ql['matKhau'], $matKhau) || password_verify($matKhau, (string)$ql['matKhau']))) {
            session_regenerate_id(true);
            
            $_SESSION['maQL'] = $ql['maQL'];
            $_SESSION['maAdmin'] = $ql['maQL'];
            $_SESSION['admin'] = $ql['taiKhoan'];
            $_SESSION['hoTenQL'] = $ql['hoTen'];

            header('Location: trang-quan-tri.php');
            exit();
        }
        $thong_bao = 'Tài khoản hoặc mật khẩu không chính xác!';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị - Sach365</title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#14201C; color:#14201C; font-family:'Be Vietnam Pro', system-ui, sans-serif; font-size:15px; padding:24px; }
        .hop { width:100%; max-width:420px; background:#fff; border-radius:16px; padding:36px; box-shadow:0 20px 50px rgba(0,0,0,.35); }
        h1 { margin:0 0 6px; font-size:26px; font-weight:800; text-align:center; color:#0F4C45; }
        .phu { margin:0 0 24px; font-size:14px; color:#55635D; text-align:center; }
        label { display:block; font-weight:600; font-size:14px; margin-bottom:6px; color:#14201C; }
        input { width:100%; height:46px; border:1.5px solid #DCE3DE; border-radius:8px; padding:0 16px; background:#F7F8F5; color:#14201C; font:inherit; transition:all 0.2s; }
        input:focus { outline:none; border-color:#0F4C45; background:#fff; box-shadow:0 0 0 3px rgba(15,76,69,0.15); }
        .truong { margin-bottom:18px; }
        .mk { position:relative; }
        .mk input { padding-right:48px; }
        .mk button { position:absolute; right:4px; top:50%; transform:translateY(-50%); height:38px; width:40px; border:none; background:transparent; color:#0F4C45; font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; border-radius:6px; transition:background 0.2s; }
        .mk button:hover { background:rgba(15,76,69,0.08); }
        .gui { width:100%; height:48px; border:none; background:#0F4C45; color:#fff; font:inherit; font-weight:700; font-size:16px; border-radius:8px; cursor:pointer; margin-top:6px; transition:all 0.2s; }
        .gui:hover:not(:disabled) { background:#0B3A35; transform: translateY(-1px); }
        .gui:disabled { opacity:0.5; cursor:not-allowed; transform:none; }
        .loi { background:#FDF2F2; border:1px solid #F8B4B4; color:#9A2F0E; padding:12px 16px; border-radius:8px; font-size:14px; margin-bottom:20px; text-align:center; font-weight:500; display:none; }
        .loi.hien { display:block; }
        .ve { margin-top:24px; text-align:center; font-size:14px; }
        .ve a { color:#0F4C45; font-weight:600; text-decoration:none; }
        .ve a:hover { text-decoration:underline; }
    </style>
</head>
<body>
    <main class="hop">
        <h1>Đăng nhập quản trị</h1>
        <p class="phu">Dành riêng cho quản trị viên Sach365</p>

        <div id="hop-loi" class="loi <?= $thong_bao !== '' ? 'hien' : '' ?>" role="alert"><?= e($thong_bao) ?></div>

        <form id="form-dang-nhap" method="POST" action="dang-nhap.php" novalidate>
            <div class="truong">
                <label for="taiKhoan">Tài khoản</label>
                <input type="text" id="taiKhoan" name="taiKhoan" autofocus autocomplete="username" value="<?= e($taiKhoan) ?>">
            </div>
            <div class="truong">
                <label for="matKhau">Mật khẩu</label>
                <div class="mk">
                    <input type="password" id="matKhau" name="matKhau" autocomplete="current-password">
                    <button type="button" id="btn-hien" aria-label="Hiện hoặc ẩn mật khẩu" title="Hiện hoặc ẩn mật khẩu">
                        <i class="fa-solid fa-eye" id="icon-mat"></i>
                    </button>
                </div>
            </div>
            <button type="submit" id="btn-gui" class="gui" disabled>Đăng nhập</button>
        </form>

        <div class="ve"><a href="../index.php">← Về trang web bán sách</a></div>
    </main>

    <script>
        var inpTaiKhoan = document.getElementById('taiKhoan');
        var inpMatKhau = document.getElementById('matKhau');
        var btnGui = document.getElementById('btn-gui');
        var hopLoi = document.getElementById('hop-loi');
        var form = document.getElementById('form-dang-nhap');

        function kiemTraInput() {
            var tk = inpTaiKhoan.value.trim();
            var mk = inpMatKhau.value.trim();
            if (tk !== '' && mk !== '') {
                btnGui.disabled = false;
            } else {
                btnGui.disabled = true;
            }
        }

        inpTaiKhoan.addEventListener('input', kiemTraInput);
        inpMatKhau.addEventListener('input', kiemTraInput);

        form.addEventListener('submit', function(e) {
            var tk = inpTaiKhoan.value.trim();
            var mk = inpMatKhau.value.trim();
            if (tk === '' || mk === '') {
                e.preventDefault();
                hopLoi.textContent = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu!';
                hopLoi.classList.add('hien');
            }
        });

        document.getElementById('btn-hien').addEventListener('click', function () {
            var an = inpMatKhau.type === 'password';
            inpMatKhau.type = an ? 'text' : 'password';
            
            var icon = document.getElementById('icon-mat');
            if (an) {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        kiemTraInput();
    </script>
</body>
</html>