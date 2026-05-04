<?php
include '../../koneksi.php';
session_start();
$is_logged_in = isset($_SESSION['id_pembeli']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Rajut Sampah Kopi - Detail Produk</title>
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
            padding: 32px;
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
        <img class="product-img" src="../Rajut Sampah Kopi.jpeg" alt="Rajut Sampah Kopi">
        <div class="product-detail">
            <div class="product-title">Rajut Sampah Kopi</div>
            <div class="product-info">
                Rajut Sampah Kopi merupakan kerajinan tangan kreatif yang memanfaatkan limbah bungkus kopi menjadi produk rajutan unik. 
                Selain ramah lingkungan, hasilnya memiliki nilai seni tinggi dan cocok untuk dekorasi atau wadah serbaguna.
            </div>
            <div class="product-price">Rp <span id="harga-produk">30.000</span></div>
            <div style="margin-bottom:18px; display:flex; align-items:center; gap:10px;">
                <label for="jumlah-barang" style="font-weight:600;">Jumlah:</label>
                <button type="button" onclick="ubahJumlah(-1)" style="width:32px; height:32px; font-size:20px; border-radius:6px; border:1px solid #ccc; background:#eee; cursor:pointer;">-</button>
                <input type="text" id="jumlah-barang" name="jumlah-barang" value="1" readonly style="width:70px; padding:7px 10px; border-radius:6px; border:1px solid #ccc; font-size:16px; text-align:center; background:#f9f9f9; cursor:default;">
                <button type="button" onclick="ubahJumlah(1)" style="width:32px; height:32px; font-size:20px; border-radius:6px; border:1px solid #ccc; background:#eee; cursor:pointer;">+</button>
            </div>
            <div class="btn-container">
<?php if ($is_logged_in): ?>
<form method="post" action="../keranjang/tambah-keranjang.php" style="display:inline;">
    <input type="hidden" name="id_produk" value="rajut-sampah-kopi">
    <input type="hidden" name="nama_produk" value="Rajut Sampah Kopi">
    <input type="hidden" name="jumlah" id="input-jumlah-barang" value="1">
    <input type="hidden" name="gambar" value="../Rajut Sampah Kopi.jpeg">
    <input type="hidden" name="file_produk" value="rajut-sampah-kopi.php">
    <input type="hidden" name="harga" value="30000">
    <button type="submit" class="btn-tambah">
        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
            <path d="M0 1a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 .485.379L3.89 3H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
        </svg>
        Tambah Keranjang
    </button>
</form>
<?php else: ?>
<a href="../auth/login.php" class="btn-tambah">
    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
        <path d="M0 1a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 .485.379L3.89 3H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
    </svg>
    Tambah Keranjang
</a>
<?php endif; ?>
                <a class="btn-beli" href="../auth/login.php">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M6 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1h3.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                    </svg>
                    Beli Sekarang
                </a>
            </div>
            <a href="../index.php" class="back-link">Kembali</a>
        </div>
    </div>

    <script>
        function ubahJumlah(delta) {
            var isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
            if (!isLoggedIn) {
                window.location.href = '../auth/login.php';
                return;
            }
            var input = document.getElementById('jumlah-barang');
            var val = parseInt(input.value) || 1;
            val += delta;
            if (val < 1) val = 1;
            input.value = val;
            var inputHidden = document.getElementById('input-jumlah-barang');
            if (inputHidden) inputHidden.value = val;
        }

        function jumlahAlert() {
            var jumlah = document.getElementById('jumlah-barang').value;
            alert('Jumlah barang yang dipilih: ' + jumlah);
            return false;
        }
    </script>
</body>

</html>
