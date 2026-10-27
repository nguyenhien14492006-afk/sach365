<?php
require_once 'dung-chung/dau-trang.php';

$tu_khoa = isset($_GET['tu_khoa']) ? trim($_GET['tu_khoa']) : '';
$maDanhMuc = isset($_GET['maDanhMuc']) ? intval($_GET['maDanhMuc']) : 0;
$maNXB = isset($_GET['maNXB']) ? intval($_GET['maNXB']) : 0;
$mucGia = isset($_GET['mucGia']) ? $_GET['mucGia'] : '';

// --- XỬ LÝ PHÂN TRANG ---
$so_sach_tren_trang = 8; // Số lượng sách hiển thị trên 1 trang
$trang_hien_tai = isset($_GET['trang']) ? max(1, intval($_GET['trang'])) : 1;
$vi_tri_bat_dau = ($trang_hien_tai - 1) * $so_sach_tren_trang;

// 1. Đếm tổng số lượng sách khớp với điều kiện lọc
$sql_count = "SELECT COUNT(*) AS tong_so FROM sach s WHERE s.trangThai = 1";
$params_count = [];
$types_count = "";

if (!empty($tu_khoa)) {
    $sql_count .= " AND s.tenSach LIKE ?";
    $params_count[] = "%" . $tu_khoa . "%";
    $types_count .= "s";
}

if ($maDanhMuc > 0) {
    $sql_count .= " AND s.maDanhMuc = ?";
    $params_count[] = $maDanhMuc;
    $types_count .= "i";
}

if ($maNXB > 0) {
    $sql_count .= " AND s.maNXB = ?";
    $params_count[] = $maNXB;
    $types_count .= "i";
}

if ($mucGia === 'duoi_100') {
    $sql_count .= " AND s.giaBan < 100000";
} elseif ($mucGia === '100_200') {
    $sql_count .= " AND s.giaBan BETWEEN 100000 AND 200000";
} elseif ($mucGia === 'tren_200') {
    $sql_count .= " AND s.giaBan > 200000";
}

$stmt_count = $conn->prepare($sql_count);
if (!empty($params_count)) {
    $stmt_count->bind_param($types_count, ...$params_count);
}
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$tong_so_sach = ($res_count && $row = $res_count->fetch_assoc()) ? $row['tong_so'] : 0;
$tong_so_trang = ceil($tong_so_sach / $so_sach_tren_trang);

// 2. Truy vấn danh sách sách theo trang (LIMIT)
$sql = "SELECT s.*, dm.tenDanhMuc FROM sach s LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc WHERE s.trangThai = 1";
$params = [];
$types = "";

if (!empty($tu_khoa)) {
    $sql .= " AND s.tenSach LIKE ?";
    $params[] = "%" . $tu_khoa . "%";
    $types .= "s";
}

if ($maDanhMuc > 0) {
    $sql .= " AND s.maDanhMuc = ?";
    $params[] = $maDanhMuc;
    $types .= "i";
}

if ($maNXB > 0) {
    $sql .= " AND s.maNXB = ?";
    $params[] = $maNXB;
    $types .= "i";
}

if ($mucGia === 'duoi_100') {
    $sql .= " AND s.giaBan < 100000";
} elseif ($mucGia === '100_200') {
    $sql .= " AND s.giaBan BETWEEN 100000 AND 200000";
} elseif ($mucGia === 'tren_200') {
    $sql .= " AND s.giaBan > 200000";
}

$sql .= " ORDER BY s.maSach DESC LIMIT ?, ?";
$params[] = $vi_tri_bat_dau;
$params[] = $so_sach_tren_trang;
$types .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res_sach = $stmt->get_result();

$res_dm = $conn->query("SELECT * FROM danhmuc WHERE trangThai = 1");
$res_nxb = $conn->query("SELECT * FROM nhaxuatban");

// Truy vấn gợi ý sách nổi bật nếu không tìm thấy kết quả nào
$res_goi_y = null;
if ($res_sach && $res_sach->num_rows === 0) {
    $res_goi_y = $conn->query("SELECT s.*, dm.tenDanhMuc FROM sach s LEFT JOIN danhmuc dm ON s.maDanhMuc = dm.maDanhMuc WHERE s.trangThai = 1 ORDER BY s.maSach DESC LIMIT 4");
}
?>

<style>
    .book-card { 
        transition: transform 0.2s ease, box-shadow 0.2s ease; 
    }
    .book-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 8px 20px rgba(15,76,69,0.12);
    }
    .filter-group {
        margin-bottom: 20px;
    }
    .filter-label {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 8px;
        display: block;
        color: #14201C;
    }
    .filter-input, .filter-select {
        width: 100%;
        height: 42px;
        box-sizing: border-box;
        border: 1.5px solid #DCE3DE;
        border-radius: 8px;
        padding: 0 12px;
        background: #F7F8F5;
        color: #14201C;
        font-size: 14px;
    }
    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: #0F4C45;
    }

    /* CSS Phân trang */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 36px;
    }
    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 14px;
        box-sizing: border-box;
        border-radius: 8px;
        border: 1.5px solid #DCE3DE;
        background: #FFFFFF;
        color: #14201C;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .page-btn:hover {
        background: #F7F8F5;
        border-color: #0F4C45;
        color: #0F4C45;
    }
    .page-btn.active {
        background: #0F4C45;
        color: #FFFFFF;
        border-color: #0F4C45;
        cursor: default;
    }
    .page-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
        background: #F7F8F5;
    }
