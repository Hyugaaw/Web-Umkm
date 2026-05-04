<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil ID pembeli dari URL
if (!isset($_GET['id'])) {
    header("Location: pembeli.php");
    exit();
}

$id = intval($_GET['id']);

// Ambil data pembeli dari database
$result = $conn->query("SELECT * FROM pengguna WHERE id = $id AND peran = 'pembeli'");
$pembeli = $result->fetch_assoc();

if (!$pembeli) {
    header("Location: pembeli.php");
    exit();
}

$error = '';

// Proses update ketika form disubmit
if (isset($_POST['submit'])) {
    $nama = $conn->real_escape_string($_POST['nama']);
    $email = $conn->real_escape_string($_POST['email']);
    $disetujui = isset($_POST['disetujui']) ? 1 : 0;

    // Validasi format email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // Cek apakah email sudah dipakai pembeli lain
        $cek = $conn->prepare("SELECT id FROM pengguna WHERE email = ? AND id != ?");
        $cek->bind_param("si", $email, $id);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "Email sudah terdaftar untuk pengguna lain!";
        } else {
            // Update data
            $stmt = $conn->prepare("UPDATE pengguna SET nama = ?, email = ?, disetujui = ?, diperbarui_pada = NOW() WHERE id = ? AND peran = 'pembeli'");
            $stmt->bind_param("ssii", $nama, $email, $disetujui, $id);
            $stmt->execute();
            $stmt->close();

            header("Location: pembeli.php");
            exit();
        }
        $cek->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pembeli</title>
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
            <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">☰</button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index3.php">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="pembeli.php">Pembeli</a></li>
                    <li class="nav-item"><a class="nav-link" href="penjual.php">Penjual</a></li>
                    <li class="nav-item"><a class="nav-link" href="kelola-produk.php">Produk</a></li>
                    <li class="nav-item"><a class="nav-link" href="item_pesanan.php">Item Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php">Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link active" href="diskon.php">Diskon</a></li>
                    <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container my-4">
        <h2 class="section-title">Edit Pembeli</h2>

        <div class="card p-4">
            <?php if ($error != ''): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama</label>
                    <input type="text" class="form-control" id="nama" name="nama" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : htmlspecialchars($pembeli['nama']) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($pembeli['email']) ?>">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="disetujui" name="disetujui" <?= (isset($_POST['disetujui']) ? 'checked' : ($pembeli['disetujui'] ? 'checked' : '')) ?>>
                    <label class="form-check-label" for="disetujui">Disetujui</label>
                </div>
                <button type="submit" name="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                <a href="pembeli.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
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