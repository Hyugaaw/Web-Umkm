<?php
session_start();
require_once "../../koneksi.php";

// ✅ Pastikan user sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// === Jika ada kode_diskon di query string saat menuju checkout, coba terapkan ===
$diskon_notice = '';
if (!empty($_GET['kode_diskon'])) {
    $kode = trim($_GET['kode_diskon']);
    $stmtk = $conn->prepare('SELECT * FROM diskon WHERE kode_diskon = ? LIMIT 1');
    $stmtk->bind_param('s', $kode);
    $stmtk->execute();
    $found = $stmtk->get_result()->fetch_assoc();
    $stmtk->close();

    if ($found) {
        $valid = true;
        $today = date('Y-m-d');
        if ($found['status'] !== 'aktif') $valid = false;
        // normalize dates: treat '0000-00-00' or empty as no restriction
        $mulai = (!empty($found['tanggal_mulai']) && $found['tanggal_mulai'] !== '0000-00-00') ? $found['tanggal_mulai'] : null;
        $selesai = (!empty($found['tanggal_selesai']) && $found['tanggal_selesai'] !== '0000-00-00') ? $found['tanggal_selesai'] : null;
        if ($mulai !== null && $mulai > $today) $valid = false;
        if ($selesai !== null && $selesai < $today) $valid = false;
        // cek minimal / maksimal nanti setelah total dihitung (we'll revalidate below)

        // khusus pengguna baru
        if ($valid && !empty($found['khusus_pengguna_baru'])) {
            $stmtck = $conn->prepare('SELECT COUNT(*) AS jml FROM pesanan WHERE pembeli_id = ?');
            $stmtck->bind_param('i', $user_id);
            $stmtck->execute();
            $jml = $stmtck->get_result()->fetch_assoc()['jml'];
            $stmtck->close();
            if ($jml > 0) $valid = false;
        }

        if ($valid) {
            // Jika ada mapping pengguna_diskon untuk user ini, sertakan id mapping
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
            // temporarily set into session; final validation occurs after totalPrice computed
            $_SESSION['applied_discount'] = $found;
            $diskon_notice = 'Voucher "' . htmlspecialchars($kode) . '" diterapkan.';
        } else {
            unset($_SESSION['applied_discount']);
            $diskon_notice = 'Voucher "' . htmlspecialchars($kode) . '" tidak dapat diterapkan.';
        }
    } else {
        // not found -> remove
        unset($_SESSION['applied_discount']);
        $diskon_notice = 'Voucher "' . htmlspecialchars($kode) . '" tidak ditemukan.';
    }
}

// ==== Handle apply/remove via POST (user memilih dari dropdown/modal) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['apply_kode']) && trim($_POST['apply_kode']) !== '') {
        $kode = trim($_POST['apply_kode']);
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

// ✅ Hapus/kurangi produk satuan di halaman checkout (kurangi jumlah, bukan hapus semua)
// Jika pengguna datang lewat fitur 'Beli' langsung, kita menyimpan data tersebut
// di session['beli_langsung'] agar tidak mengubah isi keranjang permanen.
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus_index"])) {
    $hapus_index = (int) $_POST["hapus_index"];

    // Jika ada session 'beli_langsung', operasikan pada array tersebut terlebih dahulu
    if (isset($_SESSION['beli_langsung']) && is_array($_SESSION['beli_langsung'])) {
        if (isset($_SESSION['beli_langsung'][$hapus_index])) {
            if ($_SESSION['beli_langsung'][$hapus_index]['jumlah'] > 1) {
                $_SESSION['beli_langsung'][$hapus_index]['jumlah'] -= 1;
            } else {
                unset($_SESSION['beli_langsung'][$hapus_index]);
                $_SESSION['beli_langsung'] = array_values($_SESSION['beli_langsung']);
            }
        }
        header("Location: checkout.php");
        exit();
    }

    // Fallback: operasikan pada session keranjang utama
    if (isset($_SESSION["keranjang"]) && isset($_SESSION["keranjang"][$hapus_index])) {
        if ($_SESSION["keranjang"][$hapus_index]['jumlah'] > 1) {
            $_SESSION["keranjang"][$hapus_index]['jumlah'] -= 1;
        } else {
            unset($_SESSION["keranjang"][$hapus_index]);
            $_SESSION["keranjang"] = array_values($_SESSION["keranjang"]);
        }
    }
    header("Location: checkout.php");
    exit();
}


