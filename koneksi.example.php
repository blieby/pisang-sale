
<?php
// Contoh konfigurasi database untuk proyek Pisang Sale

$host = "localhost";
$db   = "nama_database";
$user = "username_database";
$pass = "password_database";

header("Content-Type: application/json; charset=utf-8");

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "pesan" => "Database tidak terhubung"]);
    exit;
}
