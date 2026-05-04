<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// ======================
// CEK APAKAH PENGGUNA SUDAH PUNYA DISKON
// ======================
$cek_diskon_user = $conn->prepare("SELECT COUNT(*) AS total FROM pengguna_diskon WHERE pengguna_id = ?");
$cek_diskon_user->bind_param("i", $user_id);
$cek_diskon_user->execute();
$res_cek = $cek_diskon_user->get_result()->fetch_assoc();
$cek_diskon_user->close();

// ======================
// CEK TANGGAL DAN HARI BESAR
// ======================
$tanggal_sekarang = date("d");
$bulan_sekarang = date("m");
$tanggal_full = date("Y-m-d");

$hari_besar = [
    '2025-01-01' => 'Tahun Baru Masehi',
    '2025-03-31' => 'Nyepi',
    '2025-04-18' => 'Waisak',
    '2025-04-28' => 'Idul Fitri',
    '2025-04-29' => 'Idul Fitri Hari ke-2',
    '2025-06-06' => 'Idul Adha',
    '2025-06-27' => 'Tahun Baru Islam',
    '2025-12-25' => 'Natal'
];

$hari_besar_hari_ini = isset($hari_besar[$tanggal_full]) ? $hari_besar[$tanggal_full] : null;
$tanggal_kembar = ($tanggal_sekarang == $bulan_sekarang);

