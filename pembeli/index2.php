<?php

session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

require_once "../koneksi.php";
$user_nama = $_SESSION["user_nama"] ?? '';
$user_email = $_SESSION["user_email"] ?? '';
$user_peran = $_SESSION["user_peran"] ?? '';

// Ambil user_id
$user_id = $_SESSION["user_id"] ?? 0;

// ===== Sinkronisasi Session Keranjang: hapus item yang produknya sudah tidak ada =====
if (isset($_SESSION['keranjang']) && is_array($_SESSION['keranjang'])) {
    $changed = false;
    foreach ($_SESSION['keranjang'] as $idx => $item) {
        $nama = $item['nama_produk'] ?? '';
        if ($nama === '') {
            unset($_SESSION['keranjang'][$idx]);
            $changed = true;
            continue;
        }
        // Periksa apakah produk dengan nama ini masih ada di tabel produk
        $stmtp = $conn->prepare("SELECT id, harga, path_gambar, stok FROM produk WHERE nama = ? LIMIT 1");
        $stmtp->bind_param("s", $nama);
        $stmtp->execute();
        $resp = $stmtp->get_result();
        if (!$resp || $resp->num_rows === 0) {
            // Produk sudah dihapus -> hapus dari session keranjang
            unset($_SESSION['keranjang'][$idx]);
            $changed = true;
        } else {
            // Update harga/gambar pada session agar tetap sinkron (opsional)
            $rowp = $resp->fetch_assoc();
            $_SESSION['keranjang'][$idx]['harga'] = (int)($rowp['harga'] ?? $_SESSION['keranjang'][$idx]['harga'] ?? 0);
            $_SESSION['keranjang'][$idx]['gambar'] = $rowp['path_gambar'] ?? $_SESSION['keranjang'][$idx]['gambar'] ?? '';
        }
        $stmtp->close();
    }
    if ($changed) {
        // reindex atau hapus session jika kosong
        if (empty($_SESSION['keranjang'])) {
            unset($_SESSION['keranjang']);
        } else {
            $_SESSION['keranjang'] = array_values($_SESSION['keranjang']);
        }
    }
}

// --- SINKRONISASI SESSION KERANJANG DENGAN DATABASE ---
if ($user_id) {
    // Jika session keranjang belum ada, ambil dari database user_cart
    if (!isset($_SESSION['keranjang'])) {
        $keranjang = [];
        $stmt = $conn->prepare("SELECT nama_produk, gambar, jumlah FROM user_cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Ambil harga dari tabel produk
            $harga_produk = 0;
            $stmt_harga = $conn->prepare("SELECT harga FROM produk WHERE nama = ? LIMIT 1");
            $stmt_harga->bind_param("s", $row["nama_produk"]);
            $stmt_harga->execute();
            $res_harga = $stmt_harga->get_result();
            if ($row_harga = $res_harga->fetch_assoc()) {
                $harga_produk = (int)$row_harga['harga'];
            }
            $stmt_harga->close();
            $keranjang[] = [
                "nama_produk" => $row["nama_produk"],
                "gambar" => $row["gambar"],
                "jumlah" => $row["jumlah"],
                "harga" => $harga_produk
            ];
        }
        $stmt->close();
        if (!empty($keranjang)) {
            $_SESSION['keranjang'] = $keranjang;
        }
    }
    // Hitung jumlah item keranjang dari database
    $cart_count = 0;
    $stmt = $conn->prepare("SELECT SUM(jumlah) as total FROM user_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $cart_count = (int)($row['total'] ?? 0);
    }
    $stmt->close();
    // Jika keranjang kosong di database, hapus session keranjang
    if ($cart_count === 0 && isset($_SESSION['keranjang'])) {
        unset($_SESSION['keranjang']);
    }
} else {
    $cart_count = 0;
}

// Ambil semua produk aktif dari semua penjual
// Fitur pencarian produk
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
// Fungsi membuat slug dari nama produk (hanya dideklarasikan sekali)
if (!function_exists('buat_slug')) {
    function buat_slug($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text); // Hanya huruf, angka, spasi, strip
        $text = preg_replace('/[\s-]+/', '-', $text); // Ganti spasi/strip berulang jadi satu strip
        $text = trim($text, '-');
        return $text;
    }
}

