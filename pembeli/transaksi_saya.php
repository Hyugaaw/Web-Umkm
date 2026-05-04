<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}
$user_id = $_SESSION["user_id"];

// ======================
// AMBIL PESANAN DIBAYAR
// ======================
$transaksi = [];
$stmt = $conn->prepare("
    SELECT ps.id AS transaksi_id, ps.status, ps.total, ps.dibuat_pada
    FROM pesanan ps
    WHERE ps.pembeli_id = ?
    ORDER BY ps.dibuat_pada DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $transaksi[] = $row;
}
$stmt->close();

// ======================
// AMBIL PESANAN BELUM DIBAYAR (DARI KERANJANG)
// ======================
$keranjang = [];
$stmt2 = $conn->prepare("
    SELECT k.id AS keranjang_id, pr.nama, k.jumlah, pr.harga
    FROM keranjang k
    JOIN produk pr ON k.produk_id = pr.id
    WHERE k.pembeli_id = ?
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while ($row = $res2->fetch_assoc()) {
    $keranjang[] = $row;
}
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Transaksi Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f7f7f7;
        }

        .transaksi-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 18px;
        }

        .transaksi-card .card-header {
            background: #f7f7f7;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }

        .badge-status {
            float: right;
            margin-top: 2px;
        }
    </style>
</head>

<body>
    <header style="background-color:#A8E6CF; padding:10px 20px; display:flex; align-items:center; justify-content:space-between;">
        <div class="logo" style="display:flex; align-items:center; gap:10px;">
            <img src="logo.png" alt="Logo MyMarketplace" style="height:40px; margin-left:0;" />
        </div>
        <span class="fw-semibold" style="color:#008000; font-size:17px; font-weight:600;">Daftar Transaksi Saya</span>
    </header>

    <div class="container py-4">
        <!-- BELUM DIBAYAR (KERANJANG) -->
        <h3 class="mb-4">Pesanan Belum Dibayar</h3>
        <?php if (count($keranjang) === 0): ?>
            <div class="alert alert-info">Tidak ada pesanan yang belum dibayar.</div>
        <?php else: ?>
            <div class="transaksi-card card mb-3">
                <div class="card-header">
                    Belum Dibayar
                    <span class="badge badge-status bg-warning text-dark">Belum Dibayar</span>
                </div>
                <div class="card-body">
                    <ul style="padding-left:18px;">
                        <?php
                        $total_keranjang = 0;
                        foreach ($keranjang as $item):
                            $subtotal = $item['jumlah'] * $item['harga'];
                            $total_keranjang += $subtotal;
                        ?>
                            <li style="margin-bottom:6px;">
                                <?= htmlspecialchars($item['nama']) ?> -
                                <?= $item['jumlah'] ?> x Rp <?= number_format($item['harga'], 0, ',', '.') ?> =
                                <b>Rp <?= number_format($subtotal, 0, ',', '.') ?></b>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <div class="fw-bold">Total: Rp <?= number_format($total_keranjang, 0, ',', '.') ?></div>
                    <a href="http://localhost/web-umkm/pembeli/checkout/checkout.php"
                        class="btn btn-success mt-3"
                        style="background:#00ab55; border:none;">Lanjutkan Pembayaran</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- SUDAH DIBAYAR -->
        <h3 class="mb-4 mt-5">Pesanan Sudah Dibayar</h3>
        <?php if (count($transaksi) === 0): ?>
            <div class="alert alert-info">Belum ada transaksi yang dibayar.</div>
        <?php else: ?>
            <?php foreach ($transaksi as $tr): ?>
                <div class="transaksi-card card mb-3">
                    <div class="card-header">
                        ID Transaksi: #<?= htmlspecialchars($tr['transaksi_id']) ?>
                        <span class="badge badge-status <?= in_array($tr['status'], ['dibayar', 'dikirim', 'selesai']) ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= in_array($tr['status'], ['dibayar', 'dikirim', 'selesai']) ? 'Sudah Dibayar' : 'Belum Dibayar' ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2"><b>Total:</b> Rp <?= number_format($tr['total'], 0, ',', '.') ?></div>
                        <div class="mb-2" style="color:#888;">Tanggal: <?= date('d-m-Y H:i', strtotime($tr['dibuat_pada'])) ?></div>
                        <ul style="padding-left:18px;">
                            <?php
                            $stmt3 = $conn->prepare("
                                SELECT pr.nama, ip.jumlah, ip.harga_saat_pembelian
                                FROM item_pesanan ip
                                JOIN produk pr ON ip.produk_id = pr.id
                                WHERE ip.pesanan_id = ?
                            ");
                            $stmt3->bind_param("i", $tr['transaksi_id']);
                            $stmt3->execute();
                            $res3 = $stmt3->get_result();
                            while ($item = $res3->fetch_assoc()):
                            ?>
                                <li style="margin-bottom:6px;">
                                    <?= htmlspecialchars($item['nama']) ?> -
                                    <?= $item['jumlah'] ?> x Rp <?= number_format($item['harga_saat_pembelian'], 0, ',', '.') ?> =
                                    <b>Rp <?= number_format($item['jumlah'] * $item['harga_saat_pembelian'], 0, ',', '.') ?></b>
                                </li>
                            <?php endwhile;
                            $stmt3->close(); ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="text-align:center; margin-top:32px;">
            <a href="index2.php" class="btn btn-success" style="background:#00ab55; border:none; font-weight:600; font-size:16px; padding:10px 32px; border-radius:24px;">&larr; Kembali</a>
        </div>
    </div>
</body>

</html>