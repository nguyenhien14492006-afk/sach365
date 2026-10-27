<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$danh_sach_canh_bao = array();

// Xử lý AJAX cập nhật số lượng
if (isset($_POST['action']) && $_POST['action'] === 'cap_nhat_sl') {
    require_once 'dung-chung/khoi-tao.php';
    $maSach = (int)($_POST['maSach'] ?? 0);
    $soLuong = max(1, (int)($_POST['soLuong'] ?? 1));
    
    if ($maSach > 0 && isset($_SESSION['gio_hang'][$maSach])) {
        $stmt = $conn->prepare("SELECT soLuongTon AS tonKho FROM sach WHERE maSach = ?");
        $stmt->bind_param("i", $maSach);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $tonKho = (int)$row['tonKho'];
            if ($soLuong > $tonKho) {
                $soLuong = $tonKho;
            }
        }
        $stmt->close();
        
        $_SESSION['gio_hang'][$maSach]['so_luong'] = $soLuong;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'soLuong' => $soLuong]);
    exit();
}

// Xử lý thêm sản phẩm vào giỏ từ trang chi tiết
if (isset($_POST['btn_them_gio_hang'])) {
    require_once 'dung-chung/khoi-tao.php';
    $maSach = (int)$_POST['maSach'];
    $soLuong = isset($_POST['soLuong']) ? max(1, (int)$_POST['soLuong']) : 1;

    if (!isset($_SESSION['gio_hang'])) {
        $_SESSION['gio_hang'] = array();
    }

    $stmt = $conn->prepare("SELECT soLuongTon AS tonKho FROM sach WHERE maSach = ?");
    $stmt->bind_param("i", $maSach);
    $stmt->execute();
    $res = $stmt->get_result();
    $tonKho = 999;
    if ($row = $res->fetch_assoc()) {
        $tonKho = (int)$row['tonKho'];
    }
    $stmt->close();

    $soLuongHienTai = isset($_SESSION['gio_hang'][$maSach]) ? $_SESSION['gio_hang'][$maSach]['so_luong'] : 0;
    $soLuongMoi = $soLuongHienTai + $soLuong;

    if ($soLuongMoi > $tonKho) {
        $soLuongMoi = $tonKho;
    }

    if ($soLuongMoi > 0) {
        $_SESSION['gio_hang'][$maSach] = array(
            'maSach' => $maSach,
            'so_luong' => $soLuongMoi
        );
    }
    header("Location: gio-hang.php");
    exit();
}

// Xử lý xóa sản phẩm
if (isset($_GET['action']) && $_GET['action'] == 'xoa') {
    $maSach = (int)$_GET['id'];
    if (isset($_SESSION['gio_hang'][$maSach])) {
        unset($_SESSION['gio_hang'][$maSach]);
    }
    header("Location: gio-hang.php");
    exit();
}

require_once 'dung-chung/dau-trang.php';

$danh_sach_sach = array();
$tong_san_pham = 0;

if (!empty($_SESSION['gio_hang'])) {
    $ds_ids = implode(',', array_map('intval', array_keys($_SESSION['gio_hang'])));
    if (!empty($ds_ids)) {
        $sql = "SELECT *, soLuongTon AS tonKho FROM sach WHERE maSach IN ($ds_ids)";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $maSach = (int)$row['maSach'];
                if (isset($_SESSION['gio_hang'][$maSach])) {
                    $soLuongGio = (int)$_SESSION['gio_hang'][$maSach]['so_luong'];
                    $tonKho = (int)$row['tonKho'];

                    if ($soLuongGio > $tonKho) {
                        if ($tonKho > 0) {
                            $_SESSION['gio_hang'][$maSach]['so_luong'] = $tonKho;
                            $soLuongGio = $tonKho;
                            $danh_sach_canh_bao[] = "Cuốn sách <strong>" . htmlspecialchars($row['tenSach']) . "</strong> chỉ còn " . $tonKho . " sản phẩm trong kho. Đã tự động điều chỉnh số lượng!";
                        } else {
                            unset($_SESSION['gio_hang'][$maSach]);
                            $danh_sach_canh_bao[] = "Cuốn sách <strong>" . htmlspecialchars($row['tenSach']) . "</strong> vừa hết hàng nên đã được xóa khỏi giỏ.";
                            continue;
                        }
                    }

                    $row['so_luong_gio'] = $soLuongGio;
                    $danh_sach_sach[] = $row;
                    $tong_san_pham += $row['so_luong_gio'];
                }
            }
        }
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body { 
        background: #F7F8F5 !important; 
        color: #14201C; 
        font-family: 'Be Vietnam Pro', system-ui, sans-serif !important; 
    }
    .cart-item {
        display: flex; 
        flex-wrap: wrap; 
        align-items: center; 
        gap: 16px 20px; 
        padding: 20px 0; 
        border-bottom: 1px solid #DCE3DE;
    }
    .cart-item:last-child {
        border-bottom: none;
    }
