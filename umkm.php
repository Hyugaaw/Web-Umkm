<?php
// ====== Panggil koneksi ======
include 'koneksi.php';

// ====== Simpan Data Kunjungan ======
$ip_address = $_SERVER['REMOTE_ADDR'];
$tanggal = date("Y-m-d");

// Cek apakah sudah ada kunjungan hari ini dari IP yang sama
$cek = $conn->query("SELECT * FROM kunjungan WHERE ip_address='$ip_address' AND tanggal='$tanggal'");
if ($cek->num_rows == 0) {
    $conn->query("INSERT INTO kunjungan (ip_address, tanggal) VALUES ('$ip_address','$tanggal')");
}

// ====== Hitung Statistik ======
$hari_ini = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan WHERE tanggal=CURDATE()")->fetch_assoc()['jml'];
$kemarin = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan WHERE tanggal=CURDATE()-INTERVAL 1 DAY")->fetch_assoc()['jml'];
$minggu_ini = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan WHERE YEARWEEK(tanggal, 1)=YEARWEEK(CURDATE(), 1)")->fetch_assoc()['jml'];
$minggu_lalu = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan WHERE YEARWEEK(tanggal, 1)=YEARWEEK(CURDATE(), 1)-1")->fetch_assoc()['jml'];
$bulan_ini = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan WHERE YEAR(tanggal)=YEAR(CURDATE()) AND MONTH(tanggal)=MONTH(CURDATE())")->fetch_assoc()['jml'];
$bulan_lalu = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan WHERE YEAR(tanggal)=YEAR(CURDATE()-INTERVAL 1 MONTH) AND MONTH(tanggal)=MONTH(CURDATE()-INTERVAL 1 MONTH)")->fetch_assoc()['jml'];
$total = $conn->query("SELECT COUNT(*) AS jml FROM kunjungan")->fetch_assoc()['jml'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desa Serang - Kabupaten Tasikmalaya</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* -------------------- Body -------------------- */
        body {
            font-family: Arial, sans-serif;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        /* -------------------- Header -------------------- */
        header {
            background: #006400;
            padding: 10px 40px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 55px;
            width: 55px;
            border-radius: 50%;
            margin-right: 12px;
            object-fit: cover;
        }

        .logo-text h5 {
            margin: 0;
            font-weight: bold;
        }

        .logo-text small {
            color: #e0e0e0;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
            font-weight: bold;
            margin: 0 5px;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 6px;
        }

        /* -------------------- Hero -------------------- */
        .hero {
            text-align: center;
            color: white;
            background: url('https://cdn.digitaldesa.com/statics/profil-v2/assets/bg-CUzdrKVN.webp') no-repeat center center/cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .hero h2 {
            font-size: 42px;
            font-weight: bold;
            text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7);
        }

        .hero p {
            font-size: 18px;
            max-width: 600px;
            margin: 10px auto 0;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.6);
        }

        /* -------------------- Jelajahi Desa -------------------- */
        .jelajahi h3 {
            color: black;
            /* Ubah judul menjadi hitam */
        }

        .jelajahi p {
            color: black;
            /* Ubah paragraf menjadi hitam */
        }

        /* -------------------- Statistik -------------------- */
        .stat-widget {
            position: fixed;
            bottom: 80px;
            left: 20px;
            background: rgba(0, 0, 0, 0.75);
            border-radius: 10px;
            overflow: hidden;
            width: 260px;
            font-size: 14px;
            color: #fff;
        }

        .stat-header {
            background: #006400;
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
        }

        .stat-body {
            display: none;
            padding: 10px;
        }

        .stat-body div {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
        }

        /* -------------------- Footer -------------------- */
        footer {
            background: rgba(0, 0, 0, 0.85);
            padding: 15px;
            text-align: center;
            font-size: 14px;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        /* -------------------- Aksesibilitas -------------------- */
        .aksesibilitas {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .akses-btn {
            background: linear-gradient(135deg, #28a745, #006400);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .akses-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.4);
        }

        .akses-menu {
            display: none;
            flex-direction: column;
            margin-top: 8px;
            background: #006400;
            padding: 8px;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 150px;
        }

        .akses-menu button {
            margin: 3px 0;
            padding: 6px 10px;
            font-size: 13px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background: linear-gradient(135deg, #28a745, #006400);
            color: white;
            transition: background 0.3s, transform 0.2s;
        }

        .akses-menu button:hover {
            background: linear-gradient(135deg, #006400, #28a745);
            transform: scale(1.05);
        }

        /* Mode aksesibilitas */
        .balik-warna {
            background-color: black !important;
            color: yellow !important;
        }

        .abu-abu {
            filter: grayscale(100%);
        }

        .garis-bawah * {
            text-decoration: underline !important;
        }

        .kursor-besar {
            cursor: pointer !important;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="https://cdn.digitaldesa.com/uploads/profil/32.06.14.2002/common/300_tasikmalaya.png" alt="Logo Desa Serang">
                <div class="logo-text">
                    <h5>Desa Serang</h5>
                    <small>Kabupaten Tasikmalaya</small>
                </div>
            </div>
            <nav class="navbar navbar-expand-lg navbar-dark p-0">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="profil-desa.php"><i class="bi bi-info-circle-fill"></i> Profil Desa</a></li>
                    <li class="nav-item"><a class="nav-link" href="listing.php"><i class="bi bi-list-ul"></i> Listing</a></li>
                    <li class="nav-item"><a class="nav-link" href="umkm.php"><i class="bi bi-shop"></i> UMKM</a></li>
                </ul>

            </nav>
        </div>
    </header>
    <!-- Tambahkan di dalam header, setelah logo -->
    <div class="mobile-menu d-lg-none">
        <button class="btn btn-light" id="kebabBtn">
            <i class="bi bi-list"></i> <!-- icon hamburger -->
        </button>
    </div>

    <!-- Sidebar menu -->
    <div id="sidebarNav" class="sidebar-nav">
        <ul class="list-unstyled">
            <li><a href="index.php"><i class="bi bi-house-door-fill"></i> Home</a></li>
            <li><a href="profil-desa.php"><i class="bi bi-info-circle-fill"></i> Profil Desa</a></li>
            <li><a href="listing.php"><i class="bi bi-list-ul"></i> Listing</a></li>
            <li><a href="umkm.php"><i class="bi bi-shop"></i> UMKM</a></li>
        </ul>
    </div>

    <style>
        /* Hanya muncul di mobile */
        .mobile-menu {
            display: none;
        }

        @media (max-width: 992px) {
            .navbar-nav {
                display: none !important;
            }

            /* sembunyikan nav normal */
            .mobile-menu {
                display: block;
            }
        }

        /* Sidebar */
        .sidebar-nav {
            position: fixed;
            top: 0;
            left: -250px;
            /* awalnya di luar layar */
            width: 250px;
            height: 100%;
            background-color: #064420;
            color: #fff;
            padding: 20px;
            transition: left 0.3s ease;
            z-index: 1050;
        }

        .sidebar-nav ul li {
            margin: 20px 0;
        }

        .sidebar-nav ul li a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-nav ul li a:hover {
            color: #28a745;
        }

        .sidebar-nav.show {
            left: 0;
            /* munculkan sidebar */
        }

        /* Tutup tombol di sidebar */
        .sidebar-nav::before {
            content: '×';
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            cursor: pointer;
        }
    </style>

    <script>
        const kebabBtn = document.getElementById('kebabBtn');
        const sidebarNav = document.getElementById('sidebarNav');

        kebabBtn.addEventListener('click', () => {
            sidebarNav.classList.toggle('show');
        });

        // Tutup sidebar jika klik tombol ×
        sidebarNav.addEventListener('click', (e) => {
            if (e.target === sidebarNav || e.target.tagName === 'BEFORE' || e.target.textContent === '×') {
                sidebarNav.classList.remove('show');
            }
        });
    </script>


    <!-- Hero Section -->
    <section class="hero text-white" style="background-color: #28a745; padding: 70px 20px; text-align: center;">
        <p>
            <b style="font-size: 30px;">UMKM Desa Serang</b><br>
            Menampilkan informasi UMKM lokal di Desa Serang secara lengkap dan interaktif
        </p>
        <a href="./pembeli/index.php"
            style="display: inline-block; margin-top: 20px; padding: 12px 25px; 
          background-color: #006400; color: #fff; font-weight: bold; 
          text-decoration: none; border-radius: 5px; transition: 0.3s;">
            Kunjungi UMKM
        </a>

    </section>








    <!-- Statistik -->
    <div class="stat-widget">
        <div class="stat-header" onclick="toggleStat()" style="background-color: #006400; color: black; padding: 10px; border-radius: 5px; cursor: pointer;">
            📊 Statistik Kunjungan
        </div>
        <div class="stat-body" id="statBody" style="display:none; padding: 10px;">
            <div><span>Hari Ini</span><span><?= $hari_ini ?></span></div>
            <div><span>Kemarin</span><span><?= $kemarin ?></span></div>
            <div><span>Minggu Ini</span><span><?= $minggu_ini ?></span></div>
            <div><span>Minggu Lalu</span><span><?= $minggu_lalu ?></span></div>
            <div><span>Bulan Ini</span><span><?= $bulan_ini ?></span></div>
            <div><span>Bulan Lalu</span><span><?= $bulan_lalu ?></span></div>
            <div><span>Total</span><span><?= $total ?></span></div>
        </div>
    </div>

    <style>
        .stat-widget {
            position: fixed;
            left: 20px;
            /* Jarak dari kiri */
            bottom: 20px;
            /* Jarak dari bawah */
            background-color: #ffffff;
            color: #000;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 200px;
            font-family: Arial, sans-serif;
            z-index: 1000;
            /* Supaya selalu di atas elemen lain */
        }

        .stat-header {
            cursor: pointer;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
        }

        .stat-body {
            padding: 10px;
        }

        .stat-body div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
    </style>

    <script>
        function toggleStat() {
            const statBody = document.getElementById("statBody");
            statBody.style.display = (statBody.style.display === "none") ? "block" : "none";
        }
    </script>

    <!-- Footer -->
    <footer style="background-color: #064420; color: #ffffff; padding: 40px 20px; font-family: Arial, sans-serif;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 30px;">

            <!-- Logo -->
            <div style="flex: 1 1 150px; display: flex; align-items: center; justify-content: center;">
                <img src="https://cdn.digitaldesa.com/uploads/profil/32.06.14.2002/common/300_tasikmalaya.png"
                    alt="Logo Desa Serang"
                    style="height: 120px;"> <!-- naikkan height sesuai kebutuhan -->
            </div>


            <!-- Informasi Kontak -->
            <div style="flex: 2 1 400px; font-size: 14px; line-height: 1.6;">
                <strong>Pemerintah Desa Serang</strong><br>
                [Alamat kantor]<br>
                Desa Serang, Kecamatan Salawu, Kabupaten Tasikmalaya<br>
                Provinsi Jawa Barat, 46472<br>
                Hubungi Kami:
                <a href="mailto:emaildesa@digitaldesa.id" style="color: #ffffff; text-decoration: underline;">emaildesa@digitaldesa.id</a>
            </div>

        </div>

        <!-- Garis pemisah -->
        <hr style="border-color: rgba(255,255,255,0.2); margin: 30px 0;">

        <!-- Copyright -->
        <div style="text-align: center; font-size: 13px;">
            &copy; <?= date('Y') ?> Pemerintah Desa Serang. All Rights Reserved.
        </div>
    </footer>
    <!-- Tombol Aksesibilitas -->
    <div class="aksesibilitas">
        <button class="akses-btn" onclick="toggleMenu()">
            <i class="bi bi-universal-access"></i>
        </button>
        <div id="aksesMenu" class="akses-menu">
            <button onclick="ubahUkuranTeks(1)">A+</button>
            <button onclick="ubahUkuranTeks(-1)">A-</button>
            <button onclick="ubahJarak(1)">+🔠</button>
            <button onclick="ubahJarak(-1)">-🔠</button>
            <button onclick="ubahTinggi(1)">+↕</button>
            <button onclick="ubahTinggi(-1)">-↕</button>
            <button onclick="balikWarna()">Balik Warna</button>
            <button onclick="abuAbu()">Abu-Abu</button>
            <button onclick="garisBawah()">Garis Bawah</button>
            <button onclick="perbesarKursor()">Kursor</button>
            <button onclick="bacaTeks()">🔊 Baca</button>
        </div>
    </div>

    <script>
        /* -------------------- Statistik -------------------- */
        function toggleStat() {
            let body = document.getElementById("statBody");
            body.style.display = body.style.display === "block" ? "none" : "block";
        }

        /* -------------------- Aksesibilitas -------------------- */
        function toggleMenu() {
            let menu = document.getElementById("aksesMenu");
            menu.style.display = menu.style.display === "flex" ? "none" : "flex";
        }

        let ukuranTeks = 16;
        let jarakHuruf = 0;
        let tinggiBaris = 1.5;
        let isReading = false;
        let utterance;

        function ubahUkuranTeks(arah) {
            ukuranTeks = Math.min(28, Math.max(12, ukuranTeks + arah));
            document.body.style.fontSize = ukuranTeks + "px";
        }

        function ubahJarak(arah) {
            jarakHuruf = Math.min(10, Math.max(-2, jarakHuruf + arah));
            document.body.style.letterSpacing = jarakHuruf + "px";
        }

        function ubahTinggi(arah) {
            tinggiBaris = Math.min(3, Math.max(1, tinggiBaris + arah * 0.2));
            document.body.style.lineHeight = tinggiBaris;
        }

        function balikWarna() {
            document.body.classList.toggle("balik-warna");
        }

        function abuAbu() {
            document.body.classList.toggle("abu-abu");
        }

        function garisBawah() {
            document.body.classList.toggle("garis-bawah");
        }

        function perbesarKursor() {
            document.body.classList.toggle("kursor-besar");
        }

        function bacaTeks() {
            if (isReading) {
                speechSynthesis.cancel();
                isReading = false;
            } else {
                utterance = new SpeechSynthesisUtterance(document.body.innerText);
                utterance.lang = "id-ID";
                speechSynthesis.speak(utterance);
                isReading = true;
                utterance.onend = () => {
                    isReading = false;
                };
            }
        }
    </script>
</body>

</html>