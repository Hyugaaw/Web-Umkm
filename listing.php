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

    <!-- Custom CSS -->
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

        /* -------------------- Listing Section -------------------- */
        .listing h3 {
            color: black;
        }

        .listing p {
            color: black;
        }

        /* -------------------- Google Maps -------------------- */
        #map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }

        /* -------------------- Statistik -------------------- */
        .stat-widget {
            position: fixed;
            left: 20px;
            bottom: 20px;
            background-color: #ffffff;
            color: #000;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 200px;
            font-family: Arial, sans-serif;
            z-index: 1000;
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

        /* -------------------- Footer -------------------- */
        footer {
            background-color: #064420;
            color: #ffffff;
            padding: 40px 20px;
            font-family: Arial, sans-serif;
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
    <section class="hero text-white">
        <p>
            <b style="font-size: 30px;">Listing Peta Desa</b><br>
            Menampilkan Peta Desa dengan berbagai Interest Point di Desa Serang secara interaktif
        </p>
    </section>

    <!-- Listing Section -->
    <section class="listing container my-5">
        <h3 class="mb-4 text-center">Listing Desa Serang</h3>
        <p class="text-center mb-5">Menampilkan lokasi dan fasilitas Desa Serang menggunakan Google Maps</p>

        <!-- Google Maps Section -->
        <div class="row">
            <div class="col-md-8">
                <div id="map"></div>
            </div>

            <!-- Fasilitas Desa -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Fasilitas Umum</h5>
                        <ul>
                            <li>Kantor Kepala Desa</li>
                            <li>Saung</li>
                            <li>Alun-Alun Salawu</li>
                            <li>Masjid</li>
                            <li>Sekolah</li>
                            <li>SPBU Pertamina 34.46404</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Kontak Desa</h5>
                        <p>Jl. Raya Desa Serang, Kab. Tasikmalaya</p>
                        <p>Telp: (0265) XXX XXXX</p>
                        <p>Email: info@desaserang.id</p>
                    </div>
                </div>
            </div>
        </div>
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


    <!-- Footer -->
    <footer>
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 30px;">
            <div style="flex: 1 1 150px; display: flex; align-items: center; justify-content: center;">
                <img src="https://cdn.digitaldesa.com/uploads/profil/32.06.14.2002/common/300_tasikmalaya.png" alt="Logo Desa Serang" style="height: 120px;">
            </div>
            <div style="flex: 2 1 400px; font-size: 14px; line-height: 1.6;">
                <strong>Pemerintah Desa Serang</strong><br>
                [Alamat kantor]<br>
                Desa Serang, Kecamatan Salawu, Kabupaten Tasikmalaya<br>
                Provinsi Jawa Barat, 46472<br>
                Hubungi Kami:
                <a href="mailto:emaildesa@digitaldesa.id" style="color: #ffffff; text-decoration: underline;">emaildesa@digitaldesa.id</a>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,0.2); margin: 30px 0;">
        <div style="text-align: center; font-size: 13px;">
            &copy; <?= date('Y') ?> Pemerintah Desa Serang. All Rights Reserved.
        </div>
    </footer>

    <!-- Google Maps JS -->
    <script>
        let map, marker;
        let hasAnimated = false;

        function initMap() {
            const desaSerang = {
                lat: -7.370902,
                lng: 108.0480504
            };

            map = new google.maps.Map(document.getElementById("map"), {
                center: {
                    lat: 0,
                    lng: 0
                },
                zoom: 2,
                mapTypeId: "hybrid",
                disableDefaultUI: true,
                gestureHandling: "none",
                zoomControl: false,
            });

            marker = new google.maps.Marker({
                position: desaSerang,
                title: "Desa Serang, Salawu, Tasikmalaya",
                map: null,
                animation: google.maps.Animation.DROP
            });

            const steps = [{
                    zoom: 3,
                    center: {
                        lat: 10,
                        lng: 100
                    }
                },
                {
                    zoom: 5,
                    center: {
                        lat: -2,
                        lng: 115
                    }
                },
                {
                    zoom: 7,
                    center: {
                        lat: -6.8,
                        lng: 108.5
                    }
                },
                {
                    zoom: 10,
                    center: {
                        lat: -7.3,
                        lng: 108.1
                    }
                },
                {
                    zoom: 13,
                    center: {
                        lat: -7.36,
                        lng: 108.05
                    }
                },
                {
                    zoom: 17,
                    center: desaSerang
                }
            ];

            function animateStep(index = 0) {
                if (index >= steps.length) return;

                const step = steps[index];
                const startZoom = map.getZoom();
                const targetZoom = step.zoom;
                const startCenter = map.getCenter().toJSON();
                const targetCenter = step.center;

                let progress = 0;

                function stepAnimation() {
                    progress += 0.02;
                    if (progress > 1) progress = 1;

                    map.setZoom(startZoom + (targetZoom - startZoom) * progress);
                    map.setCenter({
                        lat: startCenter.lat + (targetCenter.lat - startCenter.lat) * progress,
                        lng: startCenter.lng + (targetCenter.lng - startCenter.lng) * progress
                    });

                    if (progress < 1) {
                        requestAnimationFrame(stepAnimation);
                    } else {
                        if (targetZoom >= 14 && !marker.getMap()) {
                            marker.setMap(map);
                        }
                        setTimeout(() => animateStep(index + 1), 500);
                    }
                }
                requestAnimationFrame(stepAnimation);
            }

            document.getElementById("map").style.opacity = "1";

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !hasAnimated) {
                        animateStep();
                        hasAnimated = true;
                    }
                });
            }, {
                threshold: 0.3
            });

            observer.observe(document.getElementById("map"));
        }
    </script>

    <script async src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBHvfivaBSo3goQ-PjuVLwgx5JEEUC6g7M&callback=initMap"></script>

    <!-- Statistik & Aksesibilitas JS -->
    <script>
        function toggleStat() {
            let body = document.getElementById("statBody");
            body.style.display = body.style.display === "block" ? "none" : "block";
        }

        function toggleMenu() {
            let menu = document.getElementById("aksesMenu");
            menu.style.display = menu.style.display === "flex" ? "none" : "flex";
        }

        let ukuranTeks = 16,
            jarakHuruf = 0,
            tinggiBaris = 1.5,
            isReading = false,
            utterance;

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