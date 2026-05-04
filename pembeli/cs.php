<?php
// cs.php — Halaman Customer Service Otomatis via WhatsApp Bot

// ===============================
// 🔐 TOKEN FONNTE — milik nomor 0881011749806
// ===============================
$token = "cxUCj9PQP6garfjsWWPG";

// ===============================
// 📤 Kirim Pesan Otomatis ke Nomor CS
// ===============================

// Misalnya saat halaman dibuka, bot otomatis kirim notifikasi ke admin
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.fonnte.com/send",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => array(
        "target" => "62881011749806", // nomor CS bot utama
        "message" => "🔔 Ada pengunjung membuka halaman Customer Service Bot pada " . date('d/m/Y H:i:s') . ".",
    ),
    CURLOPT_HTTPHEADER => array(
        "Authorization: $token"
    ),
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// Kamu juga bisa ubah trigger-nya: misalnya hanya kirim kalau form dikirim, dst.
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Service | Laporan Bug</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .cs-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 480px;
            padding: 30px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 10px;
        }

        h4 {
            color: #555;
            margin-bottom: 25px;
            font-weight: normal;
        }

        p {
            color: #555;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .info-box {
            background-color: #f1f5f9;
            border-left: 5px solid #007bff;
            padding: 12px;
            margin-bottom: 25px;
            text-align: left;
            border-radius: 8px;
        }

        .wa-buttons a {
            display: block;
            background-color: #25D366;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .wa-buttons a:hover {
            background-color: #1ebe5b;
            transform: scale(1.05);
        }

        .back-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            margin-top: 25px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background-color: #0056b3;
        }

        .wa-buttons img {
            vertical-align: middle;
            margin-right: 8px;
        }

        @media (max-width: 480px) {
            .cs-container {
                padding: 20px;
            }

            h2 {
                font-size: 20px;
            }

            p {
                font-size: 14px;
            }

            .wa-buttons a {
                font-size: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="cs-container">
        <h2>Customer Service 🤖</h2>
        <h4>Bantuan Otomatis & Laporan Bug</h4>

        <div class="info-box">
            <p><strong>Jam Operasional:</strong><br>
                Senin – Jumat: 08.00 – 20.00 WIB<br>
                Sabtu – Minggu: 09.00 – 17.00 WIB
            </p>
        </div>

        <p>Silakan pilih salah satu nomor CS WhatsApp di bawah ini 👇</p>

        <div class="wa-buttons">
            <a href="https://wa.me/6285853303982?text=Halo%20CS,%20kami%20menemukan%20bug%20di%20bagian%20halaman%20ini.%20Mohon%20dicek%20ya." target="_blank">
                <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" width="20"> 0858-5330-3982
            </a>
            <a href="https://wa.me/62881011749806?text=Halo%20CS,%20kami%20menemukan%20bug%20di%20bagian%20halaman%20ini.%20Mohon%20dicek%20ya." target="_blank">
                <img src="https://cdn-icons-png.flaticon.com/512/733/733585.png" width="20"> 0881-0117-49806
            </a>
        </div>

        <a href="index2.php" class="back-btn">⬅ Kembali ke Beranda</a>
    </div>
</body>

</html>