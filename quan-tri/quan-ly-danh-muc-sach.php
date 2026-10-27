<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

$loi = [];
$che_do = 'danh-sach';
$danhMuc = [
    'maDanhMuc' => 0,
    'tenDanhMuc' => '',
    'moTa' => '',
    'trangThai' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('quan-ly-danh-muc-sach.php', 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'loi');
    }
    $hanhDong = $_POST['hanh_dong'] ?? '';

    if ($hanhDong === 'doi-trang-thai') {
        $ma = (int)($_POST['maDanhMuc'] ?? 0);
        $ttMoi = (int)($_POST['trangThaiMoi'] ?? 1);
        try {
            $stmt = $conn->prepare("UPDATE danhmuc SET trangThai = ? WHERE maDanhMuc = ?");
            $stmt->bind_param('ii', $ttMoi, $ma);
            $stmt->execute();
            chuyen_huong('quan-ly-danh-muc-sach.php', $ttMoi === 1 ? 'Đã hiển thị lại danh mục.' : 'Đã ẩn danh mục khỏi cửa hàng.');
        } catch (mysqli_sql_exception $ex) {
            chuyen_huong('quan-ly-danh-muc-sach.php', 'Lỗi không thể cập nhật trạng thái: ' . $ex->getMessage(), 'loi');
        }
    }

    if ($hanhDong === 'xoa') {
        $ma = (int)($_POST['maDanhMuc'] ?? 0);
        $sachLienQuan = (int)truy_van($conn, "SELECT COUNT(*) FROM sach WHERE maDanhMuc = ?", 'i', [$ma])->fetch_row()[0];
        if ($sachLienQuan > 0) {
            try {
                $stmt = $conn->prepare("UPDATE danhmuc SET trangThai = 0 WHERE maDanhMuc = ?");
                $stmt->bind_param('i', $ma);
                $stmt->execute();
                chuyen_huong('quan-ly-danh-muc-sach.php', 'Danh mục đang chứa ' . $sachLienQuan . ' cuốn sách nên hệ thống đã tự động chuyển sang trạng thái Ẩn.');
            } catch (mysqli_sql_exception $ex) {
                chuyen_huong('quan-ly-danh-muc-sach.php', 'Lỗi không thể ẩn danh mục: ' . $ex->getMessage(), 'loi');
            }
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM danhmuc WHERE maDanhMuc = ?");
                $stmt->bind_param('i', $ma);
                $stmt->execute();
                chuyen_huong('quan-ly-danh-muc-sach.php', 'Đã xóa hoàn toàn danh mục thành công.');
            } catch (mysqli_sql_exception $ex) {
                chuyen_huong('quan-ly-danh-muc-sach.php', 'Không thể xóa danh mục này: ' . $ex->getMessage(), 'loi');
            }
        }
    }

    if ($hanhDong === 'luu') {
        $maGoc = (int)($_POST['maDanhMuc'] ?? 0);
        $danhMuc = [
            'maDanhMuc' => $maGoc,
            'tenDanhMuc' => trim($_POST['tenDanhMuc'] ?? ''),
            'moTa' => trim($_POST['moTa'] ?? ''),
            'trangThai' => ((int)($_POST['trangThai'] ?? 1) === 1) ? 1 : 0,
        ];

        if ($danhMuc['tenDanhMuc'] === '') {
            $loi[] = 'Vui lòng nhập tên danh mục sách.';
        } else {
            $check = truy_van($conn, "SELECT maDanhMuc FROM danhmuc WHERE tenDanhMuc = ? AND maDanhMuc != ?", 'si', [$danhMuc['tenDanhMuc'], $maGoc])->fetch_assoc();
            if ($check) {
                $loi[] = 'Tên danh mục này đã tồn tại trên hệ thống.';
            }
        }

        if (!$loi) {
            $ten = $danhMuc['tenDanhMuc'];
            $moTa = $danhMuc['moTa'] !== '' ? $danhMuc['moTa'] : null;
            $trangThai = $danhMuc['trangThai'];
            $ma = $maGoc;

            try {
                if ($maGoc > 0) {
                    $stmt = $conn->prepare("UPDATE danhmuc SET tenDanhMuc = ?, moTa = ?, trangThai = ? WHERE maDanhMuc = ?");
                    $stmt->bind_param('ssii', $ten, $moTa, $trangThai, $ma);
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("INSERT INTO danhmuc (tenDanhMuc, moTa, trangThai) VALUES (?, ?, ?)");
                    $stmt->bind_param('ssi', $ten, $moTa, $trangThai);
                    $stmt->execute();
                }
                chuyen_huong('quan-ly-danh-muc-sach.php', $maGoc > 0 ? 'Đã cập nhật danh mục sách.' : 'Đã thêm danh mục mới thành công.');
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
        $row = truy_van($conn, "SELECT * FROM danhmuc WHERE maDanhMuc = ?", 'i', [$id])->fetch_assoc();
        if (!$row) {
            chuyen_huong('quan-ly-danh-muc-sach.php', 'Không tìm thấy danh mục.', 'loi');
        }
        $danhMuc = $row;
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
        $dieuKien[] = "(dm.tenDanhMuc LIKE ? OR dm.maDanhMuc = ?)";
        $kieu .= 'si';
        $tham[] = '%' . $q . '%';
        $tham[] = (int)$q;
    }
    if ($locTrangThai === 0 || $locTrangThai === 1) {
        $dieuKien[] = "dm.trangThai = ?";
        $kieu .= 'i';
        $tham[] = $locTrangThai;
    }

    $where = $dieuKien ? ' WHERE ' . implode(' AND ', $dieuKien) : '';

    $tong = (int)truy_van($conn, "SELECT COUNT(*) FROM danhmuc dm" . $where, $kieu, $tham)->fetch_row()[0];
    $offset = ($trang - 1) * $moiTrang;
    $dsDanhMuc = truy_van($conn,
        "SELECT dm.*, (SELECT COUNT(*) FROM sach s WHERE s.maDanhMuc = dm.maDanhMuc) AS soLuongSach
         FROM danhmuc dm" . $where . "
         ORDER BY dm.maDanhMuc DESC LIMIT " . $moiTrang . " OFFSET " . $offset, $kieu, $tham);

    $thamSoLoc = array_filter(['q' => $q, 'trang_thai' => $locTrangThai >= 0 ? $locTrangThai : ''], function ($v) { return $v !== '' && $v !== null; });
}

$trang_hien_tai = 'danh-muc';
$tieu_de_trang = 'Quản lý danh mục sách';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<style>
    .bo-loc { display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 20px; }
    .bo-loc .tim { flex: 1 1 200px; }
    .nhan-truong { display: block; font-weight: 600; font-size: 13px; margin-bottom: 6px; color: var(--chu); }
    .o-nhap, .o-chon { width: 100%; height: 42px; border: 1.5px solid var(--vien); border-radius: 8px; padding: 0 14px; background: #fff; color: var(--chu); font: inherit; }
    .o-nhap:focus, .o-chon:focus { outline: none; border-color: var(--xanh); }
    textarea.o-nhap { height: 110px; padding: 12px 14px; resize: vertical; }
    .hanh-dong { display: flex; gap: 6px; align-items: center; }
    .nut-nguy { background: #FEE2E2; color: #DC2626; }
    .nut-nguy:hover { background: #DC2626; color: #fff; }
    .nut-an { background: #F3F4F6; color: #4B5563; }
    .nut-an:hover { background: #E5E7EB; color: #111827; }
    .nut-hien { background: #E0E7FF; color: #3730A3; }
    .nut-hien:hover { background: #C7D2FE; color: #312E81; }
    .luoi-form { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .luoi-form .full { grid-column: 1 / -1; }
    @media (max-width: 768px) { .luoi-form { grid-template-columns: 1fr; } }
</style>

<?php if ($che_do === 'danh-sach'): ?>

    <div class="dau-trang">
        <h1>Quản lý danh mục <span class="phu" style="font-weight:500">(<?= $tong ?> danh mục)</span></h1>
        <a href="quan-ly-danh-muc-sach.php?action=them" class="nut nut-chinh">+ Thêm danh mục mới</a>
    </div>

    <div class="card">
        <form method="GET" action="quan-ly-danh-muc-sach.php" class="bo-loc">
            <div class="tim">
                <label class="nhan-truong" for="q">Tìm kiếm danh mục</label>
                <input class="o-nhap" type="search" id="q" name="q" placeholder="Tên danh mục hoặc mã danh mục" value="<?= e($q) ?>">
            </div>
            <div>
                <label class="nhan-truong" for="trang_thai">Trạng thái</label>
                <select class="o-chon" id="trang_thai" name="trang_thai">
                    <option value="">Tất cả</option>
                    <option value="1" <?= $locTrangThai === 1 ? 'selected' : '' ?>>Đang hiển thị</option>
                    <option value="0" <?= $locTrangThai === 0 ? 'selected' : '' ?>>Đã ẩn</option>
                </select>
            </div>
            <button class="nut nut-chinh" type="submit">Lọc</button>
            <?php if ($thamSoLoc): ?><a class="nut" href="quan-ly-danh-muc-sach.php">Xóa lọc</a><?php endif; ?>
        </form>

        <?php if ($dsDanhMuc->num_rows > 0): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr><th>Mã DM</th><th>Tên danh mục</th><th>Mô tả</th><th class="phai-so">Số lượng sách</th><th>Trạng thái</th><th>Thao tác</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($dm = $dsDanhMuc->fetch_assoc()): ?>
                        <tr>
                            <td><strong>DM-<?= (int)$dm['maDanhMuc'] ?></strong></td>
                            <td style="min-width:180px"><strong><?= e($dm['tenDanhMuc']) ?></strong></td>
                            <td><?= e($dm['moTa'] ?? '—') ?></td>
                            <td class="phai-so"><strong><?= (int)$dm['soLuongSach'] ?></strong> cuốn</td>
                            <td>
                                <?php if ((int)$dm['trangThai'] === 1): ?>
                                    <span class="nhan-tt" style="color:#0F4C45;background:#DCEFE8">Hiển thị</span>
                                <?php else: ?>
                                    <span class="nhan-tt" style="color:#55635D;background:#EAEFEA">Đã ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="hanh-dong">
                                    <a class="nut nut-nho" href="quan-ly-danh-muc-sach.php?action=sua&id=<?= (int)$dm['maDanhMuc'] ?>">Sửa</a>
                                    
                                    <form method="POST" action="quan-ly-danh-muc-sach.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="doi-trang-thai">
                                        <input type="hidden" name="maDanhMuc" value="<?= (int)$dm['maDanhMuc'] ?>">
                                        <input type="hidden" name="trangThaiMoi" value="<?= (int)$dm['trangThai'] === 1 ? 0 : 1 ?>">
                                        <button class="nut nut-nho <?= (int)$dm['trangThai'] === 1 ? 'nut-an' : 'nut-hien' ?>" type="submit">
                                            <?= (int)$dm['trangThai'] === 1 ? 'Ẩn' : 'Hiện' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="quan-ly-danh-muc-sach.php" onsubmit="return confirm('Xóa hoặc ẩn hẳn danh mục này?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="xoa">
                                        <input type="hidden" name="maDanhMuc" value="<?= (int)$dm['maDanhMuc'] ?>">
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
            <div class="trong">Không tìm thấy danh mục nào.</div>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="dau-trang">
        <h1>Quản trị (<?= $che_do === 'sua' ? 'Sửa danh mục' : 'Thêm danh mục' ?>)</h1>
        <a class="nut" href="quan-ly-danh-muc-sach.php">← Quay lại</a>
    </div>

    <?php if ($loi): ?>
        <div class="thong-bao thong-bao-loi" style="margin-bottom:20px">
            <ul style="margin:0;padding-left:20px"><?php foreach ($loi as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form class="card" method="POST" action="quan-ly-danh-muc-sach.php">
        <h2 style="font-size:15px;color:var(--phu);margin-bottom:20px;border-bottom:1px solid var(--vien);padding-bottom:10px">Thông tin danh mục sách</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="hanh_dong" value="luu">
        <input type="hidden" name="maDanhMuc" value="<?= (int)$danhMuc['maDanhMuc'] ?>">

        <div class="luoi-form">
            <div>
                <label class="nhan-truong" for="tenDanhMuc">Tên danh mục: (*)</label>
                <input class="o-nhap" type="text" id="tenDanhMuc" name="tenDanhMuc" maxlength="100" required placeholder="Ví dụ: Giáo trình Công nghệ thông tin" value="<?= e($danhMuc['tenDanhMuc']) ?>">
            </div>

            <div>
                <label class="nhan-truong" for="trangThai">Trạng thái hiển thị:</label>
                <select class="o-chon" id="trangThai" name="trangThai">
                    <option value="1" <?= (int)$danhMuc['trangThai'] === 1 ? 'selected' : '' ?>>Hiển thị trên cửa hàng</option>
                    <option value="0" <?= (int)$danhMuc['trangThai'] === 0 ? 'selected' : '' ?>>Ẩn khỏi cửa hàng</option>
                </select>
            </div>

            <div class="full">
                <label class="nhan-truong" for="moTa">Mô tả ngắn danh mục:</label>
                <textarea class="o-nhap" id="moTa" name="moTa" placeholder="Nhập mô tả tóm tắt cho danh mục sách này..."><?= e($danhMuc['moTa']) ?></textarea>
            </div>
        </div>

        <div class="hanh-dong" style="margin-top:24px;justify-content:flex-start">
            <button class="nut nut-chinh" type="submit"><?= $che_do === 'sua' ? 'Lưu thay đổi' : 'Lưu danh mục mới' ?></button>
            <a class="nut" href="quan-ly-danh-muc-sach.php">Hủy bỏ</a>
        </div>
    </form>

<?php endif; ?>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>