// Cek apakah checkout dari tombol 'Beli' (langsung, bukan dari keranjang)
$isBeliLangsung = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_produk'])) {
    // Simpan sementara di session['beli_langsung'] agar tindakan di checkout
    // (seperti "- Kurangi") hanya merubah cart sementara ini.
    $temp = [
        'id_produk' => $_POST['id_produk'],
        'nama_produk' => $_POST['nama_produk'],
        'harga' => (int)$_POST['harga'],
        'gambar' => $_POST['gambar'],
        'jumlah' => (int)$_POST['jumlah'],
    ];
    $_SESSION['beli_langsung'] = [$temp];
    $keranjang = $_SESSION['beli_langsung'];
    $isBeliLangsung = true;
} else {
    // Jika session 'beli_langsung' pernah dibuat (bahkan kosong),
    // artinya pengguna sedang dalam flow "beli langsung" — gunakan itu.
    if (array_key_exists('beli_langsung', $_SESSION)) {
        $keranjang = $_SESSION['beli_langsung'] ?? [];
        $isBeliLangsung = true;
    } else {
        $keranjang = $_SESSION["keranjang"] ?? [];
    }
}

// Gabungkan produk dengan nama yang sama menjadi satu (untuk tampilan saja)
$gabungKeranjang = [];
foreach ($keranjang as $item) {
    $nama = $item['nama_produk'];
    if (!isset($gabungKeranjang[$nama])) {
        $gabungKeranjang[$nama] = $item;
    } else {
        $gabungKeranjang[$nama]['jumlah'] += $item['jumlah'];
    }
}
$keranjang = array_values($gabungKeranjang);

// Jika checkout berasal dari keranjang, simpan ke session agar tetap sinkron.
// Jika berasal dari "Beli" langsung, kita sudah menyimpan di $_SESSION['beli_langsung']
if (!$isBeliLangsung) {
    $_SESSION["keranjang"] = $keranjang;
} else {
    // pastikan bentuk data di session['beli_langsung'] rapi (numeric keys)
    $_SESSION['beli_langsung'] = array_values($keranjang);
}

// ✅ Ambil data pembeli dari tabel pengguna
$queryPembeli = $conn->prepare("SELECT * FROM pengguna WHERE id = ? AND peran = 'pembeli'");
$queryPembeli->bind_param("i", $user_id);
$queryPembeli->execute();
$resultPembeli = $queryPembeli->get_result();
$pembeli = $resultPembeli->fetch_assoc();

// ✅ Hitung total harga
$totalHarga = 0;
foreach ($keranjang as $item) {
    $harga = $item['harga'] ?? 0;
    $jumlah = $item['jumlah'] ?? 0;
    $totalHarga += $harga * $jumlah;
}

// ===== Validate applied discount stored in session (jangan percaya client-side)
$applied_discount = $_SESSION['applied_discount'] ?? null;
$discount_amount = 0;
if ($applied_discount) {
    // ambil fresh data dari DB untuk memastikan tidak dimodifikasi
    $stmtd = $conn->prepare('SELECT * FROM diskon WHERE id = ? LIMIT 1');
    $stmtd->bind_param('i', $applied_discount['id']);
    $stmtd->execute();
    $fresh = $stmtd->get_result()->fetch_assoc();
    $stmtd->close();

    $valid = true;
    $today = date('Y-m-d');
    if (!$fresh) {
        $valid = false;
    }
    if ($valid && $fresh['status'] !== 'aktif') {
        $valid = false;
    }
    // normalize fresh dates (accept '0000-00-00' as no restriction)
    $mulai_f = (isset($fresh['tanggal_mulai']) && $fresh['tanggal_mulai'] !== '' && $fresh['tanggal_mulai'] !== '0000-00-00') ? $fresh['tanggal_mulai'] : null;
    $selesai_f = (isset($fresh['tanggal_selesai']) && $fresh['tanggal_selesai'] !== '' && $fresh['tanggal_selesai'] !== '0000-00-00') ? $fresh['tanggal_selesai'] : null;
    if ($valid && $mulai_f !== null && $mulai_f > $today) {
        $valid = false;
    }
    if ($valid && $selesai_f !== null && $selesai_f < $today) {
        $valid = false;
    }
    if ($valid && !empty($fresh['harga_minimal']) && $totalHarga < (float)$fresh['harga_minimal']) {
        $valid = false;
    }
    if ($valid && !empty($fresh['harga_maksimal']) && $totalHarga > (float)$fresh['harga_maksimal']) {
        $valid = false;
    }

    if ($valid) {
        if (!empty($fresh['persentase'])) {
            $discount_amount = $totalHarga * ((float)$fresh['persentase'] / 100.0);
        } elseif (!empty($fresh['potongan_tetap'])) {
            $discount_amount = (float)$fresh['potongan_tetap'];
        }
        if ($discount_amount > $totalHarga) $discount_amount = $totalHarga;
        // simpan yang fresh kembali ke session agar konsisten
        $_SESSION['applied_discount'] = $fresh;
        $applied_discount = $fresh;
    } else {
        unset($_SESSION['applied_discount']);
        $applied_discount = null;
        $discount_amount = 0;
    }
}

