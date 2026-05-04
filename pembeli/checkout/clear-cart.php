<?php
session_start();
require_once "../../koneksi.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit();
}

$user_id = $_SESSION["user_id"];

// Hapus keranjang di session
unset($_SESSION["keranjang"]);


// Hapus keranjang di database user_cart
$stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// Hapus keranjang di database keranjang (penting agar tidak muncul di item_pesanan penjual)
$stmt2 = $conn->prepare("DELETE FROM keranjang WHERE pembeli_id = ?");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$stmt2->close();

echo json_encode(["success" => true]);
