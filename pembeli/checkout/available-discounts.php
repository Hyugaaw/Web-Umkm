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

$available = [];
$cek_kol = $conn->query("SHOW COLUMNS FROM pengguna_diskon LIKE 'tanggal_digunakan'");
$kolom_ada = ($cek_kol && $cek_kol->num_rows > 0);

// ambil diskon milik pengguna
$stmt_du = $conn->prepare("SELECT pd.id AS pengguna_diskon_id, d.* FROM pengguna_diskon pd JOIN diskon d ON pd.diskon_id = d.id WHERE pd.pengguna_id = ? " . ($kolom_ada ? "AND pd.tanggal_digunakan IS NULL " : "") . "AND d.status = 'aktif' AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai <= CURDATE()) AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai >= CURDATE())");
$stmt_du->bind_param('i', $user_id);
$stmt_du->execute();
$res_du = $stmt_du->get_result();
while ($r = $res_du->fetch_assoc()) {
  $available[] = $r;
}
$stmt_du->close();

// ambil diskon global aktif dari table diskon
$stmt_gd = $conn->prepare("SELECT * FROM diskon d WHERE d.status = 'aktif' AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai <= CURDATE()) AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai >= CURDATE()) ORDER BY d.dibuat_pada DESC");
$stmt_gd->execute();
$res_gd = $stmt_gd->get_result();
while ($gd = $res_gd->fetch_assoc()) {
  // avoid duplicates if same kode exists
  $found = false;
  foreach ($available as $ex) { if (isset($ex['kode_diskon']) && $ex['kode_diskon'] == $gd['kode_diskon']) { $found = true; break; } }
  if (!$found) $available[] = $gd;
}
$stmt_gd->close();

echo json_encode(['success' => true, 'discounts' => $available]);
