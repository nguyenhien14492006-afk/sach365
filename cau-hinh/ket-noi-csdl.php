<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sach365";
// tao ket noi
$conn = new mysqli($servername, $username, $password, $dbname);

// kiem tra ket noi
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// thiet la tieng viet
mysqli_set_charset($conn, "utf8mb4");
?>