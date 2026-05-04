<?php
session_start();
require_once "../../koneksi.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}
$user_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input['kode'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kode kosong']);
    exit();
}
$kode = trim($input['kode']);

// find discount
$stmt = $conn->prepare("SELECT * FROM diskon WHERE kode_diskon = ? LIMIT 1");
$stmt->bind_param('s', $kode);
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Voucher tidak ditemukan']);
    exit();
}

$valid = true;
$reason = '';
$today = date('Y-m-d');
if ($found['status'] !== 'aktif') { $valid = false; $reason = 'Voucher tidak aktif'; }
if (!empty($found['tanggal_mulai']) && $found['tanggal_mulai'] > $today) { $valid = false; $reason = 'Voucher belum berlaku'; }
if (!empty($found['tanggal_selesai']) && $found['tanggal_selesai'] < $today) { $valid = false; $reason = 'Voucher sudah kadaluarsa'; }

// compute cart total to validate min/max
$keranjang = $_SESSION['keranjang'] ?? [];
$total = 0;
foreach ($keranjang as $it) { $total += ($it['harga'] ?? 0) * ($it['jumlah'] ?? 0); }
if (!empty($found['harga_minimal']) && $total < (float)$found['harga_minimal']) { $valid = false; $reason = 'Belum memenuhi syarat harga minimal'; }
if (!empty($found['harga_maksimal']) && $total > (float)$found['harga_maksimal']) { $valid = false; $reason = 'Voucher tidak berlaku untuk total belanja ini'; }

// khusus pengguna baru
if ($valid && !empty($found['khusus_pengguna_baru'])) {
    $stmtck = $conn->prepare('SELECT COUNT(*) AS jml FROM pesanan WHERE pembeli_id = ?');
    $stmtck->bind_param('i', $user_id);
    $stmtck->execute();
    $jml = $stmtck->get_result()->fetch_assoc()['jml'];
    $stmtck->close();
    if ($jml > 0) { $valid = false; $reason = 'Voucher ini hanya untuk pengguna baru'; }
}

if ($valid) {
    // Jika user memiliki mapping pengguna_diskon untuk kode ini, sertakan id mapping
    $stmt_pd = $conn->prepare("SELECT id FROM pengguna_diskon WHERE pengguna_id = ? AND diskon_id = ? AND (tanggal_digunakan IS NULL OR tanggal_digunakan = '') LIMIT 1");
    if ($stmt_pd) {
        $stmt_pd->bind_param('ii', $user_id, $found['id']);
        $stmt_pd->execute();
        $res_pd = $stmt_pd->get_result();
        if ($rpd = $res_pd->fetch_assoc()) {
            $found['pengguna_diskon_id'] = (int)$rpd['id'];
        }
        $stmt_pd->close();
    }

    $_SESSION['applied_discount'] = $found;
    // compute discount amount
    $discount_amount = 0;
    if (!empty($found['persentase'])) {
        $discount_amount = $total * ((float)$found['persentase'] / 100.0);
    } elseif (!empty($found['potongan_tetap'])) {
        $discount_amount = (float)$found['potongan_tetap'];
    }
    if ($discount_amount > $total) $discount_amount = $total;

    echo json_encode(['success' => true, 'discount' => $found, 'amount' => round($discount_amount)]);
} else {
    echo json_encode(['success' => false, 'message' => $reason]);
}
