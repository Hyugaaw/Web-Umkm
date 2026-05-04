<?php
session_start();

if (isset($_POST['index'])) {
    $index = (int) $_POST['index'];

    // Pastikan item ada di keranjang
    if (isset($_SESSION['keranjang'][$index])) {
        // Kurangi jumlah barang 1
        if ($_SESSION['keranjang'][$index]['jumlah'] > 1) {
            $_SESSION['keranjang'][$index]['jumlah']--;
        } else {
            // Jika tinggal 1, hapus item dari keranjang
            unset($_SESSION['keranjang'][$index]);
            $_SESSION['keranjang'] = array_values($_SESSION['keranjang']); // reset index
        }
    }
}

// Kembali ke halaman checkout
header("Location: checkout.php");
exit;
