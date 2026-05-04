<?php
session_start();
require_once "../koneksi.php";

// =======================
// CEK LOGIN PENJUAL
// =======================
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$penjual_id = $_SESSION["user_id"];

// =======================
// PESANAN BELUM DIBAYAR
// (Produk masih di keranjang pembeli)
// =======================
$query_belum = "
    SELECT 
        k.id AS keranjang_id,
        k.jumlah,
        k.ditambahkan_pada,
        pr.id AS produk_id,
        pr.nama AS nama_produk,
        pr.path_gambar,
        pr.harga,
        u.id AS pembeli_id,
        u.nama AS nama_pembeli,
        u.email AS email_pembeli
    FROM keranjang k
    INNER JOIN produk pr ON k.produk_id = pr.id
    INNER JOIN pengguna u ON k.pembeli_id = u.id
    WHERE pr.penjual_id = ?
      AND NOT EXISTS (
            SELECT 1 FROM item_pesanan ip
            INNER JOIN pesanan p ON ip.pesanan_id = p.id
            WHERE ip.produk_id = k.produk_id
              AND p.pembeli_id = k.pembeli_id
      )
    ORDER BY u.nama, k.ditambahkan_pada DESC
";

$stmt_belum = $conn->prepare($query_belum);
$stmt_belum->bind_param("i", $penjual_id);
$stmt_belum->execute();
$result_belum = $stmt_belum->get_result();

$pesanan_belum = [];
while ($row = $result_belum->fetch_assoc()) {
    $pembeli_id = $row['pembeli_id'];
    if (!isset($pesanan_belum[$pembeli_id])) {
        $pesanan_belum[$pembeli_id] = [
            'nama_pembeli' => $row['nama_pembeli'],
            'email_pembeli' => $row['email_pembeli'],
            'items' => []
        ];
    }
    $pesanan_belum[$pembeli_id]['items'][] = $row;
}
$stmt_belum->close();

// =======================
// PESANAN SUDAH DIBAYAR
// =======================
$query_dibayar = "
    SELECT 
        p.id AS pesanan_id,
        p.status,
        p.total,
        p.dibuat_pada,
        u.nama AS nama_pembeli,
        u.email AS email_pembeli
    FROM pesanan p
    JOIN pengguna u ON p.pembeli_id = u.id
    WHERE p.status = 'dibayar'
    ORDER BY p.dibuat_pada DESC
";

$result_dibayar = $conn->query($query_dibayar);
$pesanan_dibayar = [];
while ($row = $result_dibayar->fetch_assoc()) {
    $pesanan_dibayar[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Transaksi Penjual</title>
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

        .img-produk {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 600;
        }

        .status-belum {
            background-color: #ffc107;
            color: #000;
        }

        .status-dibayar {
            background-color: #28a745;
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

    <div class="container my-4">
        <h2 class="section-title">Kelola Transaksi</h2>

        <!-- PESANAN BELUM DIBAYAR -->
        <h4 class="mb-3 mt-4 text-warning fw-bold">
            <i class="bi bi-hourglass-split"></i> Pesanan Belum Dibayar
        </h4>

        <?php if (empty($pesanan_belum)): ?>
            <div class="alert alert-info text-center">Tidak ada pesanan yang belum dibayar.</div>
        <?php else: ?>
            <?php foreach ($pesanan_belum as $pembeli_id => $p): ?>
                <div class="card">
                    <div class="card-header bg-warning bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($p['nama_pembeli']) ?></strong>
                            <span class="status-badge status-belum">Belum Dibayar</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($p['email_pembeli']) ?></p>
                        <p class="mb-1"><strong>Tanggal Terakhir:</strong>
                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($p['items'][0]['ditambahkan_pada']))) ?>
                        </p>

                        <div class="list-group">
                            <?php
                            $total = 0;
                            foreach ($p['items'] as $item):
                                $subtotal = $item['jumlah'] * $item['harga'];
                                $total += $subtotal;
                                $gambar = '../uploads/' . basename($item['path_gambar']);
                                if (empty($item['path_gambar']) || !file_exists($gambar)) {
                                    $gambar = '../img/no-image.png';
                                }
                            ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= htmlspecialchars($gambar) ?>" class="img-produk me-3"
                                            alt="<?= htmlspecialchars($item['nama_produk']) ?>"
                                            onerror="this.src='../img/no-image.png'">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($item['nama_produk']) ?></h6>
                                            <small><?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?></small>
                                        </div>
                                    </div>
                                    <span class="fw-bold">
                                        Rp <?= number_format($subtotal, 0, ',', '.') ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-end mt-3">
                            <h5>Total Pesanan: Rp <?= number_format($total, 0, ',', '.') ?></h5>
                            <a href="item_pesanan.php" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-list-check"></i> Lihat Item Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- PESANAN SUDAH DIBAYAR -->
        <h4 class="mb-3 mt-5 text-success fw-bold">
            <i class="bi bi-check-circle"></i> Pesanan Sudah Dibayar
        </h4>

        <?php if (empty($pesanan_dibayar)): ?>
            <div class="alert alert-info text-center">Tidak ada pesanan yang sudah dibayar.</div>
        <?php else: ?>
            <?php foreach ($pesanan_dibayar as $pesanan): ?>
                <div class="card">
                    <div class="card-header bg-success bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>Pesanan #<?= htmlspecialchars($pesanan['pesanan_id']) ?></strong>
                            <span class="status-badge status-dibayar">Sudah Dibayar</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Pembeli:</strong> <?= htmlspecialchars($pesanan['nama_pembeli']) ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($pesanan['email_pembeli']) ?></p>
                        <p class="mb-1"><strong>Total:</strong> Rp <?= number_format($pesanan['total'], 0, ',', '.') ?></p>
                        <p class="mb-0"><strong>Tanggal:</strong> <?= date('d/m/Y H:i', strtotime($pesanan['dibuat_pada'])) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>