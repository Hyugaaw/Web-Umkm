<?php
session_start();
require_once "../../koneksi.php";

$id_token = $_POST['credential'] ?? null;

if ($id_token) {
    // Verifikasi token ke Google
    $response = file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=" . $id_token);
    $payload = json_decode($response, true);

    if (isset($payload['email'])) {
        $email = $payload['email'];
        $nama  = $payload['name'];

        // Cek user di DB
        $sql = "SELECT id, peran, disetujui FROM pengguna WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User lama
            $user = $result->fetch_assoc();
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_nama']  = $nama;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_peran'] = $user['peran'];

            // Redirect sesuai peran
            if ($user['peran'] === "penjual") {
                header("Location: ../../penjual/index2.php");
                exit;
            } else {
                header("Location: ../../pembeli/index2.php");
                exit;
            }
        } else {
            // User baru → default pembeli
            $peran = "pembeli"; 
            $disetujui = 1;

            $sql = "INSERT INTO pengguna (nama, email, peran, disetujui) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nama, $email, $peran, $disetujui);
            $stmt->execute();

            $new_id = $stmt->insert_id;
            $_SESSION['user_id']    = $new_id;
            $_SESSION['user_nama']  = $nama;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_peran'] = $peran;

            // Redirect sesuai peran
            header("Location: ../../pembeli/index2.php");
            exit;
        }
    } else {
        echo "Login Google gagal (token tidak valid).";
    }
} else {
    echo "Token Google tidak diterima.";
}

