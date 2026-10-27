<?php
require_once __DIR__ . '/../dung-chung/khoi-tao.php';

unset($_SESSION['admin'], $_SESSION['maAdmin'], $_SESSION['maQL'], $_SESSION['hoTenQL']);

chuyen_huong('dang-nhap.php', 'Bạn đã đăng xuất khỏi hệ thống quản trị thành công.');