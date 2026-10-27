<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';
yeu_cau_dang_nhap();

$tuNgay = trim($_GET['tu_ngay'] ?? date('Y-m-01'));
$denNgay = trim($_GET['den_ngay'] ?? date('Y-m-d'));
$kieuNhom = $_GET['kieu_nhom'] ?? 'ngay';

$tuNgayChuan = $tuNgay . ' 00:00:00';
$denNgayChuan = $denNgay . ' 23:59:59';

if (isset($_GET['xuat_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bao-cao-doanh-thu-' . $tuNgay . '-den-' . $denNgay . '.csv"');
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    fputcsv($output, ['BÁO CÁO DOANH THU TỪ ' . $tuNgay . ' ĐẾN ' . $denNgay]);
    fputcsv($output, []);
    fputcsv($output, ['Thời gian', 'Số đơn hoàn thành', 'Doanh thu (VNĐ)']);

    $dinhDangNgay = "%Y-%m-%d";
    if ($kieuNhom === 'thang') $dinhDangNgay = "%Y-%m";
    if ($kieuNhom === 'nam') $dinhDangNgay = "%Y";

    $sqlCsv = "SELECT DATE_FORMAT(ngayLap, '{$dinhDangNgay}') AS mox, COUNT(*) AS soDon, SUM(tongTien) AS doanhThu 
               FROM donhang 
               WHERE trangThaiDonHang = 3 AND ngayLap BETWEEN ? AND ? 
               GROUP BY mox ORDER BY mox ASC";
    $resCsv = truy_van($conn, $sqlCsv, 'ss', [$tuNgayChuan, $denNgayChuan]);
    
    while ($r = $resCsv->fetch_assoc()) {
        fputcsv($output, [$r['mox'], $r['soDon'], $r['doanhThu']]);
    }
    fclose($output);
    exit;
}

$trang_hien_tai = 'trang-chu';
$tieu_de_trang = 'Tổng quan & Thống kê doanh thu';

$tongSach       = (int)gia_tri_don($conn, "SELECT COUNT(*) FROM sach WHERE trangThai = 1");
$tongDon        = (int)gia_tri_don($conn, "SELECT COUNT(*) FROM donhang");
$donMoi         = (int)gia_tri_don($conn, "SELECT COUNT(*) FROM donhang WHERE trangThaiDonHang = 0");
$khachHang      = (int)gia_tri_don($conn, "SELECT COUNT(*) FROM khachhang WHERE trangThai = 1");
$sapHet         = (int)gia_tri_don($conn, "SELECT COUNT(*) FROM sach WHERE trangThai = 1 AND soLuongTon <= " . NGUONG_SAP_HET);

$thongKeKy = truy_van($conn, "
    SELECT 
        COALESCE(SUM(tongTien), 0) AS doanhThu,
        COUNT(*) AS soDonHoanThanh,
        COALESCE((SELECT SUM(ct.soLuong) 
                  FROM chitietdonhang ct 
                  JOIN donhang d2 ON ct.maDonHang = d2.maDonHang 
                  WHERE d2.trangThaiDonHang = 3 AND d2.ngayLap BETWEEN ? AND ?), 0) AS soSachBan
    FROM donhang 
    WHERE trangThaiDonHang = 3 AND ngayLap BETWEEN ? AND ?
", 'ssss', [$tuNgayChuan, $denNgayChuan, $tuNgayChuan, $denNgayChuan])->fetch_assoc();

$doanhThuKy = (float)$thongKeKy['doanhThu'];
$soDonKy = (int)$thongKeKy['soDonHoanThanh'];
$soSachBanKy = (int)$thongKeKy['soSachBan'];
$giaTriTrungBinhDon = $soDonKy > 0 ? $doanhThuKy / $soDonKy : 0;

$dinhDangSql = "%Y-%m-%d";
if ($kieuNhom === 'thang') $dinhDangSql = "%Y-%m";
if ($kieuNhom === 'nam') $dinhDangSql = "%Y";

$sqlBieuDo = "SELECT DATE_FORMAT(ngayLap, '{$dinhDangSql}') AS nhomThoiGian, SUM(tongTien) AS doanhThu 
              FROM donhang 
              WHERE trangThaiDonHang = 3 AND ngayLap BETWEEN ? AND ? 
              GROUP BY nhomThoiGian ORDER BY nhomThoiGian ASC";
$resBieuDo = truy_van($conn, $sqlBieuDo, 'ss', [$tuNgayChuan, $denNgayChuan]);

$nhanThoiGian = [];
$duLieuDoanhThu = [];
while ($r = $resBieuDo->fetch_assoc()) {
    $nhanThoiGian[] = $r['nhomThoiGian'];
    $duLieuDoanhThu[] = (float)$r['doanhThu'];
}

$sqlTopSach = "
    SELECT s.maSach, s.tenSach, SUM(ct.soLuong) AS tongDaBan, SUM(ct.soLuong * ct.donGia) AS tongDoanhThu
    FROM chitietdonhang ct
    JOIN donhang d ON ct.maDonHang = d.maDonHang
    JOIN sach s ON ct.maSach = s.maSach
    WHERE d.trangThaiDonHang = 3 AND d.ngayLap BETWEEN ? AND ?
    GROUP BY s.maSach, s.tenSach
    ORDER BY tongDaBan DESC
    LIMIT 10
";
$dsTopSach = truy_van($conn, $sqlTopSach, 'ss', [$tuNgayChuan, $denNgayChuan]);

$sqlDanhMuc = "
    SELECT COALESCE(dm.tenDanhMuc, 'Chưa phân loại') AS tenDanhMuc, 
           SUM(ct.soLuong * ct.donGia) AS doanhThu,
           SUM(ct.soLuong) AS soLuongBan
    FROM chitietdonhang ct
    JOIN donhang d ON ct.maDonHang = d.maDonHang
    JOIN sach s ON ct.maSach = s.maSach
    LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc
    WHERE d.trangThaiDonHang = 3 AND d.ngayLap BETWEEN ? AND ?
    GROUP BY dm.maDanhMuc, dm.tenDanhMuc
    ORDER BY doanhThu DESC
";
$dsDanhMuc = truy_van($conn, $sqlDanhMuc, 'ss', [$tuNgayChuan, $denNgayChuan]);

$nhanDanhMuc = [];
$duLieuDanhMuc = [];
$bangDanhMuc = [];
while ($dm = $dsDanhMuc->fetch_assoc()) {
    $nhanDanhMuc[] = $dm['tenDanhMuc'];
    $duLieuDanhMuc[] = (float)$dm['doanhThu'];
    $bangDanhMuc[] = $dm;
}

$soTheoTrangThai = [];
$resTT = $conn->query("SELECT trangThaiDonHang, COUNT(*) AS so FROM donhang GROUP BY trangThaiDonHang");
while ($r = $resTT->fetch_assoc()) {
    $soTheoTrangThai[(int)$r['trangThaiDonHang']] = (int)$r['so'];
}

$flash = lay_flash();
require_once __DIR__ . '/../dung-chung/dau-trang-admin.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .bo-loc-thoi-gian { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; background: #fff; padding: 16px; border: 1px solid var(--vien); border-radius: 8px; margin-bottom: 20px; }
    .bo-loc-thoi-gian .nhom { display: flex; flex-direction: column; gap: 4px; }
    .khung-bieu-do { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
    @media (max-width: 992px) { .khung-bieu-do { grid-template-columns: 1fr; } }
    .the-bieu-do { background: #fff; border: 1px solid var(--vien); border-radius: 8px; padding: 16px; }
    @media print {
        .bo-loc-thoi-gian, .nut, header, nav, sidebar { display: none !important; }
        .card, .the-bieu-do { border: none !important; box-shadow: none !important; }
    }
</style>

<div class="dau-trang" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <h1>Tổng quan & Thống kê doanh thu</h1>
    <div style="display:flex; gap:8px;">
        <a class="nut" href="?<?= http_build_query(array_merge($_GET, ['xuat_csv' => 1])) ?>">📥 Xuất Excel (CSV)</a>
        <button class="nut nut-chinh" onclick="window.print()">🖨️ In / PDF</button>
    </div>
</div>

<?php if ($flash): ?>
    <div class="thong-bao thong-bao-<?= e($flash['loai']) ?>" style="margin-bottom:20px">
        <?= e($flash['noi_dung']) ?>
    </div>
<?php endif; ?>

<form method="GET" action="" class="bo-loc-thoi-gian">
    <div class="nhom">
        <label class="nhan-truong" for="tu_ngay">Từ ngày</label>
        <input class="o-nhap" type="date" id="tu_ngay" name="tu_ngay" value="<?= e($tuNgay) ?>">
    </div>
    <div class="nhom">
        <label class="nhan-truong" for="den_ngay">Đến ngày</label>
        <input class="o-nhap" type="date" id="den_ngay" name="den_ngay" value="<?= e($denNgay) ?>">
    </div>
    <div class="nhom">
        <label class="nhan-truong" for="kieu_nhom">Gom nhóm biểu đồ</label>
        <select class="o-chon" id="kieu_nhom" name="kieu_nhom">
            <option value="ngay" <?= $kieuNhom === 'ngay' ? 'selected' : '' ?>>Theo Ngày</option>
            <option value="thang" <?= $kieuNhom === 'thang' ? 'selected' : '' ?>>Theo Tháng</option>
            <option value="nam" <?= $kieuNhom === 'nam' ? 'selected' : '' ?>>Theo Năm</option>
        </select>
    </div>
    <button class="nut nut-chinh" type="submit">Lọc báo cáo</button>
    <a class="nut" href="trang-quan-tri.php">Đặt lại</a>
</form>

<div class="luoi-thong-ke" style="margin-bottom:24px;">
    <div class="the-so">
        <div class="nhan">Doanh thu trong kỳ</div>
        <div class="so" style="color:#B93A12"><?= vnd($doanhThuKy) ?></div>
        <div class="ghi-chu">Từ <?= date('d/m/Y', strtotime($tuNgay)) ?> đến <?= date('d/m/Y', strtotime($denNgay)) ?></div>
    </div>
    <div class="the-so">
        <div class="nhan">Số đơn hoàn thành</div>
        <div class="so"><?= number_format($soDonKy) ?> đơn</div>
        <div class="ghi-chu">Trung bình: <?= vnd($giaTriTrungBinhDon) ?>/đơn</div>
    </div>
    <div class="the-so">
        <div class="nhan">Số sách đã bán</div>
        <div class="so"><?= number_format($soSachBanKy) ?> cuốn</div>
        <div class="ghi-chu">Đã giao thành công</div>
    </div>
    <a class="the-so" href="quan-ly-don-hang.php?trang_thai=0">
        <div class="nhan">Đơn chờ xử lý hiện tại</div>
        <div class="so" style="color:<?= $donMoi > 0 ? '#B93A12' : 'inherit' ?>"><?= $donMoi ?></div>
        <div class="ghi-chu">Cần duyệt ngay</div>
    </a>
</div>

<div class="khung-bieu-do">
    <div class="the-bieu-do">
        <h3>Xu hướng doanh thu (<?= $kieuNhom === 'ngay' ? 'Theo Ngày' : ($kieuNhom === 'thang' ? 'Theo Tháng' : 'Theo Năm') ?>)</h3>
        <canvas id="chartDoanhThu" height="110"></canvas>
    </div>
    <div class="the-bieu-do">
        <h3>Tỷ lệ doanh thu theo Danh mục</h3>
        <canvas id="chartDanhMuc" height="220"></canvas>
    </div>
</div>

<div class="hai-cot" style="margin-bottom:24px">
    <section class="card">
        <h2>🔥 Top 10 Sách bán chạy nhất</h2>
        <?php if ($dsTopSach->num_rows > 0): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tên sách</th>
                            <th class="phai-so">Đã bán</th>
                            <th class="phai-so">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $dsTopSach->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $s['maSach'] ?></strong></td>
                                <td><strong><?= e($s['tenSach']) ?></strong></td>
                                <td class="phai-so"><strong style="color:var(--xanh)"><?= number_format($s['tongDaBan']) ?></strong></td>
                                <td class="phai-so"><strong><?= vnd($s['tongDoanhThu']) ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="trong">Không có dữ liệu bán hàng trong khoảng thời gian này.</div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>📚 Doanh thu theo Danh mục</h2>
        <?php if (!empty($bangDanhMuc)): ?>
            <div class="cuon-ngang">
                <table class="bang">
                    <thead>
                        <tr>
                            <th>Danh mục</th>
                            <th class="phai-so">Số lượng bán</th>
                            <th class="phai-so">Tổng doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bangDanhMuc as $dm): ?>
                            <tr>
                                <td><strong><?= e($dm['tenDanhMuc']) ?></strong></td>
                                <td class="phai-so"><?= number_format($dm['soLuongBan']) ?> cuốn</td>
                                <td class="phai-so"><strong><?= vnd($dm['doanhThu']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="trong">Chưa có phát sinh doanh thu theo danh mục.</div>
        <?php endif; ?>
    </section>
</div>

<div class="card" style="margin-bottom:24px">
    <h2>Trạng thái đơn hàng tổng hệ thống</h2>
    <div class="tab-loc" style="margin-bottom:0">
        <?php foreach (TRANG_THAI_DON as $ma => $t): ?>
            <a href="quan-ly-don-hang.php?trang_thai=<?= $ma ?>"><?= e($t[0]) ?> <strong><?= $soTheoTrangThai[$ma] ?? 0 ?></strong></a>
        <?php endforeach; ?>
    </div>
</div>

<script>
const ctxDoanhThu = document.getElementById('chartDoanhThu').getContext('2d');
new Chart(ctxDoanhThu, {
    type: 'line',
    data: {
        labels: <?= json_encode($nhanThoiGian) ?>,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: <?= json_encode($duLieuDoanhThu) ?>,
            borderColor: '#0F4C45',
            backgroundColor: 'rgba(15, 76, 69, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value >= 1000000 ? (value / 1000000) + ' Tr' : value.toLocaleString('vi-VN');
                    }
                }
            }
        }
    }
});

const ctxDanhMuc = document.getElementById('chartDanhMuc').getContext('2d');
new Chart(ctxDanhMuc, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($nhanDanhMuc) ?>,
        datasets: [{
            data: <?= json_encode($duLieuDanhMuc) ?>,
            backgroundColor: [
                '#0F4C45', '#B93A12', '#2563EB', '#D97706', '#059669',
                '#7C3AED', '#DB2777', '#4B5563', '#0891B2', '#65A30D'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.raw);
                    }
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../dung-chung/cuoi-trang-admin.php'; ?>