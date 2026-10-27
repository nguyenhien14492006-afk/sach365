<?php
if (!isset($sach) || !is_array($sach)) return;
?>
<div class="book-card" style="display: flex; flex-direction: column; gap: 10px; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 10px; padding: 16px; box-sizing: border-box">
    <a href="chi-tiet-sach.php?id=<?php echo (int)$sach['maSach']; ?>" style="display: block; position: relative">
        <div style="aspect-ratio: 3/4; border-radius: 6px; overflow: hidden; background: #F7F8F5; box-shadow: 0 4px 12px rgba(20,32,28,0.08)">
            <img src="tai-nguyen/hinh-anh/<?php echo !empty($sach['hinhAnh']) ? htmlspecialchars($sach['hinhAnh']) : 'no-image.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($sach['tenSach']); ?>" 
                 style="width: 100%; height: 100%; object-fit: cover"
                 onerror="this.src='https://via.placeholder.com/200x266?text=Sach365'">
        </div>
    </a>
    <div style="font-weight: 700; line-height: 1.3; height: 38px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical">
        <a href="chi-tiet-sach.php?id=<?php echo (int)$sach['maSach']; ?>"><?php echo htmlspecialchars($sach['tenSach']); ?></a>
    </div>
    <div style="font-size: 13px; color: #55635D">Danh mục: <?php echo htmlspecialchars($sach['tenDanhMuc'] ?? 'Khác'); ?></div>
    <div style="display: flex; align-items: baseline; gap: 8px; margin-top: auto">
        <span style="font-weight: 800; font-size: 17px; color: #0F4C45"><?php echo number_format($sach['giaBan'], 0, ',', '.'); ?>đ</span>
    </div>
    
    <form action="gio-hang.php" method="POST" style="margin: 0;">
        <input type="hidden" name="maSach" value="<?php echo (int)$sach['maSach']; ?>">
        <input type="hidden" name="soLuong" value="1">
        <button type="submit" name="btn_them_gio_hang" style="width: 100%; min-height: 40px; border: 1.5px solid #0F4C45; background: #FFFFFF; color: #0F4C45; font-weight: 600; border-radius: 6px; cursor: pointer">Thêm vào giỏ</button>
    </form>
</div>