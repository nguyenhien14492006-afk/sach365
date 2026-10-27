<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

$tab = $_GET['tab'] ?? 'tac-gia';
if (!in_array($tab, ['tac-gia', 'nxb'])) {
    $tab = 'tac-gia';
}

$hanhDong = $_POST['hanh_dong'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_hop_le()) {
        chuyen_huong("quan-ly-tac-gia-nxb.php?tab={$tab}", 'Phiên làm việc đã hết hạn, vui lòng quay lại trang đăng nhập.', 'loi');
    }

    if ($tab === 'tac-gia') {
        $maTG = (int)($_POST['maTacGia'] ?? 0);
        $tenTG = trim($_POST['tenTacGia'] ?? '');
        $tieuSu = trim($_POST['tieuSu'] ?? '');

        if ($hanhDong === 'luu') {
            if ($tenTG === '') {
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=tac-gia", 'Vui lòng nhập đầy đủ thông tin bắt buộc', 'loi');
            }

            if ($maTG > 0) {
                $stmt = $conn->prepare("UPDATE tacgia SET tenTacGia = ?, tieuSu = ? WHERE maTacGia = ?");
                $stmt->bind_param('ssi', $tenTG, $tieuSu, $maTG);
                $stmt->execute();
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=tac-gia", 'Cập nhật thông tin tác giả thành công.');
            } else {
                $stmt = $conn->prepare("INSERT INTO tacgia (tenTacGia, tieuSu) VALUES (?, ?)");
                $stmt->bind_param('ss', $tenTG, $tieuSu);
                $stmt->execute();
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=tac-gia", 'Thêm tác giả mới thành công.');
            }
        }

        if ($hanhDong === 'xoa') {
            try {
                $stmt = $conn->prepare("DELETE FROM tacgia WHERE maTacGia = ?");
                $stmt->bind_param('i', $maTG);
                $stmt->execute();
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=tac-gia", 'Đã xóa tác giả thành công.');
            } catch (mysqli_sql_exception $ex) {
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=tac-gia", 'Không thể xóa do dữ liệu đang được liên kết với sản phẩm sách', 'loi');
            }
        }
    } else {
        $maNXB = (int)($_POST['maNXB'] ?? 0);
        $tenNXB = trim($_POST['tenNXB'] ?? '');
        $diaChi = trim($_POST['diaChi'] ?? '');
        $sdt = trim($_POST['sdt'] ?? '');

        if ($hanhDong === 'luu') {
            if ($tenNXB === '') {
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=nxb", 'Vui lòng nhập đầy đủ thông tin bắt buộc', 'loi');
            }

            if ($maNXB > 0) {
                $stmt = $conn->prepare("UPDATE nhaxuatban SET tenNXB = ?, diaChi = ?, sdt = ? WHERE maNXB = ?");
                $stmt->bind_param('sssi', $tenNXB, $diaChi, $sdt, $maNXB);
                $stmt->execute();
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=nxb", 'Cập nhật nhà xuất bản thành công.');
            } else {
                $stmt = $conn->prepare("INSERT INTO nhaxuatban (tenNXB, diaChi, sdt) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $tenNXB, $diaChi, $sdt);
                $stmt->execute();
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=nxb", 'Thêm nhà xuất bản mới thành công.');
            }
        }

        if ($hanhDong === 'xoa') {
            try {
                $stmt = $conn->prepare("DELETE FROM nhaxuatban WHERE maNXB = ?");
                $stmt->bind_param('i', $maNXB);
                $stmt->execute();
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=nxb", 'Đã xóa nhà xuất bản thành công.');
            } catch (mysqli_sql_exception $ex) {
                chuyen_huong("quan-ly-tac-gia-nxb.php?tab=nxb", 'Không thể xóa do dữ liệu đang được liên kết với sản phẩm sách', 'loi');
            }
        }
    }
}