// ----------------------------------
// Siapkan daftar diskon yang bisa dipilih (gabungan pengguna + global aktif)
// ----------------------------------
$available_discounts = [];
// ambil diskon milik pengguna
$cek_kol = $conn->query("SHOW COLUMNS FROM pengguna_diskon LIKE 'tanggal_digunakan'");
$kolom_ada = ($cek_kol && $cek_kol->num_rows > 0);
$stmt_du = $conn->prepare("SELECT pd.id AS pengguna_diskon_id, d.* FROM pengguna_diskon pd JOIN diskon d ON pd.diskon_id = d.id WHERE pd.pengguna_id = ? " . ($kolom_ada ? "AND pd.tanggal_digunakan IS NULL " : "") . "AND d.status = 'aktif' AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai = '0000-00-00' OR d.tanggal_mulai <= CURDATE()) AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai = '0000-00-00' OR d.tanggal_selesai >= CURDATE())");
$stmt_du->bind_param('i', $user_id);
$stmt_du->execute();
$res_du = $stmt_du->get_result();
while ($r = $res_du->fetch_assoc()) {
    $available_discounts[$r['kode_diskon']] = $r;
}
$stmt_du->close();

// ambil diskon global aktif dari table diskon
$stmt_gd = $conn->prepare("SELECT * FROM diskon d WHERE d.status = 'aktif' AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai = '0000-00-00' OR d.tanggal_mulai <= CURDATE()) AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai = '0000-00-00' OR d.tanggal_selesai >= CURDATE()) ORDER BY d.dibuat_pada DESC");
$stmt_gd->execute();
$res_gd = $stmt_gd->get_result();
while ($gd = $res_gd->fetch_assoc()) {
    if (!isset($available_discounts[$gd['kode_diskon']])) $available_discounts[$gd['kode_diskon']] = $gd;
}
$stmt_gd->close();

