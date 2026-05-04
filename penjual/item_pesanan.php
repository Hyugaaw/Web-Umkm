<?php
session_start();

require_once "../koneksi.php";

// Pastikan pengguna login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$user_peran = $_SESSION["user_peran"];

// --- Ambil semua keranjang dari semua penjual, kecuali penjual itu sendiri ---
$sql_keranjang = "
    SELECT k.id AS keranjang_id, k.jumlah, k.ditambahkan_pada,
           pr.id AS produk_id, pr.nama AS nama_produk, pr.path_gambar, pr.harga, pr.penjual_id,
           u.id AS pembeli_id, u.nama AS nama_pembeli, u.email AS email_pembeli
    FROM keranjang k
    INNER JOIN produk pr ON k.produk_id = pr.id
    INNER JOIN pengguna u ON k.pembeli_id = u.id
    WHERE k.pembeli_id != pr.penjual_id
      AND NOT EXISTS (
        SELECT 1 FROM item_pesanan ip
        INNER JOIN pesanan p ON ip.pesanan_id = p.id
        WHERE ip.produk_id = k.produk_id
          AND p.pembeli_id = k.pembeli_id
    )
    ORDER BY pr.penjual_id, u.nama, k.ditambahkan_pada DESC
";
$result_keranjang = $conn->query($sql_keranjang);

$keranjang_pembeli = [];
while ($row = $result_keranjang->fetch_assoc()) {
    $penjual_id = $row['penjual_id'];
    $pembeli_id = $row['pembeli_id'];

    $keranjang_pembeli[$penjual_id][$pembeli_id]['pembeli_nama'] = $row['nama_pembeli'];
    $keranjang_pembeli[$penjual_id][$pembeli_id]['pembeli_email'] = $row['email_pembeli'];
    $keranjang_pembeli[$penjual_id][$pembeli_id]['items'][] = $row;
}

