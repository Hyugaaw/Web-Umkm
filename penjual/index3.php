<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$user_nama = $_SESSION["user_nama"] ?? '';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Utama Penjual</title>
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

        .hero {
            background: linear-gradient(90deg, #28a745, #20c997);
            color: white;
            padding: 50px 20px;
            border-radius: 15px;
            text-align: center;
        }

        .hero h1 {
            font-weight: 700;
            margin-bottom: 15px;
        }

        .info-section {
            margin-top: 50px;
        }

        .info-card {
            border-radius: 15px;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 25px;
            transition: all 0.3s ease-in-out;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        }

        .info-card i {
            font-size: 40px;
            color: #28a745;
            margin-bottom: 15px;
        }

        footer {
            background-color: #28a745;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 50px;
            border-radius: 12px;
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">☰</button>
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
                    <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="container my-4">
        <div class="hero">
            <h1>Selamat Datang, <?= htmlspecialchars($user_nama) ?>!</h1>
            <p>Kelola toko dan produk Anda dengan mudah di halaman penjual marketplace kami.</p>
        </div>

        <!-- Penjelasan Menu -->
        <div class="info-section">
            <h3 class="text-center mb-4 fw-bold text-success">Informasi Menu Penjual</h3>
            <d class="row g-4">
                <div class="container mt-4">
                    <div class="row g-4">

                        <!-- Baris 1 -->
                        <div class="col-md-4">
                            <div class="info-card text-center p-3 shadow-sm rounded-4">
                                <i class="bi bi-tags fs-1 text-primary"></i>
                                <h5 class="mt-3">Kategori</h5>
                                <p>
                                    Halaman untuk mengelola kategori produk. Anda dapat menambahkan, mengedit,
                                    atau menghapus kategori agar pembeli mudah menemukan produk sesuai jenisnya.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card text-center p-3 shadow-sm rounded-4">
                                <i class="bi bi-person fs-1 text-primary"></i>
                                <h5 class="mt-3">Pembeli</h5>
                                <p>
                                    Berisi daftar pengguna yang melakukan pembelian di toko Anda. Anda dapat
                                    melihat informasi pembeli dan riwayat transaksi mereka.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card text-center p-3 shadow-sm rounded-4">
                                <i class="bi bi-shop fs-1 text-primary"></i>
                                <h5 class="mt-3">Penjual</h5>
                                <p>
                                    Menampilkan informasi tentang diri Anda sebagai penjual dan juga penjual lain
                                    dalam sistem marketplace. Berguna untuk melihat profil dan reputasi toko.
                                </p>
                            </div>
                        </div>

                        <!-- Baris 2 -->
                        <div class="col-md-4">
                            <div class="info-card text-center p-3 shadow-sm rounded-4">
                                <i class="bi bi-box-seam fs-1 text-primary"></i>
                                <h5 class="mt-3">Produk</h5>
                                <p>
                                    Di sini Anda bisa menambah, mengedit, atau menonaktifkan produk yang dijual.
                                    Pastikan produk memiliki deskripsi, harga, dan gambar yang menarik.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card text-center p-3 shadow-sm rounded-4">
                                <i class="bi bi-basket fs-1 text-primary"></i>
                                <h5 class="mt-3">Item Pesanan</h5>
                                <p>
                                    Menampilkan daftar setiap item produk yang dipesan pembeli. Anda bisa melihat
                                    rincian pesanan seperti jumlah, harga, dan status pengiriman.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card text-center p-3 shadow-sm rounded-4">
                                <i class="bi bi-cash-coin fs-1 text-primary"></i>
                                <h5 class="mt-3">Transaksi</h5>
                                <p>
                                    Menampilkan seluruh riwayat pembayaran yang dilakukan oleh pembeli. Anda dapat
                                    melihat total dan status pembayaran dengan mudah.
                                </p>
                            </div>
                        </div>

                        <!-- Baris 3 - Card Promosi Lebar Penuh -->
                        <div class="col-md-12">
                            <div class="info-card text-center p-4 shadow-sm rounded-4 bg-light border h-100">
                                <i class="bi bi-percent fs-1 text-success"></i>
                                <h5 class="mt-3">Diskon dan Promo</h5>
                                <p class="mt-2">
                                    Nikmati berbagai penawaran menarik setiap harinya! Anda dapat melihat daftar diskon aktif,
                                    promo musiman, hingga potongan harga khusus untuk pelanggan setia.
                                    Pastikan Anda selalu memeriksa halaman ini agar tidak ketinggalan kesempatan
                                    mendapatkan harga terbaik untuk produk favorit Anda.
                                </p>
                                <div class="mt-3">
                                    <a href="diskon.php" class="btn btn-success rounded-pill px-4">
                                        Lihat Diskon
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <footer>
                    <small>© <?= date('Y') ?> Marketplace UMKM | Dikembangkan untuk mendukung digitalisasi penjual lokal.</small>
                </footer>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>