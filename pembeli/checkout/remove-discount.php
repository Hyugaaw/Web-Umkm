<?php
session_start();
header('Content-Type: application/json');
require_once "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if (isset($_SESSION['applied_discount'])) unset($_SESSION['applied_discount']);

// Return available discounts count so client can decide to open modal
echo json_encode(['success' => true]);
