<?php
session_start();
require_once "../koneksi.php";

// Pastikan pengguna sudah login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];

// =====================
// AMBIL PESANAN SUDAH DIBAYAR
// =====================
$pesanan_dibayar = [];
$stmt = $conn->prepare("
    SELECT ps.id AS pesanan_id, ps.status, ps.total, ps.dibuat_pada
    FROM pesanan ps
    WHERE ps.pembeli_id = ? AND ps.status = 'dibayar'
    ORDER BY ps.dibuat_pada DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $pesanan_dibayar[] = $row;
}
$stmt->close();

// =====================
// AMBIL PRODUK DARI KERANJANG (BELUM DIBAYAR)
// =====================
$keranjang_produk = [];
$stmt2 = $conn->prepare("
    SELECT pr.nama AS nama_produk, pr.harga, pr.path_gambar, k.jumlah
    FROM keranjang k
    JOIN produk pr ON k.produk_id = pr.id
    WHERE k.pembeli_id = ?
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $keranjang_produk[] = $row;
}
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f7f7f7;
        }

        .pesanan-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 18px;
        }

        .pesanan-card .card-header {
            background: #f9f9f9;
            font-weight: 600;
            border-radius: 10px 10px 0 0;
        }

        .produk-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .produk-item img {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 10px;
        }

        header {
            background-color: #A8E6CF;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        header img {
            height: 40px;
        }
    </style>
</head>

<body>
    <header>
        <div class="logo" style="display:flex; align-items:center; gap:10px;">
            <img src="logo.png" alt="Logo MyMarketplace">
        </div>
        <span class="fw-semibold" style="color:#008000; font-size:17px;">Daftar Pesanan Saya</span>
    </header>

    <div class="container py-4">
        <!-- ===================== -->
        <!-- BAGIAN PESANAN BELUM DIBAYAR -->
        <!-- ===================== -->
        <h3 class="mb-4 fw-semibold">Pesanan Belum Dibayar</h3>

        <?php if (count($keranjang_produk) === 0): ?>
            <div class="alert alert-warning">Belum ada produk di keranjang.</div>
        <?php else: ?>
            <div class="pesanan-card card mb-3">
                <div class="card-header">
                    Status: <span class="badge bg-warning text-dark">Belum Dibayar</span>
                </div>
                <div class="card-body">
                    <ul style="list-style:none; padding-left:0;">
                        <?php
                        $total_keranjang = 0;
                        foreach ($keranjang_produk as $item):
                            $subtotal = $item['harga'] * $item['jumlah'];
                            $total_keranjang += $subtotal;
                        ?>
                            <li class="produk-item">
                                <img src="../<?= htmlspecialchars($item['path_gambar']) ?>" alt="Gambar Produk">
                                <span>
                                    <b><?= htmlspecialchars($item['nama_produk']) ?></b><br>
                                    <?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?> =
                                    <b>Rp <?= number_format($subtotal, 0, ',', '.') ?></b>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <b>Total Belum Dibayar:</b> Rp <?= number_format($total_keranjang, 0, ',', '.') ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===================== -->
        <!-- BAGIAN PESANAN SUDAH DIBAYAR -->
        <!-- ===================== -->
        <h3 class="mb-4 fw-semibold mt-5">Pesanan Sudah Dibayar</h3>

        <?php if (count($pesanan_dibayar) === 0): ?>
            <div class="alert alert-info">Belum ada pesanan yang dibayar.</div>
        <?php else: ?>
            <?php foreach ($pesanan_dibayar as $ps): ?>
                <div class="pesanan-card card mb-3">
                    <div class="card-header">
                        ID Pesanan: #<?= htmlspecialchars($ps['pesanan_id']) ?>
                        <span class="badge bg-success float-end">Dibayar</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><b>Total:</b> Rp <?= number_format($ps['total'], 0, ',', '.') ?></div>
                        <div class="mb-2 text-muted">Tanggal: <?= date('d-m-Y H:i', strtotime($ps['dibuat_pada'])) ?></div>
                        <hr>

                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index2.php" class="btn btn-success" style="background:#00ab55; border:none; font-weight:600; font-size:16px; padding:10px 32px; border-radius:24px;">
                &larr; Kembali
            </a>
        </div>
    </div>
</body>

</html>