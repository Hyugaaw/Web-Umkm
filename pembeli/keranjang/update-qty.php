<?php
session_start();
// Ensure responses are JSON and hide HTML error output from breaking JS
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline){
    error_log("PHP error [$errno] $errstr in $errfile on line $errline");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $errstr]);
    exit;
});

set_exception_handler(function($e){
    error_log("Uncaught exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server exception: ' . $e->getMessage()]);
    exit;
});

if (!isset($_SESSION['keranjang'])) {
    echo json_encode(['success' => false, 'message' => 'Keranjang tidak ditemukan']);
    exit;
}

$index = isset($_POST['index']) ? intval($_POST['index']) : -1;
$jumlah = isset($_POST['jumlah']) ? intval($_POST['jumlah']) : 1;

if ($index < 0 || !isset($_SESSION['keranjang'][$index])) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit;
}


if ($jumlah < 1) $jumlah = 1;

require_once dirname(__DIR__, 2) . '/koneksi.php';
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id && isset($_SESSION['keranjang'][$index]['nama_produk'])) {
        $nama_produk = $_SESSION['keranjang'][$index]['nama_produk'];
        $jumlah_lama = $_SESSION['keranjang'][$index]['jumlah'];
        $selisih = $jumlah - $jumlah_lama;

        // Try to get produk_id and penjual_id from POST first, then session to scope updates
        $produk_id = isset($_POST['produk_id']) ? intval($_POST['produk_id']) : (isset($_SESSION['keranjang'][$index]['produk_id']) ? intval($_SESSION['keranjang'][$index]['produk_id']) : 0);
        $penjual_id = isset($_POST['penjual_id']) && $_POST['penjual_id'] !== '' ? intval($_POST['penjual_id']) : (isset($_SESSION['keranjang'][$index]['penjual_id']) ? intval($_SESSION['keranjang'][$index]['penjual_id']) : null);

        // Update stok produk sesuai selisih — choose product row using produk_id if available, else try (nama + penjual_id) or fallback to nama
        if ($produk_id > 0) {
            $stmt_produk = $conn->prepare('SELECT id, stok FROM produk WHERE id = ? LIMIT 1');
            $stmt_produk->bind_param('i', $produk_id);
        } elseif ($penjual_id !== null) {
            $stmt_produk = $conn->prepare('SELECT id, stok FROM produk WHERE nama = ? AND penjual_id = ? LIMIT 1');
            $stmt_produk->bind_param('si', $nama_produk, $penjual_id);
        } else {
            $stmt_produk = $conn->prepare('SELECT id, stok FROM produk WHERE nama = ? LIMIT 1');
            $stmt_produk->bind_param('s', $nama_produk);
        }
        $stmt_produk->execute();
        $res_produk = $stmt_produk->get_result();
        if ($produk = $res_produk->fetch_assoc()) {
            $produk_id = $produk['id'];
            $stok_sekarang = (int)$produk['stok'];
        if ($selisih > 0) {
            // Tambah jumlah di keranjang, kurangi stok
            if ($stok_sekarang >= $selisih) {
                $stmt_stok = $conn->prepare('UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?');
                $stmt_stok->bind_param('iii', $selisih, $produk_id, $selisih);
                $stmt_stok->execute();
                $stmt_stok->close();
                $_SESSION['keranjang'][$index]['jumlah'] = $jumlah;
            } else {
                // Tidak cukup stok, batalkan update
                echo json_encode(['success' => false, 'message' => 'Stok tidak cukup!']);
                $stmt_produk->close();
                exit;
            }
        } elseif ($selisih < 0) {
            // Kurangi jumlah di keranjang, tambahkan stok
            $stmt_stok = $conn->prepare('UPDATE produk SET stok = stok + ? WHERE id = ?');
            $plus = abs($selisih);
            $stmt_stok->bind_param('ii', $plus, $produk_id);
            $stmt_stok->execute();
            $stmt_stok->close();
            $_SESSION['keranjang'][$index]['jumlah'] = $jumlah;
        } else {
            // Tidak ada perubahan
        }

        // Update jumlah di user_cart (database) — scope by produk_id or penjual_id if available
        if ($produk_id > 0) {
            $stmt_upd_cart = $conn->prepare('UPDATE user_cart SET jumlah = ? WHERE user_id = ? AND produk_id = ? LIMIT 1');
            $stmt_upd_cart->bind_param('iii', $jumlah, $user_id, $produk_id);
        } elseif ($penjual_id !== null) {
            $stmt_upd_cart = $conn->prepare('UPDATE user_cart SET jumlah = ? WHERE user_id = ? AND nama_produk = ? AND penjual_id = ? LIMIT 1');
            $stmt_upd_cart->bind_param('iisi', $jumlah, $user_id, $nama_produk, $penjual_id);
        } else {
            $stmt_upd_cart = $conn->prepare('UPDATE user_cart SET jumlah = ? WHERE user_id = ? AND nama_produk = ? LIMIT 1');
            $stmt_upd_cart->bind_param('iis', $jumlah, $user_id, $nama_produk);
        }
        if ($stmt_upd_cart) {
            $stmt_upd_cart->execute();
            $stmt_upd_cart->close();
        }

        // Sinkronisasi ke tabel keranjang (update jumlah) only when produk_id known
        if ($produk_id > 0) {
            $stmt_upd = $conn->prepare('UPDATE keranjang SET jumlah = ? WHERE pembeli_id = ? AND produk_id = ?');
            $stmt_upd->bind_param('iii', $jumlah, $user_id, $produk_id);
            $stmt_upd->execute();
            $stmt_upd->close();
        }
        }
        $stmt_produk->close();
    }

    // get latest stok for response (if we have produk_id)
    $stok_terbaru = null;
    if (!empty($produk_id)) {
        $q = $conn->prepare('SELECT stok FROM produk WHERE id = ? LIMIT 1');
        if ($q) {
            $q->bind_param('i', $produk_id);
            $q->execute();
            $r = $q->get_result();
            if ($row = $r->fetch_assoc()) {
                $stok_terbaru = (int)$row['stok'];
            }
            $q->close();
        }
    }

echo json_encode(['success' => true, 'jumlah' => $jumlah, 'stok_terbaru' => $stok_terbaru]);
