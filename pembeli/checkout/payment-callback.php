<?php
require_once "../../koneksi.php";
// Jika dipanggil via browser (redirect), tampilkan notifikasi sukses dan redirect ke checkout.php
if (isset($_GET['success']) && $_GET['success'] == '1') {
	echo '<script>alert("Pembayaran DANA berhasil!");window.location.href="checkout.php?success=1";</script>';
	exit;
}


$data = json_decode(file_get_contents('php://input'), true);
$log = date('Y-m-d H:i:s') . "\n";
$log .= "RAW: " . file_get_contents('php://input') . "\n";
$log .= "PARSED: " . json_encode($data) . "\n";
file_put_contents("callback.log", $log, FILE_APPEND);

// Ambil reference/order_id dan status dari callback
$reference = $data['order_id'] ?? $data['reference'] ?? null;
$status = $data['transaction_status'] ?? $data['status'] ?? null;


// Jika simulasi sukses, update status transaksi di database
if ($reference && ($status === 'settlement' || $status === 'success')) {
	$log .= "Callback status success/settlement\n";
	// Update status transaksi (cocokkan midtrans_reference)
	$stmt = $conn->prepare("UPDATE transaksi SET status = 'success' WHERE midtrans_reference = ?");
	if ($stmt) {
		$stmt->bind_param("s", $reference);
		$stmt->execute();
		$log .= "Update transaksi success\n";
	} else {
		$log .= "ERROR: Gagal prepare update transaksi\n";
	}

	// Ambil pesanan_id dari transaksi
	$stmt2 = $conn->prepare("SELECT pesanan_id FROM transaksi WHERE midtrans_reference = ?");
	if ($stmt2) {
		$stmt2->bind_param("s", $reference);
		$stmt2->execute();
		$stmt2->bind_result($pesanan_id);
		if ($stmt2->fetch()) {
			$stmt2->close();
			$log .= "Dapat pesanan_id: $pesanan_id\n";
			// Update status pesanan menjadi 'dibayar'
			$stmt_update_pesanan = $conn->prepare("UPDATE pesanan SET status = 'dibayar' WHERE id = ?");
			if ($stmt_update_pesanan) {
				$stmt_update_pesanan->bind_param("i", $pesanan_id);
				$stmt_update_pesanan->execute();
				$stmt_update_pesanan->close();
				$log .= "Status pesanan $pesanan_id diupdate ke 'dibayar'\n";
			} else {
				$log .= "ERROR: Gagal update status pesanan\n";
			}
			// Copy item_pesanan ke pesanan_detail dan kurangi stok produk
			$sql = "SELECT produk_id, jumlah, harga_saat_pembelian FROM item_pesanan WHERE pesanan_id = ?";
			$stmt3 = $conn->prepare($sql);
			if ($stmt3) {
				$stmt3->bind_param("i", $pesanan_id);
				$stmt3->execute();
				$result = $stmt3->get_result();
				while ($row = $result->fetch_assoc()) {
					// Insert ke pesanan_detail
					$stmt4 = $conn->prepare("INSERT INTO pesanan_detail (pesanan_id, produk_id, jumlah, harga) VALUES (?, ?, ?, ?)");
					if ($stmt4) {
						$stmt4->bind_param("iiid", $pesanan_id, $row['produk_id'], $row['jumlah'], $row['harga_saat_pembelian']);
						$stmt4->execute();
						$stmt4->close();
						$log .= "Insert pesanan_detail produk_id {$row['produk_id']} jumlah {$row['jumlah']}\n";
					} else {
						$log .= "ERROR: Gagal insert pesanan_detail\n";
					}
					// Update stok produk
					$stmt5 = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ? AND stok >= ?");
					if ($stmt5) {
						$stmt5->bind_param("iii", $row['jumlah'], $row['produk_id'], $row['jumlah']);
						$stmt5->execute();
						$stmt5->close();
						$log .= "Stok produk id {$row['produk_id']} dikurangi {$row['jumlah']}\n";
					} else {
						$log .= "ERROR: Gagal prepare update produk\n";
					}
				}
				$stmt3->close();
			} else {
				$log .= "ERROR: Gagal prepare select item_pesanan\n";
			}

			// Jika payload callback menyertakan kode diskon atau informasi diskon,
			// tandai pengguna_diskon terkait sebagai telah digunakan.
			// Pertama, cek apakah transaksi menyimpan langsung pengguna_diskon_id
			$stmt_check_pd = $conn->prepare("SELECT pengguna_diskon_id FROM transaksi WHERE pesanan_id = ? LIMIT 1");
			if ($stmt_check_pd) {
				$stmt_check_pd->bind_param("i", $pesanan_id);
				$stmt_check_pd->execute();
				$res_pd = $stmt_check_pd->get_result();
				if ($rowpd = $res_pd->fetch_assoc()) {
					$pd_id = $rowpd['pengguna_diskon_id'] ?? null;
					if (!empty($pd_id)) {
						$stmtu = $conn->prepare("UPDATE pengguna_diskon SET tanggal_digunakan = NOW(), status = 'digunakan' WHERE id = ? AND (tanggal_digunakan IS NULL OR tanggal_digunakan = '')");
						if ($stmtu) { $stmtu->bind_param("i", $pd_id); $stmtu->execute(); $stmtu->close(); $log .= "Marked pengguna_diskon id $pd_id as used\n"; }
					}
				}
				$stmt_check_pd->close();
			}

			// Jika tidak ada pengguna_diskon_id di transaksi, cek apakah callback menyertakan kode diskon
			$diskon_kode = $data['discount_code'] ?? $data['kode_diskon'] ?? null;
			if ($diskon_kode) {
				// cari id diskon berdasarkan kode
				$stmtd = $conn->prepare("SELECT id FROM diskon WHERE kode_diskon = ? LIMIT 1");
				if ($stmtd) {
					$stmtd->bind_param("s", $diskon_kode);
					$stmtd->execute();
					$resd = $stmtd->get_result();
					if ($rowd = $resd->fetch_assoc()) {
						$diskon_id = (int)$rowd['id'];
						// dapatkan pembeli_id dari pesanan
						$stmtp = $conn->prepare("SELECT pembeli_id FROM pesanan WHERE id = ? LIMIT 1");
						if ($stmtp) {
							$stmtp->bind_param("i", $pesanan_id);
							$stmtp->execute();
							$resp = $stmtp->get_result();
							if ($rp = $resp->fetch_assoc()) {
								$pembeli_id = (int)$rp['pembeli_id'];
								// update pengguna_diskon
								$stmtu = $conn->prepare("UPDATE pengguna_diskon SET tanggal_digunakan = NOW(), status = 'digunakan' WHERE pengguna_id = ? AND diskon_id = ? AND (tanggal_digunakan IS NULL OR tanggal_digunakan = '')");
								if ($stmtu) {
									$stmtu->bind_param("ii", $pembeli_id, $diskon_id);
									$stmtu->execute();
									$stmtu->close();
									$log .= "Pengguna_diskon untuk pembeli $pembeli_id diskon $diskon_id diupdate jadi digunakan\n";
								}
							}
							$stmtp->close();
						}
					}
					$stmtd->close();
				}
			}
		} else {
			$log .= "ERROR: pesanan_id tidak ditemukan\n";
			$stmt2->close();
		}
	} else {
		$log .= "ERROR: Gagal prepare select pesanan_id\n";
	}
	file_put_contents("callback.log", $log, FILE_APPEND);
}

http_response_code(200);
