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
    <title>Profil Desa - Desa Serang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
        }

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
            color: #fff;
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

        .hero {
            text-align: center;
            padding: 70px 20px;
            background-color: #28a745;
            color: #fff;
        }

        .hero h2 {
            font-size: 42px;
            font-weight: bold;
        }

        .hero p {
            font-size: 18px;
            max-width: 600px;
            margin: 10px auto 0;
        }

        .profil-container,
        .visi-misi,
        .sejarah {
            margin-top: 50px;
        }

        .visi-misi ul li {
            margin-bottom: 5px;
        }

        .stat-widget {
            position: fixed;
            left: 20px;
            bottom: 20px;
            background-color: #ffffff;
            color: #000;
            border: 1px solid #ccc;
            border-radius: 8px;
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
            display: none;
        }

        .stat-body div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        footer {
            background: #064420;
            color: #fff;
            padding: 40px 20px;
            text-align: center;
            font-family: Arial, sans-serif;
            margin-top: 50px;
        }

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
            cursor: pointer;
        }

        .akses-menu {
            display: none;
            flex-direction: column;
            margin-top: 8px;
            background: #006400;
            padding: 8px;
            border-radius: 10px;
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
        }

        .akses-menu button:hover {
            background: linear-gradient(135deg, #006400, #28a745);
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
                    <li class="nav-item"><a class="nav-link active" href="profil-desa.php"><i class="bi bi-info-circle-fill"></i> Profil Desa</a></li>
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

    <!-- Hero -->
    <section class="hero">
        <h2>Profil Desa Serang</h2>
        <p>Temukan informasi lengkap mengenai sejarah, visi-misi, peta desa Serang</p>
    </section>

    <!-- Profil Desa -->
    <section class="profil-container container">
        <h3>Tentang Desa Serang</h3>
        <p>Desa Serang terletak di Kabupaten Tasikmalaya. Desa ini memiliki potensi alam dan budaya yang kaya, serta penduduk yang ramah dan kreatif. Melalui website ini, Anda dapat mengetahui informasi terkait pemerintahan desa, fasilitas publik, UMKM lokal, serta berita dan kegiatan desa terbaru.</p>
        <p>Fasilitas di Desa Serang meliputi balai desa, posyandu, sekolah dasar, dan sarana olahraga. Selain itu, UMKM lokal berkembang pesat dengan berbagai produk unggulan yang dapat dimanfaatkan oleh masyarakat maupun wisatawan.</p>
    </section>

    <!-- Visi & Misi -->
    <section class="visi-misi container">
        <h3>Visi & Misi Desa Serang</h3>
        <div class="row">
            <div class="col-md-6">
                <h5>Visi</h5>
                <p>Terwujudnya Desa Serang yang maju, mandiri, dan berbudaya, dengan masyarakat sejahtera dan lingkungan yang lestari.</p>
            </div>
            <div class="col-md-6">
                <h5>Misi</h5>
                <ul>
                    <li>Meningkatkan kualitas pendidikan dan kesehatan masyarakat.</li>
                    <li>Mengembangkan ekonomi lokal dan UMKM.</li>
                    <li>Melestarikan budaya dan lingkungan desa.</li>
                    <li>Meningkatkan pelayanan publik dan partisipasi masyarakat.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Sejarah Desa -->
    <section class="sejarah container">
        <h3>Sejarah Desa Serang</h3>
        <p>Desa Serang memiliki sejarah panjang yang dimulai sejak abad ke-18. Desa ini awalnya merupakan wilayah pertanian dengan masyarakat yang hidup bersatu dalam tradisi dan budaya lokal. Seiring waktu, Desa Serang berkembang menjadi desa yang mandiri dengan fasilitas publik, pendidikan, dan UMKM yang semakin maju.</p>
    </section>

    <!-- Google Maps Section -->
    <section class="maps container my-5">
        <h3 style="font-size: 35px; font-weight: bold; color: black;">Lokasi Desa Serang</h3>
        <p style="font-size: 18px; color: black;">
            Temukan lokasi Desa Serang di peta berikut.
        </p>
        <div id="map" style="width: 100%; height: 400px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); opacity:0;"></div>
    </section>

    <style>
        #map {
            width: 100%;
            height: 100vh;
            /* full screen tinggi */
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.4);
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
        }
    </style>

    <script>
        let map, marker;
        let hasAnimated = false; // supaya animasi hanya sekali per scroll

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
                    progress += 0.02; // kontrol kecepatan
                    if (progress > 1) progress = 1;

                    const currentZoom = startZoom + (targetZoom - startZoom) * progress;
                    map.setZoom(currentZoom);

                    const currentLat = startCenter.lat + (targetCenter.lat - startCenter.lat) * progress;
                    const currentLng = startCenter.lng + (targetCenter.lng - startCenter.lng) * progress;
                    map.setCenter({
                        lat: currentLat,
                        lng: currentLng
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

            // Fade-in peta
            document.getElementById("map").style.opacity = "1";

            // Gunakan Intersection Observer agar animasi jalan hanya jika section terlihat
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !hasAnimated) {
                        animateStep();
                        hasAnimated = true;
                    }
                });
            }, {
                threshold: 0.3
            }); // minimal 30% terlihat

            observer.observe(document.getElementById("map"));
        }
    </script>

    <script async
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBHvfivaBSo3goQ-PjuVLwgx5JEEUC6g7M&callback=initMap">
    </script>



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

    <!-- Aksesibilitas -->
    <div class="aksesibilitas">
        <button class="akses-btn" onclick="toggleMenu()"><i class="bi bi-universal-access"></i></button>
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
                }
            }
        }
    </script>


</body>

</html>