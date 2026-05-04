<?php
// koneksi.php
$host = 'localhost';
$port = 3306;
$db   = 'umkm';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Koneksi PDO
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi PDO gagal: " . $e->getMessage());
}

// Koneksi MySQLi
$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die("Koneksi MySQLi gagal: " . $conn->connect_error);
}
