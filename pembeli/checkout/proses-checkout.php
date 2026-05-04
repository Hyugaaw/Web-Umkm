<?php
session_start();
require_once "../../koneksi.php";


// --- AJAX: Beli Langsung Satu Produk ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ajax']) && $_POST['ajax'] == '1' &&
    isset($_POST['produk_id']) && isset($_POST['jumlah'])
) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $produk_id = (int)$_POST['produk_id'];
    $jumlah = (int)$_POST['jumlah'];
    if (!$user_id || !$produk_id || $jumlah < 1) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid!']);
        exit;
    }
    // Ambil stok terbaru
    $stmt = $conn->prepare('SELECT harga, stok FROM produk WHERE id = ? AND status = "aktif"');
    $stmt->bind_param('i', $produk_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $produk = $res->fetch_assoc();
    $stmt->close();
    if (!$produk) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan!']);
        exit;
    }
    $stok_terbaru = (int)$produk['stok'];
    if ($stok_terbaru < $jumlah) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak cukup!', 'stok_terbaru' => $stok_terbaru]);
        exit;
    }
    // Kurangi stok produk
    $stmt = $conn->prepare('UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?');
    $stmt->bind_param('iii', $jumlah, $produk_id, $jumlah);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        // Simpan ke user_cart (untuk konsistensi, bisa dihapus jika tidak perlu)
    // Simpan ke user_cart menggunakan produk_id agar unik per produk
    $stmt2 = $conn->prepare('INSERT INTO user_cart (user_id, produk_id, nama_produk, gambar, jumlah) VALUES (?, ?, (SELECT nama FROM produk WHERE id = ?), (SELECT path_gambar FROM produk WHERE id = ?), ?) ON DUPLICATE KEY UPDATE jumlah = jumlah + VALUES(jumlah)');
    // types: i (user_id), i (produk_id), i (subselect id), i (subselect id), i (jumlah)
    $stmt2->bind_param('iiiii', $user_id, $produk_id, $produk_id, $produk_id, $jumlah);
        $stmt2->execute();
        $stmt2->close();
        // Ambil stok terbaru
        $stmt3 = $conn->prepare('SELECT stok FROM produk WHERE id = ?');
        $stmt3->bind_param('i', $produk_id);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        $row3 = $res3->fetch_assoc();
        $stmt3->close();
        echo json_encode(['success' => true, 'stok_terbaru' => (int)$row3['stok']]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update stok!']);
        exit;
    }
}

// --- PROSES CHECKOUT KERANJANG BIASA ---
if (!isset($_SESSION["user_id"]) || empty($_SESSION["keranjang"])) {
    header("Location: checkout.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$keranjang = $_SESSION["keranjang"];
$totalHarga = $_POST['total'];
$metode = strtolower(trim($_POST['metode'])); // pastikan lowercase
$nama = $_POST['nama'];
$alamat = $_POST['alamat'];
$no_telepon = $_POST['no_telepon'];

// Validasi metode pembayaran

// Hanya izinkan shopeepay, qris, gopay
$metodeValid = ["shopeepay", "qris", "gopay"];
if (!in_array($metode, $metodeValid)) {
    die("Metode pembayaran tidak valid.");
}

$conn->begin_transaction();

try {
    // Simpan data pesanan (status default 'pending')
    $stmt = $conn->prepare("INSERT INTO pesanan (pembeli_id, total, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("id", $user_id, $totalHarga);
    $stmt->execute();
    $pesanan_id = $stmt->insert_id;

    // Simpan item pesanan
    $stmt_item = $conn->prepare("INSERT INTO item_pesanan (pesanan_id, produk_id, jumlah, harga_saat_pembelian) VALUES (?, ?, ?, ?)");
    foreach ($keranjang as $item) {
        $stmt_item->bind_param("iiid", $pesanan_id, $item['id'], $item['jumlah'], $item['harga']);
        $stmt_item->execute();
    }

    // Panggil API pembayaran
    $paymentData = buatPembayaranAPI($pesanan_id, $totalHarga, $metode, $nama, $no_telepon);

    // Simpan transaksi (midtrans_reference, status default 'pending')
    $stmt_transaksi = $conn->prepare("INSERT INTO transaksi (pesanan_id, metode, midtrans_reference, status, amount, checkout_url) VALUES (?, ?, ?, 'pending', ?, ?)");
    $stmt_transaksi->bind_param("issds", $pesanan_id, $metode, $paymentData['reference'], $totalHarga, $paymentData['checkout_url']);
    $stmt_transaksi->execute();

    $conn->commit();

    // Hapus keranjang user dari database juga
    $stmt_del_cart = $conn->prepare("DELETE FROM user_cart WHERE user_id = ?");
    $stmt_del_cart->bind_param("i", $user_id);
    $stmt_del_cart->execute();
    $stmt_del_cart->close();

    unset($_SESSION['keranjang']);

    header("Location: " . $paymentData['checkout_url']);
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Terjadi kesalahan: " . $e->getMessage();
}

/**
 * Fungsi buatPembayaranAPI
 * Menghubungkan ke Tripay untuk Shopeepay, QRIS, Gopay
 */
function buatPembayaranAPI($pesanan_id, $amount, $metode, $nama, $no_telepon) {
    $apiKey = "API_KEY_TRIPAY"; // Ganti API Key Tripay kamu
    $endpoint = "https://tripay.co.id/api-sandbox/transaction/create";

    $merchant_ref = "INV" . $pesanan_id . time();
    $signature = hash_hmac('sha256', $merchant_ref . $amount, $apiKey);

    // Penyesuaian nama metode jika perlu (misal: shopeepay, qris, gopay)
    $method = strtolower($metode);

    $data = [
        "method" => $method,
        "merchant_ref" => $merchant_ref,
        "amount" => $amount,
        "customer_name" => $nama,
        "customer_email" => "customer@example.com", // bisa ambil dari DB
        "customer_phone" => $no_telepon,
        "order_items" => [
            [
                "name" => "Pembelian Produk",
                "price" => $amount,
                "quantity" => 1
            ]
        ],
        "return_url" => "http://localhost/web-umkm/pembeli/checkout/finish.php",
        "expired_time" => time() + (24 * 60 * 60),
        "signature" => $signature
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);

    if (!empty($response['data'])) {
        return [
            "checkout_url" => $response['data']['checkout_url'],
            "reference" => $response['data']['reference']
        ];
    }

    throw new Exception("Pembayaran gagal: " . ($response['message'] ?? "Tidak ada respon API"));
}
?>