// ✅ Fungsi validasi gambar
function getValidImagePath($relPath)
{
    $default = '../img/no-image.png';
    if (empty($relPath)) return $default;

    $relPath = trim($relPath);

    if (strpos($relPath, 'uploads/') === 0 && file_exists("../../" . $relPath)) {
        return "../../" . $relPath;
    }

    if (file_exists("../" . $relPath)) {
        return "../" . $relPath;
    }

    if (file_exists("../../uploads/" . basename($relPath))) {
        return "../../uploads/" . basename($relPath);
    }

    if (file_exists("../img/" . basename($relPath))) {
        return "../img/" . basename($relPath);
    }

    return $default;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Tokopedia Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f5f5;
        }

        .navbar-tokopedia {
            background-color: #008000;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 20px auto;
        }

        .product-img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .summary-box {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 90px;
        }

        .checkout-btn {
            background-color: #00ab4e;
            color: white;
            border: none;
        }

        .checkout-btn:hover {
            background-color: #008f3c;
        }

        .btn-outline-secondary,
        .btn-outline-success {
            font-weight: 600;
            border-width: 2px;
            border-radius: 6px;
            transition: 0.2s;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            padding: 8px 18px;
        }

        .btn-outline-secondary:hover {
            background: #e9ecef;
            color: #333;
        }

        .btn-outline-success:hover {
            background: #d1f5e0;
            color: #008a40;
        }

        .payment-card {
            cursor: pointer;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            width: 110px;
            text-align: center;
            transition: 0.3s;
        }

        .payment-card input[type="radio"] {
            display: none;
        }

        .payment-card .card-content img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            display: block;
            margin: 0 auto 8px;
        }

        input[type="radio"]:checked+.card-content {
            border: 2px solid #42b549;
            background-color: #f0fdf4;
            border-radius: 8px;
        }

        .d-flex.gap-3 {
            flex-wrap: wrap;
        }

        .hapus-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            transition: 0.2s;
        }

        .hapus-btn:hover {
            background-color: #b02a37;
        }

        /* Discount modal and card styles (copied from keranjang) */
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
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <div class="alert alert-success text-center" role="alert" style="position:fixed;top:10px;left:50%;transform:translateX(-50%);z-index:9999;">
            Pembayaran berhasil! Terima kasih, pesanan Anda telah diproses.
        </div>
        <script>
            setTimeout(function() {
                document.querySelector('.alert-success').style.display = 'none';
            }, 4000);
        </script>
    <?php endif; ?>

    <?php if (!empty($diskon_notice)): ?>
        <div class="alert alert-info text-center" role="alert" style="position:fixed;top:60px;left:50%;transform:translateX(-50%);z-index:9999;">
            <?= $diskon_notice ?>
        </div>
        <script>
            setTimeout(function() {
                var a = document.querySelectorAll('.alert-info');
                if (a[0]) a[0].style.display = 'none';
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Navbar -->
    <nav class="navbar navbar-tokopedia sticky-top" style="padding-left:0;padding-right:0;">
        <div class="container-fluid" style="padding-left:0;padding-right:0;">
            <div class="d-flex align-items-center" style="gap:10px;">
                <img src="../logo.png" alt="Logo UMKM" height="38" style="border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.04);margin-left:18px;margin-top:4px;margin-right:8px;">
            </div>
        </div>
    </nav>

    <div class="container checkout-container">
        <h2 class="mb-4">Checkout</h2>
        <div class="mb-3 d-flex gap-2 flex-wrap">
            <a href="../keranjang/keranjang.php" class="btn btn-outline-secondary d-flex align-items-center justify-content-center" style="min-width:180px;">
                ← Kembali ke Keranjang
            </a>
            <a href="../index2.php" class="btn btn-outline-success d-flex align-items-center justify-content-center" style="min-width:180px;">
                ← Kembali ke Daftar Produk
            </a>
        </div>

        <div class="row">
            <!-- Form Checkout -->
            <div class="col-lg-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <strong>Alamat Pengiriman</strong>
                    </div>
                    <div class="card-body">
                        <form id="checkout-form">
                            <div class="mb-3">
                                <label class="form-label">Nama Penerima</label>
                                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($pembeli['nama'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($pembeli['alamat'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" name="no_telepon" class="form-control" value="<?= htmlspecialchars($pembeli['no_telepon'] ?? '') ?>" required>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <strong>Metode Pembayaran</strong>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex gap-3 justify-content-start flex-wrap">
                                        <?php
                                        $metodePembayaran = [
                                            "shopeepay" => "shopeepay.jpeg",
                                            "gopay" => "Gopay.jpeg"
                                        ];
                                        foreach ($metodePembayaran as $metodeKey => $logoPath):
                                        ?>
                                            <label class="payment-card">
                                                <input type="radio" name="metode" value="<?= $metodeKey ?>" required>
                                                <div class="card-content">
                                                    <img src="<?= $logoPath ?>" alt="<?= ucfirst($metodeKey) ?>">
                                                </div>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="total" value="<?= $totalHarga ?>">
                        </form>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Pesanan -->
            <div class="col-lg-4">
                <?php if (!empty($keranjang)): ?>
                    <div class="summary-box" id="ringkasan-pesanan">
                        <h5 class="mb-3">Ringkasan Pesanan</h5>
                        <?php foreach ($keranjang as $index => $item):
                            $subtotal = ($item['harga'] ?? 0) * ($item['jumlah'] ?? 0);
                            $gambar = getValidImagePath($item['gambar'] ?? '');
                            $nama_produk = htmlspecialchars($item["nama_produk"] ?? "Produk");
                        ?>
                            <div class="d-flex mb-3 align-items-center justify-content-between produk-item">
                                <div class="d-flex align-items-center">
                                    <img src="<?= $gambar ?>" alt="<?= $nama_produk ?>" class="product-img me-3" title="<?= htmlspecialchars($gambar) ?>">
                                    <div>
                                        <h6 class="mb-1"><?= $nama_produk ?></h6>
                                        <p class="mb-1 text-muted">Rp <?= number_format($item['harga'] ?? 0, 0, ',', '.') ?> x <?= $item['jumlah'] ?? 0 ?></p>
                                        <p class="text-success fw-semibold mb-0">Subtotal: Rp <?= number_format($subtotal, 0, ',', '.') ?></p>
                                    </div>
                                </div>
                                <!-- ✅ Tombol hapus per item -->
                                <form method="POST" action="checkout.php" style="margin:0;">
                                    <input type="hidden" name="hapus_index" value="<?= $index ?>">
                                    <button type="submit" class="hapus-btn">- Kurangi</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <?php
                        $total_after_discount = $totalHarga;
                        if (!empty($applied_discount) && $discount_amount > 0) {
                            $total_after_discount = $totalHarga - $discount_amount;
                            if ($total_after_discount < 0) $total_after_discount = 0;
                        }
                        ?>

                        <div class="mb-1 d-flex justify-content-between align-items-center">
                            <span>Total (sebelum diskon)</span>
                            <span>Rp <?= number_format($totalHarga, 0, ',', '.') ?></span>
                        </div>
                        <?php if ($discount_amount > 0): ?>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span style="font-size:16px;">Diskon (<?= htmlspecialchars($applied_discount['kode_diskon'] ?? $applied_discount['kode'] ?? '') ?>)
                                    <?php if (!empty($applied_discount['persentase'])): ?>
                                        <small style="color:#0b6b31; font-weight:700; margin-left:6px;"><?= (int)$applied_discount['persentase'] ?>%</small>
                                    <?php elseif (!empty($applied_discount['potongan_tetap'])): ?>
                                        <small style="color:#0b6b31; font-weight:700; margin-left:6px;">Rp <?= number_format($applied_discount['potongan_tetap'], 0, ',', '.') ?></small>
                                    <?php endif; ?>
                                </span>
                                <span style="font-size:16px; color:#d9534f;">- Rp <?= number_format($discount_amount, 0, ',', '.') ?></span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                <span style="font-size:16px;">Total Setelah Diskon</span>
                                <strong style="font-size:18px; color:#00ab55;">Rp <?= number_format($total_after_discount, 0, ',', '.') ?></strong>
                            </div>
                            <div style="margin-top:8px;">
                                <div class="discount-chip" style="box-shadow:0 6px 14px rgba(11,107,49,0.06);">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="margin-right:6px;">
                                        <path d="M20 10v7a2 2 0 0 1-2 2h-7l-7-7 7-7 7 7z" fill="#00ab55" />
                                    </svg>
                                    <span><?= htmlspecialchars($applied_discount['nama_diskon'] ?? '') ?> (<?= htmlspecialchars($applied_discount['kode_diskon'] ?? $applied_discount['kode'] ?? '') ?>)</span>
                                    <?php if (!empty($applied_discount['persentase'])): ?>
                                        <span style="margin-left:8px; font-weight:700; color:#0b6b31;"><?= (int)$applied_discount['persentase'] ?>%</span>
                                    <?php elseif (!empty($applied_discount['potongan_tetap'])): ?>
                                        <span style="margin-left:8px; font-weight:700; color:#0b6b31;">Rp <?= number_format($applied_discount['potongan_tetap'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm" id="removeDiscountBtn" style="background:transparent; border:none; color:#0b6b31; padding:0; font-size:18px; line-height:1;">&times;</button>
                                </div>
                                <div style="text-align:center; margin-top:8px; font-size:13px;">
                                    <a href="?remove_discount=1" style="color:#d9534f;">atau klik di sini jika tombol tidak berfungsi</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="d-flex justify-content-between mb-0">
                                <span>Total Pembayaran</span>
                                <strong id="total-pembayaran">Rp <?= number_format($totalHarga, 0, ',', '.') ?></strong>
                            </p>
                            <?php if (!empty($available_discounts)): ?>
                                <div class="mb-2">
                                    <button class="btn btn-outline-secondary w-100" id="openDiscountModal">Pilih Diskon <span style="float:right;">&#9662;</span></button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <button type="button" class="btn checkout-btn w-100 mt-3" onclick="bayar()">Bayar Sekarang</button>
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
                    <div id="modalDiscountContainer" class="modal-discount-list">
                        <!-- dynamic loaded discounts -->
                        <div class="text-center text-muted">Memuat voucher...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Midtrans Snap JS -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-Tv3tGx3vca1i-c6V"></script>

    <script>
        async function bayar() {
            const form = document.getElementById("checkout-form");
            const formData = new FormData(form);
            const nama = formData.get("nama").trim();
            const alamat = formData.get("alamat").trim();
            const no_telepon = formData.get("no_telepon").trim();
            const metodePembayaran = document.querySelector('input[name="metode"]:checked');
            if (!nama || !alamat || !no_telepon || !metodePembayaran) {
                alert("Semua field harus diisi dan metode pembayaran harus dipilih!");
                return;
            }

            const order_id = "ORDER" + Date.now();
            let total = formData.get("total");

            const keranjangData = <?php echo json_encode($keranjang); ?>;
            // Sertakan informasi diskon jika ada
            const appliedDiscount = <?php echo json_encode($applied_discount ?? null); ?>;
            const discountAmount = <?php echo json_encode($discount_amount ?? 0); ?>;
            if (appliedDiscount) {
                total = parseInt(total) - parseInt(Math.round(discountAmount));
                if (total < 0) total = 0;
            }

            const payload = {
                order_id: order_id,
                total_amount: parseInt(total),
                payment_method: metodePembayaran.value,
                nama: nama,
                alamat: alamat,
                no_telepon: no_telepon,
                keranjang: keranjangData,
                discount: appliedDiscount ? {
                    id: appliedDiscount.id,
                    kode: appliedDiscount.kode_diskon,
                    amount: Math.round(discountAmount)
                } : null
            };

            try {
                const res = await fetch("payment-api.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (data.token) {
                    snap.pay(data.token, {
                        onSuccess: async function(result) {
                            // Simpan pesanan utama
                            await fetch("../api/simpan-pesanan.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    order_id: result.order_id,
                                    gross_amount: result.gross_amount,
                                    payment_type: result.payment_type,
                                    nama: nama,
                                    alamat: alamat,
                                    no_telepon: no_telepon,
                                    keranjang: keranjangData,
                                    discount: appliedDiscount ? {
                                        id: appliedDiscount.id,
                                        kode: appliedDiscount.kode_diskon,
                                        amount: Math.round(discountAmount),
                                        pengguna_diskon_id: appliedDiscount.pengguna_diskon_id || null
                                    } : null
                                })
                            });

                            // Simpan item pesanan ke tabel item_pesanan
                            await fetch("../api/item-pesanan.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json"
                                },
                                body: JSON.stringify({
                                    order_id: result.order_id,
                                    items: keranjangData.map(item => ({
                                        produk_id: item.id,
                                        jumlah: item.jumlah,
                                        harga_saat_pembelian: item.harga
                                    })),
                                    discount: appliedDiscount ? {
                                        id: appliedDiscount.id,
                                        kode: appliedDiscount.kode_diskon,
                                        amount: Math.round(discountAmount)
                                    } : null
                                })
                            });

                            // Hapus keranjang
                            await fetch("clear-cart.php", {
                                method: "POST"
                            });

                            showSuccessModal(result);
                        },
                        onPending: function(result) {
                            console.log("Pending:", result);
                        },
                        onError: function(result) {
                            console.error("Error:", result);
                            alert("Terjadi kesalahan dalam pembayaran!");
                        }
                    });
                } else {
                    alert("Gagal memproses pembayaran: " + (data.error || "Unknown error"));
                }
            } catch (error) {
                console.error(error);
                alert("Terjadi kesalahan saat memproses pembayaran.");
            }
        }

        function showSuccessModal(result) {
            let modalHtml = `
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">Pembayaran Berhasil!</h5>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <svg width="60" height="60" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#00ab4e"/><path d="M7 13l3 3 7-7" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h4 class="mb-2">Payment successful</h4>
        <p class="mb-1">Rp ${result.gross_amount ? Number(result.gross_amount).toLocaleString('id-ID') : '-'} </p>
        <p class="mb-1">Order ID: <b>${result.order_id || '-'}</b></p>
        <p class="text-success">Terima kasih, pesanan Anda telah diproses.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success w-100" id="closeSuccessModal">OK</button>
      </div>
    </div>
  </div>
</div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            let modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
            document.getElementById('closeSuccessModal').onclick = function() {
                modal.hide();
                window.location.href = '../index2.php';
            };
        }
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // open discount modal and load discounts dynamically
        document.getElementById('openDiscountModal')?.addEventListener('click', async function() {
            var modalEl = document.getElementById('discountModal');
            var modal = new bootstrap.Modal(modalEl);
            modal.show();

            // fetch discounts
            const container = document.getElementById('modalDiscountContainer');
            container.innerHTML = '<div class="text-center text-muted">Memuat voucher...</div>';
            try {
                const res = await fetch('available-discounts.php');
                const j = await res.json();
                if (j && j.success) {
                    if (!j.discounts || j.discounts.length === 0) {
                        container.innerHTML = '<div class="text-center text-muted">Tidak ada voucher tersedia.</div>';
                        return;
                    }
                    container.innerHTML = '';
                    j.discounts.forEach(function(ad) {
                        const card = document.createElement('div');
                        card.className = 'discount-card';
                        card.innerHTML = `
                            <div class="discount-icon">%</div>
                            <div style="flex:1;">
                              <div class="d-flex align-items-center justify-content-between">
                                <div>
                                  <div class="discount-name">${escapeHtml(ad.nama_diskon || '')}</div>
                                  <div class="discount-meta">Kode: <strong>${escapeHtml(ad.kode_diskon || '')}</strong></div>
                                </div>
                                <div><div class="discount-badge">${ad.persentase ? (parseInt(ad.persentase)+'%') : (ad.potongan_tetap ? ('Rp '+numberWithDot(ad.potongan_tetap)) : '')}</div></div>
                              </div>
                              <div class="discount-meta">Berlaku: ${ad.tanggal_mulai ? formatDate(ad.tanggal_mulai) : '-'} s/d ${ad.tanggal_selesai ? formatDate(ad.tanggal_selesai) : '-'}</div>
                            </div>
                            <div style="margin-left:12px;"><button class="btn btn-success discount-cta">Terapkan</button></div>
                        `;
                        // apply handler
                        card.querySelector('.discount-cta').addEventListener('click', async function() {
                            try {
                                const res2 = await fetch('apply-discount.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        kode: ad.kode_diskon
                                    })
                                });
                                const r2 = await res2.json();
                                if (r2 && r2.success) {
                                    // update summary DOM
                                    updateSummaryWithDiscount(r2.discount, r2.amount);
                                    modal.hide();
                                } else {
                                    alert(r2.message || 'Gagal menerapkan voucher');
                                }
                            } catch (e) {
                                console.error(e);
                                alert('Gagal menerapkan voucher');
                            }
                        });
                        container.appendChild(card);
                    });
                } else {
                    container.innerHTML = '<div class="text-center text-muted">Gagal memuat voucher.</div>';
                }
            } catch (e) {
                console.error(e);
                container.innerHTML = '<div class="text-center text-muted">Gagal memuat voucher.</div>';
            }
        });

        // handle remove discount via AJAX and partial update (reload summary)
        document.getElementById('removeDiscountBtn')?.addEventListener('click', async function() {
            try {
                const res = await fetch('remove-discount.php', {
                    method: 'POST'
                });
                const j = await res.json();
                if (j && j.success) {
                    // remove discount UI from summary
                    clearDiscountUI();
                } else {
                    alert('Gagal menghapus voucher');
                }
            } catch (e) {
                console.error(e);
                alert('Gagal menghapus voucher');
            }
        });

        // helpers
        function escapeHtml(s) {
            if (!s) return '';
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function formatDate(d) {
            try {
                const dt = new Date(d);
                return ('0' + dt.getDate()).slice(-2) + '-' + ('0' + (dt.getMonth() + 1)).slice(-2) + '-' + dt.getFullYear();
            } catch (e) {
                return d;
            }
        }

        function numberWithDot(n) {
            if (n == null) return '';
            return Number(n).toLocaleString('id-ID');
        }

        function updateSummaryWithDiscount(discountObj, amount) {
            // update total before (exists) and insert discount lines and chip
            const totalBeforeEl = document.querySelector('#ringkasan-pesanan .mb-1 span') || null;
            // simpler: reload to ensure server and session consistent
            location.reload();
        }

        function clearDiscountUI() {
            location.reload();
        }
    </script>
</body>

</html>