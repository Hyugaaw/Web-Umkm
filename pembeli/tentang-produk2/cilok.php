<?php
require_once dirname(__DIR__, 2) . '/koneksi.php';
$nama_produk = 'Cilok';
$stmt = $conn->prepare("SELECT * FROM produk WHERE nama = ? LIMIT 1");
$stmt->bind_param("s", $nama_produk);
$stmt->execute();
$result = $stmt->get_result();
$produk = $result->fetch_assoc();
$stmt->close();
$conn->close();
if (!$produk) {
    die('Produk tidak ditemukan.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Cilok - Detail Produk</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f7f7f7;
            margin: 0;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.10);
            padding: 32px 32px 32px 32px;
            display: flex;
            gap: 36px;
        }

        .product-img {
            width: 340px;
            height: 340px;
            object-fit: cover;
            border-radius: 14px;
            background: #f2f2f2;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.06);
        }

        .product-detail {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .product-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #222;
        }

        .product-info {
            font-size: 1.15rem;
            color: #444;
            margin-bottom: 18px;
        }

        .product-price {
            font-size: 1.5rem;
            color: #00ab55;
            font-weight: 700;
            margin-bottom: 22px;
        }

        .btn-container {
            display: flex;
            gap: 18px;
            margin-bottom: 18px;
        }

        .btn-tambah,
        .btn-beli {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 17px;
            cursor: pointer;
            text-decoration: none;
            transition: 0.2s;
        }

        .btn-tambah {
            background: #00ab55;
            color: #fff;
        }

        .btn-tambah:hover {
            background: #008a40;
        }

        .btn-beli {
            background: #f39c12;
            color: #fff;
        }

        .btn-beli:hover {
            background: #d68910;
        }

        .btn-tambah svg,
        .btn-beli svg {
            width: 20px;
            height: 20px;
        }

        .back-link {
            display: inline-block;
            padding: 10px 28px;
            background: #888;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: 0.2s;
            margin: 0 auto;
        }

        @media (max-width: 900px) {
            .container {
                flex-direction: column;
                align-items: center;
                padding: 24px 8px;
            }

            .product-img {
                width: 100%;
                max-width: 340px;
                height: 220px;
            }

            .product-detail {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
    <?php
    $gambar_produk = !empty($produk['path_gambar']) ? '../' . $produk['path_gambar'] : '../img/no-image.png';
    ?>
    <img class="product-img" src="<?= htmlspecialchars($gambar_produk) ?>" alt="Cilok">
        <div class="product-detail">
            <div class="product-title"><?= htmlspecialchars($produk['nama'] ?? 'Cilok') ?></div>
            <div class="product-info">
                <?= htmlspecialchars($produk['deskripsi'] ?? 'Cilok kenyal khas Bandung, disajikan dengan bumbu kacang gurih, cocok untuk camilan kapan saja.') ?>
            </div>
            <div style="font-size:1.1rem; color:#009688; font-weight:600; margin-bottom:8px;">
                Stok tersedia: <span id="stok-tersedia"><?= (int)$produk['stok'] ?></span>
            </div>
            <div class="product-price">Rp <span id="harga-produk"><?= number_format($produk['harga'], 0, ',', '.') ?></span></div>
            <div style="margin-bottom:18px; display:flex; align-items:center; gap:10px;">
                <label for="jumlah-barang" style="font-weight:600;">Jumlah:</label>
                <button type="button" onclick="ubahJumlah(-1)" style="width:32px; height:32px; font-size:20px; border-radius:6px; border:1px solid #ccc; background:#eee; cursor:pointer;">-</button>
                <input type="text" id="jumlah-barang" name="jumlah-barang" value="1" readonly style="width:70px; padding:7px 10px; border-radius:6px; border:1px solid #ccc; font-size:16px; text-align:center; background:#f9f9f9; cursor:default;">
                <button type="button" onclick="ubahJumlah(1)" style="width:32px; height:32px; font-size:20px; border-radius:6px; border:1px solid #ccc; background:#eee; cursor:pointer;">+</button>
            </div>
            <div class="btn-container">
                <form method="post" action="./keranjang/tambah-keranjang.php" style="display:inline;" id="form-keranjang">
                    <input type="hidden" name="id_produk" value="<?= (int)$produk['id'] ?>">
                    <input type="hidden" name="nama_produk" value="<?= htmlspecialchars($produk['nama'] ?? 'Cilok') ?>">
                    <input type="hidden" name="harga" value="<?= $produk['harga'] ?>">
                    <input type="hidden" name="jumlah" id="input-jumlah-barang" value="1">
                    <input type="hidden" name="gambar" value="<?= htmlspecialchars($produk['path_gambar']) ?>">
                    <input type="hidden" name="file_produk" value="cilok.php">
                    <button type="button" class="btn-tambah" onclick="submitKeranjang()">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 1a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 .485.379L3.89 3H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                        </svg>
                        Tambah Keranjang
                    </button>
                </form>
                <form method="post" action="./checkout/checkout.php" style="display:inline;">
                    <input type="hidden" name="id_produk" value="<?= htmlspecialchars($produk['slug'] ?? 'cilok') ?>">
                    <input type="hidden" name="nama_produk" value="<?= htmlspecialchars($produk['nama'] ?? 'Cilok') ?>">
                    <input type="hidden" name="harga" value="<?= $produk['harga'] ?>">
                    <input type="hidden" name="jumlah" id="input-jumlah-barang-checkout" value="1">
                    <input type="hidden" name="gambar" value="<?= htmlspecialchars($produk['path_gambar']) ?>">
                    <input type="hidden" name="file_produk" value="cilok.php">
                    <button type="submit" class="btn-beli" formaction="./checkout/checkout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M6 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1h3.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                        </svg>
                        Beli Sekarang
                    </button>
                </form>
            </div>
            <a href="./index2.php" class="back-link">Kembali</a>
        </div>
    </div>
    <script>
        var hargaProduk = <?= (int)$produk['harga'] ?>;
        var stokAwal = <?= (int)$produk['stok'] ?>;
        function updateHargaDanStok() {
            var jumlah = parseInt(document.getElementById('jumlah-barang').value) || 1;
            document.getElementById('harga-produk').textContent = (hargaProduk * jumlah).toLocaleString('id-ID');
            var stokTersedia = stokAwal - (jumlah - 1);
            if (stokTersedia < 0) stokTersedia = 0;
            document.getElementById('stok-tersedia').textContent = stokTersedia;
            var inputHarga = document.getElementById('input-harga-barang');
            if(inputHarga) inputHarga.value = hargaProduk;
            var inputHargaCheckout = document.getElementById('input-harga-barang-checkout');
            if(inputHargaCheckout) inputHargaCheckout.value = hargaProduk;
        }
        function ubahJumlah(delta) {
            var input = document.getElementById('jumlah-barang');
            var val = parseInt(input.value) || 1;
            val += delta;
            if (val < 1) val = 1;
            if (val > stokAwal) val = stokAwal;
            input.value = val;
            var inputHidden = document.getElementById('input-jumlah-barang');
            if(inputHidden) inputHidden.value = val;
            var inputCheckout = document.getElementById('input-jumlah-barang-checkout');
            if(inputCheckout) inputCheckout.value = val;
            updateHargaDanStok();
        }
        function submitKeranjang() {
            document.getElementById('form-keranjang').submit();
        }
        document.addEventListener('DOMContentLoaded', function() {
            updateHargaDanStok();
        });
    </script>
</body>

</html>
