<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

// Hapus kategori jika tombol hapus ditekan
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    // Cek apakah kategori masih digunakan di tabel produk yang statusnya aktif
    $cekProduk = $conn->query("SELECT COUNT(*) as total FROM produk WHERE kategori_id = $id AND status = 'aktif'");
    $dataProduk = $cekProduk->fetch_assoc();
    if ($dataProduk['total'] > 0) {
        // Simpan pesan error ke session
        $_SESSION['error_kategori'] = 'Kategori tidak dapat dihapus karena masih digunakan oleh produk.';
        header("Location: kategori.php");
        exit();
    } else {
        $conn->query("DELETE FROM kategori WHERE id = $id");
        $_SESSION['sukses_kategori'] = 'Kategori berhasil dihapus.';
        header("Location: kategori.php");
        exit();
    }
}

// Ambil semua kategori dari database
$result = $conn->query("SELECT * FROM kategori ORDER BY jenis ASC, nama ASC");
$kategori = [];
while ($row = $result->fetch_assoc()) {
    $kategori[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori</title>
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

        .card h5,
        .card h6 {
            font-weight: 600;
        }

        .section-title {
            margin-top: 40px;
            margin-bottom: 20px;
            font-weight: 700;
            color: #333;
        }

        .bg-success-light {
            background-color: rgba(40, 167, 69, 0.1);
        }

        .bg-warning-light {
            background-color: rgba(255, 193, 7, 0.1);
        }

        .bg-danger-light {
            background-color: rgba(220, 53, 69, 0.1);
        }

        .bg-info-light {
            background-color: rgba(23, 162, 184, 0.1);
        }

        .bg-purple-light {
            background-color: rgba(102, 51, 153, 0.1);
        }

        .icon {
            font-size: 32px;
            margin-bottom: 8px;
            color: #333;
        }

        canvas {
            width: 100% !important;
            max-height: 350px;
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
        <?php if (isset($_SESSION['error_kategori'])): ?>
            <div class="alert alert-danger text-center">
                <?= $_SESSION['error_kategori'];
                unset($_SESSION['error_kategori']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['sukses_kategori'])): ?>
            <div class="alert alert-success text-center">
                <?= $_SESSION['sukses_kategori'];
                unset($_SESSION['sukses_kategori']); ?>
            </div>
        <?php endif; ?>
        <h2 class="section-title">Daftar Kategori</h2>

        <div class="d-flex justify-content-between mb-3">
            <a href="tambah-kategori.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Tambah Kategori
            </a>
        </div>

        <?php if (count($kategori) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered align-middle text-center">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Jenis</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($kategori as $k): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= ucfirst(str_replace('-', ' ', $k['nama'])) ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $k['jenis'])) ?></td>
                                <td>
                                    <a href="edit-kategori.php?id=<?= $k['id'] ?>" class="btn btn-warning btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="kategori.php?hapus=<?= $k['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Belum ada kategori yang ditambahkan.</div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Data produk
        const products = [{
                nama: "Anyaman Rajutan Tangan (1)",
                gambar: "Anyaman Rajutan tangan (1).jpeg",
                info: "Kerajinan tangan unik, cocok untuk dekorasi rumah."
            },
            {
                nama: "Anyaman Rajutan Tangan (2)",
                gambar: "Anyaman Rajutan tangan (2).jpeg",
                info: "Produk lokal berkualitas, hasil karya UMKM."
            },
            {
                nama: "Anyaman Rajutan Tangan (3)",
                gambar: "Anyaman Rajutan tangan (3).jpeg",
                info: "Anyaman kuat dan tahan lama."
            },
            {
                nama: "Anyaman Rajutan Tangan (4)",
                gambar: "Anyaman Rajutan tangan (4).jpeg",
                info: "Motif menarik, cocok untuk hadiah."
            },
            {
                nama: "Bala / Bakwan",
                gambar: "Bala - Bala _ Bakwan.jpeg",
                info: "Cemilan gurih khas Indonesia."
            },
            {
                nama: "Basreng ",
                gambar: "Basreng.jpeg",
                info: "Basreng pedas, cocok untuk teman nonton."
            },
            {
                nama: "Basreng 2",
                gambar: "BAsreng 2.jpeg",
                info: "Basreng renyah dan gurih."
            },
            {
                nama: "Cilok",
                gambar: "Cilok.jpeg",
                info: "Cilok kenyal dengan bumbu kacang."
            },
            {
                nama: "Cimol",
                gambar: "Cimol.jpeg",
                info: "Cimol kopong, gurih di luar lembut di dalam."
            },
            {
                nama: "Comet",
                gambar: "Comet.jpeg",
                info: "Cemilan manis dan lezat."
            },
            {
                nama: "Cuhcur",
                gambar: "Cuhcur.jpeg",
                info: "Kue tradisional khas Sunda."
            },
            {
                nama: "Es Lilin",
                gambar: "Es Lilin.jpeg",
                info: "Es jadul, segar dan manis."
            },
            {
                nama: "Gehu",
                gambar: "Gehu.jpeg",
                info: "Gehu (Goreng Hula) adalah tahu goreng isi sayuran yang renyah dan gurih."
            },
            {
                nama: "Keripik Singkong",
                gambar: "Keripik Singkong.jpeg",
                info: "Keripik singkong renyah, cocok untuk camilan."
            },
            {
                nama: "Keripik Singkong 2",
                gambar: "Keripik Singkong (1).jpeg",
                info: "Keripik singkong renyah, dan gurih cocok untuk camilan."
            },
            {
                nama: "Keripik Pisang",
                gambar: "Keripik Pisang.jpeg",
                info: "keripik pisang merupakan cemilan melezatkan"
            },
            {
                nama: "Keripik Pisang 2",
                gambar: "Keripik Pisang 2.jpeg",
                info: "Keripik pisang renyah, dan gurih cocok untuk camilan."
            },
            {
                nama: "Kerajinan Tangan",
                gambar: "kerjinan tangan (1).jpeg",
                info: "kerajinan tangan merupakan produk yang dibuat dengan keterampilan tangan.",
                customSlug: "kerajinan-tangan"
            },
            {
                nama: "Kerajinan Tangan 2",
                gambar: "kerjinan tangan (2).jpeg",
                info: "kerajinan tangan merupakan produk yang dibuat dengan keterampilan tangan.",
                customSlug: "kerajinan-tangan-2"
            },
            {
                nama: "Kesed Rumah ",
                gambar: "kesed (1).jpeg",
                info: "Kesed rumah adalah kerajinan tangan yang terbuat dari bahan alami."
            },
            {
                nama: "Kesed Rumah 2",
                gambar: "kesed (2).jpeg",
                info: "Kesed rumah adalah kerajinan tangan yang terbuat dari bahan alami ."
            },
            {
                nama: "Martabak",
                gambar: "Martabak.jpeg",
                info: "Martabak Manis dan Gurih."
            },
            {
                nama: "Pisang Aromat",
                gambar: "Pisang Aromat.jpeg",
                info: "Pisang aromat goreng renyah."
            },
            {
                nama: "Raginang",
                gambar: "Raginang.jpeg",
                info: "Raginang gurih dan renyah."
            },
            {
                nama: "Rajut Botol",
                gambar: "rajut botol.jpeg",
                info: "Rajut botol unik dan ramah lingkungan."
            },
            {
                nama: "Rajut Sampah Kopi",
                gambar: "rajut sampah kopi.jpeg",
                info: "Sate ayam bumbu kacang."
            },
            {
                nama: "Rempeyek 1",
                gambar: "Rempeyek 2.jpeg",
                info: "Rempeyek renyah dan gurih."
            },
            {
                nama: "Rempeyek 2",
                gambar: "Rempeyek 5.jpeg",
                info: "Rempeyek renyah dan gurih."
            },
            {
                nama: "Rempeyek 3",
                gambar: "Rempeyek.jpeg",
                info: "Rempeyek renyah dan gurih."
            },
            {
                nama: "Telor Gabus",
                gambar: "Telor Gabus.jpeg",
                info: "Telor gabus gurih dan renyah."
            },
        ];

        // Daftar harga produk sesuai file PHP harga
        const hargaProduk = {
            'anyaman-rajutan-tangan-1': 75000,
            'anyaman-rajutan-tangan-2': 85000,
            'anyaman-rajutan-tangan-3': 65000,
            'anyaman-rajutan-tangan-4': 90000,
            'bala-bakwan': 2000,
            'basreng': 2000,
            'basreng-2': 2000,
            'cilok': 5000,
            'cimol': 10000,
            'comet': 2000,
            'cuhcur': 3000,
            'es-lilin': 2000,
            'gehu': 5000,
            'keripik-singkong': 5000,
            'keripik-singkong-2': 7000,
            'keripik-pisang': 10000,
            'keripik-pisang-2': 10000,
            'kerajinan-tangan': 45000,
            'kerajinan-tangan-2': 35000,
            'kesed-rumah': 25000,
            'kesed-rumah-2': 30000,
            'martabak': 45000,
            'pisang-aromat': 20000,
            'raginang': 15000,
            'rajut-botol': 25000,
            'rajut-sampah-kopi': 30000,
            'rempeyek-1': 20000,
            'rempeyek-2': 22000,
            'rempeyek-3': 18000,
            'telor-gabus': 20000
        };
    </script>
</body>

</html>