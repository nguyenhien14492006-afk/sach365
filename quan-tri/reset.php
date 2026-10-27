<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
echo "Da xoa session thanh cong! Click vao link duoi de dang nhap:<br>";
echo '<a href="dang-nhap.php">Trang dang nhap</a>';
?>