// --- Tangani tambah ke keranjang via AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_keranjang'])) {
    $produk_id = intval($_POST['produk_id']);
    $jumlah = intval($_POST['jumlah']);

    // Cek apakah item sudah ada di keranjang
    $cek = $conn->prepare("SELECT id, jumlah FROM keranjang WHERE pembeli_id=? AND produk_id=?");
    $cek->bind_param("ii", $user_id, $produk_id);
    $cek->execute();
    $res = $cek->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $new_jumlah = $row['jumlah'] + $jumlah;
        $update = $conn->prepare("UPDATE keranjang SET jumlah=? WHERE id=?");
        $update->bind_param("ii", $new_jumlah, $row['id']);
        $update->execute();
        $update->close();
    } else {
        $insert = $conn->prepare("INSERT INTO keranjang (pembeli_id, produk_id, jumlah, ditambahkan_pada) VALUES (?, ?, ?, NOW())");
        $insert->bind_param("iii", $user_id, $produk_id, $jumlah);
        $insert->execute();
        $insert->close();
    }

    $cek->close();

    echo json_encode(["status" => "success"]);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Pesanan</title>
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
            border: none;
            border-radius: 12px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: scale(1.02);
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            font-size: 0.9em;
            font-weight: 600;
            text-transform: capitalize;
        }

        footer {
            margin-top: 30px;
        }

        .card-img-top {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
            background: #f4f4f4;
            display: block;
            aspect-ratio: 4/3;
        }

        .card-body.p-2 {
            padding: 0.5rem 0.7rem !important;
        }

        .card.h-100 {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border-radius: 10px;
            border: 1px solid #eaeaea;
            background: #fff;
            transition: box-shadow 0.2s;
            min-width: 120px;
            max-width: 180px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
        }

        .card.h-100:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
        }

        .card-body .fw-bold.small.mb-1 {
            font-size: 1em;
        }

        .card-body .small {
            font-size: 0.93em;
        }

        .card-header.bg-light {
            font-size: 1em;
            padding: 0.6rem 1rem;
        }

        .row.g-2 {
            row-gap: 10px;
        }

        @media (max-width:576px) {
            .row.g-2 {
                display: flex !important;
                flex-wrap: nowrap !important;
                overflow-x: auto;
                gap: 0.5rem;
                row-gap: 0;
                margin-left: -8px;
                margin-right: -8px;
                padding-bottom: 8px;
            }

            .row.g-2::-webkit-scrollbar {
                height: 6px;
            }

            .row.g-2::-webkit-scrollbar-thumb {
                background: #e0e0e0;
                border-radius: 3px;
            }

            .card.h-100.w-100 {
                min-width: 70vw;
                max-width: 80vw;
                flex: 0 0 70vw;
                margin-right: 8px;
            }

            .col-lg-3,
            .col-md-3,
            .col-6,
            .mb-2,
            .d-flex,
            .align-items-stretch {
                flex: unset !important;
                max-width: unset !important;
                width: unset !important;
                padding-left: 0 !important;
                padding-right: 0 !important;
            }
        }

        @media (max-width:992px) {
            .col-lg-3 {
                flex: 0 0 33.3333%;
                max-width: 33.3333%;
            }
        }

        @media (max-width:768px) {

            .col-md-3,
            .col-lg-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .card-img-top {
                height: 60px;
            }

            .card.h-100 {
                min-width: 100px;
                max-width: 100%;
            }
        }

        @media (max-width:576px) {

            .col-md-3,
            .col-lg-3 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .card-img-top {
                height: 44vw;
                min-height: 90px;
                max-height: 180px;
            }

            .card.h-100 {
                min-width: 100%;
                max-width: 100%;
            }
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
                    <li class="nav-item"><a class="nav-link active" href="item_pesanan.php">Item Pesanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="kelola-transaksi.php">Transaksi</a></li>
                    <li class="nav-item"><a class="nav-link" href="diskon.php">Diskon</a></li>
                    <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container my-4">
        <h2 class="section-title">Item Pesanan</h2>

        <?php if (count($keranjang_pembeli) > 0): ?>
            <?php foreach ($keranjang_pembeli as $penjual_id => $pembelis): ?>
                <div class="mb-4">
                    <h5 class="mb-3 text-secondary">Penjual ID: <?= $penjual_id ?></h5>
                    <?php foreach ($pembelis as $pembeli_id => $data):
                        $total_keranjang_pembeli = 0;
                    ?>
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <strong><?= htmlspecialchars($data['pembeli_nama']) ?></strong> (<?= htmlspecialchars($data['pembeli_email']) ?>)
                            </div>
                            <div class="card-body p-2">
                                <div class="row g-2">
                                    <?php foreach ($data['items'] as $item):
                                        $subtotal = $item['jumlah'] * $item['harga'];
                                        $total_keranjang_pembeli += $subtotal;
                                    ?>
                                        <div class="col-lg-3 col-md-3 col-6 mb-2 d-flex align-items-stretch">
                                            <div class="card h-100 w-100">
                                                <?php
                                                $gambar_path = '../img/no-image.png';
                                                if (!empty($item['path_gambar'])) {
                                                    $possible_paths = [
                                                        "../" . $item['path_gambar'],
                                                        "../gambar/" . basename($item['path_gambar']),
                                                        $item['path_gambar']
                                                    ];
                                                    foreach ($possible_paths as $test_path) {
                                                        if (file_exists($test_path)) {
                                                            $gambar_path = $test_path;
                                                            break;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($gambar_path) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['nama_produk']) ?>" onerror="this.src='../img/no-image.png'">
                                                <div class="card-body p-2">
                                                    <div class="fw-bold small mb-1"><?= htmlspecialchars($item['nama_produk']) ?></div>
                                                    <div class="small">Jumlah: <strong><?= htmlspecialchars($item['jumlah']) ?></strong></div>
                                                    <div class="small">Harga: <strong>Rp <?= number_format($item['harga'], 0, ',', '.') ?></strong></div>
                                                    <div class="small">Total: <strong class="text-success">Rp <?= number_format($subtotal, 0, ',', '.') ?></strong></div>
                                                    <div class="small text-muted">Ditambahkan: <?= date('d M Y, H:i', strtotime($item['ditambahkan_pada'])) ?> WIB</div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2 text-end">
                                    <span class="fw-bold">Total Keranjang Pembeli: </span>
                                    <span class="fw-bold text-success">Rp <?= number_format($total_keranjang_pembeli, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Belum ada item pesanan untuk produk Anda. Item pesanan akan muncul setelah pembeli belum melakukan checkout saja.
            </div>
        <?php endif; ?>

        <footer class="text-center py-3 mt-4 border-top">
            &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function tambahKeranjang(produkId, jumlah, btn) {
            btn.disabled = true;
            fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "tambah_keranjang=1&produk_id=" + produkId + "&jumlah=" + jumlah
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("Berhasil menambahkan ke keranjang!");
                        location.reload();
                    } else {
                        alert("Gagal menambahkan ke keranjang.");
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Terjadi kesalahan.");
                    btn.disabled = false;
                });
        }
    </script>
</body>

</html>