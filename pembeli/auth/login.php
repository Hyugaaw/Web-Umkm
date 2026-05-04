<?php
session_start();
require_once "../../koneksi.php";

$error = "";

// Proses login manual (Email & Password)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"])) {
    $email = $conn->real_escape_string($_POST["email"]);
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi";
    } else {
        // Ambil data dari tabel pengguna
        $sql = "SELECT id, nama, email, kata_sandi_hash, peran 
                FROM pengguna 
                WHERE email = ? 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Email tidak ditemukan";
        } else {
            $user = $result->fetch_assoc();

            // Cek password
            if (!password_verify($password, $user["kata_sandi_hash"])) {
                $error = "Password salah";
            } else {
                // Login sukses
                $_SESSION["user_id"]    = $user["id"];
                $_SESSION["user_nama"]  = $user["nama"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_peran"] = $user["peran"];
                $_SESSION["login_hash"] = password_hash(
                    $user["email"] . $user["id"],
                    PASSWORD_DEFAULT
                );

                // Redirect sesuai role
                if ($user["peran"] === "pembeli") {
                    header("Location: ../index2.php");
                } elseif ($user["peran"] === "penjual") {
                    header("Location: ../../penjual/index3.php"); // langsung ke index2.php penjual
                } else {
                    header("Location: ../index2.php");
                }
                exit();
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
    <title>Login - UMKM Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 15px;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            /* batas maksimal */
            background: #fff;
            margin: 40px auto;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo-container img {
            width: 80px;
            height: auto;
        }

        .login-box h2 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 25px;
            color: #000;
        }

        /* Input & tombol sejajar penuh */
        .login-box input,
        .login-box .btn,
        .g_id_signin {
            width: 100% !important;
            box-sizing: border-box;
        }

        .login-box input {
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .btn {
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #03ac0e;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn:disabled {
            background: #ccc;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .divider hr {
            flex: 1;
            border: none;
            border-top: 1px solid #ddd;
        }

        .divider span {
            margin: 0 10px;
            color: #777;
            font-size: 14px;
        }

        .links {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-top: 10px;
        }

        .links a {
            color: #03ac0e;
            text-decoration: none;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        /* Biar tombol Google sejajar dengan input & button */
        .g_id_signin {
            display: flex !important;
            justify-content: center;
            align-items: center;
            margin: 8px 0;
        }

        /* Responsiveness */
        @media (max-width: 480px) {
            .login-box {
                padding: 20px;
                margin: 20px auto;
            }

            .logo-container img {
                width: 60px;
            }

            .login-box h2 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="../logo.png" alt="UMKM Market Logo">
        </div>

        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="email" name="email" placeholder="Nomor HP atau Email" required>
            <input type="password" name="password" placeholder="Kata Sandi" required>
            <button type="submit" class="btn">Selanjutnya</button>
        </form>

        <div class="links">
            <a href="forgot-password.php">Lupa kata sandi?</a>
            <a href="register.php">Daftar</a>
        </div>

        <!-- Tombol Kembali -->
        <div style="margin-top:15px; text-align:center;">
            <a href="../index.php"
                style="display:inline-block;
            padding:10px 16px;
            background:#6fdc6f;
            color:#fff;
            font-weight:600;
            border-radius:6px;
            text-decoration:none;
            font-size:15px;">
                ← Kembali ke Beranda
            </a>
        </div>

        <!-- Tombol Login Google -->
        <div id="g_id_onload"
            data-client_id="1082579647521-8hkq6vk4eqcfcp7rak71agp2naif7pi4.apps.googleusercontent.com"
            data-login_uri="http://localhost/web-umkm/pembeli/auth/google-callback.php"
            data-auto_prompt="false">
        </div>

        <div class="g_id_signin"
            data-type="standard"
            data-shape="rectangular"
            data-theme="outline"
            data-text="signin_with"
            data-size="large"
            data-logo_alignment="left">
        </div>
    </div>
</body>

</html>