<?php
require_once 'dung-chung/dau-trang.php';
require_once 'dung-chung/khoi-tao.php';

if (!isset($_SESSION['maKH'])) {
    header('Location: dang-nhap.php');
    exit();
}

$maKH = (int)$_SESSION['maKH'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'huy_don') {
    if (!csrf_hop_le()) {
        chuyen_huong('lich-su-don-hang.php', 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'loi');
    }

    $maDon = (int)($_POST['maDonHang'] ?? 0);
    
    $stmt = $conn->prepare("SELECT maDonHang, maKM FROM donhang WHERE maDonHang = ? AND maKH = ? AND trangThaiDonHang = 0");
    $stmt->bind_param('ii', $maDon, $maKH);
    $stmt->execute();
    $don = $stmt->get_result()->fetch_assoc();

    if ($don) {
        try {
            $conn->begin_transaction();

            $stmtHuy = $conn->prepare("UPDATE donhang SET trangThaiDonHang = 4 WHERE maDonHang = ?");
            $stmtHuy->bind_param('i', $maDon);
            $stmtHuy->execute();

            $resCT = truy_van($conn, "SELECT maSach, soLuong FROM chitietdonhang WHERE maDonHang = ?", 'i', [$maDon]);
            $stmtKho = $conn->prepare("UPDATE sach SET soLuongTon = soLuongTon + ? WHERE maSach = ?");
            while ($ct = $resCT->fetch_assoc()) {
                $stmtKho->bind_param('ii', $ct['soLuong'], $ct['maSach']);
                $stmtKho->execute();
            }

            if ($don['maKM']) {
                $stmtKM = $conn->prepare("UPDATE khuyenmai SET soLuong = soLuong + 1 WHERE maKM = ? AND soLuong IS NOT NULL");
                $stmtKM->bind_param('i', $don['maKM']);
                $stmtKM->execute();
            }

            $stmtLS = $conn->prepare("INSERT INTO lichsutrangthai (maDonHang, trangThai, ghiChu) VALUES (?, 4, 'Khách hàng tự hủy đơn hàng khi đơn đang ở trạng thái Chờ xác nhận')");
            $stmtLS->bind_param('i', $maDon);
            $stmtLS->execute();

            $conn->commit();
            chuyen_huong('lich-su-don-hang.php', 'Đã hủy đơn hàng #' . $maDon . ' thành công và hoàn tồn kho.');
        } catch (Throwable $ex) {
            $conn->rollback();
            chuyen_huong('lich-su-don-hang.php', 'Lỗi không thể hủy đơn hàng: ' . $ex->getMessage(), 'loi');
        }
    } else {
        chuyen_huong('lich-su-don-hang.php', 'Đơn hàng không tồn tại hoặc hệ thống đã chuyển sang bước xử lý tiếp theo.', 'loi');
    }
}