</style>

<main style="max-width: 1200px; margin: 0 auto; padding: 10px 0 64px; box-sizing: border-box">
    
    <?php if (!empty($danh_sach_canh_bao)): ?>
        <div style="background: #FDF2F2; border: 1px solid #F8B4B4; color: #B93A12; padding: 14px 18px; border-radius: 10px; font-size: 14px; margin-bottom: 20px">
            <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Thông báo tồn kho:
            </div>
            <ul style="margin: 0; padding-left: 20px">
                <?php foreach ($danh_sach_canh_bao as $cb): ?>
                    <li><?php echo $cb; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1 style="margin: 0; font-size: 30px; letter-spacing: -0.03em; font-weight: 800">
            Giỏ hàng của bạn <span id="item-count-title" style="font-size: 18px; font-weight: 500; color: #55635D">(<?php echo $tong_san_pham; ?> sản phẩm)</span>
        </h1>
        <a href="index.php" style="color: #0F4C45; font-weight: 600; text-decoration: none;">← Tiếp tục mua sắm</a>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 32px; align-items: flex-start">
      
        <section style="flex: 2 1 480px; min-width: 0; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 8px 24px; box-sizing: border-box">
            <div id="cart-list" style="<?php echo empty($danh_sach_sach) ? 'display: none;' : ''; ?>">
                <?php foreach ($danh_sach_sach as $sach): 
                    $giaBan = (float)$sach['giaBan'];
                    $soLuong = (int)$sach['so_luong_gio'];
                    $thanhTien = $giaBan * $soLuong;
                    $tonKho = (int)$sach['tonKho'];
                ?>
                    <div class="cart-item" data-price="<?php echo $giaBan; ?>" data-stock="<?php echo $tonKho; ?>" id="item-<?php echo $sach['maSach']; ?>">
                        <div style="width:64px; aspect-ratio:3/4; border-radius:4px; overflow:hidden; flex-shrink:0; background:#eee">
                            <img src="tai-nguyen/hinh-anh/<?php echo htmlspecialchars($sach['hinhAnh']); ?>" alt="<?php echo htmlspecialchars($sach['tenSach']); ?>" style="width:100%; height:100%; object-fit:cover" onerror="this.src='https://via.placeholder.com/64x85?text=Sach365'">
                        </div>

                        <div style="flex: 1 1 200px; min-width: 0">
                            <div style="font-weight: 700; font-size: 16px; line-height: 1.3"><?php echo htmlspecialchars($sach['tenSach']); ?></div>
                            <div style="font-size: 13px; color: #55635D; margin-top: 2px;">
                                <?php echo number_format($giaBan, 0, ',', '.'); ?>đ 
                                <span style="color: #0F4C45; font-weight: 600;">(Kho: <?php echo $tonKho; ?>)</span>
                            </div>
                            <a href="gio-hang.php?action=xoa&id=<?php echo $sach['maSach']; ?>" onclick="return confirm('Bạn có chắc muốn xóa sách này khỏi giỏ hàng?');" style="display: inline-block; margin-top: 6px; color: #B93A12; font-size: 13px; font-weight: 600; text-decoration: none;">Xóa</a>
                        </div>

                        <div style="display: flex; align-items: center; border: 1.5px solid #DCE3DE; border-radius: 8px">
                            <button type="button" onclick="updateQty('item-<?php echo $sach['maSach']; ?>', -1, <?php echo $sach['maSach']; ?>, <?php echo $tonKho; ?>)" style="width: 40px; height: 40px; border: none; background: transparent; font-size: 18px; cursor: pointer; color: #14201C">−</button>
                            <span class="qty-val" style="min-width: 32px; text-align: center; font-weight: 700"><?php echo $soLuong; ?></span>
                            <button type="button" onclick="updateQty('item-<?php echo $sach['maSach']; ?>', 1, <?php echo $sach['maSach']; ?>, <?php echo $tonKho; ?>)" style="width: 40px; height: 40px; border: none; background: transparent; font-size: 18px; cursor: pointer; color: #14201C">+</button>
                        </div>

                        <div class="line-total" style="min-width: 110px; text-align: right; font-weight: 800; font-size: 17px; color: #0F4C45"><?php echo number_format($thanhTien, 0, ',', '.'); ?>đ</div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="empty-msg" style="<?php echo empty($danh_sach_sach) ? 'display: block;' : 'display: none;'; ?> padding: 48px 0; text-align: center; color: #55635D">
                Giỏ hàng của bạn đang trống.
                <div style="margin-top: 16px"><a href="index.php" style="color: #0F4C45; font-weight: 600; text-decoration: none;">Khám phá sách mới ngay →</a></div>
            </div>
        </section>

        <aside style="flex: 1 1 320px; min-width: 0; background: #FFFFFF; border: 1px solid #DCE3DE; border-radius: 12px; padding: 28px; box-sizing: border-box; display: flex; flex-direction: column; gap: 16px">
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.02em">Tóm tắt đơn hàng</h2>

            <div style="display: flex; justify-content: space-between"><span style="color: #55635D">Tạm tính</span><span id="subtotal" style="font-weight: 600">0đ</span></div>
            <div style="display: flex; justify-content: space-between"><span style="color: #55635D">Phí vận chuyển</span><span id="shipping" style="font-weight: 600">Miễn phí</span></div>
            
            <div id="ship-note" style="font-size: 13px; color: #0F4C45; background: #E6F0EC; border-radius: 8px; padding: 10px 12px">
                Đơn hàng của bạn được miễn phí vận chuyển.
            </div>

            <div style="display: flex; justify-content: space-between; align-items: baseline; padding-top: 16px; border-top: 1px solid #DCE3DE">
                <span style="font-weight: 700">Tổng cộng</span>
                <span id="total" style="font-size: 26px; font-weight: 800; color: #0F4C45">0đ</span>
            </div>

            <button type="button" onclick="goCheckout()" style="min-height: 54px; border: none; background: #0F4C45; color: #FFFFFF; font-weight: 700; font-size: 16px; border-radius: 8px; cursor: pointer">Tiến hành thanh toán</button>
            <div style="font-size: 12px; color: #55635D; text-align: center">Thanh toán an toàn · Thẻ, ví điện tử, COD</div>
        </aside>

    </div>
