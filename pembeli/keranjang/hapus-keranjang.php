<?php
session_start();

// Pastikan user login
if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit();
}

require_once dirname(__DIR__, 2) . '/koneksi.php';
$user_id = $_SESSION["user_id"];

// Hapus semua keranjang jika menerima POST 'hapus_semua'
if (isset($_POST["hapus_semua"])) {
    // Kembalikan stok semua produk ke tabel produk (gunakan produk_id untuk akurasi)
    $stmt_cart = $conn->prepare("SELECT produk_id, jumlah FROM user_cart WHERE user_id = ?");
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $res_cart = $stmt_cart->get_result();
    while ($row = $res_cart->fetch_assoc()) {
        if (!empty($row['produk_id'])) {
            // Update stok produk berdasarkan produk_id
            $stmt_upd = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
            $stmt_upd->bind_param("ii", $row['jumlah'], $row['produk_id']);
            $stmt_upd->execute();
            $stmt_upd->close();
        }
    }
    $stmt_cart->close();

    unset($_SESSION["keranjang"]);
    // Hapus juga dari database
    $stmt = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    // Hapus juga dari tabel keranjang (untuk penjual)
    $stmtk = $conn->prepare("DELETE FROM keranjang WHERE pembeli_id = ?");
    $stmtk->bind_param("i", $user_id);
    $stmtk->execute();
    $stmtk->close();
    // Reset applied discount in session so voucher kembali ke semula (tidak dipakai)
    if (isset($_SESSION['applied_discount'])) {
        unset($_SESSION['applied_discount']);
    }
}
// Hapus satu item jika menerima GET 'index'
elseif (isset($_GET["index"])) {
    $index = (int) $_GET["index"];
    if (isset($_SESSION["keranjang"])) {
        if (isset($_SESSION["keranjang"][$index])) {
            $item = $_SESSION["keranjang"][$index];
            $jumlah = $item["jumlah"] ?? 1;
            // Gunakan produk_id dari session (atau fallback ke 'id' jika ada) untuk mengembalikan stok
            $produk_id = null;
            if (isset($item['produk_id']) && $item['produk_id']) {
                $produk_id = (int)$item['produk_id'];
            } elseif (isset($item['id']) && $item['id']) {
                $produk_id = (int)$item['id'];
            }
            if ($produk_id && $jumlah > 0) {
                $stmt_upd = $conn->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?");
                $stmt_upd->bind_param("ii", $jumlah, $produk_id);
                $stmt_upd->execute();
                $stmt_upd->close();
            }

            unset($_SESSION["keranjang"][$index]);
            $_SESSION["keranjang"] = array_values($_SESSION["keranjang"]);
            // Sinkronkan ke database: hapus semua, lalu insert ulang sisa keranjang
            $conn->query("DELETE FROM user_cart WHERE user_id = $user_id");
            $conn->query("DELETE FROM keranjang WHERE pembeli_id = $user_id");
            if (!empty($_SESSION["keranjang"])) {
                $stmt = $conn->prepare("INSERT INTO user_cart (user_id, produk_id, nama_produk, gambar, jumlah) VALUES (?, ?, ?, ?, ?)");
                $stmtk = $conn->prepare("INSERT INTO keranjang (pembeli_id, produk_id, jumlah) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE jumlah = VALUES(jumlah)");
                foreach ($_SESSION["keranjang"] as $item) {
                    $nama = $item["nama_produk"] ?? '';
                    $gambar = $item["gambar"] ?? '';
                    $jumlah = $item["jumlah"] ?? 1;
                    $produk_id = isset($item['produk_id']) ? (int)$item['produk_id'] : (isset($item['id']) ? (int)$item['id'] : null);
                    // Fallback: jika produk_id tidak ada di session, cari berdasarkan nama
                    if (!$produk_id && $nama) {
                        $stmtp = $conn->prepare("SELECT id FROM produk WHERE nama = ? LIMIT 1");
                        $stmtp->bind_param("s", $nama);
                        $stmtp->execute();
                        $resp = $stmtp->get_result();
                        if ($rowp = $resp->fetch_assoc()) {
                            $produk_id = $rowp['id'];
                        }
                        $stmtp->close();
                    }
                    $produk_id = (int) ($produk_id ?? 0);
                    $stmt->bind_param("iissi", $user_id, $produk_id, $nama, $gambar, $jumlah);
                    $stmt->execute();
                    if ($produk_id > 0) {
                        $stmtk->bind_param("iii", $user_id, $produk_id, $jumlah);
                        $stmtk->execute();
                    }
                }
                $stmt->close();
                $stmtk->close();
            }
        }
    }
}

// Balik lagi ke keranjang.php
header("Location: keranjang.php");
exit();