$dsDonHang = truy_van($conn, "
    SELECT d.*, 
        km.maVoucher,
        dv.tenDVVC
    FROM donhang d 
    LEFT JOIN khuyenmai km ON d.maKM = km.maKM
    LEFT JOIN donvivanchuyen dv ON d.maDVVC = dv.maDVVC
    WHERE d.maKH = ? 
    ORDER BY d.maDonHang DESC
", 'i', [$maKH]);

$flash = lay_flash();
?>

<div style="max-width: 1000px; margin: 0 auto; padding-bottom: 40px;">
    <h1 style="font-size: 26px; font-weight: 800; margin-bottom: 24px;">Lịch sử đơn hàng của bạn</h1>

    <?php if ($flash): ?>
        <div style="padding:14px 18px; border-radius:8px; margin-bottom:20px; font-weight:600; background:<?= $flash['loai'] === 'loi' ? '#FDE8E2; color:#9A2F0E; border:1px solid #F8B4B4;' : '#DCEFE8; color:#0F4C45; border:1px solid #A3D9C9;' ?>">
            <?= e($flash['noi_dung']) ?>
        </div>
    <?php endif; ?>

    <?php if ($dsDonHang->num_rows === 0): ?>
        <div class="dh-the" style="text-align: center; padding: 56px 24px;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#55635D" stroke-width="1.5" style="margin-bottom:16px;">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <path d="M16 10a4 4 0 0 1-8 0"></path>
            </svg>
            <h2 style="font-size: 20px; font-weight: 700; color: #14201C; margin-bottom: 8px;">Bạn chưa có đơn hàng nào</h2>
            <p style="color: #55635D; margin-bottom: 24px;">Hãy khám phá hàng nghìn đầu sách hấp dẫn trên Sach365 và chọn cho mình cuốn sách yêu thích.</p>
            <a href="index.php" class="dh-nut-phu" style="display: inline-flex; align-items: center; justify-content: center; text-decoration: none; border-radius: 8px; padding: 0 32px;">Quay về trang chủ mua sắm</a>
        </div>
    <?php else: ?>

        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php while ($d = $dsDonHang->fetch_assoc()): 
                $maDon = (int)$d['maDonHang'];
                
                $resCT = truy_van($conn, "
                    SELECT ct.*, s.tenSach, s.hinhAnh 
                    FROM chitietdonhang ct 
                    JOIN sach s ON ct.maSach = s.maSach 
                    WHERE ct.maDonHang = ?
                ", 'i', [$maDon]);

                $resLS = truy_van($conn, "
                    SELECT * FROM lichsutrangthai 
                    WHERE maDonHang = ? 
                    ORDER BY maLichSu ASC
                ", 'i', [$maDon]);
            ?>
                <div class="dh-the">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid #EAEFEA; padding-bottom: 14px; margin-bottom: 16px;">
                        <div>
                            <strong style="font-size: 17px; color:#14201C;">Đơn hàng #<?= $maDon ?></strong>
                            <span style="font-size: 13px; color: #55635D; margin-left: 10px;">Ngày lập: <?= date('d/m/Y H:i', strtotime($d['ngayLap'])) ?></span>
                        </div>
                        <div><?= nhan_trang_thai_don($d['trangThaiDonHang']) ?></div>
                    </div>

                    <div style="font-size: 14px; color: #34443E; margin-bottom: 16px; background:#F7F8F5; padding:12px 16px; border-radius:8px;">
                        <div>Người nhận: <strong><?= e($d['tenNguoiNhan']) ?></strong> (<?= e($d['sdtNguoiNhan']) ?>)</div>
                        <div style="margin-top:4px;">Địa chỉ giao hàng: <?= e($d['diaChiGiao']) ?></div>
                        <?php if (!empty($d['tenDVVC'])): ?>
                            <div style="margin-top:4px; color:#0F4C45;">Đơn vị vận chuyển: <strong><?= e($d['tenDVVC']) ?></strong> <?= !empty($d['maVanDon']) ? '(Mã vận đơn: ' . e($d['maVanDon']) . ')' : '' ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        <?php while ($sp = $resCT->fetch_assoc()): ?>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                    <img src="tai-nguyen/hinh-anh/<?= e($sp['hinhAnh']) ?>" alt="" style="width: 44px; height: 58px; object-fit: cover; border-radius: 4px; background: #EAEFEA; flex-shrink: 0;" onerror="this.src='https://via.placeholder.com/44x58?text=Sach365'">
                                    <div style="min-width: 0;">
                                        <div style="font-weight: 600; font-size: 14px; color: #14201C; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?= e($sp['tenSach']) ?></div>
                                        <div style="font-size: 13px; color: #55635D; margin-top: 2px;"><?= vnd($sp['donGia']) ?> × <?= (int)$sp['soLuong'] ?> cuốn</div>
                                    </div>
                                </div>
                                <div style="font-weight: 700; font-size: 14px; color: #14201C; white-space: nowrap;">
                                    <?= vnd($sp['donGia'] * $sp['soLuong']) ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: flex-end; border-top: 1px solid #EAEFEA; padding-top: 14px; margin-top: 16px; flex-wrap: wrap; gap: 16px;">
                        <div>
                            <button type="button" onclick="dongMoChiTiet(<?= $maDon ?>)" style="background:none; border:none; color:#0F4C45; font-weight:700; cursor:pointer; font-size:14px; padding:0; display:flex; align-items:center; gap:6px;">
                                <span>📋 Xem lịch sử cập nhật &amp; Phí chi tiết</span>
                                <i class="fa-solid fa-chevron-down" id="icon-mui-ten-<?= $maDon ?>"></i>
                            </button>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 13px; color: #55635D;">
                                Tiền hàng: <?= vnd($d['tongTienHang']) ?> | Phí ship: <?= (float)$d['phiVanChuyen'] == 0 ? 'Free' : vnd($d['phiVanChuyen']) ?>
                                <?php if ((float)$d['tienGiamGia'] > 0): ?>
                                    | Giảm giá: -<?= vnd($d['tienGiamGia']) ?>
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 18px; font-weight: 800; color: #B93A12; margin-top: 2px;">
                                Tổng thanh toán: <?= vnd($d['tongTien']) ?>
                            </div>
                        </div>
                    </div>

                    <div id="khung-chi-tiet-<?= $maDon ?>" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px dashed #DCE3DE; background: #FAFAFA; padding: 16px; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px; font-size: 14px; color: #0F4C45;">Lịch sử cập nhật trạng thái đơn hàng (Thời gian thực):</h4>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #34443E;">
                            <?php if ($resLS && $resLS->num_rows > 0): ?>
                                <?php while ($ls = $resLS->fetch_assoc()): ?>
                                    <li style="margin-bottom: 6px;">
                                        <strong><?= date('d/m/Y H:i:s', strtotime($ls['thoiGian'])) ?></strong>: 
                                        <?= e($ls['ghiChu']) ?>
                                    </li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li>Đơn hàng đã được khởi tạo thành công trên hệ thống.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if ((int)$d['trangThaiDonHang'] === 0): ?>
                        <div style="display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 12px; border-top: 1px solid #EAEFEA;">
                            <form method="POST" action="lich-su-don-hang.php" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng #<?= $maDon ?>? Số lượng sách sẽ được hoàn về kho.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="huy_don">
                                <input type="hidden" name="maDonHang" value="<?= $maDon ?>">
                                <button class="nut nut-nguy" type="submit">Hủy đơn hàng</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>

    <?php endif; ?>
</div>

<script>
function dongMoChiTiet(maDon) {
    var khung = document.getElementById('khung-chi-tiet-' + maDon);
    var icon = document.getElementById('icon-mui-ten-' + maDon);
    if (khung.style.display === 'none') {
        khung.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        khung.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>

<?php require_once 'dung-chung/cuoi-trang.php'; ?>