</main>

<script>
    const freeShipLimit = 250000;

    function formatVND(n) {
        return n.toLocaleString('vi-VN') + 'đ';
    }

    function recalculate() {
        const items = document.querySelectorAll('.cart-item');
        let subtotal = 0;
        let totalItems = 0;

        items.forEach(item => {
            const price = parseInt(item.getAttribute('data-price'));
            const qty = parseInt(item.querySelector('.qty-val').innerText);
            const lineTotal = price * qty;
            
            item.querySelector('.line-total').innerText = formatVND(lineTotal);
            subtotal += lineTotal;
            totalItems += qty;
        });

        document.getElementById('item-count-title').innerText = `(${totalItems} sản phẩm)`;

        let shipping = 0;
        const shipNote = document.getElementById('ship-note');
        
        if (items.length === 0 || subtotal === 0) {
            shipping = 0;
            document.getElementById('shipping').innerText = '0đ';
            shipNote.innerText = 'Giỏ hàng đang trống.';
        } else if (subtotal >= freeShipLimit) {
            shipping = 0;
            document.getElementById('shipping').innerText = 'Miễn phí';
            shipNote.innerText = 'Đơn hàng của bạn được miễn phí vận chuyển.';
        } else {
            shipping = 25000;
            document.getElementById('shipping').innerText = formatVND(shipping);
            shipNote.innerText = `Mua thêm ${formatVND(freeShipLimit - subtotal)} để được miễn phí vận chuyển.`;
        }

        document.getElementById('subtotal').innerText = formatVND(subtotal);
        document.getElementById('total').innerText = formatVND(subtotal + shipping);

        if (items.length === 0) {
            document.getElementById('cart-list').style.display = 'none';
            document.getElementById('empty-msg').style.display = 'block';
        }
    }

    function updateQty(id, delta, maSach, maxStock) {
        const item = document.getElementById(id);
        const qtySpan = item.querySelector('.qty-val');
        let currentQty = parseInt(qtySpan.innerText);
        
        let newQty = currentQty + delta;

        if (maxStock !== undefined && newQty > maxStock) {
            alert('Rất tiếc, sản phẩm này chỉ còn ' + maxStock + ' cuốn trong kho!');
            newQty = maxStock;
        }

        newQty = Math.max(1, newQty);
        qtySpan.innerText = newQty;
        
        recalculate();

        const formData = new FormData();
        formData.append('action', 'cap_nhat_sl');
        formData.append('maSach', maSach);
        formData.append('soLuong', newQty);

        fetch('gio-hang.php', {
            method: 'POST',
            body: formData
        });
    }

    function goCheckout() {
        const items = document.querySelectorAll('.cart-item');
        if (items.length === 0) {
            alert('Giỏ hàng của bạn đang trống!');
        } else {
            window.location.href = 'dat-hang.php';
        }
    }

    window.onload = recalculate;
</script>

<?php 
require_once 'dung-chung/cuoi-trang.php'; 
ob_end_flush();
?>