<?php
session_start();
require_once "../koneksi.php";

// CEK LOGIN PENJUAL
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$pesan = "";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// CEK DATA DISKON
$query = $conn->prepare("SELECT * FROM diskon WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Diskon tidak ditemukan.'); window.location='diskon.php';</script>";
    exit();
}

$diskon = $result->fetch_assoc();

// PROSES UPDATE DISKON
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_diskon = trim($_POST["nama_diskon"]);
    $kode_diskon = trim($_POST["kode_diskon"]);
    $deskripsi = trim($_POST["deskripsi"]);
    $persentase = floatval($_POST["persentase"]);
    $tanggal_mulai = $_POST["tanggal_mulai"];
    $tanggal_selesai = $_POST["tanggal_selesai"];
    $status = $_POST["status"];

    $update = $conn->prepare("UPDATE diskon SET nama_diskon=?, kode_diskon=?, deskripsi=?, persentase=?, tanggal_mulai=?, tanggal_selesai=?, status=? WHERE id=?");
    $update->bind_param("sssisssi", $nama_diskon, $kode_diskon, $deskripsi, $persentase, $tanggal_mulai, $tanggal_selesai, $status, $id);

    if ($update->execute()) {
        $pesan = "<div class='alert alert-success text-center'>✅ Diskon berhasil diperbarui.</div>";
        // Refresh data setelah update
        $query = $conn->prepare("SELECT * FROM diskon WHERE id = ?");
        $query->bind_param("i", $id);
        $query->execute();
        $diskon = $query->get_result()->fetch_assoc();
    } else {
        $pesan = "<div class='alert alert-danger text-center'>❌ Gagal memperbarui diskon.</div>";
    }

    $update->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Diskon</title>
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

        .section-title {
            margin-top: 20px;
            margin-bottom: 20px;
            font-weight: 700;
            color: #333;
            text-align: center;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        label {
            font-weight: 500;
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
                    <a class="nav-link" href="index3.php">Beranda</a>
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

    <div class="container my-4">
        <h2 class="section-title">Edit Diskon</h2>

        <?= $pesan ?>

        <div class="card p-4">
            <form method="POST">
                <div class="mb-3">
                    <label for="nama_diskon" class="form-label">Nama Diskon</label>
                    <input type="text" class="form-control" name="nama_diskon" id="nama_diskon" required value="<?= htmlspecialchars($diskon['nama_diskon']) ?>">
                </div>

                <div class="mb-3">
                    <label for="kode_diskon" class="form-label">Kode Diskon</label>
                    <input type="text" class="form-control" name="kode_diskon" id="kode_diskon" required value="<?= htmlspecialchars($diskon['kode_diskon']) ?>">
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" name="deskripsi" id="deskripsi" rows="3"><?= htmlspecialchars($diskon['deskripsi']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="persentase" class="form-label">Persentase Diskon (%)</label>
                    <input type="number" class="form-control" name="persentase" id="persentase" min="1" max="100" required value="<?= htmlspecialchars($diskon['persentase']) ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" required value="<?= htmlspecialchars($diskon['tanggal_mulai']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" name="tanggal_selesai" id="tanggal_selesai" required value="<?= htmlspecialchars($diskon['tanggal_selesai']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" name="status" id="status" required>
                        <option value="aktif" <?= $diskon['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $diskon['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="text-end">
                    <a href="diskon.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>