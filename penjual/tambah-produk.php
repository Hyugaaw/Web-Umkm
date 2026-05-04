<?php
session_start();
require_once "../koneksi.php";

// Hanya penjual yang bisa masuk
if (!isset($_SESSION["user_id"]) || $_SESSION["user_peran"] !== "penjual") {
    header("Location: ../auth/login.php");
    exit();
}

$penjual_id = $_SESSION["user_id"];
$pesan = "";

// Ambil semua kategori (nama, jenis, harga)
$kategoriList = [];
$stmt = $conn->prepare("SELECT id, nama, jenis, harga FROM kategori ORDER BY nama ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $nama_gambar = str_replace('-', ' ', ucfirst($row['nama'])) . ".jpeg";
    $row['gambar'] = $nama_gambar;
    $kategoriList[] = $row;
}
$kategori_kosong = count($kategoriList) === 0;

// Buat daftar produk cepat dari kategori
$products = [];
foreach ($kategoriList as $kat) {
    $products[] = [
        'nama' => $kat['nama'],
        'gambar' => $kat['gambar'],
        'harga' => isset($kat['harga']) ? (int)$kat['harga'] : 0,
        'kategori_jenis' => $kat['jenis'],
        'kategori_id' => $kat['id']
    ];
}

// Tambah produk cepat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$kategori_kosong) {
    $selectedIndex = (int)$_POST['produk_index'];
    $kategori_id   = (int)$_POST['kategori_id'];
    $status        = $_POST['status'];
    $stok          = max(1, (int)$_POST['stok']);

    if (!isset($products[$selectedIndex])) {
        $pesan = "Produk tidak valid!";
    } else {
        $produk = $products[$selectedIndex];
        $nama   = ucwords(str_replace('-', ' ', $produk['nama']));
        $harga  = $produk['harga'];
        $gambar = $produk['gambar'];
        $path_gambar = "";

        // Cari gambar di folder tertentu
        $sourcePaths = [
            "../penjual/" . $gambar,
            "../pembeli/" . $gambar,
            "../predefined_images/" . $gambar
        ];
        $found = false;
        foreach ($sourcePaths as $sourcePath) {
            if (file_exists($sourcePath)) {
                $newFileName = uniqid("produk_", true) . "." . pathinfo($gambar, PATHINFO_EXTENSION);
                $uploadDir = "../uploads/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $destPath = $uploadDir . $newFileName;
                copy($sourcePath, $destPath);
                $path_gambar = "uploads/" . $newFileName;
                $found = true;
                break;
            }
        }

        // Jika belum ketemu, coba cari gambar mirip di uploads
        if (!$found) {
            $uploadsDir = dirname(__DIR__) . '/uploads/';
            $target = strtolower(preg_replace('/[\s\-_]/', '', pathinfo($gambar, PATHINFO_FILENAME)));
            $ext = strtolower(pathinfo($gambar, PATHINFO_EXTENSION));
            if (is_dir($uploadsDir)) {
                foreach (scandir($uploadsDir) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $fileNoExt = strtolower(preg_replace('/[\s\-_]/', '', pathinfo($file, PATHINFO_FILENAME)));
                    $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if ($fileNoExt === $target && $fileExt === $ext) {
                        $path_gambar = "uploads/" . $file;
                        $found = true;
                        break;
                    }
                }
            }
        }

        // Jika tetap tidak ketemu, pakai default
        if (!$path_gambar) {
            $path_gambar = "uploads/no-image.jpg";
        }

        // Simpan ke database
        $stmt = $conn->prepare("INSERT INTO produk 
            (penjual_id, nama, deskripsi, harga, stok, kategori_id, status, path_gambar, dibuat_pada, diperbarui_pada)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $deskripsi = "Deskripsi untuk " . $nama;
        $stmt->bind_param("issdiiss", $penjual_id, $nama, $deskripsi, $harga, $stok, $kategori_id, $status, $path_gambar);
        if ($stmt->execute()) {
            $pesan = "✅ Produk berhasil ditambahkan!";
        } else {
            $pesan = "❌ Gagal menambahkan produk: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk Cepat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            max-width: 650px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #28a745;
            border: none;
        }

        .btn-primary:hover {
            background-color: #218838;
        }
    </style>
</head>

<body>
    <div class="container my-5">
        <div class="card p-4">
            <h3 class="text-center text-success mb-4">Tambah Produk Cepat</h3>

            <?php if (!empty($pesan)): ?>
                <div class="alert alert-info text-center"><?= htmlspecialchars($pesan) ?></div>
            <?php endif; ?>

            <?php if ($kategori_kosong): ?>
                <div class="alert alert-warning text-center">
                    Belum ada kategori. Silakan tambahkan kategori terlebih dahulu di
                    <a href="kategori.php">halaman kategori</a>.
                </div>
            <?php else: ?>
                <form method="POST">
                    <!-- Pilih produk -->
                    <div class="mb-3">
                        <label for="produk_index" class="form-label">Pilih Produk</label>
                        <select class="form-select" id="produk_index" name="produk_index" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach ($products as $index => $p): ?>
                                <option
                                    value="<?= $index ?>"
                                    data-kategori-id="<?= $p['kategori_id'] ?>"
                                    data-harga="<?= $p['harga'] ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('-', ' ', $p['nama']))) ?>
                                    <?= $p['harga'] ? ' - Rp ' . number_format($p['harga'], 0, ',', '.') : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Kategori -->
                    <div class="mb-3">
                        <label for="kategori_id" class="form-label">Kategori</label>
                        <select class="form-select" id="kategori_id" name="kategori_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= $kat['id'] ?>"><?= htmlspecialchars($kat['jenis']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Harga -->
                    <div class="mb-3">
                        <label for="harga_produk" class="form-label">Harga</label>
                        <input type="text" class="form-control" id="harga_produk" readonly>
                    </div>

                    <!-- Stok -->
                    <div class="mb-3">
                        <label for="stok" class="form-label">Stok</label>
                        <input type="number" class="form-control" id="stok" name="stok" min="1" value="10" required>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="aktif">Aktif</option>
                            <option value="tidak aktif">Tidak Aktif</option>
                        </select>
                    </div>

                    <!-- Tombol -->
                    <div class="text-center">
                        <button type="submit" class="btn btn-primary px-4">Tambah Produk</button>
                        <a href="kelola-produk.php" class="btn btn-secondary px-4">Kembali</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const produkSelect = document.getElementById('produk_index');
        const kategoriSelect = document.getElementById('kategori_id');
        const hargaInput = document.getElementById('harga_produk');

        produkSelect.addEventListener('change', function() {
            const opt = produkSelect.options[produkSelect.selectedIndex];
            const kategoriId = opt.getAttribute('data-kategori-id');
            const harga = opt.getAttribute('data-harga');

            if (kategoriId) {
                kategoriSelect.value = kategoriId;
                for (let option of kategoriSelect.options) {
                    if (!option.value) continue;
                    option.style.display = (option.value === kategoriId) ? '' : 'none';
                }
            } else {
                kategoriSelect.value = '';
                for (let option of kategoriSelect.options) option.style.display = '';
            }
            hargaInput.value = harga ? parseInt(harga).toLocaleString('id-ID') : '';
        });

        window.addEventListener('DOMContentLoaded', () => {
            kategoriSelect.value = '';
            for (let opt of kategoriSelect.options) opt.style.display = '';
            hargaInput.value = '';
        });
    </script>
</body>

</html>