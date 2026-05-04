<?php
// ../api/item-pesanan.php
header('Content-Type: application/json; charset=utf-8');
require_once "../../koneksi.php"; // pastikan path ini sesuai

// Ambil input JSON
$raw = file_get_contents("php://input");
if (!$raw) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Tidak ada data dikirim"
    ]);
    exit;
}

$data = json_decode($raw, true);

// Validasi data
if (!isset($data['order_id']) || empty($data['items'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Data order_id atau items tidak ditemukan"
    ]);
    exit;
}

$order_id = $data['order_id'];
$items = $data['items'];

// Ambil id pesanan dari tabel pesanan berdasarkan kode_pesanan (atau order_id jika diberikan)
$query = $conn->prepare("SELECT id FROM pesanan WHERE kode_pesanan = ? OR id = ? LIMIT 1");
$lookupById = is_numeric($order_id) ? intval($order_id) : 0;
$query->bind_param("si", $order_id, $lookupById);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Pesanan dengan order_id '$order_id' tidak ditemukan"
    ]);
    exit;
}

$row = $result->fetch_assoc();
$pesanan_id = $row['id'];

// Insert tiap item ke tabel item_pesanan
$stmt = $conn->prepare("INSERT INTO item_pesanan (pesanan_id, produk_id, jumlah, harga_saat_pembelian) VALUES (?, ?, ?, ?)");

foreach ($items as $item) {
    // Pastikan semua field ada
    if (!isset($item['produk_id'], $item['jumlah'], $item['harga_saat_pembelian'])) continue;

    $produk_id = intval($item['produk_id']);
    $jumlah = intval($item['jumlah']);
    $harga = floatval($item['harga_saat_pembelian']);

    $stmt->bind_param("iiid", $pesanan_id, $produk_id, $jumlah, $harga);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "message" => "Item pesanan berhasil disimpan",
    "pesanan_id" => $pesanan_id
]);
