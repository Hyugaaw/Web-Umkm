<?php
session_start();
require_once "../koneksi.php";

// Pastikan login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_nama = $_SESSION["user_nama"] ?? '';
$user_email = $_SESSION["user_email"] ?? '';
$user_peran = $_SESSION["user_peran"] ?? '';

/* ================================================================
   FILTER & SORT
================================================================ */
$sort = $_GET['sort'] ?? 'terlaris';

switch ($sort) {
    case 'harga_terendah':
        $orderBy = "p.harga ASC";
        break;
    case 'harga_tertinggi':
        $orderBy = "p.harga DESC";
        break;
    case 'stok_terbanyak':
        $orderBy = "p.stok DESC";
        break;
    default: // terlaris
        $orderBy = "p.nama ASC";
        break;
}

/* ================================================================
   QUERY PRODUK PALING LARIS — UNTUK TAMPILAN LEADERBOARD
================================================================ */
$query = "
    SELECT 
        p.id,
        p.nama,
        p.harga,
        p.path_gambar,
        COALESCE(p.stok, 0) AS stok,
        COALESCE(p.stok_awal, 0) AS stok_awal,
        u.nama AS nama_penjual
    FROM produk p
    LEFT JOIN pengguna u 
        ON u.id = p.penjual_id AND u.peran = 'penjual'
    WHERE 
        p.status = 'aktif'
    ORDER BY $orderBy
    LIMIT 12
";

$result = $conn->query($query);
$produk_laris = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Paling Laris</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        header {
            background: linear-gradient(90deg, #00ab55, #00d27f);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        header img {
            height: 42px;
        }

        header span {
            font-size: 18px;
            font-weight: 600;
        }

        .filter-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 25px;
        }

        .filter-bar select {
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            background: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-bar select:hover {
            border-color: #00ab55;
        }

        .produk-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            z-index: 0;
        }

        .produk-card.top-3 {
            background: linear-gradient(145deg, #fffde7, #fff8e1);
        }

        .produk-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.1);
        }

        .produk-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
            transition: transform 0.4s ease;
        }

        .produk-card:hover img {
            transform: scale(1.08);
        }

        .rank-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 13px;
            color: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            z-index: 5;
        }

        .rank-1 {
            background: linear-gradient(45deg, #ff5722, #ff8a50);
        }

        .rank-2 {
            background: linear-gradient(45deg, #ff9800, #ffc107);
        }

        .rank-3 {
            background: linear-gradient(45deg, #ffc107, #fff176);
            color: #333;
        }

        .crown-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            font-size: 22px;
            color: gold;
            text-shadow: 0 0 4px rgba(0, 0, 0, 0.3);
        }

        .produk-info {
            padding: 14px 16px 18px;
            position: relative;
        }

        .produk-info h5 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 6px;
        }

        .produk-info p {
            margin: 3px 0;
            font-size: 14px;
            color: #666;
        }

        .badge-penjual {
            background: #e8f5e9;
            color: #00ab55;
            font-size: 13px;
            border-radius: 20px;
            padding: 4px 10px;
            display: inline-block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .harga {
            font-weight: bold;
            color: #00ab55;
            font-size: 15px;
            margin-top: 6px;
        }

        .back-btn {
            background: #00ab55;
            color: white;
            font-weight: 600;
            border: none;
            padding: 10px 28px;
            border-radius: 25px;
            transition: 0.25s ease;
            text-decoration: none;
        }

        .back-btn:hover {
            background: #008a40;
            color: white;
        }
    </style>
</head>

<body>
    <header>
        <img src="logo.png" alt="Logo UMKM Market">
        <span>🔥 Produk Paling Laris</span>
    </header>

    <div class="container py-5">
        <div class="filter-bar">
            <form method="get" class="d-flex">
                <label for="sort" class="me-2 mt-1 fw-semibold">Urutkan:</label>
                <select name="sort" id="sort" onchange="this.form.submit()">
                    <option value="terlaris" <?= $sort == 'terlaris' ? 'selected' : '' ?>>Terlaris</option>
                    <option value="harga_terendah" <?= $sort == 'harga_terendah' ? 'selected' : '' ?>>Harga Terendah</option>
                    <option value="harga_tertinggi" <?= $sort == 'harga_tertinggi' ? 'selected' : '' ?>>Harga Tertinggi</option>
                    <option value="stok_terbanyak" <?= $sort == 'stok_terbanyak' ? 'selected' : '' ?>>Stok Terbanyak</option>
                </select>
            </form>
        </div>

        <?php if (count($produk_laris) === 0): ?>
            <div class="alert alert-info text-center">
                Belum ada data produk.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php
                $rank = 1;
                foreach ($produk_laris as $p):
                    $img = !empty($p['path_gambar']) ? "../" . htmlspecialchars($p['path_gambar']) : "../img/no-image.png";
                    $stok = (int)$p['stok'];
                    $top3 = $rank <= 3 ? 'top-3' : '';
                ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="produk-card <?= $top3 ?>">
                            <div class="rank-badge rank-<?= $rank <= 3 ? $rank : '' ?>">#<?= $rank ?></div>
                            <?php if ($rank <= 3): ?>
                                <i class="fa-solid fa-crown crown-icon"></i>
                            <?php endif; ?>
                            <img src="<?= $img ?>" onerror="this.src='../img/no-image.png'" alt="<?= htmlspecialchars($p['nama']) ?>">
                            <div class="produk-info">
                                <span class="badge-penjual"><?= htmlspecialchars($p['nama_penjual'] ?? 'Tidak diketahui') ?></span>
                                <h5><?= htmlspecialchars($p['nama']) ?></h5>
                                <p>Harga: <b>Rp <?= number_format($p['harga'], 0, ',', '.') ?></b></p>
                                <p>Stok tersisa: <?= $stok ?> pcs</p>
                            </div>
                        </div>
                    </div>
                <?php
                    $rank++;
                endforeach;
                ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-5">
            <a href="<?= $user_peran === 'penjual' ? 'dashboard_penjual.php' : 'index2.php' ?>" class="back-btn">&larr; Kembali ke Dashboard</a>
        </div>
    </div>
</body>

</html>