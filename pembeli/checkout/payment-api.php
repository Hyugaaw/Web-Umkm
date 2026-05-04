<?php
require_once "../../koneksi.php";

header('Content-Type: application/json');

// Ambil data dari POST
$data = json_decode(file_get_contents('php://input'), true);
$order_id       = $data['order_id'] ?? null;
$total_amount   = $data['total_amount'] ?? null;
$payment_method = $data['payment_method'] ?? null;
$nama           = $data['nama'] ?? null;
$alamat         = $data['alamat'] ?? null;
$no_telepon     = $data['no_telepon'] ?? null;

if (!$order_id || !$total_amount || !$payment_method) {
    echo json_encode(['error' => 'Data pembayaran tidak lengkap']);
    exit;
}

// Server Key Midtrans (ganti dengan server key kamu)
$serverKey = 'Mid-server--ugCyzDSfh_iyqDaBJ9kg-Ht';

// Validasi metode pembayaran
if (!in_array(strtolower($payment_method), ["shopeepay", "gopay"])) {
    echo json_encode(['error' => 'Metode pembayaran tidak valid']);
    exit;
}

// Payload umum untuk Snap API
$payload = [
    "transaction_details" => [
        "order_id"     => $order_id,
        "gross_amount" => (int)$total_amount
    ],
    "customer_details" => [
        "first_name"    => $nama,
        "phone"         => $no_telepon,
        "billing_address" => [
            "first_name" => $nama,
            "address"    => $alamat
        ]
    ]
];

// ...existing code...

// Tambahan khusus untuk Shopeepay
if (strtolower($payment_method) === "shopeepay") {
    $payload["enabled_payments"] = ["shopeepay"];
    // Tambahkan expiry agar waktu pembayaran lebih lama
    $payload["expiry"] = [
        "start_time" => date("Y-m-d H:i:s O"), // waktu mulai
        "unit" => "minute", // satuan waktu
        "duration" => 1440 // durasi pembayaran, misal 30 menit
    ];
}

// Tambahan khusus untuk Gopay
if (strtolower($payment_method) === "gopay") {
    $payload["gopay"] = [
        "enable_callback" => true,
        "callback_url"    => "https://yourdomain.com/payment-callback.php"
    ];
    $payload["enabled_payments"] = ["gopay"];
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://app.sandbox.midtrans.com/snap/v1/transactions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic '.base64_encode($serverKey.":")
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}
curl_close($ch);

// Kembalikan token Snap ke checkout.php
$result = json_decode($response, true);
if (isset($result['token'])) {
    echo json_encode(['token' => $result['token']]);
} else {
    echo json_encode([
        'error' => 'Tidak ada token Snap',
        'response' => $result
    ]);
}
