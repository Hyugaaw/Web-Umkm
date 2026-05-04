<?php
session_start();
require_once dirname(__DIR__, 2) . '/koneksi.php';

// --- ENDPOINT AJAX JUMLAH KERANJANG ---
if (isset($_GET['ajax']) && $_GET['ajax'] === 'count') {
  $user_id = $_SESSION['user_id'] ?? 0;
  $total_item = 0;
  if ($user_id) {
    $stmt = $conn->prepare('SELECT SUM(jumlah) as total FROM user_cart WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      $total_item = (int)($row['total'] ?? 0);
    }
    $stmt->close();
  }
  echo json_encode(['count' => $total_item]);
  exit;
}

if (!isset($_SESSION["user_id"])) {
  header("Location: auth/login.php");
  exit();
}

$user_id = $_SESSION["user_id"];

// === TAMBAHAN: Simpan & ambil keranjang dari database ===

// Buat tabel jika belum ada
$conn->query("
  CREATE TABLE IF NOT EXISTS user_cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    produk_id INT DEFAULT NULL,
    nama_produk VARCHAR(255),
    gambar VARCHAR(255),
    jumlah INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )
");

// Ambil keranjang LANGSUNG dari database user_cart
$keranjang = [];
$stmt = $conn->prepare("SELECT produk_id, nama_produk, gambar, jumlah FROM user_cart WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$hasil = $stmt->get_result();
if ($hasil && $hasil->num_rows > 0) {
  while ($row = $hasil->fetch_assoc()) {
    $harga_produk = 0;
    $penjual_id = null;
    $stok_produk = 0;
    $produk_id = isset($row['produk_id']) ? (int)$row['produk_id'] : 0;
    if ($produk_id > 0) {
      $stmt_harga = $conn->prepare("SELECT harga, penjual_id, stok FROM produk WHERE id = ? LIMIT 1");
      $stmt_harga->bind_param("i", $produk_id);
    } else {
      $stmt_harga = $conn->prepare("SELECT harga, penjual_id, stok FROM produk WHERE nama = ? LIMIT 1");
      $stmt_harga->bind_param("s", $row['nama_produk']);
    }
    $stmt_harga->execute();
    $res_harga = $stmt_harga->get_result();
    if ($row_harga = $res_harga->fetch_assoc()) {
      $harga_produk = (int)$row_harga['harga'];
      $penjual_id = $row_harga['penjual_id'] ?? null;
      $stok_produk = (int)$row_harga['stok'];
    }
    $stmt_harga->close();
    $keranjang[] = [
      'produk_id' => $produk_id,
      'nama_produk' => $row['nama_produk'],
      'gambar' => $row['gambar'],
      'jumlah' => (int)$row['jumlah'],
      'harga' => $harga_produk,
      'penjual_id' => $penjual_id,
      'stok' => $stok_produk
    ];
  }
  if (!empty($keranjang)) {
    $_SESSION['keranjang'] = $keranjang;
  } else {
    unset($_SESSION['keranjang']);
  }
} else {
  unset($_SESSION['keranjang']);
  $keranjang = [];
}
$stmt->close();

// Ambil diskon aktif milik user untuk ditampilkan di keranjang (opsional pilih)
$diskon_user = [];
$cek_kol = $conn->query("SHOW COLUMNS FROM pengguna_diskon LIKE 'tanggal_digunakan'");
$kolom_ada = ($cek_kol && $cek_kol->num_rows > 0);
$stmt_du = $conn->prepare("SELECT pd.id AS pengguna_diskon_id, d.* FROM pengguna_diskon pd JOIN diskon d ON pd.diskon_id = d.id WHERE pd.pengguna_id = ? " . ($kolom_ada ? "AND pd.tanggal_digunakan IS NULL " : "") . "AND d.status = 'aktif' AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai = '0000-00-00' OR d.tanggal_mulai <= CURDATE()) AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai = '0000-00-00' OR d.tanggal_selesai >= CURDATE())");
$stmt_du->bind_param('i', $user_id);
$stmt_du->execute();
$res_du = $stmt_du->get_result();
while ($r = $res_du->fetch_assoc()) {
  $diskon_user[] = $r;
}
$stmt_du->close();

// ==== Proses penerapan kode_diskon dari query string ====
$diskon_pesan = '';
if (!empty($_GET['kode_diskon'])) {
  $kode_masuk = trim($_GET['kode_diskon']);

  // Cari diskon berdasarkan kode
  $stmt_k = $conn->prepare("SELECT * FROM diskon WHERE kode_diskon = ? LIMIT 1");
  $stmt_k->bind_param('s', $kode_masuk);
  $stmt_k->execute();
  $res_k = $stmt_k->get_result();
  if ($d = $res_k->fetch_assoc()) {
    $valid = true;
    // cek status dan tanggal (gunakan helper untuk menangani '0000-00-00' dan kosong)
    if ($d['status'] !== 'aktif') {
      $valid = false;
      $diskon_pesan = 'Kode diskon tidak aktif.';
    }
    $today = date('Y-m-d');
    // treat '0000-00-00' or empty as no restriction
    $mulai = (!empty($d['tanggal_mulai']) && $d['tanggal_mulai'] !== '0000-00-00') ? $d['tanggal_mulai'] : null;
    $selesai = (!empty($d['tanggal_selesai']) && $d['tanggal_selesai'] !== '0000-00-00') ? $d['tanggal_selesai'] : null;
    if ($mulai !== null && $mulai > $today) {
      $valid = false;
      $diskon_pesan = 'Kode diskon belum berlaku.';
    }
    if ($selesai !== null && $selesai < $today) {
      $valid = false;
      $diskon_pesan = 'Kode diskon sudah kadaluarsa.';
    }

    // cek syarat harga min/max terhadap total keranjang
    $subtotal_all = $total ?? 0; // nanti $total dihitung setelah loop, jadi guard
    // hitung subtotal sekarang (fallback jika $total belum tersedia)
    if (!isset($subtotal_all) || $subtotal_all == 0) {
      $subtotal_all = 0;
      foreach ($keranjang as $it) {
        $subtotal_all += ($it['harga'] * $it['jumlah']);
      }
    }
    if (!empty($d['harga_minimal']) && $subtotal_all < (float)$d['harga_minimal']) {
      $valid = false;
      $diskon_pesan = 'Belum memenuhi syarat harga minimal untuk menggunakan voucher ini.';
    }
    if (!empty($d['harga_maksimal']) && $subtotal_all > (float)$d['harga_maksimal']) {
      $valid = false;
      $diskon_pesan = 'Voucher ini tidak berlaku untuk total belanja sebesar ini.';
    }

    // cek apakah diskon ini harus diberikan khusus (khusus_pengguna_baru) atau syarat lain
    if ($d['khusus_pengguna_baru']) {
      // sederhana: anggap pengguna baru adalah yang belum punya pesanan
      $stmt_ck = $conn->prepare('SELECT COUNT(*) AS jml FROM pesanan WHERE pembeli_id = ?');
      $stmt_ck->bind_param('i', $user_id);
      $stmt_ck->execute();
      $jml = $stmt_ck->get_result()->fetch_assoc()['jml'];
      $stmt_ck->close();
      if ($jml > 0) {
        $valid = false;
        $diskon_pesan = 'Voucher ini hanya untuk pengguna baru.';
      }
    }

    // jika valid, simpan ke session (akan digunakan saat checkout)
    if ($valid) {
      // Tandai penggunaan hanya pada level pengguna (pengguna_diskon)
      // Jika kolom tanggal_digunakan tersedia, update baris pengguna_diskon untuk user ini dan diskon ini.
      if ($kolom_ada) {
        $stmt_pd = $conn->prepare("UPDATE pengguna_diskon SET tanggal_digunakan = NOW() WHERE pengguna_id = ? AND diskon_id = ? LIMIT 1");
        if ($stmt_pd) {
          $stmt_pd->bind_param('ii', $user_id, $d['id']);
          $stmt_pd->execute();
          // Jika tidak ada baris yg di-update, mungkin pengguna belum punya entri -> insert sebagai sudah digunakan
          if ($stmt_pd->affected_rows === 0) {
            $stmt_pd->close();
            $stmt_ins = $conn->prepare("INSERT INTO pengguna_diskon (pengguna_id, diskon_id, tanggal_didapat, tanggal_digunakan) VALUES (?, ?, NOW(), NOW())");
            if ($stmt_ins) {
              $stmt_ins->bind_param('ii', $user_id, $d['id']);
              $stmt_ins->execute();
              $stmt_ins->close();
            }
          } else {
            $stmt_pd->close();
          }
        }
      } else {
        // Jika tidak ada kolom tanggal_digunakan, pastikan ada entri pengguna_diskon untuk riwayat pengguna
        $stmt_chk = $conn->prepare("SELECT id FROM pengguna_diskon WHERE pengguna_id = ? AND diskon_id = ? LIMIT 1");
        if ($stmt_chk) {
          $stmt_chk->bind_param('ii', $user_id, $d['id']);
          $stmt_chk->execute();
          $res_chk = $stmt_chk->get_result();
          if ($res_chk && $res_chk->num_rows === 0) {
            $stmt_chk->close();
            $stmt_ins2 = $conn->prepare("INSERT INTO pengguna_diskon (pengguna_id, diskon_id, tanggal_didapat) VALUES (?, ?, NOW())");
            if ($stmt_ins2) {
              $stmt_ins2->bind_param('ii', $user_id, $d['id']);
              $stmt_ins2->execute();
              $stmt_ins2->close();
            }
          } else {
            $stmt_chk->close();
          }
        }
      }

      // Jangan menonaktifkan row di table diskon global — penggunaan dicatat per-pengguna
      $_SESSION['applied_discount'] = $d;
      $diskon_pesan = 'Diskon berhasil diterapkan.';
    } else {
      unset($_SESSION['applied_discount']);
    }
  } else {
    $diskon_pesan = 'Kode diskon tidak ditemukan.';
    unset($_SESSION['applied_discount']);
  }
  $stmt_k->close();
}

// ----------------------------------
// Siapkan daftar diskon yang bisa dipilih (gabungan pengguna + global aktif)
// ----------------------------------
$available_discounts = [];
// tambahkan semua dari pengguna (sudah diambil di $diskon_user)
foreach ($diskon_user as $d) {
  if (!isset($available_discounts[$d['kode_diskon']])) $available_discounts[$d['kode_diskon']] = $d;
}
// ambil diskon global aktif dari table diskon
$stmt_gd = $conn->prepare("SELECT * FROM diskon d WHERE d.status = 'aktif' AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai = '0000-00-00' OR d.tanggal_mulai <= CURDATE()) AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai = '0000-00-00' OR d.tanggal_selesai >= CURDATE()) ORDER BY d.dibuat_pada DESC");
$stmt_gd->execute();
$res_gd = $stmt_gd->get_result();
while ($gd = $res_gd->fetch_assoc()) {
  if (!isset($available_discounts[$gd['kode_diskon']])) $available_discounts[$gd['kode_diskon']] = $gd;
}
$stmt_gd->close();

// ==== Handle apply/remove via POST (user memilih dari dropdown) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['apply_kode']) && trim($_POST['apply_kode']) !== '') {
    $kode = trim($_POST['apply_kode']);
    // redirect ke GET handling untuk reuse logic
    header('Location: ?kode_diskon=' . urlencode($kode));
    exit;
  }
  if (isset($_POST['remove_discount'])) {
    unset($_SESSION['applied_discount']);
    // redirect to clean URL without query
    $base = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $base);
    exit;
  }
}
// Also support remove via GET (fallback)
if (isset($_GET['remove_discount'])) {
  unset($_SESSION['applied_discount']);
  $base = strtok($_SERVER['REQUEST_URI'], '?');
  header('Location: ' . $base);
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Keranjang Belanja</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f5f5f5;
    }

    .navbar-tokopedia {
      background-color: #008000;
    }

    .cart-item {
      background: #fff;
      border-radius: 14px;
      padding: 18px 22px;
      margin-bottom: 22px;
      box-shadow: 0 4px 16px rgba(66, 181, 73, 0.08);
      display: flex;
      align-items: center;
      gap: 22px;
      transition: box-shadow 0.2s;
    }

    .cart-item:hover {
      box-shadow: 0 8px 32px rgba(66, 181, 73, 0.16);
    }

    .cart-item img {
      width: 100%;
      max-width: 170px;
      height: 170px;
      object-fit: cover;
      border-radius: 10px;
      background: #f2f2f2;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
    }

    .cart-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .cart-title {
      font-size: 17px;
      font-weight: 600;
      color: #333;
      margin-bottom: 2px;
    }

    .cart-price {
      color: #00ab55;
      font-weight: 600;
      font-size: 15px;
      margin-bottom: 2px;
    }

    .cart-qty {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 2px;
    }

    .qty-btn {
      background: #f7f7f7;
      border: none;
      border-radius: 50%;
      width: 28px;
      height: 28px;
      font-size: 18px;
      color: #00ab55;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
    }

    .qty-btn:hover {
      background: #e6f4ee;
      color: #008a40;
    }

    .cart-subtotal {
      color: #00ab55;
      font-weight: 600;
      font-size: 15px;
      margin-bottom: 2px;
    }

    .cart-remove {
      margin-top: 6px;
    }

    .btn-outline-danger {
      color: #ff4d4d;
      border-color: #ff4d4d;
    }

    .btn-outline-danger:hover {
      background-color: #ff4d4d;
      color: #fff;
      border-color: #ff4d4d;
    }

    .summary-box {
      background: linear-gradient(180deg, #ffffff, #fbfffb);
      border-radius: 14px;
      padding: 24px 22px;
      box-shadow: 0 10px 24px rgba(9, 30, 66, 0.04);
      position: sticky;
      top: 90px;
      margin-bottom: 24px;
    }

    .summary-box h5 {
      color: #222;
      font-weight: 700;
      margin-bottom: 18px;
    }

    .summary-box .btn-success {
      background: linear-gradient(180deg, #00bf66, #00994f);
      border: none;
      font-weight: 700;
      font-size: 16px;
      border-radius: 12px;
      padding: 14px 0;
      margin-top: 18px;
      box-shadow: 0 8px 20px rgba(0, 171, 85, 0.14);
    }

    .summary-box .btn-success:hover {
      background: #008a40;
    }

    /* Discount modal and card styles */
    .discount-card {
      border-radius: 12px;
      padding: 14px;
      border: 1px solid #eef3f2;
      background: linear-gradient(180deg, #ffffff 0%, #fbfffc 100%);
      box-shadow: 0 6px 18px rgba(9, 30, 66, 0.04);
      transition: transform .15s ease, box-shadow .15s ease;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .discount-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 18px 28px rgba(9, 30, 66, 0.08);
    }

    .discount-icon {
      width: 56px;
      height: 56px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg, #e6fff0, #d6fbe7);
      color: #0b6b31;
      font-weight: 700;
      font-size: 18px;
    }

    .discount-name {
      font-weight: 700;
      font-size: 15px;
      color: #0b6b31;
    }

    .discount-meta {
      font-size: 13px;
      color: #6b7280;
      margin-top: 4px;
    }

    .discount-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #e9f7ee;
      color: #0b6b31;
      padding: 6px 10px;
      border-radius: 18px;
      font-weight: 600;
    }

    .discount-chip .remove-x {
      cursor: pointer;
      color: #0b6b31;
      font-weight: 700;
      margin-left: 6px;
    }

    .modal-discount-list {
      display: grid;
      gap: 12px;
      grid-template-columns: 1fr;
    }

    @media(min-width:760px) {
      .modal-discount-list {
        grid-template-columns: 1fr 1fr;
      }
    }

    .discount-cta {
      border-radius: 8px;
      padding: 8px 12px;
      font-weight: 700;
    }

    .discount-badge {
      background: #00ab55;
      color: #fff;
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 700;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-tokopedia sticky-top" style="padding-left:0;padding-right:0;">
    <div class="container-fluid" style="padding-left:0;padding-right:0;">
      <div class="d-flex align-items-center" style="gap:10px;">
        <img src="../logo.png" alt="Logo UMKM" height="38" style="border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.04);margin-left:18px;margin-top:4px;margin-right:8px;">
      </div>
    </div>
  </nav>

  <div class="container my-4">
    <div class="row">
      <div class="col-lg-8">
        <h3 class="mb-3">Keranjang</h3>
        <?php if (!empty($diskon_pesan)): ?>
          <div class="alert alert-info"><?= htmlspecialchars($diskon_pesan) ?></div>
        <?php endif; ?>

        <?php if (empty($keranjang)): ?>
          <div class="alert alert-info">Keranjang masih kosong.</div>
        <?php else: ?>
          <?php
          $total = 0;
          $groups = [];
          foreach ($keranjang as $index => $item) {
            $penjual = $item['penjual_id'] ?? 0;
            if (!isset($groups[$penjual])) $groups[$penjual] = [];
            $groups[$penjual][] = ['index' => $index, 'item' => $item];
          }

          // 🔹 Ambil nama penjual dari tabel pengguna
          $seller_names = [];
          function get_seller_name($conn, $penjual_id, &$cache)
          {
            if ($penjual_id === null || $penjual_id === 0) return 'Penjual Lain';
            if (isset($cache[$penjual_id])) return $cache[$penjual_id];
            $name = 'Penjual #' . $penjual_id;

            $check = $conn->query("SHOW TABLES LIKE 'pengguna'");
            if (!($check && $check->num_rows > 0)) {
              $cache[$penjual_id] = $name;
              return $name;
            }

            $stmt = $conn->prepare("SELECT nama FROM pengguna WHERE id = ? AND peran = 'penjual' LIMIT 1");
            if ($stmt) {
              $stmt->bind_param('i', $penjual_id);
              $stmt->execute();
              $res = $stmt->get_result();
              if ($r = $res->fetch_assoc()) $name = $r['nama'] ?: $name;
              $stmt->close();
            }

            $cache[$penjual_id] = $name;
            return $name;
          }

          foreach ($groups as $penjual_id => $entries):
            $seller_title = get_seller_name($conn, $penjual_id, $seller_names);
            $seller_subtotal = 0;
          ?>
            <div class="seller-group mb-4" data-penjual="<?= htmlspecialchars($penjual_id) ?>">
              <div class="p-3 mb-3 rounded-3" style="background: #f7f7ff; border-left: 6px solid #00ab55;">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <strong><?= htmlspecialchars($seller_title) ?></strong>
                    <div style="font-size:12px;color:#666;">Penjual ID: <?= htmlspecialchars($penjual_id) ?></div>
                  </div>
                </div>
              </div>

              <?php foreach ($entries as $entry):
                $index = $entry['index'];
                $item = $entry['item'];
                $nama_produk = $item["nama_produk"];
                $harga_produk = 0;
                $stok_produk = 0;
                // Prefer to lookup by produk_id when available to ensure we hit the exact product row
                $resolved_produk_id = isset($item['produk_id']) ? (int)$item['produk_id'] : 0;
                $penjual_for_item = $item['penjual_id'] ?? null;

                if ($resolved_produk_id > 0) {
                  $stmt = $conn->prepare("SELECT harga, stok FROM produk WHERE id = ? LIMIT 1");
                  $stmt->bind_param("i", $resolved_produk_id);
                } elseif ($penjual_for_item !== null && $penjual_for_item !== '') {
                  // Use both name and penjual_id to avoid conflicts when multiple sellers have same product name
                  $stmt = $conn->prepare("SELECT id, harga, stok FROM produk WHERE nama = ? AND penjual_id = ? LIMIT 1");
                  $stmt->bind_param("si", $nama_produk, $penjual_for_item);
                } else {
                  // Fallback to name-only lookup (less reliable)
                  $stmt = $conn->prepare("SELECT id, harga, stok FROM produk WHERE nama = ? LIMIT 1");
                  $stmt->bind_param("s", $nama_produk);
                }
                if ($stmt) {
                  $stmt->execute();
                  $result = $stmt->get_result();
                  if ($row = $result->fetch_assoc()) {
                    // if id present in result, use it to keep session in sync
                    if (isset($row['id'])) {
                      $resolved_produk_id = (int)$row['id'];
                    }
                    $harga_produk = (int)($row['harga'] ?? 0);
                    $stok_produk = (int)($row['stok'] ?? 0);
                  }
                  $stmt->close();
                }

                // Keep session's produk_id in sync when we resolved an id
                if ($resolved_produk_id > 0 && isset($_SESSION['keranjang'][$index])) {
                  $_SESSION['keranjang'][$index]['produk_id'] = $resolved_produk_id;
                }
                $jumlah = (int)$item["jumlah"];
                $subtotal = $harga_produk * $jumlah;
                $total += $subtotal;
                $seller_subtotal += $subtotal;

                $gambar = '../img/no-image.png';
                if (!empty($item["gambar"])) {
                  $gambar_dari_session = $item["gambar"];
                  $possible_paths = [
                    "../index2/pembeli/" . basename($gambar_dari_session),
                    "../" . $gambar_dari_session,
                    "../../uploads/" . basename($gambar_dari_session),
                    $gambar_dari_session
                  ];
                  foreach ($possible_paths as $test_path) {
                    $abs_path = realpath(dirname(__FILE__) . '/' . $test_path);
                    if ($abs_path && file_exists($abs_path)) {
                      $gambar = $test_path;
                      break;
                    }
                  }
                }
              ?>
                <div class="cart-item" data-produk-id="<?= (int)$item['produk_id'] ?>">
                  <img src="<?= htmlspecialchars($gambar) ?>" alt="<?= htmlspecialchars($item["nama_produk"]) ?>" onerror="this.src='../img/no-image.png'">
                  <div class="cart-info">
                    <div class="cart-title d-flex justify-content-between align-items-center">
                      <span><?= htmlspecialchars($item["nama_produk"]) ?></span>
                      <span class="badge bg-secondary ms-2" style="font-size:13px;">Stock Tersedia: <?= $stok_produk ?></span>
                    </div>
                    <div class="cart-price">Rp <?= number_format($harga_produk, 0, ',', '.') ?></div>
                    <form class="cart-qty" method="post" action="update-qty.php" data-index="<?= $index ?>" data-produk-id="<?= $resolved_produk_id ?>" data-penjual-id="<?= htmlspecialchars($penjual_for_item ?? '') ?>">
                      <input type="hidden" name="index" value="<?= $index ?>">
                      <input type="hidden" name="produk_id" value="<?= $resolved_produk_id ?>">
                      <input type="hidden" name="penjual_id" value="<?= htmlspecialchars($penjual_for_item ?? '') ?>">
                      <button type="button" class="qty-btn qty-minus">-</button>
                      <span class="qty-value" id="qty-<?= $index ?>"><?= $jumlah ?></span>
                      <button type="button" class="qty-btn qty-plus">+</button>
                    </form>
                    <div class="cart-subtotal">Subtotal: Rp <?= number_format($subtotal, 0, ',', '.') ?></div>
                    <div class="cart-remove">
                      <a href="hapus-keranjang.php?index=<?= $index ?>" class="btn btn-sm btn-outline-danger">Hapus</a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="seller-footer mb-4 p-3 rounded-3" style="background:#fff; border:1px dashed rgba(0,0,0,0.04);">
                <div class="d-flex justify-content-between align-items-center">
                  <div style="font-weight:600;">Subtotal Penjual</div>
                  <div style="font-weight:700; color:#00ab55;">Rp <?= number_format($seller_subtotal, 0, ',', '.') ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <form method="post" action="hapus-keranjang.php" class="mt-2">
            <input type="hidden" name="hapus_semua" value="1">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus semua keranjang?')">🗑 Hapus Semua Keranjang</button>
          </form>
        <?php endif; ?>

        <a href="../index2.php" class="btn btn-secondary mt-3">⬅ Kembali ke Beranda</a>
      </div>

      <div class="col-lg-4">
        <?php if (!empty($keranjang)): ?>
          <div class="summary-box">
            <h5>Ringkasan Belanja</h5>
            <div class="mb-2 d-flex justify-content-between align-items-center">
              <span style="font-size:16px;">Total</span>
              <strong style="font-size:18px; color:#00ab55;">Rp <?= number_format($total, 0, ',', '.') ?></strong>
            </div>
            <?php
            $applied = $_SESSION['applied_discount'] ?? null;
            $discount_amount = 0;
            if ($applied) {
              if (!empty($applied['persentase'])) {
                $discount_amount = ($total * ((float)$applied['persentase'] / 100.0));
              } elseif (!empty($applied['potongan_tetap'])) {
                $discount_amount = (float)$applied['potongan_tetap'];
              }
              if ($discount_amount > $total) $discount_amount = $total;
            ?>
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <span style="font-size:16px;">Diskon (<?= htmlspecialchars($applied['kode_diskon']) ?>)
                  <?php if (!empty($applied['persentase'])): ?>
                    <small style="color:#0b6b31; font-weight:700; margin-left:6px;"><?= (int)$applied['persentase'] ?>%</small>
                  <?php elseif (!empty($applied['potongan_tetap'])): ?>
                    <small style="color:#0b6b31; font-weight:700; margin-left:6px;">Rp <?= number_format($applied['potongan_tetap'], 0, ',', '.') ?></small>
                  <?php endif; ?>
                </span>
                <span style="font-size:16px; color:#d9534f;">- Rp <?= number_format($discount_amount, 0, ',', '.') ?></span>
              </div>
              <div class="mb-2 d-flex justify-content-between align-items-center">
                <span style="font-size:16px;">Total Setelah Diskon</span>
                <strong style="font-size:18px; color:#00ab55;">Rp <?= number_format($total - $discount_amount, 0, ',', '.') ?></strong>
              </div>
              <div style="margin-top:8px;">
                <div class="discount-chip" style="box-shadow:0 6px 14px rgba(11,107,49,0.06);">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="margin-right:6px;">
                    <path d="M20 10v7a2 2 0 0 1-2 2h-7l-7-7 7-7 7 7z" fill="#00ab55" />
                  </svg>
                  <span><?= htmlspecialchars($applied['nama_diskon']) ?> (<?= htmlspecialchars($applied['kode_diskon']) ?>)</span>
                  <?php if (!empty($applied['persentase'])): ?>
                    <span style="margin-left:8px; font-weight:700; color:#0b6b31;"><?= (int)$applied['persentase'] ?>%</span>
                  <?php elseif (!empty($applied['potongan_tetap'])): ?>
                    <span style="margin-left:8px; font-weight:700; color:#0b6b31;">Rp <?= number_format($applied['potongan_tetap'], 0, ',', '.') ?></span>
                  <?php endif; ?>
                  <form method="post" style="display:inline; margin:0 0 0 8px;">
                    <button type="submit" name="remove_discount" class="btn btn-sm" style="background:transparent; border:none; color:#0b6b31; padding:0; font-size:18px; line-height:1;">&times;</button>
                  </form>
                </div>
                <div style="text-align:center; margin-top:8px; font-size:13px;">
                  <a href="?remove_discount=1" style="color:#d9534f;">atau klik di sini jika tombol tidak berfungsi</a>
                </div>
              </div>
            <?php } else { ?>
              <?php if (!empty($available_discounts)): ?>
                <div class="mb-2">
                  <button class="btn btn-outline-secondary w-100" id="openDiscountModal">Pilih Diskon <span style="float:right;">&#9662;</span></button>
                </div>
              <?php else: ?>
                <div class="mb-2">Lagi belum ada promo, nih</div>
              <?php endif; ?>
            <?php } ?>
            <?php
            $checkout_url = '../checkout/checkout.php';
            if (!empty($_SESSION['applied_discount']) && !empty($_SESSION['applied_discount']['kode_diskon'])) {
              $checkout_url .= '?kode_diskon=' . urlencode($_SESSION['applied_discount']['kode_diskon']);
            }
            ?>
            <a href="<?= $checkout_url ?>" class="btn btn-success w-100">Checkout</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Discount selection modal -->
  <div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="discountModalLabel">Pilih Diskon</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted">Pilih voucher yang ingin Anda terapkan ke keranjang.</p>
          <div class="modal-discount-list">
            <?php foreach ($available_discounts as $ad): ?>
              <div class="discount-card">
                <div class="discount-icon">%</div>
                <div style="flex:1;">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <div class="discount-name"><?= htmlspecialchars($ad['nama_diskon']) ?></div>
                      <div class="discount-meta">Kode: <strong><?= htmlspecialchars($ad['kode_diskon']) ?></strong></div>
                    </div>
                    <div>
                      <div class="discount-badge"><?php if (!empty($ad['persentase'])) echo (int)$ad['persentase'] . '%';
                                                  elseif (!empty($ad['potongan_tetap'])) echo 'Rp ' . number_format($ad['potongan_tetap'], 0, ',', '.'); ?></div>
                    </div>
                  </div>
                  <div class="discount-meta">Berlaku: <?= $ad['tanggal_mulai'] ? date('d-m-Y', strtotime($ad['tanggal_mulai'])) : '-' ?> s/d <?= $ad['tanggal_selesai'] ? date('d-m-Y', strtotime($ad['tanggal_selesai'])) : '-' ?></div>
                </div>
                <div style="margin-left:12px;">
                  <form method="post">
                    <input type="hidden" name="apply_kode" value="<?= htmlspecialchars($ad['kode_diskon']) ?>">
                    <button class="btn btn-success discount-cta">Terapkan</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('openDiscountModal')?.addEventListener('click', function() {
      var modal = new bootstrap.Modal(document.getElementById('discountModal'));
      modal.show();
    });

    // Simple toast/alert helper
    function showToast(message, type = 'info') {
      var wrapper = document.createElement('div');
      wrapper.innerHTML = '<div class="toast align-items-center text-bg-' + (type === 'error' ? 'danger' : 'success') + ' border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position:fixed;top:20px;right:20px;z-index:99999;">' +
        '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>';
      document.body.appendChild(wrapper);
      var t = new bootstrap.Toast(wrapper.querySelector('.toast'));
      t.show();
      setTimeout(() => {
        t.hide();
        wrapper.remove();
      }, 3500);
    }

    // If server set diskon_pesan, show toast
    <?php if (!empty($diskon_pesan)): ?>
      showToast(<?= json_encode($diskon_pesan) ?>);
    <?php endif; ?>
    document.querySelectorAll('.cart-qty').forEach(function(form) {
      var index = form.getAttribute('data-index');
      var minusBtn = form.querySelector('.qty-minus');
      var plusBtn = form.querySelector('.qty-plus');
      var qtyValue = form.querySelector('.qty-value');

      minusBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var qty = parseInt(qtyValue.textContent);
        if (qty > 1) {
          updateQty(index, qty - 1, qtyValue);
        }
      });

      plusBtn.addEventListener('click', function(e) {
        e.preventDefault();
        var qty = parseInt(qtyValue.textContent);
        updateQty(index, qty + 1, qtyValue);
      });
    });

    function updateQty(index, newQty, qtyElem) {
      var form = document.querySelector('.cart-qty[data-index="' + index + '"]');
      // prefer data attribute (resolved produk_id) over the hidden input value
      var produkId = form ? (form.getAttribute('data-produk-id') || form.querySelector('input[name="produk_id"]').value) : '';
      var penjualId = form ? (form.getAttribute('data-penjual-id') || form.querySelector('input[name="penjual_id"]').value) : '';

      fetch('update-qty.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'index=' + encodeURIComponent(index) +
            '&jumlah=' + encodeURIComponent(newQty) +
            '&produk_id=' + encodeURIComponent(produkId) +
            '&penjual_id=' + encodeURIComponent(penjualId)
        })
        .then(res => res.text())
        .then(text => {
          let data = null;
          try {
            data = JSON.parse(text);
          } catch (e) {
            alert('Respon server tidak valid JSON:\n\n' + text);
            throw e;
          }
          if (data && data.success) {
            qtyElem.textContent = newQty;
            location.reload();
          } else {
            alert((data && data.message) ? data.message : 'Gagal memperbarui jumlah');
          }
        });
    }
  </script>
</body>

</html>