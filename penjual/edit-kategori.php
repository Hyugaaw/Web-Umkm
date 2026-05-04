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

// Pastikan ada parameter ID
if (!isset($_GET['id'])) {
    header("Location: kategori.php");
    exit();
}

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM kategori WHERE id = $id");

if ($result->num_rows === 0) {
    header("Location: kategori.php");
    exit();
}

$data = $result->fetch_assoc();

// Proses update kategori
if (isset($_POST['simpan'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $jenis = '';

    // Cek kategori masuk kelompok mana
    foreach ($kategori_tersedia as $kelompok => $daftar) {
        if (in_array($nama, $daftar)) {
            $jenis = $kelompok;
            break;
        }
    }

    if ($jenis) {
        // Update data kategori
        $conn->query("UPDATE kategori SET nama='$nama', jenis='$jenis' WHERE id=$id");
        header("Location: kategori.php");
        exit();
    } else {
        $error = "Nama kategori tidak valid!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori</title>
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
        <h2 class="section-title">Edit Kategori</h2>

        <div class="card p-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama" class="form-label">Pilih Nama Kategori</label>
                    <select name="nama" id="nama" class="form-select" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_tersedia as $kelompok => $daftar): ?>
                            <optgroup label="<?= ucfirst(str_replace('_', ' ', $kelompok)) ?>">
                                <?php foreach ($daftar as $k): ?>
                                    <option value="<?= $k ?>" <?= ($data['nama'] === $k) ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('-', ' ', $k)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="simpan" class="btn btn-success">Simpan Perubahan</button>
                <a href="kategori.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>