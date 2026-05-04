<?php
session_start();
require_once "../koneksi.php";

// CEK LOGIN PENJUAL
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$pesan = "";

// PROSES HAPUS DISKON
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus_id"])) {
    $hapus_id = intval($_POST["hapus_id"]);

    $query_hapus = $conn->prepare("DELETE FROM diskon WHERE id = ?");
    $query_hapus->bind_param("i", $hapus_id);

    if ($query_hapus->execute()) {
        $pesan = "<div class='alert alert-success text-center'>✅ Diskon berhasil dihapus.</div>";
    } else {
        $pesan = "<div class='alert alert-danger text-center'>❌ Gagal menghapus diskon.</div>";
    }

    $query_hapus->close();
}

// AMBIL SEMUA DATA DISKON
$query = "SELECT * FROM diskon ORDER BY dibuat_pada DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Diskon</title>
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
        }

        .status-aktif {
            background-color: #28a745;
            color: #fff;
        }

        .status-nonaktif {
            background-color: #dc3545;
            color: #fff;
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
        <h2 class="section-title">Daftar Diskon</h2>

        <?= $pesan ?>

        <div class="text-end mb-3">
            <a href="tambah-diskon.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Tambah Diskon Baru
            </a>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="alert alert-info text-center">Belum ada data diskon.</div>
        <?php else: ?>
            <?php while ($d = $result->fetch_assoc()): ?>
                <?php
                // Format tanggal aman
                $tgl_mulai = (!empty($d['tanggal_mulai']) && $d['tanggal_mulai'] != '0000-00-00')
                    ? date("d M Y", strtotime($d['tanggal_mulai']))
                    : '-';
                $tgl_selesai = (!empty($d['tanggal_selesai']) && $d['tanggal_selesai'] != '0000-00-00')
                    ? date("d M Y", strtotime($d['tanggal_selesai']))
                    : '-';
                ?>
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($d['nama_diskon']) ?> (<?= htmlspecialchars($d['kode_diskon']) ?>)</strong>
                        <span class="status-badge <?= $d['status'] == 'aktif' ? 'status-aktif' : 'status-nonaktif' ?>">
                            <?= ucfirst($d['status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <p><strong>Deskripsi:</strong> <?= htmlspecialchars($d['deskripsi'] ?? '-') ?></p>
                        <p><strong>Persentase:</strong> <?= $d['persentase'] ? $d['persentase'] . '%' : '-' ?></p>
                        <p><strong>Berlaku:</strong> <?= $tgl_mulai ?> s/d <?= $tgl_selesai ?></p>

                        <div class="text-end d-flex justify-content-end gap-2">
                            <a href="edit-diskon.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>

                            <!-- Tombol Hapus Langsung -->
                            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus diskon ini?')">
                                <input type="hidden" name="hapus_id" value="<?= $d['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>