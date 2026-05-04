<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$penjual_id = $_SESSION["user_id"];
$pesan = "";

// Ambil ID produk dari query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: kelola-produk.php");
    exit();
}
$produk_id = (int)$_GET['id'];

// Ambil data produk yang akan diedit
$stmt = $conn->prepare("SELECT id, nama, deskripsi, harga, stok, kategori_id, status FROM produk WHERE id = ? AND penjual_id = ?");
$stmt->bind_param("ii", $produk_id, $penjual_id);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();

if (!$produk) {
    header("Location: kelola-produk.php");
    exit();
}

// Ambil semua kategori untuk dropdown
$kategoriList = [];
$kategoriStmt = $conn->prepare("SELECT id, nama, jenis FROM kategori ORDER BY nama ASC");
$kategoriStmt->execute();
$kategoriResult = $kategoriStmt->get_result();
while ($row = $kategoriResult->fetch_assoc()) {
    $kategoriList[] = $row;
}
$kategoriStmt->close();

// Proses form edit produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $harga = isset($_POST['harga']) ? (float)$_POST['harga'] : 0;
    $stok = isset($_POST['stok']) ? (int)$_POST['stok'] : 1;
    if ($stok < 1) $stok = 1;
    $stmt = $conn->prepare("UPDATE produk SET harga=?, stok=?, diperbarui_pada=NOW() WHERE id=? AND penjual_id=?");
    $stmt->bind_param("diii", $harga, $stok, $produk_id, $penjual_id);
    if ($stmt->execute()) {
        $pesan = "Produk berhasil diperbarui!";
        $produk['harga'] = $harga;
        $produk['stok'] = $stok;
    } else {
        $pesan = "Gagal memperbarui produk: " . $conn->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container my-5">
        <h2 class="text-center mb-4">Edit Produk</h2>
        <?php if (!empty($pesan)): ?>
            <div class="alert alert-info"><?= htmlspecialchars($pesan) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Produk</label>
                <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($produk['nama']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="deskripsi" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" readonly><?= htmlspecialchars($produk['deskripsi']) ?></textarea>
            </div>
            <div class="mb-3">
                <label for="harga" class="form-label">Harga</label>
                <input type="number" class="form-control" id="harga" name="harga" min="0" value="<?= htmlspecialchars($produk['harga']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="stok" class="form-label">Stok</label>
                <input type="number" class="form-control" id="stok" name="stok" min="1" value="<?= htmlspecialchars($produk['stok']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="kategori_id" class="form-label">Kategori</label>
                <select class="form-select" id="kategori_id" name="kategori_id" disabled>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?= $kat['id'] ?>" <?= $produk['kategori_id'] == $kat['id'] ? 'selected' : '' ?>><?= htmlspecialchars(ucwords(str_replace('_', ' ', $kat['jenis']))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" disabled>
                    <option value="aktif" <?= $produk['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="tidak aktif" <?= $produk['status'] == 'tidak aktif' ? 'selected' : '' ?>>Tidak Aktif</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="kelola-produk.php" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Daftar harga produk sesuai file PHP -->
    <script>
        const hargaProduk = {
            'anyaman-rajutan-tangan-1': 75000,
            'anyaman-rajutan-tangan-2': 85000,
            'anyaman-rajutan-tangan-3': 65000,
            'anyaman-rajutan-tangan-4': 90000,
            'bala-bakwan': 2000,
            'basreng': 2000,
            'basreng-2': 2000,
            'cilok': 5000,
            'cimol': 10000,
            'comet': 2000,
            'cuhcur': 3000,
            'es-lilin': 2000,
            'gehu': 5000,
            'keripik-singkong': 5000,
            'keripik-singkong-2': 7000,
            'keripik-pisang': 10000,
            'keripik-pisang-2': 10000,
            'kerajinan-tangan': 45000,
            'kerajinan-tangan-2': 35000,
            'kesed-rumah': 25000,
            'kesed-rumah-2': 30000,
            'martabak': 45000,
            'pisang-aromat': 20000,
            'raginang': 15000,
            'rajut-botol': 25000,
            'rajut-sampah-kopi': 30000,
            'rempeyek-1': 20000,
            'rempeyek-2': 22000,
            'rempeyek-3': 18000,
            'telor-gabus': 20000
        };
    </script>
</body>

</html>