$products = [];
if ($search !== '') {
    $stmt = $conn->prepare("SELECT id, nama, deskripsi, harga, stok, path_gambar, penjual_id FROM produk WHERE status = 'aktif' AND stok > 0 AND (nama LIKE ? OR deskripsi LIKE ?) ORDER BY id DESC");
    $like = "%$search%";
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} else {
    $sql = "SELECT id, nama, deskripsi, harga, stok, path_gambar, penjual_id FROM produk WHERE status = 'aktif' AND stok > 0 ORDER BY id DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Pembeli - UMKM Market</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f7f7f7;
            min-height: 100vh;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 40px 14px 40px;
            background-color: #008000;
            color: white;
            flex-wrap: wrap;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            z-index: 10;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-right {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-left: auto;
        }

        .auth-links {
            display: flex;
            gap: 10px;
        }

        .auth-card {
            background-color: white;
            color: #00ab55;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
            white-space: nowrap;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .auth-card:hover {
            background-color: #e6f4ee;
            color: #008a40;
        }

        .header-cart {
            background-color: #ff4d4d;
            color: white;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 7px;
            cursor: pointer;
            white-space: nowrap;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .header-cart:hover {
            background-color: #e63939;
        }

        .header-cart svg {
            fill: white;
            width: 22px;
            height: 22px;
        }

        .user-info-bar {
            background: #fff;
            color: #444;
            max-width: 1200px;
            margin: 24px auto 0 auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            padding: 18px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .user-info-bar .user {
            font-size: 16px;
        }

        .logout-btn {
            background: #e53935;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 22px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .logout-btn:hover {
            background: #b71c1c;
        }

        .main-content {
            padding: 32px 24px 32px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .search-bar {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 0;
        }

        .search-bar input[type="text"] {
            width: 50%;
            padding: 12px 18px;
            border: 1px solid #ccc;
            border-radius: 25px 0 0 25px;
            outline: none;
            min-width: 200px;
            font-size: 16px;
            background: #fff;
            transition: border 0.2s;
        }

        .search-bar input[type="text"]:focus {
            border: 1.5px solid #00ab55;
        }

        .search-bar button {
            padding: 12px 28px;
            border: none;
            background-color: #00ab55;
            color: white;
            font-weight: 600;
            border-radius: 0 25px 25px 0;
            cursor: pointer;
            transition: 0.2s;
            font-size: 16px;
        }

        .search-bar button:hover {
            background-color: #008a40;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 28px;
        }

        .product-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.07);
            padding: 14px 12px 18px 12px;
            text-align: center;
            transition: transform 0.18s, box-shadow 0.18s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 320px;
        }

        .product-card:hover {
            transform: translateY(-4px) scale(1.035);
            box-shadow: 0px 6px 18px rgba(0, 0, 0, 0.13);
        }

        .product-card img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            border-radius: 10px;
            background: #f2f2f2;
        }

        .product-card p {
            margin-top: 14px;
            font-weight: 600;
            color: #333;
            font-size: 15px;
            min-height: 40px;
        }

        .btn-container {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 14px;
            flex-wrap: wrap;
        }

        .product-card button,
        .product-card a {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 0;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: 0.2s;
            min-width: 90px;
            font-size: 15px;
        }

        .btn-tambah {
            background-color: #00ab55;
            color: white;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .btn-tambah:hover {
            background-color: #008a40;
        }

        .btn-beli {
            background-color: #f39c12;
            color: white;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .btn-beli:hover {
            background-color: #d68910;
        }

        @media (max-width: 1024px) {
            .main-content {
                padding: 18px 4vw;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 8px 2vw;
            }
        }
    </style>
</head>

<body>

    <body>
        <header style="background-color:#A8E6CF; padding:6px 14px; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 4px rgba(0,0,0,0.1); position:relative;">
            <div class="logo">
                <img src="logo.png" alt="Logo MyMarketplace" style="height:30px;" />
            </div>

            <!-- Tombol burger (muncul hanya di mobile) -->
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">☰</button>

            <nav class="top-right" id="topRight">
                <button class="close-btn" id="closeBtn" aria-label="Close navigation">×</button>

                <div class="auth-links">
                    <!-- login / register -->
                </div>

                <div class="nav-buttons">
                    <!-- Tambahkan ini di <head> agar ikon Bootstrap aktif -->
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

                    <!-- Tombol Header -->
                    <a class="header-btn" href="pesanan_saya.php" title="Daftar Pesanan Saya" style="background-color:#00ab55;">
                        <i class="bi bi-bag-check-fill"></i> Pesanan
                    </a>

                    <a class="header-btn" href="transaksi_saya.php" title="Daftar Transaksi Saya" style="background-color:#007bff;">
                        <i class="bi bi-cash-stack"></i> Transaksi
                    </a>

                    <a class="header-btn" href="produk-terlaris.php" title="Produk Terlaris" style="background-color:#6c63ff;">
                        <i class="bi bi-star-fill"></i> Produk Paling Laris
                    </a>

                    <a class="header-btn" href="diskon_saya.php" title="Daftar Diskon Saya" style="background-color:#ff9800;">
                        <i class="bi bi-tags-fill"></i> Diskon Saya
                    </a>

                    <a class="cart-btn" href="../pembeli/keranjang/keranjang.php" title="Keranjang Belanja">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="#FFFFFF" stroke="#FFFFFF" viewBox="0 0 16 16" width="16" height="16">
                            <path d="M0 1a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 .485.379L3.89 3H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                        </svg>
                        <span id="cart-count"><?php echo $cart_count; ?></span>
                    </a>

                </div>
            </nav>
        </header>

        <style>
            body {
                margin: 0;
                font-family: 'Segoe UI', Arial, sans-serif;
            }

            .nav-toggle {
                display: none;
                font-size: 22px;
                background: none;
                border: none;
                cursor: pointer;
                color: #333;
            }

            .top-right {
                display: flex;
                align-items: center;
                gap: 8px;
                transition: transform 0.3s ease;
            }

            .close-btn {
                display: none;
                background: none;
                border: none;
                font-size: 26px;
                color: #333;
                position: absolute;
                top: 10px;
                right: 15px;
                cursor: pointer;
            }

            .nav-buttons {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .header-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                min-width: 75px;
                height: 26px;
                border-radius: 5px;
                color: #fff;
                font-weight: 600;
                font-size: 11.5px;
                text-decoration: none;
                padding: 0 6px;
                transition: background-color 0.2s;
            }

            .header-btn:hover {
                opacity: 0.9;
            }

            .cart-btn {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                background-color: #FFD700;
                border-radius: 6px;
                transition: transform 0.2s;
            }

            .cart-btn:hover {
                transform: scale(1.05);
            }

            #cart-count {
                position: absolute;
                top: -6px;
                right: -6px;
                background: #FF3B30;
                color: white;
                border-radius: 50%;
                padding: 1px 4px;
                font-size: 9px;
                font-weight: bold;
            }

            /* Desktop */
            @media (min-width: 769px) {
                .top-right {
                    position: static;
                    background: none;
                    flex-direction: row;
                    transform: none;
                }
            }

            /* Mobile */
            @media (max-width: 768px) {
                .nav-toggle {
                    display: block;
                }

                .top-right {
                    position: fixed;
                    top: 0;
                    left: 0;
                    height: 100%;
                    width: 200px;
                    background-color: #fff;
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 60px 15px;
                    box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
                    transform: translateX(-100%);
                    z-index: 999;
                }

                .top-right.active {
                    transform: translateX(0);
                }

                .close-btn {
                    display: block;
                }

                .nav-buttons {
                    flex-direction: column;
                    width: 100%;
                    gap: 8px;
                }

                .header-btn {
                    width: 100%;
                    text-align: left;
                }

                .cart-btn {
                    margin-top: 10px;
                }
            }
        </style>

        <script>
            const navToggle = document.getElementById('navToggle');
            const topRight = document.getElementById('topRight');
            const closeBtn = document.getElementById('closeBtn');

            navToggle.addEventListener('click', () => topRight.classList.add('active'));
            closeBtn.addEventListener('click', () => topRight.classList.remove('active'));

            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) topRight.classList.remove('active');
            });
        </script>


        <!-- Tombol Customer Service -->
        <a href="cs.php" class="cs-button" title="Hubungi Customer Service">
            💬 Customer Service
        </a>

        <!-- CSS Tombol CS -->
        <style>
            .cs-button {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background-color: #28a745;
                /* Warna hijau */
                color: #fff;
                font-weight: bold;
                font-size: 16px;
                text-decoration: none;
                padding: 12px 18px;
                border-radius: 50px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
                transition: all 0.3s ease;
                z-index: 9999;
            }

            /* Efek hover */
            .cs-button:hover {
                background-color: #218838;
                transform: scale(1.05);
            }

            /* Responsif untuk HP */
            @media (max-width: 768px) {
                .cs-button {
                    bottom: 15px;
                    right: 15px;
                    font-size: 14px;
                    padding: 10px 15px;
                }
            }

            /* Responsif untuk layar kecil (HP sangat kecil) */
            @media (max-width: 480px) {
                .cs-button {
                    bottom: 10px;
                    right: 10px;
                    font-size: 13px;
                    padding: 8px 12px;
                }
            }
        </style>


    </body>

    <script>
        // Tambahkan animasi shake pada badge keranjang dan delay saat produk ditambahkan
        document.addEventListener("click", function(e) {
            if (e.target && e.target.classList.contains("btn-tambah")) {
                e.preventDefault();
                const form = e.target.closest("form");
                const formData = new FormData(form);

                fetch("keranjang/tambah-keranjang.php", {
                        method: "POST",
                        body: formData,
                        headers: {
                            "X-Requested-With": "XMLHttpRequest"
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        // Tambahkan delay sebelum update badge dan animasi
                        setTimeout(function() {
                            const cartCount = document.getElementById("cart-count");
                            cartCount.textContent = data.count;
                            // Trigger animasi shake
                            cartCount.classList.remove("shake-anim");
                            void cartCount.offsetWidth;
                            cartCount.classList.add("shake-anim");
                        }, 400); // delay 400ms
                    });
            }
        });
    </script>

    <div class="user-info-bar">
        <div class="user">
            Selamat Datang, <b><?php echo htmlspecialchars($user_nama); ?></b> | Email: <?php echo htmlspecialchars($user_email); ?> | Peran: <?php echo htmlspecialchars($user_peran); ?>
        </div>
        <a href="auth/logout.php"><button class="logout-btn">Logout</button></a>
    </div>


    <div class="main-content">
        <div class="search-bar">
            <form method="get" action="" style="display:flex; width:100%;">
                <input type="text" id="searchInput" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search); ?>" />
                <button type="submit" id="searchBtn">Cari</button>
            </form>
        </div>
        <div class="products-grid" id="productsGrid">
            <?php if (count($products) === 0): ?>
                <div style="grid-column: 1/-1; text-align:center; color:#888;">
                    <?php if ($search !== ''): ?>
                        Tidak ditemukan produk untuk kata kunci <b><?php echo htmlspecialchars($search); ?></b>.
                    <?php else: ?>
                        Belum ada produk yang tersedia.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($products as $prod): ?>
                    <div class="product-card" data-produk-id="<?= $prod['id'] ?>" data-penjual-id="<?= htmlspecialchars($prod['penjual_id'] ?? '') ?>">
                        <?php
                        $gambar_produk = !empty($prod['path_gambar']) ? $prod['path_gambar'] : 'img/no-image.png';
                        ?>
                        <img src="../<?= htmlspecialchars($gambar_produk) ?>" alt="<?= htmlspecialchars($prod['nama']) ?>">
                        <?php $slug = buat_slug($prod['nama']); ?>
                        <p><a href="produk.php?slug=<?= urlencode($slug) ?>" style="color:inherit;text-decoration:underline;">
                                <?= htmlspecialchars($prod['nama']) ?></a></p>
                        <div style="margin-bottom:8px;font-weight:bold;color:#00ab55;">Rp <?= number_format($prod['harga'], 0, ',', '.') ?></div>
                        <div id="stok-produk-<?= $prod['id'] ?>" style="margin-bottom:8px; color:#444; font-size:14px; min-height:36px;">Stok: <span><?= htmlspecialchars($prod['stok']) ?></span></div>
                        <div style="margin-bottom:8px; color:#666; font-size:13px;">
                            Penjual ID: <span><?= htmlspecialchars($prod['penjual_id'] ?? '-') ?></span> |
                            Nama: <span>
                                <?php
                                // ambil nama penjual
                                $stmt_penjual = $conn->prepare("SELECT nama FROM pengguna WHERE id = ? AND peran = 'penjual'");
                                $stmt_penjual->bind_param("i", $prod['penjual_id']);
                                $stmt_penjual->execute();
                                $result_penjual = $stmt_penjual->get_result()->fetch_assoc();
                                echo htmlspecialchars($result_penjual['nama'] ?? 'Tidak diketahui');
                                ?>
                            </span>
                        </div>

                        <div class="btn-container">
                            <?php if ((int)$prod['stok'] > 0): ?>
                                <button class="btn-tambah" onclick="tambahKeranjang(<?= $prod['id'] ?>, 1, this)">Tambah</button>
                                <button class="btn-beli" onclick="beliLangsung(<?= $prod['id'] ?>, 1, this)">Beli</button>
                            <?php else: ?>
                                <button class="btn-tambah" style="background:#ccc; color:#888; cursor:not-allowed;" disabled>Stok Habis</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- Script pencarian produk dinonaktifkan karena data produk sekarang dari PHP/database. -->
    <script>
        // Fungsi AJAX untuk tambah keranjang
        function tambahKeranjang(produkId, jumlah, btn) {
            btn.disabled = true;
            fetch('keranjang/tambah-keranjang.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'produk_id=' + encodeURIComponent(produkId) + '&jumlah=' + encodeURIComponent(jumlah) + '&ajax=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update stok di UI
                        const stokSpan = document.querySelector('#stok-produk-' + produkId + ' span');
                        if (stokSpan) stokSpan.textContent = data.stok_terbaru;
                        btn.classList.add('shake-anim');
                        setTimeout(() => btn.classList.remove('shake-anim'), 500);
                        // Update badge keranjang di pojok kanan atas dan animasi
                        fetch('keranjang/keranjang.php?ajax=count')
                            .then(res => res.json())
                            .then(cart => {
                                const cartCount = document.getElementById('cart-count');
                                if (cartCount && cart.count !== undefined) {
                                    cartCount.textContent = cart.count;
                                    cartCount.classList.remove('shake-anim');
                                    void cartCount.offsetWidth;
                                    cartCount.classList.add('shake-anim');
                                }
                            });
                        if (parseInt(data.stok_terbaru) <= 0) {
                            btn.disabled = true;
                            btn.textContent = 'Stok Habis';
                            btn.style.background = '#ccc';
                            btn.style.color = '#888';
                            btn.style.cursor = 'not-allowed';
                            // Disable tombol beli juga
                            let card = btn.closest('.product-card');
                            if (card) {
                                let btnBeli = card.querySelector('.btn-beli');
                                if (btnBeli) {
                                    btnBeli.disabled = true;
                                    btnBeli.textContent = 'Stok Habis';
                                    btnBeli.style.background = '#ccc';
                                    btnBeli.style.color = '#888';
                                    btnBeli.style.cursor = 'not-allowed';
                                }
                            }
                        }
                    } else {
                        alert(data.message || 'Gagal menambah ke keranjang!');
                    }
                })
                .catch(() => alert('Gagal menambah ke keranjang!'))
                .finally(() => {
                    btn.disabled = false;
                });
        }

        // Fungsi AJAX untuk beli langsung
        function beliLangsung(produkId, jumlah, btn) {
            // Ambil data produk dari DOM
            const card = btn.closest('.product-card');
            const nama = card.querySelector('a').textContent.trim();
            const hargaText = card.querySelector('div[style*="font-weight:bold"]').textContent.trim();
            const harga = hargaText.replace(/[^\d]/g, '');
            const gambar = card.querySelector('img').getAttribute('src').replace('../', '');
            // Buat form dinamis untuk POST ke checkout.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'checkout/checkout.php';
            form.style.display = 'none';
            // id_produk
            const f_id = document.createElement('input');
            f_id.name = 'id_produk';
            f_id.value = produkId;
            form.appendChild(f_id);
            // nama_produk
            const f_nama = document.createElement('input');
            f_nama.name = 'nama_produk';
            f_nama.value = nama;
            form.appendChild(f_nama);
            // harga
            const f_harga = document.createElement('input');
            f_harga.name = 'harga';
            f_harga.value = harga;
            form.appendChild(f_harga);
            // gambar
            const f_gambar = document.createElement('input');
            f_gambar.name = 'gambar';
            f_gambar.value = gambar;
            form.appendChild(f_gambar);
            // jumlah
            const f_jumlah = document.createElement('input');
            f_jumlah.name = 'jumlah';
            f_jumlah.value = jumlah;
            form.appendChild(f_jumlah);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
    <style>
        /* Animasi shake untuk badge keranjang */
        @keyframes shake {
            0% {
                transform: translateX(0);
            }

            20% {
                transform: translateX(-5px);
            }

            40% {
                transform: translateX(5px);
            }

            60% {
                transform: translateX(-5px);
            }

            80% {
                transform: translateX(5px);
            }

            100% {
                transform: translateX(0);
            }
        }

        .shake-anim {
            animation: shake 0.4s cubic-bezier(.36, .07, .19, .97) both;
        }
    </style>
</body>

</html>