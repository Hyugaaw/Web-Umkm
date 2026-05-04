<?php
// ==============================================
// 🤖 Webhook Bot WhatsApp Otomatis via Fonnte
// ==============================================
// Dibuat untuk: Customer Service Otomatis & Laporan Bug
// Didesain dengan sistem percakapan dan menu cerdas
// ==============================================

// Token Fonnte (gunakan token nomor bot utama)
$token = "cxUCj9PQP6garfjsWWPG";

// Folder penyimpanan data sementara (buat folder 'data' di server)
$dataDir = __DIR__ . "/data";
if (!file_exists($dataDir)) mkdir($dataDir, 0777, true);

// Ambil data dari Fonnte (JSON)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit("No data received");
}

// Ambil informasi dasar
$sender = preg_replace('/[^0-9]/', '', $data['from'] ?? '');
$message = strtolower(trim($data['message'] ?? ''));

// Simpan log setiap pesan masuk
file_put_contents("log.txt", date('d/m/Y H:i:s') . " | FROM: $sender | MSG: $message\n", FILE_APPEND);

// Lokasi file state pengguna
$userFile = "$dataDir/$sender.json";

// Ambil status percakapan user terakhir
$state = file_exists($userFile) ? json_decode(file_get_contents($userFile), true) : ["menu" => "awal"];

// ==============================================
// 🎯 Fungsi Balas Pesan via Fonnte API
// ==============================================
function sendFonnteMessage($target, $message, $token)
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.fonnte.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $target,
            'message' => $message
        ],
        CURLOPT_HTTPHEADER => [
            "Authorization: $token"
        ]
    ]);
    $response = curl_exec($curl);
    curl_close($curl);

    file_put_contents("log.txt", date('d/m/Y H:i:s') . " | TO: $target | MSG: $message\n", FILE_APPEND);
}

// ==============================================
// 💬 Logika Menu Percakapan
// ==============================================
$reply = "";
switch ($state["menu"]) {

    case "awal":
        if (in_array($message, ["hi", "halo", "hai", "menu", "start"])) {
            $reply = "Halo 👋\nSelamat datang di *CS Otomatis Bot*.\n\nSilakan pilih menu berikut:\n\n1️⃣ Bantuan\n2️⃣ Laporan Bug\n3️⃣ Panduan Penggunaan\n4️⃣ Hubungi Admin";
            $state["menu"] = "utama";
        } else {
            $reply = "Halo! Ketik *menu* untuk melihat daftar layanan kami 😊";
        }
        break;

    case "utama":
        if ($message == "1") {
            $reply = "Kamu memilih *Bantuan* 🛠️\nSilakan jelaskan masalahmu, nanti sistem akan membantu secara otomatis.";
            $state["menu"] = "bantuan";
        } elseif ($message == "2") {
            $reply = "Kamu memilih *Laporan Bug* 🐞\nKetik detail bug yang kamu temukan (misalnya: tombol tidak berfungsi, error di halaman, dll).";
            $state["menu"] = "laporan_bug";
        } elseif ($message == "3") {
            $reply = "Panduan Penggunaan 📖\n\n1. Buka halaman utama website kami\n2. Klik tombol 'CS Otomatis'\n3. Pilih topik sesuai kebutuhan\n\nKetik *menu* untuk kembali ke awal.";
            $state["menu"] = "utama";
        } elseif ($message == "4") {
            $reply = "Hubungi admin di WhatsApp: https://wa.me/62881011749806\nAtau balas *menu* untuk kembali.";
            $state["menu"] = "utama";
        } else {
            $reply = "Pilihan tidak dikenal ⚠️\nKetik *menu* untuk kembali ke daftar layanan.";
        }
        break;

    case "bantuan":
        if ($message == "menu") {
            $reply = "Kembali ke menu utama:\n\n1️⃣ Bantuan\n2️⃣ Laporan Bug\n3️⃣ Panduan Penggunaan\n4️⃣ Hubungi Admin";
            $state["menu"] = "utama";
        } else {
            $reply = "Terima kasih, pesan bantuanmu sudah dicatat ✅\nTim kami akan meninjau dan membalas segera.\n\nKetik *menu* untuk kembali.";
            $state["menu"] = "akhir";
        }
        break;

    case "laporan_bug":
        if ($message == "menu") {
            $reply = "Kembali ke menu utama:\n\n1️⃣ Bantuan\n2️⃣ Laporan Bug\n3️⃣ Panduan Penggunaan\n4️⃣ Hubungi Admin";
            $state["menu"] = "utama";
        } else {
            $reply = "Laporan bug kamu sudah diterima 🐞\nKami akan memeriksanya secepatnya!\n\nKetik *menu* untuk kembali.";
            $state["menu"] = "akhir";
        }
        break;

    default:
        $reply = "Ketik *menu* untuk memulai kembali percakapan.";
        $state["menu"] = "awal";
        break;
}

// Simpan status terbaru user
file_put_contents($userFile, json_encode($state));

// Kirim balasan ke user
if ($reply !== "") {
    sendFonnteMessage($sender, $reply, $token);
}

echo "OK";
