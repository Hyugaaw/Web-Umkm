<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

// Daftar kategori yang sudah dikelompokkan
$kategori_tersedia = [
    "makanan" => [
        'bala-bakwan',
        'basreng',
        'basreng-2',
        'cilok',
        'cimol',
        'comet',
        'cuhcur',
        'es-lilin',
        'gehu',
        'keripik-singkong',
        'keripik-singkong-2',
        'keripik-pisang',
        'keripik-pisang-2',
        'martabak',
        'pisang-aromat',
        'raginang',
        'rempeyek-1',
        'rempeyek-2',
        'rempeyek-3',
        'telor-gabus'
    ],
    "kerajinan" => [
        'anyaman-rajutan-tangan-1',
        'anyaman-rajutan-tangan-2',
        'anyaman-rajutan-tangan-3',
        'anyaman-rajutan-tangan-4',
        'kerajinan-tangan',
        'kerajinan-tangan-2',
        'rajut-botol',
        'rajut-sampah-kopi'
    ],
    "perlengkapan_rumah" => [
        'kesed-rumah',
        'kesed-rumah-2'
    ]
];

// Daftar harga produk sesuai index2.php
$harga_produk = [
    'anyaman-rajutan-tangan-1' => 75000,
    'anyaman-rajutan-tangan-2' => 85000,
    'anyaman-rajutan-tangan-3' => 65000,
    'anyaman-rajutan-tangan-4' => 90000,
    'bala-bakwan' => 2000,
    'basreng' => 2000,
    'basreng-2' => 2000,
    'cilok' => 5000,
    'cimol' => 10000,
    'comet' => 2000,
    'cuhcur' => 3000,
    'es-lilin' => 2000,
    'gehu' => 5000,
    'keripik-singkong' => 5000,
    'keripik-singkong-2' => 7000,
    'keripik-pisang' => 10000,
    'keripik-pisang-2' => 10000,
    'kerajinan-tangan' => 45000,
    'kerajinan-tangan-2' => 35000,
    'kesed-rumah' => 25000,
    'kesed-rumah-2' => 30000,
    'martabak' => 45000,
    'pisang-aromat' => 20000,
    'raginang' => 15000,
    'rajut-botol' => 25000,
    'rajut-sampah-kopi' => 30000,
    'rempeyek-1' => 20000,
    'rempeyek-2' => 22000,
    'rempeyek-3' => 18000,
    'telor-gabus' => 20000
];

// Proses tambah kategori baru
if (isset($_POST['tambah'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $jenis = '';
    $harga = isset($harga_produk[$nama]) ? intval($harga_produk[$nama]) : 0;

    // Cari kategori tersebut masuk kelompok mana
    foreach ($kategori_tersedia as $kelompok => $daftar) {
        if (in_array($nama, $daftar)) {
            $jenis = $kelompok;
            break;
        }
    }

    if ($jenis && $harga > 0) {
        // Cek dulu apakah kategori sudah ada
        $cek = $conn->query("SELECT * FROM kategori WHERE nama='$nama'");
        if ($cek->num_rows == 0) {
            $conn->query("INSERT INTO kategori (nama, jenis, harga) VALUES ('$nama', '$jenis', $harga)");
            header("Location: kategori.php");
            exit();
        } else {
            $error = "Kategori sudah ada!";
        }
    } else {
        $error = "Nama kategori tidak valid atau harga tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .navbar {
            background-color: #28a745;
        }

        .navbar .nav-link {
            color: #fff !important;
            font-weight: 500;
            margin-left: 10px;
        }

        .navbar .nav-link.active {
            font-weight: bold;
            text-decoration: underline;
        }

        .navbar-brand img {
            height: 50px;
            margin-right: 10px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            margin-top: 40px;
            margin-bottom: 20px;
            font-weight: 700;
            color: #333;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center flex-wrap" href="index3.php">
                <img src="tut-wuri-handayani.jpg" alt="Tut Wuri Handayani">
                <img src="lppm.jpg" alt="LPPM" style="height:40px; margin-left:5px;">
                <img src="bsi.jpg" alt="UBSI" style="height:40px; margin-left:5px;">
                <span class="ms-2 text-black fw-semibold">Marketplace Penjual</span>
            </a>
            <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                ☰
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index3.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kategori.php">Kategori</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pembeli.php">Pembeli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="penjual.php">Penjual</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola-produk.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="item_pesanan.php">Item Pesanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="kelola-transaksi.php">Transaksi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="diskon.php">Diskon</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger fw-bold" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container my-4">
        <h2 class="section-title">Tambah Kategori</h2>

        <div class="card p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama" class="form-label">Pilih Nama Kategori</label>
                    <select name="nama" id="nama" class="form-select" required onchange="updateHarga()">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_tersedia as $kelompok => $daftar): ?>
                            <optgroup label="<?= ucfirst(str_replace('_', ' ', $kelompok)) ?>">
                                <?php foreach ($daftar as $k): ?>
                                    <option value="<?= $k ?>" data-harga="<?= isset($harga_produk[$k]) ? $harga_produk[$k] : '' ?>">
                                        <?= ucfirst(str_replace('-', ' ', $k)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="harga" class="form-label">Harga (Rp)</label>
                    <input type="text" id="harga" name="harga" class="form-control" readonly>
                </div>
                <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                <a href="kategori.php" class="btn btn-secondary">Batal</a>
            </form>
            <script>
                function updateHarga() {
                    var select = document.getElementById('nama');
                    var harga = select.options[select.selectedIndex].getAttribute('data-harga') || '';
                    document.getElementById('harga').value = harga ? parseInt(harga).toLocaleString('id-ID') : '';
                }
            </script>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>