<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

$maDVVC = (int)($_POST['maDVVC'] ?? 0);
$tenDVVC = trim($_POST['tenDVVC'] ?? '');
$sdtLienHe = trim($_POST['sdtLienHe'] ?? '');
$trangThai = isset($_POST['trangThai']) ? (int)$_POST['trangThai'] : 1;
$hanhDong = $_POST['hanh_dong'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong('quan-ly-don-vi-van-chuyen.php', 'Phiên làm việc đã hết hạn, vui lòng đăng nhập lại.', 'loi');
    }

    if ($hanhDong === 'luu') {
        if ($tenDVVC === '') {
            chuyen_huong('quan-ly-don-vi-van-chuyen.php', 'Vui lòng nhập tên đơn vị vận chuyển.', 'loi');
        }

        $stmtCheck = $conn->prepare("SELECT maDVVC FROM donvivanchuyen WHERE tenDVVC = ? AND maDVVC <> ? LIMIT 1");
        $stmtCheck->bind_param('si', $tenDVVC, $maDVVC);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->fetch_assoc()) {
            chuyen_huong('quan-ly-don-vi-van-chuyen.php', 'Đơn vị vận chuyển đã tồn tại', 'loi');
        }

        if ($maDVVC > 0) {
            $stmt = $conn->prepare("UPDATE donvivanchuyen SET tenDVVC = ?, sdtLienHe = ?, trangThai = ? WHERE maDVVC = ?");
            $stmt->bind_param('ssii', $tenDVVC, $sdtLienHe, $trangThai, $maDVVC);
            $stmt->execute();
            chuyen_huong('quan-ly-don-vi-van-chuyen.php', 'Cập nhật đơn vị vận chuyển thành công.');
        } else {
            $stmt = $conn->prepare("INSERT INTO donvivanchuyen (tenDVVC, sdtLienHe, trangThai) VALUES (?, ?, ?)");
            $stmt->bind_param('ssi', $tenDVVC, $sdtLienHe, $trangThai);
            $stmt->execute();
            chuyen_huong('quan-ly-don-vi-van-chuyen.php', 'Thêm đơn vị vận chuyển mới thành công.');
        }
    }

    if ($hanhDong === 'doi_trang_thai') {
        $stmt = $conn->prepare("UPDATE donvivanchuyen SET trangThai = ? WHERE maDVVC = ?");
        $stmt->bind_param('ii', $trangThai, $maDVVC);
        $stmt->execute();
        chuyen_huong('quan-ly-don-vi-van-chuyen.php', 'Đã cập nhật trạng thái hợp tác đối tác.');
    }
}

$dsDVVC = $conn->query("
    SELECT dv.*, COUNT(d.maDonHang) AS soDonGiao 
    FROM donvivanchuyen dv 
    LEFT JOIN donhang d ON dv.maDVVC = d.maDVVC 
    GROUP BY dv.maDVVC 
    ORDER BY dv.maDVVC DESC
");

$trang_hien_tai = 'dvvc';
$tieu_de_trang = 'Quản lý đơn vị vận chuyển';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<div class="dau-trang">
    <h1>Quản lý đối tác vận chuyển</h1>
</div>

<div class="hai-cot" style="display:grid; grid-template-columns: 1fr 2fr; gap:24px;">
    <form class="card" method="POST" action="quan-ly-don-vi-van-chuyen.php">
        <h2>Thêm / Sửa đối tác</h2>
        <?= csrf_field() ?>
        <input type="hidden" name="hanh_dong" value="luu">
        <input type="hidden" name="maDVVC" id="maDVVC" value="0">

        <div style="margin-bottom:16px;">
            <label class="nhan-truong" for="tenDVVC">Tên đối tác vận chuyển: (*)</label>
            <input class="o-nhap" type="text" id="tenDVVC" name="tenDVVC" required placeholder="VD: Giao Hàng Nhanh, Viettel Post...">
        </div>

        <div style="margin-bottom:16px;">
            <label class="nhan-truong" for="sdtLienHe">Số điện thoại liên hệ (sdtLienHe):</label>
            <input class="o-nhap" type="text" id="sdtLienHe" name="sdtLienHe" placeholder="VD: 1900 xxxx hoặc 09xxxxxxx">
        </div>

        <div style="margin-bottom:16px;">
            <label class="nhan-truong" for="trangThai">Trạng thái hợp tác:</label>
            <select class="o-nhap" id="trangThai" name="trangThai">
                <option value="1">1 - Đang hoạt động</option>
                <option value="0">0 - Ngừng hợp tác</option>
            </select>
        </div>

        <div class="hanh-dong">
            <button class="nut nut-chinh" type="submit" id="btn-luu">Thêm đối tác</button>
            <button class="nut" type="button" onclick="datLaiForm()" id="btn-huy" style="display:none;">Hủy</button>
        </div>
    </form>

    <div class="card">
        <h2>Danh sách đơn vị vận chuyển</h2>
        <div class="cuon-ngang">
            <table class="bang">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Tên đối tác</th>
                        <th>SĐT liên hệ</th>
                        <th>Trạng thái</th>
                        <th class="phai-so">Đơn đã giao</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($dv = $dsDVVC->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $dv['maDVVC'] ?></td>
                            <td><strong><?= e($dv['tenDVVC']) ?></strong></td>
                            <td><?= e($dv['sdtLienHe'] ?? '—') ?></td>
                            <td>
                                <?php if ((int)$dv['trangThai'] === 1): ?>
                                    <span style="background:#DCEFE8; color:#0F4C45; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px;">Đang hoạt động</span>
                                <?php else: ?>
                                    <span style="background:#FDE8E2; color:#9A2F0E; padding:4px 10px; border-radius:12px; font-weight:700; font-size:12px;">Ngừng hợp tác</span>
                                <?php endif; ?>
                            </td>
                            <td class="phai-so"><?= (int)$dv['soDonGiao'] ?> đơn</td>
                            <td>
                                <div class="hanh-dong">
                                    <button class="nut nut-nho" onclick="suaDVVC(<?= $dv['maDVVC'] ?>, '<?= e($dv['tenDVVC']) ?>', '<?= e($dv['sdtLienHe'] ?? '') ?>', <?= (int)$dv['trangThai'] ?>)">Sửa</button>
                                    
                                    <form method="POST" action="quan-ly-don-vi-van-chuyen.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                        <input type="hidden" name="maDVVC" value="<?= $dv['maDVVC'] ?>">
                                        <input type="hidden" name="trangThai" value="<?= (int)$dv['trangThai'] === 1 ? 0 : 1 ?>">
                                        <button class="nut nut-nho <?= (int)$dv['trangThai'] === 1 ? 'nut-nguy' : 'nut-chinh' ?>" type="submit">
                                            <?= (int)$dv['trangThai'] === 1 ? 'Ngừng hợp tác' : 'Kích hoạt' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function suaDVVC(id, ten, sdt, trangThai) {
    document.getElementById('maDVVC').value = id;
    document.getElementById('tenDVVC').value = ten;
    document.getElementById('sdtLienHe').value = sdt;
    document.getElementById('trangThai').value = trangThai;
    document.getElementById('btn-luu').textContent = 'Lưu thay đổi';
    document.getElementById('btn-huy').style.display = 'inline-flex';
}
function datLaiForm() {
    document.getElementById('maDVVC').value = '0';
    document.getElementById('tenDVVC').value = '';
    document.getElementById('sdtLienHe').value = '';
    document.getElementById('trangThai').value = '1';
    document.getElementById('btn-luu').textContent = 'Thêm đối tác';
    document.getElementById('btn-huy').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>