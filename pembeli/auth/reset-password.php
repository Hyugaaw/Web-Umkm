<?php
require_once "../../koneksi.php";

$token = $_GET["token"] ?? "";
$error = "";
$success = "";

if (!$token) die("Token tidak valid");

// cek token
$sql = "SELECT email, expired_at FROM password_reset WHERE token=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Token tidak ditemukan");
}

$data = $result->fetch_assoc();
if (strtotime($data["expired_at"]) < time()) {
    die("Token sudah kadaluarsa");
}
$email = $data["email"];

// kalau submit form password baru
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"] ?? "";
    $konfirmasi = $_POST["konfirmasi"] ?? "";

    if ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "UPDATE pengguna SET kata_sandi_hash=? WHERE email=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $hash, $email);

        if ($stmt->execute()) {
            $success = "Password berhasil direset.";
            $conn->query("DELETE FROM password_reset WHERE email='$email'");
        } else {
            $error = "Gagal reset password.";
        }
    }
}