// ======================
// JIKA PENGGUNA BELUM PUNYA DISKON, BERIKAN DISKON ACAK
// ======================
if ($res_cek['total'] == 0) {
    $stmt_rand = $conn->prepare("
        SELECT id 
        FROM diskon
        WHERE status = 'aktif'
          AND (tanggal_mulai IS NULL OR tanggal_mulai <= CURDATE())
          AND (tanggal_selesai IS NULL OR tanggal_selesai >= CURDATE())
        ORDER BY RAND()
        LIMIT 1
    ");
    $stmt_rand->execute();
    $res_rand = $stmt_rand->get_result();
    if ($row_diskon = $res_rand->fetch_assoc()) {
        $diskon_id_baru = $row_diskon['id'];
        $stmt_insert = $conn->prepare("
            INSERT INTO pengguna_diskon (pengguna_id, diskon_id, tanggal_didapat)
            VALUES (?, ?, NOW())
        ");
        $stmt_insert->bind_param("ii", $user_id, $diskon_id_baru);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    $stmt_rand->close();
}

// ======================
// DISKON KHUSUS HARI BESAR
// ======================
if ($hari_besar_hari_ini) {
    $stmt_cek_hb = $conn->prepare("SELECT id FROM diskon WHERE nama_diskon = ? LIMIT 1");
    $nama_hb = "Diskon " . $hari_besar_hari_ini;
    $stmt_cek_hb->bind_param("s", $nama_hb);
    $stmt_cek_hb->execute();
    $res_hb = $stmt_cek_hb->get_result();
    if ($res_hb->num_rows > 0) {
        $diskon_id_hb = $res_hb->fetch_assoc()['id'];
    } else {
        $kode_hb = strtoupper("HB" . date("dmY"));
        $desc = "Diskon spesial memperingati " . $hari_besar_hari_ini;
        $stmt_tambah_hb = $conn->prepare("
            INSERT INTO diskon (nama_diskon, kode_diskon, deskripsi, persentase, status, tanggal_mulai, tanggal_selesai)
            VALUES (?, ?, ?, 20, 'aktif', CURDATE(), CURDATE())
        ");
        $stmt_tambah_hb->bind_param("sss", $nama_hb, $kode_hb, $desc);
        $stmt_tambah_hb->execute();
        $diskon_id_hb = $stmt_tambah_hb->insert_id;
        $stmt_tambah_hb->close();
    }
    $stmt_cek_hb->close();

    $cek_user_hb = $conn->prepare("SELECT COUNT(*) AS jml FROM pengguna_diskon WHERE pengguna_id = ? AND diskon_id = ?");
    $cek_user_hb->bind_param("ii", $user_id, $diskon_id_hb);
    $cek_user_hb->execute();
    $jml_hb = $cek_user_hb->get_result()->fetch_assoc()['jml'];
    $cek_user_hb->close();

    if ($jml_hb == 0) {
        $stmt_insert_hb = $conn->prepare("
            INSERT INTO pengguna_diskon (pengguna_id, diskon_id, tanggal_didapat)
            VALUES (?, ?, NOW())
        ");
        $stmt_insert_hb->bind_param("ii", $user_id, $diskon_id_hb);
        $stmt_insert_hb->execute();
        $stmt_insert_hb->close();
    }
}

// ======================
// DISKON KHUSUS TANGGAL KEMBAR
// ======================
if ($tanggal_kembar) {
    $nama_tk = "Diskon Tanggal Kembar $tanggal_sekarang/$bulan_sekarang";
    $stmt_cek_tk = $conn->prepare("SELECT id FROM diskon WHERE nama_diskon = ? LIMIT 1");
    $stmt_cek_tk->bind_param("s", $nama_tk);
    $stmt_cek_tk->execute();
    $res_tk = $stmt_cek_tk->get_result();
    if ($res_tk->num_rows > 0) {
        $diskon_id_tk = $res_tk->fetch_assoc()['id'];
    } else {
        $kode_tk = strtoupper("TK" . date("dm"));
        $desc_tk = "Diskon spesial tanggal kembar $tanggal_sekarang/$bulan_sekarang!";
        $stmt_tambah_tk = $conn->prepare("
            INSERT INTO diskon (nama_diskon, kode_diskon, deskripsi, persentase, status, tanggal_mulai, tanggal_selesai)
            VALUES (?, ?, ?, 11, 'aktif', CURDATE(), CURDATE())
        ");
        $stmt_tambah_tk->bind_param("sss", $nama_tk, $kode_tk, $desc_tk);
        $stmt_tambah_tk->execute();
        $diskon_id_tk = $stmt_tambah_tk->insert_id;
        $stmt_tambah_tk->close();
    }
    $stmt_cek_tk->close();

    $cek_user_tk = $conn->prepare("SELECT COUNT(*) AS jml FROM pengguna_diskon WHERE pengguna_id = ? AND diskon_id = ?");
    $cek_user_tk->bind_param("ii", $user_id, $diskon_id_tk);
    $cek_user_tk->execute();
    $jml_tk = $cek_user_tk->get_result()->fetch_assoc()['jml'];
    $cek_user_tk->close();

    if ($jml_tk == 0) {
        $stmt_insert_tk = $conn->prepare("
            INSERT INTO pengguna_diskon (pengguna_id, diskon_id, tanggal_didapat)
            VALUES (?, ?, NOW())
        ");
        $stmt_insert_tk->bind_param("ii", $user_id, $diskon_id_tk);
        $stmt_insert_tk->execute();
        $stmt_insert_tk->close();
    }
}

// ======================
// CEK KOLOM tanggal_digunakan
// ======================
$kolom_ada = false;
$cek_kolom = $conn->query("SHOW COLUMNS FROM pengguna_diskon LIKE 'tanggal_digunakan'");
if ($cek_kolom && $cek_kolom->num_rows > 0) {
    $kolom_ada = true;
}

// ======================
// DISKON SUDAH DIGUNAKAN
// ======================
$diskon_digunakan = [];
if ($kolom_ada) {
    $stmt2 = $conn->prepare("
        SELECT d.*, pd.tanggal_digunakan
        FROM pengguna_diskon pd
        JOIN diskon d ON pd.diskon_id = d.id
        WHERE pd.pengguna_id = ?
          AND pd.tanggal_digunakan IS NOT NULL
        ORDER BY pd.tanggal_digunakan DESC
    ");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $diskon_digunakan[] = $row['id'];

        // Otomatis ubah status diskon menjadi nonaktif jika sudah digunakan
        $update_status = $conn->prepare("UPDATE diskon SET status = 'nonaktif' WHERE id = ?");
        $update_status->bind_param("i", $row['id']);
        $update_status->execute();
        $update_status->close();
    }
    $stmt2->close();
}

// ======================
// DISKON AKTIF (BELUM DIGUNAKAN)
// ======================
$diskon_aktif = [];
$query_aktif = "
    SELECT d.*, pd.id AS pengguna_diskon_id, pd.tanggal_didapat
    FROM pengguna_diskon pd
    JOIN diskon d ON pd.diskon_id = d.id
    WHERE pd.pengguna_id = ?
      AND d.status = 'aktif'
      AND (d.tanggal_mulai IS NULL OR d.tanggal_mulai <= CURDATE())
      AND (d.tanggal_selesai IS NULL OR d.tanggal_selesai >= CURDATE())
";
if ($kolom_ada) $query_aktif .= " AND pd.tanggal_digunakan IS NULL";
$stmt_user_diskon = $conn->prepare($query_aktif);
$stmt_user_diskon->bind_param("i", $user_id);
$stmt_user_diskon->execute();
$res_user_diskon = $stmt_user_diskon->get_result();
while ($row = $res_user_diskon->fetch_assoc()) {
    $diskon_aktif[] = $row;
}
$stmt_user_diskon->close();

// ======================
// TAMBAHKAN DISKON GLOBAL (tidak duplikat dan belum digunakan)
// ======================
$stmt_global = $conn->prepare("
    SELECT * FROM diskon
    WHERE status = 'aktif'
      AND (tanggal_mulai IS NULL OR tanggal_mulai <= CURDATE())
      AND (tanggal_selesai IS NULL OR tanggal_selesai >= CURDATE())
");
$stmt_global->execute();
$res_global = $stmt_global->get_result();
while ($g = $res_global->fetch_assoc()) {
    if (in_array($g['id'], $diskon_digunakan)) continue; // sudah digunakan skip
    $ada = false;
    foreach ($diskon_aktif as $ex) {
        if ($ex['id'] == $g['id']) {
            $ada = true;
            break;
        }
    }
    if (!$ada) $diskon_aktif[] = $g;
}
$stmt_global->close();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diskon Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f7f7f7;
        }

        .diskon-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 18px;
        }

        .diskon-card .card-header {
            background: #f7f7f7;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }

        .badge-status {
            float: right;
            margin-top: 2px;
        }

        .header-bar {
            background-color: #A8E6CF;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-bar span {
            color: #008000;
            font-size: 17px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <header class="header-bar">
        <div class="logo" style="display:flex; align-items:center; gap:10px;">
            <img src="logo.png" alt="Logo MyMarketplace" style="height:40px;" />
        </div>
        <span>Diskon Saya</span>
    </header>

    <div class="container py-4">
        <h3 class="mb-4">Diskon yang Bisa Digunakan</h3>
        <?php if (count($diskon_aktif) === 0): ?>
            <div class="alert alert-info">Belum ada diskon yang tersedia untuk Anda saat ini.</div>
        <?php else: ?>
            <?php foreach ($diskon_aktif as $d): ?>
                <div class="diskon-card card mb-3">
                    <div class="card-header">
                        <?= htmlspecialchars($d['nama_diskon']) ?>
                        <span class="badge bg-success badge-status">Belum Digunakan</span>
                    </div>
                    <div class="card-body">
                        <p><b>Kode Diskon:</b> <?= htmlspecialchars($d['kode_diskon']) ?></p>
                        <p><b>Deskripsi:</b> <?= htmlspecialchars($d['deskripsi'] ?? '-') ?></p>
                        <p>
                            <b>Potongan:</b>
                            <?php
                            if ($d['persentase']) {
                                echo $d['persentase'] . "%";
                            } elseif ($d['potongan_tetap']) {
                                echo "Rp " . number_format($d['potongan_tetap'], 0, ',', '.');
                            } else {
                                echo "-";
                            }
                            ?>
                        </p>
                        <p>
                            <b>Berlaku:</b>
                            <?= $d['tanggal_mulai'] ? date('d-m-Y', strtotime($d['tanggal_mulai'])) : '-' ?> s/d
                            <?= $d['tanggal_selesai'] ? date('d-m-Y', strtotime($d['tanggal_selesai'])) : '-' ?>
                        </p>
                        <a href="keranjang/keranjang.php?kode_diskon=<?= urlencode($d['kode_diskon']) ?>" class="btn btn-success" style="background:#00ab55; border:none;">Gunakan Diskon</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h3 class="mb-4 mt-5">Riwayat Diskon yang Sudah Digunakan</h3>
        <?php if (!$kolom_ada || count($diskon_digunakan) === 0): ?>
            <div class="alert alert-info">Belum ada riwayat penggunaan diskon.</div>
        <?php else: ?>
            <?php
            $stmt3 = $conn->prepare("
            SELECT d.*, pd.tanggal_digunakan
            FROM pengguna_diskon pd
            JOIN diskon d ON pd.diskon_id = d.id
            WHERE pd.pengguna_id = ? AND pd.tanggal_digunakan IS NOT NULL
            ORDER BY pd.tanggal_digunakan DESC
        ");
            $stmt3->bind_param("i", $user_id);
            $stmt3->execute();
            $res3 = $stmt3->get_result();
            while ($d = $res3->fetch_assoc()): ?>
                <div class="diskon-card card mb-3">
                    <div class="card-header">
                        <?= htmlspecialchars($d['nama_diskon']) ?>
                        <span class="badge bg-secondary badge-status">Sudah Digunakan</span>
                    </div>
                    <div class="card-body">
                        <p><b>Kode Diskon:</b> <?= htmlspecialchars($d['kode_diskon']) ?></p>
                        <p><b>Deskripsi:</b> <?= htmlspecialchars($d['deskripsi'] ?? '-') ?></p>
                        <p>
                            <b>Potongan:</b>
                            <?php
                            if ($d['persentase']) {
                                echo $d['persentase'] . "%";
                            } elseif ($d['potongan_tetap']) {
                                echo "Rp " . number_format($d['potongan_tetap'], 0, ',', '.');
                            } else {
                                echo "-";
                            }
                            ?>
                        </p>
                        <p>
                            <b>Berlaku:</b>
                            <?= $d['tanggal_mulai'] ? date('d-m-Y', strtotime($d['tanggal_mulai'])) : '-' ?> s/d
                            <?= $d['tanggal_selesai'] ? date('d-m-Y', strtotime($d['tanggal_selesai'])) : '-' ?>
                        </p>
                        <p style="color:#888;">Digunakan pada: <?= date('d-m-Y H:i', strtotime($d['tanggal_digunakan'])) ?></p>
                    </div>
                </div>
            <?php endwhile;
            $stmt3->close(); ?>
        <?php endif; ?>

        <div style="text-align:center; margin-top:32px;">
            <a href="index2.php" class="btn btn-success" style="background:#00ab55; border:none; font-weight:600; font-size:16px; padding:10px 32px; border-radius:24px;">&larr; Kembali</a>
        </div>
    </div>
</body>

</html>