<?php
session_start();
require_once "../../koneksi.php";
header('Content-Type: application/json');

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION["user_id"] ?? 0;
if (!$user_id || empty($data['gross_amount']) || empty($data['keranjang']) || !is_array($data['keranjang'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

// 1. Insert ke pesanan (simpan kode_pesanan jika ada)
$kode_pesanan = $data['order_id'] ?? null;
if ($kode_pesanan) {
    $stmt = $conn->prepare("INSERT INTO pesanan (kode_pesanan, pembeli_id, total, status) VALUES (?, ?, ?, 'dibayar')");
    $stmt->bind_param("sid", $kode_pesanan, $user_id, $data['gross_amount']);
} else {
    $stmt = $conn->prepare("INSERT INTO pesanan (pembeli_id, total, status) VALUES (?, ?, 'dibayar')");
    $stmt->bind_param("id", $user_id, $data['gross_amount']);
}
$stmt->execute();
$pesanan_id = $stmt->insert_id;
$stmt->close();

// 1a. Simpan alamat pengiriman jika ada
if (!empty($data['nama']) && !empty($data['alamat']) && !empty($data['no_telepon'])) {
    $stmt = $conn->prepare("INSERT INTO alamat_pesanan (pesanan_id, nama_penerima, alamat_lengkap, no_telepon) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $pesanan_id, $data['nama'], $data['alamat'], $data['no_telepon']);
    $stmt->execute();
    $stmt->close();
}

// 2. Insert item pesanan
foreach ($data['keranjang'] as $item) {
    $produk_id = $item['id'] ?? ($item['produk_id'] ?? null);
    $jumlah = $item['jumlah'] ?? 1;
    $harga = $item['harga'] ?? ($item['harga_saat_pembelian'] ?? 0);
    $stmt = $conn->prepare("INSERT INTO item_pesanan (pesanan_id, produk_id, jumlah, harga_saat_pembelian) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $pesanan_id, $produk_id, $jumlah, $harga);
    $stmt->execute();
    $stmt->close();
}

// 3. Insert ke transaksi
$metode = $data['payment_type'] ?? ($data['payment_method'] ?? 'qris');
$amount = $data['gross_amount'];
$stmt = $conn->prepare("INSERT INTO transaksi (pesanan_id, metode, status, amount, midtrans_reference) VALUES (?, ?, 'dibayar', ?, ?)");
$order_ref = $kode_pesanan ?? null;
$stmt->bind_param("isds", $pesanan_id, $metode, $amount, $order_ref);
$stmt->execute();
$transaksi_id = $stmt->insert_id;
$stmt->close();

// Jika client mengirimkan pengguna_diskon_id (mapping ke tabel pengguna_diskon),
// simpan ke transaksi (jika kolom ada) untuk diproses pada callback.
if (!empty($data['discount']['pengguna_diskon_id'])) {
    $pengguna_diskon_id = (int)$data['discount']['pengguna_diskon_id'];
    // coba update kolom transaksi.pengguna_diskon_id jika ada
    $cek_kol = $conn->query("SHOW COLUMNS FROM transaksi LIKE 'pengguna_diskon_id'");
    if ($cek_kol && $cek_kol->num_rows > 0) {
        $stmt_up = $conn->prepare("UPDATE transaksi SET pengguna_diskon_id = ? WHERE id = ?");
        if ($stmt_up) {
            $stmt_up->bind_param("ii", $pengguna_diskon_id, $transaksi_id);
            $stmt_up->execute();
            $stmt_up->close();
        }
    }

    // Karena pada flow Midtrans Snap client-side "onSuccess" kita memanggil
    // simpan-pesanan.php, dan kemungkinan webhook tidak dipanggil,
    // kita juga aman untuk menandai pengguna_diskon sebagai digunakan di sini.
    // Pastikan hanya menandai mapping milik user dan belum dipakai.
    $stmt_mark = $conn->prepare("UPDATE pengguna_diskon SET tanggal_digunakan = NOW(), status = 'digunakan' WHERE id = ? AND pengguna_id = ? AND (tanggal_digunakan IS NULL OR tanggal_digunakan = '')");
    if ($stmt_mark) {
        $stmt_mark->bind_param("ii", $pengguna_diskon_id, $user_id);
        $stmt_mark->execute();
        $stmt_mark->close();
    }
}
// Jika client tidak mengirim pengguna_diskon_id tetapi mengirimkan diskon.id,
// coba cari mapping pengguna_diskon milik user untuk diskon tersebut dan prosesnya.
elseif (!empty($data['discount']['id'])) {
    $diskon_id = (int)$data['discount']['id'];
    // cari mapping pengguna_diskon yang belum dipakai
    $stmt_find = $conn->prepare("SELECT id FROM pengguna_diskon WHERE pengguna_id = ? AND diskon_id = ? AND (tanggal_digunakan IS NULL OR tanggal_digunakan = '') LIMIT 1");
    if ($stmt_find) {
        $stmt_find->bind_param("ii", $user_id, $diskon_id);
        $stmt_find->execute();
        $res_find = $stmt_find->get_result();
        if ($rowf = $res_find->fetch_assoc()) {
            $pengguna_diskon_id = (int)$rowf['id'];
            // update transaksi.pengguna_diskon_id jika kolom ada
            $cek_kol = $conn->query("SHOW COLUMNS FROM transaksi LIKE 'pengguna_diskon_id'");
            if ($cek_kol && $cek_kol->num_rows > 0) {
                $stmt_up2 = $conn->prepare("UPDATE transaksi SET pengguna_diskon_id = ? WHERE id = ?");
                if ($stmt_up2) {
                    $stmt_up2->bind_param("ii", $pengguna_diskon_id, $transaksi_id);
                    $stmt_up2->execute();
                    $stmt_up2->close();
                }
            }
            // tandai sebagai digunakan
            $stmt_mark2 = $conn->prepare("UPDATE pengguna_diskon SET tanggal_digunakan = NOW(), status = 'digunakan' WHERE id = ? AND pengguna_id = ? AND (tanggal_digunakan IS NULL OR tanggal_digunakan = '')");
            if ($stmt_mark2) {
                $stmt_mark2->bind_param("ii", $pengguna_diskon_id, $user_id);
                $stmt_mark2->execute();
                $stmt_mark2->close();
            }
        }
        $stmt_find->close();
    }
}

// 4. Jika ada diskon, kita tidak langsung menandai pengguna_diskon sebagai digunakan di sini
// karena pembayaran belum dikonfirmasi. Pada callback pembayaran, server akan
// menandai pengguna_diskon sebagai 'digunakan'. Namun kita menyimpan mapping
// pengguna_diskon_id (jika dikirim) ke transaksi pada blok di atas untuk referensi.

// 5. (Opsional) Hapus keranjang di session untuk user yang checkout dari keranjang
if (isset($_SESSION['keranjang'])) unset($_SESSION['keranjang']);

// 6. Hapus applied_discount dari session setelah pesanan tersimpan (jika ada)
if (isset($_SESSION['applied_discount'])) unset($_SESSION['applied_discount']);

echo json_encode(['success' => true, 'pesanan_id' => $pesanan_id, 'kode_pesanan' => $kode_pesanan]);