$dsTacGia = $conn->query("
    SELECT tg.*, COUNT(st.maSach) AS soDauSach 
    FROM tacgia tg 
    LEFT JOIN sachtacgia st ON tg.maTacGia = st.maTacGia 
    GROUP BY tg.maTacGia 
    ORDER BY tg.maTacGia DESC
");

$dsNXB = $conn->query("
    SELECT nxb.*, COUNT(s.maSach) AS soDauSach 
    FROM nhaxuatban nxb 
    LEFT JOIN sach s ON nxb.maNXB = s.maNXB 
    GROUP BY nxb.maNXB 
    ORDER BY nxb.maNXB DESC
");

$trang_hien_tai = 'tac-gia-nxb';
$tieu_de_trang = 'Quản lý Tác giả & Nhà xuất bản';
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<div class="dau-trang">
    <h1>Quản lý Tác giả &amp; Nhà xuất bản</h1>
</div>

<div style="display: flex; gap: 12px; margin-bottom: 24px;">
    <a href="quan-ly-tac-gia-nxb.php?tab=tac-gia" class="nut <?= $tab === 'tac-gia' ? 'nut-chinh' : '' ?>" style="text-decoration: none;">
        ✍️ Phân hệ Tác giả
    </a>
    <a href="quan-ly-tac-gia-nxb.php?tab=nxb" class="nut <?= $tab === 'nxb' ? 'nut-chinh' : '' ?>" style="text-decoration: none;">
        🏢 Phân hệ Nhà xuất bản
    </a>
</div>

<?php if ($tab === 'tac-gia'): ?>
    <div class="hai-cot" style="display:grid; grid-template-columns: 1fr 2fr; gap:24px;">
        <form class="card" method="POST" action="quan-ly-tac-gia-nxb.php?tab=tac-gia">
            <h2>Thêm / Sửa Tác giả</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="maTacGia" id="maTacGia" value="0">

            <div style="margin-bottom:16px;">
                <label class="nhan-truong" for="tenTacGia">Tên tác giả: (*)</label>
                <input class="o-nhap" type="text" id="tenTacGia" name="tenTacGia" required placeholder="Nhập tên tác giả...">
            </div>

            <div style="margin-bottom:16px;">
                <label class="nhan-truong" for="tieuSu">Tiểu sử tác giả:</label>
                <textarea class="o-nhap" id="tieuSu" name="tieuSu" rows="4" placeholder="Nhập tóm tắt tiểu sử..."></textarea>
            </div>

            <div class="hanh-dong">
                <button class="nut nut-chinh" type="submit" id="btn-luu-tg">Thêm tác giả</button>
                <button class="nut" type="button" onclick="datLaiFormTG()" id="btn-huy-tg" style="display:none;">Hủy</button>
            </div>
        </form>

        <div class="card">
            <h2>Danh sách Tác giả</h2>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên tác giả</th>
                            <th>Tiểu sử</th>
                            <th class="phai-so">Số đầu sách</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($tg = $dsTacGia->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $tg['maTacGia'] ?></td>
                                <td><strong><?= e($tg['tenTacGia']) ?></strong></td>
                                <td><div style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($tg['tieuSu'] ?? '—') ?></div></td>
                                <td class="phai-so"><?= (int)$tg['soDauSach'] ?> cuốn</td>
                                <td>
                                    <div class="hanh-dong">
                                        <button class="nut nut-nho" onclick="suaTacGia(<?= $tg['maTacGia'] ?>, '<?= e($tg['tenTacGia']) ?>', '<?= e($tg['tieuSu'] ?? '') ?>')">Sửa</button>
                                        <form method="POST" action="quan-ly-tac-gia-nxb.php?tab=tac-gia" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tác giả này?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="hanh_dong" value="xoa">
                                            <input type="hidden" name="maTacGia" value="<?= $tg['maTacGia'] ?>">
                                            <button class="nut nut-nho nut-nguy" type="submit">Xóa</button>
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
    function suaTacGia(id, ten, tieuSu) {
        document.getElementById('maTacGia').value = id;
        document.getElementById('tenTacGia').value = ten;
        document.getElementById('tieuSu').value = tieuSu;
        document.getElementById('btn-luu-tg').textContent = 'Lưu thay đổi';
        document.getElementById('btn-huy-tg').style.display = 'inline-flex';
    }
    function datLaiFormTG() {
        document.getElementById('maTacGia').value = '0';
        document.getElementById('tenTacGia').value = '';
        document.getElementById('tieuSu').value = '';
        document.getElementById('btn-luu-tg').textContent = 'Thêm tác giả';
        document.getElementById('btn-huy-tg').style.display = 'none';
    }
    </script>

<?php else: ?>
    <div class="hai-cot" style="display:grid; grid-template-columns: 1fr 2fr; gap:24px;">
        <form class="card" method="POST" action="quan-ly-tac-gia-nxb.php?tab=nxb">
            <h2>Thêm / Sửa Nhà xuất bản</h2>
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="maNXB" id="maNXB" value="0">

            <div style="margin-bottom:16px;">
                <label class="nhan-truong" for="tenNXB">Tên nhà xuất bản: (*)</label>
                <input class="o-nhap" type="text" id="tenNXB" name="tenNXB" required placeholder="Nhập tên NXB...">
            </div>

            <div style="margin-bottom:16px;">
                <label class="nhan-truong" for="diaChi">Địa chỉ:</label>
                <input class="o-nhap" type="text" id="diaChi" name="diaChi" placeholder="Nhập địa chỉ trụ sở...">
            </div>

            <div style="margin-bottom:16px;">
                <label class="nhan-truong" for="sdt">Số điện thoại:</label>
                <input class="o-nhap" type="text" id="sdt" name="sdt" placeholder="Nhập số điện thoại liên hệ...">
            </div>

            <div class="hanh-dong">
                <button class="nut nut-chinh" type="submit" id="btn-luu-nxb">Thêm NXB</button>
                <button class="nut" type="button" onclick="datLaiFormNXB()" id="btn-huy-nxb" style="display:none;">Hủy</button>
            </div>
        </form>

        <div class="card">
            <h2>Danh sách Nhà xuất bản</h2>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên NXB</th>
                            <th>Địa chỉ</th>
                            <th>SĐT</th>
                            <th class="phai-so">Số đầu sách</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($nxb = $dsNXB->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $nxb['maNXB'] ?></td>
                                <td><strong><?= e($nxb['tenNXB']) ?></strong></td>
                                <td><?= e($nxb['diaChi'] ?? '—') ?></td>
                                <td><?= e($nxb['sdt'] ?? '—') ?></td>
                                <td class="phai-so"><?= (int)$nxb['soDauSach'] ?> cuốn</td>
                                <td>
                                    <div class="hanh-dong">
                                        <button class="nut nut-nho" onclick="suaNXB(<?= $nxb['maNXB'] ?>, '<?= e($nxb['tenNXB']) ?>', '<?= e($nxb['diaChi'] ?? '') ?>', '<?= e($nxb['sdt'] ?? '') ?>')">Sửa</button>
                                        <form method="POST" action="quan-ly-tac-gia-nxb.php?tab=nxb" onsubmit="return confirm('Bạn có chắc chắn muốn xóa NXB này?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="hanh_dong" value="xoa">
                                            <input type="hidden" name="maNXB" value="<?= $nxb['maNXB'] ?>">
                                            <button class="nut nut-nho nut-nguy" type="submit">Xóa</button>
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
    function suaNXB(id, ten, diaChi, sdt) {
        document.getElementById('maNXB').value = id;
        document.getElementById('tenNXB').value = ten;
        document.getElementById('diaChi').value = diaChi;
        document.getElementById('sdt').value = sdt;
        document.getElementById('btn-luu-nxb').textContent = 'Lưu thay đổi';
        document.getElementById('btn-huy-nxb').style.display = 'inline-flex';
    }
    function datLaiFormNXB() {
        document.getElementById('maNXB').value = '0';
        document.getElementById('tenNXB').value = '';
        document.getElementById('diaChi').value = '';
        document.getElementById('sdt').value = '';
        document.getElementById('btn-luu-nxb').textContent = 'Thêm NXB';
        document.getElementById('btn-huy-nxb').style.display = 'none';
    }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>