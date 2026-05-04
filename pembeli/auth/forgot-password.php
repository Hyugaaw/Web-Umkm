<?php
session_start();
require_once "../../koneksi.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $konfirmasi = isset($_POST["konfirmasi"]) ? $_POST["konfirmasi"] : "";

    if (empty($email) || empty($password) || empty($konfirmasi)) {
        $error = "Semua field harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok";
    } else {
        // Cek email terdaftar
        $sql = "SELECT id FROM pengguna WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $error = "Email tidak ditemukan";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE pengguna SET kata_sandi_hash = ? WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $hash, $email);
            if ($stmt->execute()) {
                $success = "Password berhasil direset. Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat reset password.";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - UMKM Market</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(120deg, #f8f8f8 60%, #e0f7fa 100%);
        }
        .forgot-container {
            max-width: 440px;
            margin: 60px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
            padding: 36px 32px 28px 32px;
            position: relative;
        }
        .marketplace-header {
            text-align: center;
            margin-bottom: 18px;
        }
        .marketplace-logo {
            width: 90px;
            height: 90px;
            object-fit: contain;
            margin-bottom: 12px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.10);
            border: 3px solid #009688;
            padding: 8px;
        }
        .forgot-container h2 {
            margin-top: 0;
            margin-bottom: 18px;
            text-align: center;
            color: #009688;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .forgot-container label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .forgot-container input[type="email"],
        .forgot-container input[type="password"] {
            width: 100%;
            padding: 11px;
            margin-bottom: 16px;
            border: 1px solid #b2dfdb;
            border-radius: 6px;
            font-size: 15px;
            background: #f9f9f9;
            transition: border 0.2s;
        }
        .forgot-container input:focus {
            border: 1.5px solid #009688;
            outline: none;
        }
        .forgot-container button {
            width: 100%;
            padding: 11px;
            background: linear-gradient(90deg, #009688 60%, #4CAF50 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 17px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .forgot-container button:hover {
            background: linear-gradient(90deg, #00796b 60%, #388e3c 100%);
        }
        .forgot-container .error {
            color: #e53935;
            margin-bottom: 14px;
            text-align: center;
            font-weight: 500;
        }
        .forgot-container .success {
            color: #388e3c;
            margin-bottom: 14px;
            text-align: center;
            font-weight: 500;
        }
        .back-btn {
            margin: 24px auto 0 auto;
            display: block;
            width: 80%;
            background: #fff;
            color: #009688;
            border: 2px solid #009688;
            font-weight: 600;
            font-size: 15px;
            border-radius: 6px;
            padding: 10px 0;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .back-btn:hover {
            background: #009688;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="marketplace-header">
            <img src="../logo.png" alt="UMKM Market Logo" class="marketplace-logo">
        </div>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <h2>Lupa Password</h2>
        <form method="post" autocomplete="off">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autofocus>
            <label for="password">Password Baru</label>
            <input type="password" id="password" name="password" required>
            <label for="konfirmasi">Konfirmasi Password Baru</label>
            <input type="password" id="konfirmasi" name="konfirmasi" required>
            <button type="submit">Reset Password</button>
        </form>
        <button class="back-btn" onclick="window.location.href='login.php'" type="button">&larr; Kembali ke Login</button>
    </div>
</body>
</html>
