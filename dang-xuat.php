<?php
session_start();
session_unset();
session_destroy();

echo "<script>
    alert('Bạn đã đăng xuất thành công!');
    window.location.href = 'index.php';
</script>";
exit();
?>