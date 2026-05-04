<?php
session_start();
require_once dirname(__DIR__, 2) . '/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // AJAX mode: produk_id & jumlah
    $is_ajax = (isset($_POST['ajax']) && $_POST['ajax'] == '1') ||
        (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

    $produk_id = isset($_POST['produk_id']) ? (int)$_POST['produk_id'] : (isset($_POST['id_produk']) ? (int)$_POST['id_produk'] : 0);
    $jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;
    $user_id = $_SESSION['user_id'] ?? 0;
    if (!$user_id || !$produk_id || $jumlah < 1) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid!']);
            exit;
        }
        header('Location: ../index2.php');
        exit();
    }

    // Ambil stok terbaru
    $stmt = $conn->prepare('SELECT nama, path_gambar, harga, stok FROM produk WHERE id = ? AND status = "aktif"');
    $stmt->bind_param('i', $produk_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $produk = $res->fetch_assoc();
    $stmt->close();
    if (!$produk) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan!']);
            exit;
        }
        header('Location: ../index2.php');
        exit();
    }
    $stok_terbaru = (int)$produk['stok'];
    if ($stok_terbaru < $jumlah) {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak cukup!', 'stok_terbaru' => $stok_terbaru]);
            exit;
        }
        header('Location: ../index2.php');
        exit();
    }
    // Kurangi stok produk
    $stmt = $conn->prepare('UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?');
    $stmt->bind_param('iii', $jumlah, $produk_id, $jumlah);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        // Tambahkan ke user_cart (insert/update)

        // Cek apakah produk sudah ada di user_cart (gunakan produk_id)
        $stmt_cek_cart = $conn->prepare('SELECT jumlah FROM user_cart WHERE user_id = ? AND produk_id = ?');
        $stmt_cek_cart->bind_param('ii', $user_id, $produk_id);
        $stmt_cek_cart->execute();
        $res_cek_cart = $stmt_cek_cart->get_result();
        if ($row_cart = $res_cek_cart->fetch_assoc()) {
            // Jika sudah ada, update jumlah (replace, bukan tambah)
            $new_jumlah = $row_cart['jumlah'] + $jumlah;
            $stmt2 = $conn->prepare('UPDATE user_cart SET jumlah = ? WHERE user_id = ? AND produk_id = ?');
            $stmt2->bind_param('iii', $new_jumlah, $user_id, $produk_id);
            $stmt2->execute();
            $stmt2->close();
            $jumlah_final = $new_jumlah;
        } else {
            // Insert baru
            // Simpan juga produk_id agar unik per produk (bukan hanya nama)
            $stmt2 = $conn->prepare('INSERT INTO user_cart (user_id, produk_id, nama_produk, gambar, jumlah) VALUES (?, ?, ?, ?, ?)');
            $stmt2->bind_param('iissi', $user_id, $produk_id, $produk['nama'], $produk['path_gambar'], $jumlah);
            $stmt2->execute();
            $stmt2->close();
            $jumlah_final = $jumlah;
        }
        $stmt_cek_cart->close();

        // Sinkronisasi ke tabel keranjang (insert/update)
        // Jika sudah ada, update jumlah, jika belum insert baru
        // Sinkronisasi ke tabel keranjang (jumlah harus sama dengan user_cart)
        $stmt_cek = $conn->prepare('SELECT id FROM keranjang WHERE pembeli_id = ? AND produk_id = ?');
        $stmt_cek->bind_param('ii', $user_id, $produk_id);
        $stmt_cek->execute();
        $res_cek = $stmt_cek->get_result();
        if ($row_cek = $res_cek->fetch_assoc()) {
            // Update jumlah (replace, bukan tambah)
            $stmt_upd = $conn->prepare('UPDATE keranjang SET jumlah = ? WHERE pembeli_id = ? AND produk_id = ?');
            $stmt_upd->bind_param('iii', $jumlah_final, $user_id, $produk_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        } else {
            // Insert baru
            $stmt_ins = $conn->prepare('INSERT INTO keranjang (pembeli_id, produk_id, jumlah) VALUES (?, ?, ?)');
            $stmt_ins->bind_param('iii', $user_id, $produk_id, $jumlah_final);
            $stmt_ins->execute();
            $stmt_ins->close();
        }
        $stmt_cek->close();
        // Ambil stok terbaru
        $stmt3 = $conn->prepare('SELECT stok FROM produk WHERE id = ?');
        $stmt3->bind_param('i', $produk_id);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        $row3 = $res3->fetch_assoc();
        $stmt3->close();
        if ($is_ajax) {
            echo json_encode(['success' => true, 'stok_terbaru' => (int)$row3['stok']]);
            exit;
        }
        header('Location: ../index2.php');
        exit();
    } else {
        if ($is_ajax) {
            echo json_encode(['success' => false, 'message' => 'Gagal update stok!']);
            exit;
        }
        header('Location: ../index2.php');
        exit();
    }
}
?>

<!-- Bagian di bawah ini hanya tampil jika produk berhasil ditambahkan -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Produk Ditambahkan ke Keranjang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fafafa;
            text-align: center;
            padding-top: 50px;
        }

        img {
            width: 200px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        .btn {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
        }

        .btn:hover {
            background: #218838;
        }
    </style>
</head>

<body>

    <?php
    // Menampilkan gambar otomatis dari folder index2/pembeli
    if (isset($_POST["gambar"])) {
        $gambar = htmlspecialchars($_POST["gambar"]);
        $path = "index2/pembeli/" . basename($gambar);
        if (file_exists($path)) {
            echo "<img src='$path' alt='Produk'>";
        } else {
            echo "<p><strong>Gambar tidak ditemukan di folder index2/pembeli/</strong></p>";
        }
    }
    ?>

    <h2>Produk berhasil ditambahkan ke keranjang!</h2>
    <a href="keranjang.php" class="btn">Lihat Keranjang</a>
    <a href="index2/pembeli/" class="btn" style="background:#007bff;">Kembali ke Produk</a>

</body>

</html>