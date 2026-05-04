<?php
session_start();
require_once "../../koneksi.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = isset($_POST["nama"]) ? trim($_POST["nama"]) : "";
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $konfirmasi = isset($_POST["konfirmasi"]) ? $_POST["konfirmasi"] : "";
    $peran = isset($_POST["peran"]) ? $_POST["peran"] : "pembeli";

    if (empty($nama) || empty($email) || empty($password) || empty($konfirmasi)) {
        $error = "Semua field harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak cocok";
    } else {
        $sql = "SELECT id FROM pengguna WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email sudah terdaftar";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $disetujui = ($peran === "penjual") ? 0 : 1;
            $sql = "INSERT INTO pengguna (nama, email, kata_sandi_hash, peran, disetujui) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $nama, $email, $hash, $peran, $disetujui);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil. Silakan login.";
            } else {
                $error = "Terjadi kesalahan saat registrasi.";
            }
        }

        $stmt->close();
    }
}

$conn->close();


// =============================================================
// Tambahan untuk integrasi Google Register
// =============================================================

// Jika request berasal dari Google (misalnya ada parameter 'google_register')
if (isset($_POST["google_register"]) && $_POST["google_register"] === "1") {
    // Ambil data dari Google (biasanya sudah dikirim via POST)
    $googleNama  = isset($_POST["google_nama"]) ? trim($_POST["google_nama"]) : "";
    $googleEmail = isset($_POST["google_email"]) ? trim($_POST["google_email"]) : "";
    $googleId    = isset($_POST["google_id"]) ? trim($_POST["google_id"]) : "";

    if (!empty($googleNama) && !empty($googleEmail)) {
        require_once "../../koneksi.php";

        // Cek apakah email Google sudah terdaftar
        $sql = "SELECT id FROM pengguna WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $googleEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Jika belum terdaftar → masukkan ke DB
            $peran = "pembeli";
            $disetujui = 1;
            $dummyPassword = password_hash($googleId, PASSWORD_DEFAULT);

            $sql = "INSERT INTO pengguna (nama, email, kata_sandi_hash, peran, disetujui) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $googleNama, $googleEmail, $dummyPassword, $peran, $disetujui);
            $stmt->execute();
        }

        $stmt->close();
        $conn->close();

        // Setelah register Google → arahkan ke verify.php
        header("Location: verify.php?email=" . urlencode($googleEmail));
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - UMKM Market</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f5f5f5;
        }

        /* Container utama */
        .main-container {
            display: flex;
            min-height: 100vh;
            background: #fff;
        }

        /* Kiri: hanya gambar & teks */
        .left-side {
            flex: 0.6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: #fff;
        }

        .left-side img {
            max-width: 70%;
            height: auto;
            margin-bottom: 20px;
        }

        .left-side h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }

        .left-side p {
            font-size: 15px;
            color: #555;
            text-align: center;
        }

        /* Kanan: form */
        .right-side {
            flex: 0.4;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #fff;
        }

        /* Form container */
        .register-container {
            width: 100%;
            max-width: 360px;
            padding: 30px;
            border: 1px solid #eee;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .marketplace-header {
            text-align: center;
            margin-bottom: 16px;
        }

        .marketplace-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .register-container h2 {
            margin: 0 0 20px;
            text-align: center;
            color: #009688;
            font-weight: 600;
            font-size: 20px;
        }

        .register-container input[type="text"],
        .register-container input[type="email"],
        .register-container input[type="password"],
        .register-container select {
            width: 100%;
            padding: 12px;
            margin-bottom: 14px;
            border: 1px solid #b2dfdb;
            border-radius: 6px;
            font-size: 14px;
            background: #f9f9f9;
            transition: border 0.2s;
        }

        .register-container input:focus,
        .register-container select:focus {
            border: 1.5px solid #009688;
            outline: none;
        }

        .register-container button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: linear-gradient(90deg, #00ab55 60%, #009688 100%);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            margin-bottom: 14px;
            transition: background 0.2s;
        }

        .register-container button[type="submit"]:hover {
            background: linear-gradient(90deg, #009688 60%, #00796b 100%);
        }

        .register-container .error {
            color: #e53935;
            margin-bottom: 14px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
        }

        .register-container .success {
            color: #388e3c;
            margin-bottom: 14px;
            text-align: center;
            font-weight: 500;
            font-size: 14px;
        }

        .back-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            background: #fff;
            color: #009688;
            border: 2px solid #009688;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #009688;
            color: #fff;
        }

        .google-register {
            display: flex;
            justify-content: center;
            margin-bottom: 14px;
        }

        @media (max-width: 900px) {
            .main-container {
                flex-direction: column;
            }

            .left-side,
            .right-side {
                width: 100%;
                padding: 15px;
            }

            .left-side img {
                max-width: 40%;
                height: auto;
                margin-bottom: 20px;
            }
        }

        .branding-content {
            text-align: center;
            max-width: 400px;
        }

        .branding-content img {
            max-width: 80%;
            margin-bottom: 20px;
        }

        .branding-content h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }

        .branding-content p {
            font-size: 15px;
            color: #555;
        }

        .left-side img {
            max-width: 40%;
            height: auto;
            margin-bottom: 20px;
        }

        @media (max-width: 900px) {
            .left-side img {
                max-width: 60%;
            }
        }

        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 15px 0;
            color: #777;
            font-size: 14px;
            font-weight: 500;
        }

        .separator::before,
        .separator::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }

        .separator:not(:empty)::before {
            margin-right: 10px;
        }

        .separator:not(:empty)::after {
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Kiri: Gambar branding + teks -->
        <div class="left-side">
            <img src="../logo.png" alt="UMKM Market Illustration">
        </div>

        <!-- Kanan: Form -->
        <div class="right-side">
            <div class="register-container">

                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif (!empty($success)): ?>
                    <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <h2>Daftar Sekarang</h2>
                <form method="post" autocomplete="off">
                    <label for="nama">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" required autofocus>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>

                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>

                    <label for="konfirmasi">Konfirmasi Password</label>
                    <input type="password" id="konfirmasi" name="konfirmasi" required>

                    <label for="peran">Daftar sebagai</label>
                    <select id="peran" name="peran" required>
                        <option value="pembeli">Pembeli</option>
                        <option value="penjual">Penjual</option>
                    </select>

                    <button type="submit">Daftar</button>

                    <div class="separator">
                        <span>Atau</span>
                    </div>

                    <div class="google-register">
                        <div id="g_id_onload"
                            data-client_id="1082579647521-8hkq6vk4eqcfcp7rak71agp2naif7pi4.apps.googleusercontent.com"
                            data-login_uri="http://localhost/web-umkm/pembeli/auth/google-register-callback.php"
                            data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin"
                            data-type="standard"
                            data-shape="rectangular"
                            data-theme="outline"
                            data-text="signup_with"
                            data-size="large"
                            data-logo_alignment="left">
                        </div>
                    </div>
                </form>

                <button class="back-btn" onclick="window.location.href='login.php'" type="button">
                    &larr; Kembali ke Login
                </button>
            </div>
        </div>
    </div>

</body>

</html>