</style>

<div style="max-width: 1200px; margin: 0 auto; padding: 12px 0 48px; box-sizing: border-box">
    
    <nav style="font-size: 13px; color: #55635D; margin-bottom: 24px">
        <a href="index.php" style="text-decoration: none;">Trang chủ</a> / 
        <span style="color: #14201C; font-weight: 600;">Danh sách sách</span>
    </nav>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px">
        <h1 style="margin: 0; font-size: 32px; font-weight: 800; letter-spacing: -0.02em">Tất Cả Sách</h1>
        <div style="font-size: 14px; color: #55635D">Hiển thị <strong><?php echo $tong_so_sach; ?></strong> sản phẩm</div>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 32px; align-items: flex-start">
        
        <aside style="flex: 1 1 260px; max-width: 300px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 24px; box-sizing: border-box">
            <h2 style="margin: 0 0 20px; font-size: 18px; font-weight: 800; border-bottom: 1px solid #DCE3DE; padding-bottom: 12px">Bộ Lọc Sách</h2>
            
            <form action="danh-sach-sach.php" method="GET" style="display: flex; flex-direction: column;">
                
                <div class="filter-group">
                    <label class="filter-label">Từ khóa tìm kiếm</label>
                    <input type="text" name="tu_khoa" class="filter-input" placeholder="Tên sách..." value="<?php echo htmlspecialchars($tu_khoa); ?>">
                </div>

                <div class="filter-group">
                    <label class="filter-label">Danh mục sách</label>
                    <select name="maDanhMuc" class="filter-select">
                        <option value="0">Tất cả danh mục</option>
                        <?php if ($res_dm && $res_dm->num_rows > 0): ?>
                            <?php while ($dm = $res_dm->fetch_assoc()): ?>
                                <option value="<?php echo $dm['maDanhMuc']; ?>" <?php echo ($maDanhMuc == $dm['maDanhMuc']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dm['tenDanhMuc']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Nhà xuất bản</label>
                    <select name="maNXB" class="filter-select">
                        <option value="0">Tất cả NXB</option>
                        <?php if ($res_nxb && $res_nxb->num_rows > 0): ?>
                            <?php while ($nxb = $res_nxb->fetch_assoc()): ?>
                                <option value="<?php echo $nxb['maNXB']; ?>" <?php echo ($maNXB == $nxb['maNXB']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nxb['tenNXB']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Mức giá</label>
                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 14px; color: #34443E">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="mucGia" value="" <?php echo empty($mucGia) ? 'checked' : ''; ?>> Tất cả mức giá
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="mucGia" value="duoi_100" <?php echo ($mucGia == 'duoi_100') ? 'checked' : ''; ?>> Dưới 100.000đ
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="mucGia" value="100_200" <?php echo ($mucGia == '100_200') ? 'checked' : ''; ?>> 100.000đ - 200.000đ
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" name="mucGia" value="tren_200" <?php echo ($mucGia == 'tren_200') ? 'checked' : ''; ?>> Trên 200.000đ
                        </label>
                    </div>
                </div>

                <button type="submit" style="margin-top: 8px; height: 46px; background: #0F4C45; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 700; cursor: pointer">Áp Dụng Lọc</button>
            </form>
        </aside>

        <section style="flex: 3 1 600px; min-width: 0">
            
            <?php if ($res_sach && $res_sach->num_rows > 0): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 24px">
                    <?php while ($sach = $res_sach->fetch_assoc()): ?>
                        <div class="book-card" style="display: flex; flex-direction: column; gap: 10px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px; box-sizing: border-box">
                            <a href="chi-tiet-sach.php?id=<?php echo $sach['maSach']; ?>" style="display: block; position: relative">
                                <div style="aspect-ratio: 3/4; border-radius: 6px; overflow: hidden; background: #F7F8F5; box-shadow: 0 4px 12px rgba(20,32,28,0.08)">
                                    <img src="tai-nguyen/hinh-anh/<?php echo !empty($sach['hinhAnh']) ? htmlspecialchars($sach['hinhAnh']) : 'no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($sach['tenSach']); ?>" style="width: 100%; height: 100%; object-fit: cover">
                                </div>
                            </a>
                            <div style="font-weight: 700; line-height: 1.3; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical">
                                <a href="chi-tiet-sach.php?id=<?php echo $sach['maSach']; ?>"><?php echo htmlspecialchars($sach['tenSach']); ?></a>
                            </div>
                            <div style="font-size: 13px; color: #55635D">Danh mục: <?php echo htmlspecialchars($sach['tenDanhMuc'] ?? 'Khác'); ?></div>
                            <div style="display: flex; align-items: baseline; gap: 8px; margin-top: auto">
                                <span style="font-weight: 800; font-size: 17px; color: #0F4C45"><?php echo number_format($sach['giaBan'], 0, ',', '.'); ?>đ</span>
                            </div>
                            <a href="chi-tiet-sach.php?id=<?php echo $sach['maSach']; ?>" style="min-height: 40px; border: 1.5px solid #0F4C45; background: #FFFFFF; color: #0F4C45; font-weight: 600; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none">Xem chi tiết</a>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- KHỐI HIỂN THỊ PHÂN TRANG -->
                <?php if ($tong_so_trang > 1): ?>
                    <?php $query_params = $_GET; ?>
                    <div class="pagination-wrapper">
                        <!-- Nút Trước -->
                        <?php 
                            $query_params['trang'] = $trang_hien_tai - 1;
                            $url_prev = '?' . http_build_query($query_params);
                        ?>
                        <a href="<?php echo $url_prev; ?>" class="page-btn <?php echo ($trang_hien_tai <= 1) ? 'disabled' : ''; ?>">
                            &laquo; Trước
                        </a>

                        <!-- Danh sách các số trang -->
                        <?php for ($i = 1; $i <= $tong_so_trang; $i++): ?>
                            <?php 
                                $query_params['trang'] = $i;
                                $url_page = '?' . http_build_query($query_params);
                            ?>
                            <a href="<?php echo $url_page; ?>" class="page-btn <?php echo ($i == $trang_hien_tai) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Nút Sau -->
                        <?php 
                            $query_params['trang'] = $trang_hien_tai + 1;
                            $url_next = '?' . http_build_query($query_params);
                        ?>
                        <a href="<?php echo $url_next; ?>" class="page-btn <?php echo ($trang_hien_tai >= $tong_so_trang) ? 'disabled' : ''; ?>">
                            Sau &raquo;
                        </a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Thông báo khi không có kết quả -->
                <div style="background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 48px; text-align: center; margin-bottom: 32px">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#55635D" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 12px"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #14201C">Không tìm thấy cuốn sách nào</h3>
                    <p style="margin: 0 0 20px; font-size: 14px; color: #55635D">Thử điều chỉnh bộ lọc hoặc từ khóa để tìm kết quả khác.</p>
                    <a href="danh-sach-sach.php" style="display: inline-block; padding: 10px 20px; background: #0F4C45; color: #FFFFFF; font-weight: 700; font-size: 14px; border-radius: 6px; text-decoration: none">Xóa bộ lọc</a>
                </div>

                <!-- Khối Gợi ý sách khi trống kết quả -->
                <?php if ($res_goi_y && $res_goi_y->num_rows > 0): ?>
                    <div>
                        <h3 style="margin: 0 0 20px; font-size: 20px; font-weight: 800; color: #14201C">Gợi Ý Sách Dành Cho Bạn</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 24px">
                            <?php while ($sach_goi_y = $res_goi_y->fetch_assoc()): ?>
                                <div class="book-card" style="display: flex; flex-direction: column; gap: 10px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px; box-sizing: border-box">
                                    <a href="chi-tiet-sach.php?id=<?php echo $sach_goi_y['maSach']; ?>" style="display: block; position: relative">
                                        <div style="aspect-ratio: 3/4; border-radius: 6px; overflow: hidden; background: #F7F8F5; box-shadow: 0 4px 12px rgba(20,32,28,0.08)">
                                            <img src="tai-nguyen/hinh-anh/<?php echo !empty($sach_goi_y['hinhAnh']) ? htmlspecialchars($sach_goi_y['hinhAnh']) : 'no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($sach_goi_y['tenSach']); ?>" style="width: 100%; height: 100%; object-fit: cover">
                                        </div>
                                    </a>
                                    <div style="font-weight: 700; line-height: 1.3; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical">
                                        <a href="chi-tiet-sach.php?id=<?php echo $sach_goi_y['maSach']; ?>"><?php echo htmlspecialchars($sach_goi_y['tenSach']); ?></a>
                                    </div>
                                    <div style="font-size: 13px; color: #55635D">Danh mục: <?php echo htmlspecialchars($sach_goi_y['tenDanhMuc'] ?? 'Khác'); ?></div>
                                    <div style="display: flex; align-items: baseline; gap: 8px; margin-top: auto">
                                        <span style="font-weight: 800; font-size: 17px; color: #0F4C45"><?php echo number_format($sach_goi_y['giaBan'], 0, ',', '.'); ?>đ</span>
                                    </div>
                                    <a href="chi-tiet-sach.php?id=<?php echo $sach_goi_y['maSach']; ?>" style="min-height: 40px; border: 1.5px solid #0F4C45; background: #FFFFFF; color: #0F4C45; font-weight: 600; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none">Xem chi tiết</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </section>

    </div>

</div>

<?php
require_once 'dung-chung/cuoi-trang.php';
?>