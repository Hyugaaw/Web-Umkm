<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}
require_once "../koneksi.php";

// Fungsi membuat slug dari nama produk (harus sama dengan di index2.php)
function buat_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text); // Hanya huruf, angka, spasi, strip
    $text = preg_replace('/[\s-]+/', '-', $text); // Ganti spasi/strip berulang jadi satu strip
    $text = trim($text, '-');
    return $text;
}

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') {
    http_response_code(404);
    echo "Produk tidak ditemukan.";
    exit();
}

// Cari produk aktif dan stok > 0 yang slug-nya cocok
$stmt = $conn->prepare("SELECT * FROM produk WHERE status = 'aktif' AND stok > 0");
$stmt->execute();
$result = $stmt->get_result();
$produk = null;
$nama_produk = '';
while ($row = $result->fetch_assoc()) {
    if (buat_slug($row['nama']) === $slug) {
        $produk = $row;
        $nama_produk = $row['nama'];
        break;
    }
}
$stmt->close();

if (!$produk) {
    http_response_code(404);
    echo "Produk tidak ditemukan atau tidak aktif/stok habis.";
    exit();
}

$file = __DIR__ . "/tentang-produk2/{$slug}.php";
if (!file_exists($file)) {
    http_response_code(404);
    echo "Halaman detail produk belum tersedia.";
    exit();
}

include $file;
