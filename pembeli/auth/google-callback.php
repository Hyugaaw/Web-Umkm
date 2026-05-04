<?php
// google-callback.php
session_start();
require_once "../../koneksi.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_token = $_POST['credential'] ?? '';
    $client_id = '1082579647521-8hkq6vk4eqcfcp7rak71agp2naif7pi4.apps.googleusercontent.com'; // Client ID Google

    if ($id_token) {
        // Verifikasi token ke Google pakai cURL
        $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . $id_token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            echo "<script>alert('Gagal memverifikasi token Google.');window.location='login.php';</script>";
            exit();
        }

        $payload = json_decode($response, true);

        // Pastikan token valid untuk aplikasi ini
        if (isset($payload['email']) && isset($payload['aud']) && $payload['aud'] === $client_id) {
            $email = $payload['email'];
            $nama  = $payload['name'] ?? '';

            // Cek apakah user sudah ada
            $sql = "SELECT id, nama, email, peran, disetujui FROM pengguna WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo "<script>alert('Kesalahan server (stmt error).');window.location='login.php';</script>";
                exit();
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // ==== LOGIN ====
                $user = $result->fetch_assoc();

                if ($user["peran"] === "penjual" && !$user["disetujui"]) {
                    echo "<script>alert('Akun penjual belum disetujui admin');window.location='login.php';</script>";
                    exit();
                }

                // Regenerate session untuk keamanan
                session_regenerate_id(true);

                $_SESSION["user_id"]    = $user["id"];
                $_SESSION["user_nama"]  = $user["nama"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_peran"] = $user["peran"];
                $_SESSION["login_hash"] = password_hash($user["email"] . $user["id"], PASSWORD_DEFAULT);
                $_SESSION["login_time"] = time();

                if ($user["peran"] === "penjual") {
                    header("Location: ../penjual/index2.php");
                } else {
                    header("Location: ../index2.php");
                }
                exit();
            } else {
                // ==== AUTO-REGISTER + LOGIN ====
                $randomPass = bin2hex(random_bytes(8));
                $hash = password_hash($randomPass, PASSWORD_DEFAULT);
                $peran = "pembeli";
                $disetujui = 1;

                $sql = "INSERT INTO pengguna (nama, email, kata_sandi_hash, peran, disetujui) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    echo "<script>alert('Kesalahan server (insert error).');window.location='login.php';</script>";
                    exit();
                }
                $stmt->bind_param("ssssi", $nama, $email, $hash, $peran, $disetujui);

                if ($stmt->execute()) {
                    $id = $stmt->insert_id;

                    // Regenerate session
                    session_regenerate_id(true);

                    $_SESSION["user_id"]    = $id;
                    $_SESSION["user_nama"]  = $nama;
                    $_SESSION["user_email"] = $email;
                    $_SESSION["user_peran"] = 'pembeli';
                    $_SESSION["login_hash"] = password_hash($email . $id, PASSWORD_DEFAULT);
                    $_SESSION["login_time"] = time();

                    header("Location: ../index2.php");
                    exit();
                } else {
                    echo "<script>alert('Gagal auto-register Google.');window.location='login.php';</script>";
                    exit();
                }
            }
        } else {
            echo "<script>alert('Token Google tidak valid.');window.location='login.php';</script>";
            exit();
        }
    }
}

// Kalau tidak ada request POST
header('Location: login.php');
exit();

// --- ignore ---
$conn->closedir();
?>