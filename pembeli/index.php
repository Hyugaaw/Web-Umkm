<?php include '../koneksi.php'; ?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Marketplace</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        /* --- Tempelkan CSS kamu di sini --- */
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f7f7f7;
            min-height: 100vh;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 40px 14px 40px;
            background-color: #00ab55;
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

        /* Wrapper untuk kanan atas */
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

        /* Troli icon style */
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

        /* Main Content */
        .main-content {
            padding: 32px 24px 32px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Search Bar */
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

        /* Produk grid */
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
            /* Lebih besar, tetap landscape */
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

        /* Horizontal Scroll */
        .horizontal-scroll-wrapper {
            display: flex;
            overflow-x: auto;
            gap: 18px;
            padding-bottom: 18px;
            margin-bottom: 18px;
            scroll-behavior: smooth;
        }

        .horizontal-scroll-card {
            min-width: 260px;
            max-width: 260px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.07);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px 10px 16px 10px;
            transition: transform 0.18s, box-shadow 0.18s;
        }

        .horizontal-scroll-card:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0px 6px 18px rgba(0, 0, 0, 0.13);
        }

        .horizontal-scroll-card img {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            background: #f2f2f2;
        }

        .horizontal-scroll-card .card-title {
            font-weight: 600;
            margin: 10px 0 4px 0;
            color: #333;
            font-size: 15px;
            text-align: center;
        }

        .horizontal-scroll-card .card-info {
            font-size: 13px;
            color: #666;
            text-align: center;
        }

        /* Single Card (kiri) */
        .horizontal-single-card {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 340px;
        }

        .horizontal-single-card .big-product-card {
            width: 100%;
            max-width: 480px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.10);
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow: hidden;
            padding-bottom: 18px;
            margin: 0 auto;
        }

        .big-product-card img {
            width: 100%;
            height: 220px;
            /* Lebih besar, tetap landscape */
            object-fit: cover;
            border-radius: 20px 20px 0 0;
            background: #f2f2f2;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
        }

        .big-product-card .card-title {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 18px 0 8px 0;
            color: #222;
            text-align: center;
        }

        .big-product-card .card-info {
            font-size: 1rem;
            color: #444;
            text-align: center;
            margin: 0 18px;
        }

        .side-card {
            border-radius: 14px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.07);
            padding: 28px 22px 24px 22px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-height: 120px;
            margin-bottom: 24px;
            background: #fff;
        }

        .side-card:last-child {
            margin-bottom: 0;
        }

        .side-card.ukm {
            background: linear-gradient(135deg, #00c3a5 0%, #7ee8fa 100%);
            color: #fff;
        }

        .side-card.pkl {
            background: linear-gradient(135deg, #ffb347 0%, #ffcc80 100%);
            color: #5a3e00;
        }

        .side-card-title {
            font-weight: 700;
            margin: 0 0 10px 0;
            font-size: 20px;
            letter-spacing: 0.5px;
        }

        .side-card-info {
            font-size: 16px;
            line-height: 1.7;
        }

        @media (max-width: 1024px) {
            .horizontal-section {
                flex-direction: column;
                gap: 24px;
            }

            .horizontal-single-card .horizontal-scroll-card {
                min-width: 220px;
                max-width: 100%;
            }

            .side-card {
                min-height: 80px;
                padding: 18px 10px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 18px 4vw;
            }

            .horizontal-section {
                flex-direction: column;
                gap: 18px;
            }

            .horizontal-single-card .horizontal-scroll-card {
                min-width: 140px;
                max-width: 100%;
            }

            .side-card {
                min-height: 60px;
                padding: 14px 10px;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 8px 2vw;
            }

            .horizontal-section {
                flex-direction: column;
                gap: 10px;
            }

            .horizontal-single-card .horizontal-scroll-card {
                min-width: 100px;
                max-width: 100%;
            }

            .side-card {
                min-height: 40px;
                padding: 10px 6px;
            }
        }

        /* Card info UKM dan PKL */
        .side-card {
            border-radius: 12px;
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.07);
            padding: 18px 18px 18px 18px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-height: 90px;
            margin-bottom: 0;
        }

        .side-card.ukm {
            background: linear-gradient(135deg, #00c3a5 0%, #7ee8fa 100%);
            color: #fff;
        }

        .side-card.pkl {
            background: linear-gradient(135deg, #ffb347 0%, #ffcc80 100%);
            color: #5a3e00;
        }

        .side-card-title {
            font-weight: 700;
            margin: 0 0 6px 0;
            font-size: 17px;
            letter-spacing: 0.5px;
        }

        .side-card-info {
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 1024px) {
            .horizontal-section {
                flex-direction: column;
                gap: 16px;
            }

            .side-card {
                min-height: 70px;
            }
        }

        @media (max-width: 768px) {
            .horizontal-section {
                flex-direction: column;
                gap: 12px;
            }

            .side-card {
                min-height: 60px;
                padding: 14px 10px;
            }
        }

        @media (max-width: 480px) {
            .horizontal-section {
                flex-direction: column;
                gap: 8px;
            }

            .side-card {
                min-height: 40px;
                padding: 10px 6px;
            }
        }

        /* CSS Modal Login */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-box {
            background: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 380px;
            padding: 24px;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s ease;
        }

        .modal-box h2 {
            text-align: center;
            margin-bottom: 18px;
        }

        .modal-box input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .modal-box button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #03ac0e;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        .modal-box button:hover {
            background: #02910c;
        }

        .close {
            position: absolute;
            top: 12px;
            right: 18px;
            font-size: 22px;
            cursor: pointer;
            color: #666;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 18px 0;
        }

        .divider hr {
            flex: 1;
            border: none;
            border-top: 1px solid #ddd;
        }

        .divider span {
            margin: 0 10px;
            color: #999;
        }

        .links {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 14px;
        }

        .links a {
            color: #03ac0e;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Atur lebar field input & tombol supaya tidak melebihi modal */
        .modal-box input,
        .modal-box button,
        .modal-box .g_id_signin {
            width: 100%;
            /* ikut lebar modal */
            box-sizing: border-box;
            /* padding & border ikut dihitung */
            margin: 8px 0;
            /* kasih jarak antar elemen */
            display: block;
            /* biar rapih */
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="logo.png" alt="Logo MyMarketplace">
        </div>

        <!-- Tombol Kebab Menu (hanya mobile) -->
        <button class="nav-toggle" id="navToggle" aria-label="Toggle Menu">
            ☰
        </button>

        <div class="top-right" id="navMenu">
            <div class="auth-links">
                <a class="auth-card" href="auth/login.php">Login</a>
                <a class="auth-card" href="auth/register.php">Daftar</a>
                <a class="auth-card" href="http://localhost/web-umkm/umkm.php">Logout</a>
            </div>
            <a class="header-cart" href="auth/login.php" title="Keranjang Belanja">
                <div style="color: black;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 1a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 .485.379L3.89 3H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                    </svg>
                </div>
            </a>
        </div>
    </header>

    <style>
        /* --- Reset dasar --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: green;
            border-bottom: 1px solid #ddd;
            position: relative;
        }

        .logo img {
            height: 40px;
        }

        /* Menu kanan desktop */
        .top-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .auth-links a {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 5px;
            color: #333;
            background-color: #f0f0f0;
            transition: 0.3s;
        }

        .auth-links a:hover {
            background-color: #ddd;
        }

        /* Keranjang */
        .header-cart svg {
            width: 24px;
            height: 24px;
            fill: white;
            stroke: black;
            cursor: pointer;
        }

        /* Tombol kebab */
        .nav-toggle {
            display: none;
            font-size: 24px;
            background: none;
            border: none;
            cursor: pointer;
            color: white;
        }

        /* --- Mobile Styles --- */
        @media (max-width: 768px) {
            .top-right {
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                background-color: #fff;
                border-top: 1px solid #ddd;
                display: none;
            }

            .top-right.show {
                display: flex;
            }

            .auth-links {
                flex-direction: column;
                gap: 10px;
                padding: 10px 0;
            }

            .header-cart {
                margin-bottom: 10px;
            }

            .nav-toggle {
                display: block;
            }
        }
    </style>

    <script>
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');

        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('show');
        });
    </script>



    <!-- Main content -->
    <div class="main-content">
        <!-- Card Single Produk Otomatis + 2 Card Info -->
        <div class="horizontal-section">
            <!-- Kiri: Card single produk otomatis -->
            <div class="left-card">
                <div class="horizontal-single-card" id="horizontalSingleCard">
                    <!-- Produk otomatis muncul di sini -->
                </div>
            </div>
            <!-- Kanan: 2 card info UMKM dan PKL tanpa gambar -->
            <div class="right-card">
                <div class="side-card ukm">
                    <div class="side-card-title">Tentang UKM</div>
                    <div class="side-card-info">
                        UMKM adalah usaha yang dikelola oleh masyarakat lokal, mendukung ekonomi kreatif dan pemberdayaan warga.
                    </div>
                </div>
                <div class="side-card pkl">
                    <div class="side-card-title">Informasi UMKM</div>
                    <div class="side-card-info">
                        Temukan berbagai informasi menarik seputar UMKM, mulai dari profil usaha, produk unggulan, hingga inovasi digital yang mendukung pengembangan ekonomi lokal.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .horizontal-section {
            display: flex;
            gap: 24px;
            margin-bottom: 32px;
            align-items: stretch;
            /* Biar tinggi kiri dan kanan sejajar */
        }

        /* Kiri (produk) */
        .left-card {
            flex: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }

        .horizontal-single-card {
            width: 100%;
            min-height: 250px;
            /* Atur sesuai kebutuhan */
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 16px;
        }

        /* Kanan (info UMKM + PKL) */
        .right-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Biar card kanan tersebar merata */
            gap: 18px;
        }

        .side-card {
            flex: 1;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            /* lebih lega */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Judul */
        .side-card-title {
            font-weight: bold;
            margin-bottom: 12px;
            /* jarak lebih jauh ke teks isi */
            font-size: 16px;
            color: #333;
        }

        /* Isi */
        .side-card-info {
            font-size: 14px;
            line-height: 1.6;
            /* biar teks nggak rapat */
            color: #555;
        }

        /* Biar tombol Google sejajar dengan input & button */
        .g_id_signin {
            display: flex !important;
            /* fleksibel biar bisa center */
            justify-content: center;
            /* posisikan di tengah */
            align-items: center;
            width: 100% !important;
            /* sama lebar dengan input dan button */
            box-sizing: border-box;
            /* hitung padding & border */
            margin: 8px 0;
            /* kasih jarak atas bawah */
        }

        .modal-image {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .modal-image img {
            width: 100%;
            /* memanjang mengikuti lebar modal */
            height: auto;
            /* tinggi menyesuaikan proporsi gambar */
            object-fit: contain;
            /* jangan terpotong, tetap proporsional */
            border-radius: 0;
            /* hilangkan bulat */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 16px;
        }
    </style>


    <!-- Search Bar -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Cari produk..." />
        <button id="searchBtn">Cari</button>
    </div>

    <!-- Grid Produk -->
    <div class="products-grid" id="productsGrid">
        <!-- Produk akan di-render oleh JavaScript -->
    </div>
    <!-- Modal Login -->
    <div id="loginModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close">&times;</span>

            <!-- Gambar UMKM -->
            <div class="modal-image">
                <img src="./logo.png" alt="UMKM" />
            </div>


            <h2>Masuk</h2>
            <form method="POST" action="auth/login.php">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Kata Sandi" required>
                <button type="submit">Masuk sekarang</button>
            </form>


            <div class="divider">
                <hr><span>atau</span>
                <hr>
            </div>


            <!-- Tombol Google -->
            <div id="g_id_onload"
                data-client_id="1082579647521-8hkq6vk4eqcfcp7rak71agp2naif7pi4.apps.googleusercontent.com"
                data-login_uri="http://localhost/web-umkm/pembeli/auth/google-callback.php"
                data-auto_prompt="false"></div>
            <div class="g_id_signin"
                data-type="standard"
                data-shape="rectangular"
                data-theme="outline"
                data-text="signin_with"
                data-size="large"
                data-logo_alignment="left"></div>

            <div class="links">
                <a href="auth/forgot-password.php">Lupa kata sandi?</a>
                <a href="auth/register.php">Daftar</a>
            </div>
        </div>
    </div>
    <script>
        // Modal login
        const loginModal = document.getElementById("loginModal");
        const openLoginBtn = document.getElementById("openLoginModal");
        const closeBtn = document.querySelector("#loginModal .close");

        // Buka modal
        openLoginBtn.addEventListener("click", function(e) {
            e.preventDefault();
            loginModal.style.display = "flex";
            document.body.style.overflow = "hidden"; // kunci scroll
        });


        // Tutup modal (klik X)
        closeBtn.addEventListener("click", function() {
            loginModal.style.display = "none";
            document.body.style.overflow = ""; // kembalikan scroll
        });


        // Tutup modal (klik luar box)
        window.addEventListener("click", function(e) {
            if (e.target === loginModal) {
                loginModal.style.display = "none";
                document.body.style.overflow = ""; // kembalikan scroll
            }
        });
    </script>


    </div>
</body>
<script>
    // Data produk
    const products = [{
            nama: "Anyaman Rajutan Tangan 1",
            gambar: "Anyaman Rajutan tangan 1.jpeg",
            info: "Kerajinan tangan unik, cocok untuk dekorasi rumah."
        },
        {
            nama: "Anyaman Rajutan Tangan 2",
            gambar: "Anyaman Rajutan tangan 2.jpeg",
            info: "Produk lokal berkualitas, hasil karya UMKM."
        },
        {
            nama: "Anyaman Rajutan Tangan 3",
            gambar: "Anyaman Rajutan tangan 3.jpeg",
            info: "Anyaman kuat dan tahan lama."
        },
        {
            nama: "Anyaman Rajutan Tangan 4",
            gambar: "Anyaman Rajutan tangan 4.jpeg",
            info: "Motif menarik, cocok untuk hadiah."
        },
        {
            nama: "Bala / Bakwan",
            gambar: "bala bakwan.jpeg",
            info: "Cemilan gurih khas Indonesia."
        },
        {
            nama: "Basreng",
            gambar: "basreng.jpeg",
            info: "Basreng renyah dan gurih."

        },
        {
            nama: "Basreng 2",
            gambar: "basreng 2.jpeg",
            info: "Basreng pedas, cocok untuk teman nonton."
        },
        {
            nama: "Cilok",
            gambar: "cilok.jpeg",
            info: "Cilok kenyal dengan bumbu kacang."
        },
        {
            nama: "Cimol",
            gambar: "cimol.jpeg",
            info: "Cimol kopong, gurih di luar lembut di dalam."
        },
        {
            nama: "Comet",
            gambar: "comet.jpeg",
            info: "Cemilan manis dan lezat."
        },
        {
            nama: "Cuhcur",
            gambar: "Cuhcur.jpeg",
            info: "Kue tradisional khas Sunda."
        },
        {
            nama: "Es Lilin",
            gambar: "Es Lilin.jpeg",
            info: "Es jadul, segar dan manis."
        },
        {
            nama: "Gehu",
            gambar: "Gehu.jpeg",
            info: "Gehu (Goreng Hula) adalah tahu goreng isi sayuran yang renyah dan gurih."
        },
        {
            nama: "Keripik Singkong",
            gambar: "keripik singkong.jpeg",
            info: "Keripik singkong renyah, cocok untuk camilan."
        },
        {
            nama: "Keripik Singkong 2",
            gambar: "keripik singkong 2.jpeg",
            info: "Keripik singkong renyah, dan gurih cocok untuk camilan."
        },
        {
            nama: "Keripik Pisang",
            gambar: "keripik pisang.jpeg",
            info: "keripik pisang merupakan cemilan melezatkan"
        },
        {
            nama: "Keripik Pisang 2",
            gambar: "keripik pisang 2.jpeg",
            info: "Keripik pisang renyah, dan gurih cocok untuk camilan."
        },
        {
            nama: "Kerajinan Tangan ",
            gambar: "kerajinan tangan.jpeg",
            info: "kerajinan tangan merupakan produk yang dibuat dengan keterampilan tangan."
        },
        {
            nama: "Kerajinan Tangan ",
            gambar: "kerajinan tangan 2.jpeg",
            info: "kerajinan tangan merupakan produk yang dibuat dengan keterampilan tangan."
        },
        {
            nama: "Kesed Rumah ",
            gambar: "kesed rumah.jpeg",
            info: "Kesed rumah adalah kerajinan tangan yang terbuat dari bahan alami."
        },
        {
            nama: "Kesed Rumah 2",
            gambar: "kesed rumah 2.jpeg",
            info: "Kesed rumah adalah kerajinan tangan yang terbuat dari bahan alami ."
        },
        {
            nama: "Martabak",
            gambar: "Martabak.jpeg",
            info: "Martabak Manis dan Gurih."
        },
        {
            nama: "Pisang Aromat",
            gambar: "pisang aromat.jpeg",
            info: "Pisang aromat goreng renyah."
        },
        {
            nama: "Raginang",
            gambar: "raginang.jpeg",
            info: "Raginang gurih dan renyah."
        },
        {
            nama: "Rajut Botol",
            gambar: "rajut botol.jpeg",
            info: "Rajut botol unik dan ramah lingkungan."
        },
        {
            nama: "Rajut Sampah Kopi",
            gambar: "rajut sampah kopi.jpeg",
            info: "Sate ayam bumbu kacang."
        },
        {
            nama: "Rempeyek 1",
            gambar: "rempeyek 1.jpeg",
            info: "Rempeyek renyah dan gurih."
        },
        {
            nama: "Rempeyek 2",
            gambar: "rempeyek 2.jpeg",
            info: "Rempeyek renyah dan gurih."
        },
        {
            nama: "Rempeyek 3",
            gambar: "rempeyek 3.jpeg",
            info: "Rempeyek renyah dan gurih."
        },
        {
            nama: "Telor Gabus",
            gambar: "telor gabus.jpeg",
            info: "Telor gabus gurih dan renyah."
        },
    ];


    function renderProducts(list) {
        const grid = document.getElementById('productsGrid');
        grid.innerHTML = '';
        if (list.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#888;">Produk tidak ditemukan.</div>';
            return;
        }
        let kerajinanTanganCount = 0;
        list.forEach((product) => {
            let slug = product.nama
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            // Khusus untuk produk 'Kerajinan Tangan', buat slug berbeda jika sudah ada satu
            if (slug === 'kerajinan-tangan') {
                kerajinanTanganCount++;
                if (kerajinanTanganCount === 2) {
                    slug = 'kerajinan-tangan-2';
                }
            }
            grid.innerHTML += `
        <div class="product-card">
            <img src="${product.gambar}" alt="${product.nama}">
                <p><a href="/web-umkm/pembeli/tentang-produk/${slug}.php" style="color:inherit;text-decoration:underline;">${product.nama}</a></p>
            <div class="btn-container">
                <a class="btn-tambah" href="auth/login.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M0 1a1 1 0 0 1 1-1h1.5a.5.5 0 0 1 .485.379L3.89 3H14.5a.5.5 0 0 1 .49.598l-1.5 7A.5.5 0 0 1 13 11H4a.5.5 0 0 1-.491-.408L1.01 1H1a1 1 0 0 1-1-1zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                    </svg>
                    Tambah
                </a>
                <a class="btn-beli" href="auth/login.php">Beli</a>
            </div>
        </div>
        `;
        });
    }

    // Render horizontal scroll cards
    function renderHorizontalScroll(list) {
        const wrapper = document.getElementById('horizontalScroll');
        wrapper.innerHTML = '';
        // Tampilkan 8 produk pertama saja agar tidak terlalu panjang
        const showList = list.slice(0, 8);
        showList.forEach(product => {
            wrapper.innerHTML += `
            <div class="horizontal-scroll-card">
                <img src="${product.gambar}" alt="${product.nama}">
                <div class="card-title">${product.nama}</div>
                <div class="card-info">${product.info ? product.info : ''}</div>
            </div>
        `;
        });
    }

    // Auto scroll horizontal card
    function autoScrollHorizontal() {
        const wrapper = document.getElementById('horizontalScroll');
        let scrollAmount = 0;
        let maxScroll = 0;
        let direction = 1;
        setInterval(() => {
            maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
            if (maxScroll <= 0) return;
            if (scrollAmount >= maxScroll) direction = -1;
            if (scrollAmount <= 0) direction = 1;
            scrollAmount += direction * 2; // kecepatan scroll
            wrapper.scrollTo({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }, 30);
    }

    // Render single horizontal card (satu gambar saja)
    function renderHorizontalSingleCard(list, idx) {
        const wrapper = document.getElementById('horizontalSingleCard');
        const product = list[idx];




        wrapper.innerHTML = `   
        <div class="big-product-card">
            <img src="${product.gambar}" alt="${product.nama}">
            <div class="card-title">${product.nama}</div>
            <div class="card-info">${product.info ? product.info : ''}</div>
        </div>
    `;
    }

    // Auto scroll single card
    function autoScrollSingleCard() {
        let idx = 0;
        renderHorizontalSingleCard(products, idx);
        setInterval(() => {
            idx = (idx + 1) % products.length;
            renderHorizontalSingleCard(products, idx);
        }, 2200);
    }

    // Inisialisasi produk dan single card scroll

    renderProducts(products);
    // Render single produk besar dan auto scroll satu per satu
    autoScrollSingleCard();

    // Event search
    document.getElementById('searchBtn').addEventListener('click', function() {
        const keyword = document.getElementById('searchInput').value.trim().toLowerCase();
        const filtered = products.filter(p => p.nama.toLowerCase().includes(keyword));
        renderProducts(filtered);
    });

    // Enter key support
    document.getElementById('searchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('searchBtn').click();
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        const loginLink = document.querySelector('.auth-links a[href="auth/login.php"]');
        const modal = document.getElementById("loginModal");
        const closeBtn = modal.querySelector(".close");

        if (loginLink) {
            loginLink.addEventListener("click", function(e) {
                e.preventDefault(); // cegah pindah halaman
                modal.style.display = "flex";
            });
        }

        closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
        });

        window.addEventListener("click", function(e) {
            if (e.target === modal) modal.style.display = "none";
        });
    });
</script>

</html>