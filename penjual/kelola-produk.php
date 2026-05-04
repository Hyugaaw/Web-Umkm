<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$penjual_id = $_SESSION["user_id"];

// ==========================
// 🔹 Hapus produk stok 0 otomatis
// ==========================
$hapus_otomatis = $conn->prepare("DELETE FROM produk WHERE penjual_id = ? AND stok <= 0");
$hapus_otomatis->bind_param("i", $penjual_id);
$hapus_otomatis->execute();
$hapus_otomatis->close();

// ==========================
// 🔹 Handle delete manual
// ==========================
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);

    // Pastikan produk yang dihapus milik penjual yang sedang login
    $cekProduk = $conn->prepare("SELECT id FROM produk WHERE id = ? AND penjual_id = ?");
    $cekProduk->bind_param("ii", $id, $penjual_id);
    $cekProduk->execute();
    $cekProduk->store_result();

    if ($cekProduk->num_rows > 0) {
        // 1️⃣ Hapus dari item_pesanan (agar tidak melanggar foreign key)
        $hapusItemPesanan = $conn->prepare("
            DELETE ip FROM item_pesanan ip
            JOIN produk p ON ip.produk_id = p.id
            WHERE p.id = ? AND p.penjual_id = ?
        ");
        $hapusItemPesanan->bind_param("ii", $id, $penjual_id);
        $hapusItemPesanan->execute();
        $hapusItemPesanan->close();

        // 2️⃣ Hapus produk dari semua keranjang pembeli — hanya untuk produk milik penjual ini
        $hapusKeranjangPembeli = $conn->prepare("
            DELETE k FROM keranjang k 
            JOIN produk p ON k.produk_id = p.id 
            WHERE p.id = ? AND p.penjual_id = ?
        ");
        $hapusKeranjangPembeli->bind_param("ii", $id, $penjual_id);
        $hapusKeranjangPembeli->execute();
        $hapusKeranjangPembeli->close();

        // 3️⃣ Hapus dari user_cart hanya untuk produk milik penjual ini
        $hapusUserCart = $conn->prepare("
            DELETE uc FROM user_cart uc
            JOIN produk p ON uc.produk_id = p.id
            WHERE p.id = ? AND p.penjual_id = ?
        ");
        $hapusUserCart->bind_param("ii", $id, $penjual_id);
        $hapusUserCart->execute();
        $hapusUserCart->close();

        // 4️⃣ Hapus produk dari tabel produk
        $stmt = $conn->prepare("DELETE FROM produk WHERE id = ? AND penjual_id = ?");
        $stmt->bind_param("ii", $id, $penjual_id);
        if ($stmt->execute()) {
            header("Location: kelola-produk.php");
            exit();
        } else {
            echo "Error deleting record: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "<div style='color:red;text-align:center;margin-top:20px;'>❌ Produk tidak ditemukan atau bukan milik Anda.</div>";
    }
    $cekProduk->close();
}

// ==========================
// 🔹 Handle delete ALL products for this penjual
// ==========================
if (isset($_POST['hapus_semua']) && $_POST['hapus_semua'] == '1') {
    $conn->begin_transaction();
    try {
        $namesStmt = $conn->prepare("SELECT id, nama FROM produk WHERE penjual_id = ?");
        $namesStmt->bind_param("i", $penjual_id);
        $namesStmt->execute();
        $resNames = $namesStmt->get_result();
        $produkIds = [];
        while ($r = $resNames->fetch_assoc()) {
            $produkIds[] = (int)$r['id'];
        }
        $namesStmt->close();

        if (!empty($produkIds)) {
            $inIds = implode(',', array_map('intval', $produkIds));

            // 1️⃣ Hapus dari item_pesanan
            $conn->query("
                DELETE ip FROM item_pesanan ip
                JOIN produk p ON ip.produk_id = p.id
                WHERE p.penjual_id = $penjual_id AND p.id IN ($inIds)
            ");

            // 2️⃣ Hapus dari keranjang
            $conn->query("
                DELETE k FROM keranjang k 
                JOIN produk p ON k.produk_id = p.id 
                WHERE p.penjual_id = $penjual_id AND p.id IN ($inIds)
            ");

            // 3️⃣ Hapus dari user_cart
            $conn->query("
                DELETE uc FROM user_cart uc
                JOIN produk p ON uc.produk_id = p.id
                WHERE p.penjual_id = $penjual_id AND p.id IN ($inIds)
            ");
        }

        // 4️⃣ Hapus produk milik penjual ini
        $delStmt = $conn->prepare("DELETE FROM produk WHERE penjual_id = ?");
        $delStmt->bind_param("i", $penjual_id);
        $delStmt->execute();
        $delStmt->close();

        $conn->commit();
        header("Location: kelola-produk.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Terjadi kesalahan saat menghapus semua produk: " . $e->getMessage();
        exit();
    }
}

// ==========================
// 🔹 Ambil data produk aktif
// ==========================
$sql = "SELECT id, nama, deskripsi, harga, stok, path_gambar 
        FROM produk 
        WHERE penjual_id = ? AND status = 'aktif'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $penjual_id);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Produk</title>
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
            display: flex;
            flex-direction: column;
        }

        .card h5,
        .card h6 {
            font-weight: 600;
        }

        .section-title {
            margin-top: 20px;
            margin-bottom: 20px;
            font-weight: 700;
            color: #333;
            text-align: center;
        }

        .product-img {
            height: 180px;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
        }

        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-footer {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .btn-tambah-center {
            display: flex;
            justify-content: center;
            margin-top: 10px;
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
                    <li class="nav-item"><a class="nav-link" href="diskon.php">Diskon</a></li>
                    <li class="nav-item"><a class="nav-link text-danger fw-bold" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Konten Utama -->
    <div class="container my-4">
        <h2 class="section-title">Kelola Produk</h2>

        <div class="btn-tambah-center mb-3" style="gap:10px; display:flex; justify-content:center;">
            <a href="tambah-produk.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Tambah Stok</a>
            <form method="post" action="kelola-produk.php" onsubmit="return confirm('Yakin ingin menghapus SEMUA produk Anda? Aksi ini tidak dapat dibatalkan. Semua keranjang pengguna yang merujuk produk Anda juga akan dihapus.');" style="display:inline-block;">
                <input type="hidden" name="hapus_semua" value="1">
                <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Hapus Semua Produk</button>
            </form>
        </div>

        <?php if (count($products) > 0): ?>
            <div class="row g-4">
                <?php foreach ($products as $prod): ?>
                    <div class="col-md-2">
                        <div class="card h-100">
                            <img src="../<?= htmlspecialchars($prod['path_gambar']) ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($prod['nama']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $prod['nama']))) ?></h5>
                                <p class="card-text"><?= htmlspecialchars($prod['deskripsi']) ?></p>
                                <p>Stok: <?= htmlspecialchars($prod['stok']) ?></p>
                                <p class="fw-bold text-success text-center">Rp <?= number_format($prod['harga'], 0, ',', '.') ?></p>
                                <div class="card-footer">
                                    <a href="edit-produk.php?id=<?= $prod['id'] ?>" class="btn btn-warning btn-sm w-100">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="kelola-produk.php?hapus=<?= $prod['id'] ?>" class="btn btn-danger btn-sm w-100" onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center">Belum ada produk yang ditambahkan.</div>
        <?php endif; ?>
    </div>

    <footer class="text-center py-3 mt-4 border-top">
        &copy; <?= date('Y') ?> Marketplace Penjual. Semua hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>