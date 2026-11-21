<?php
require "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo json_encode(["ok" => false, "pesan" => "Metode tidak diizinkan"]);
  exit;
}

$d       = json_decode(file_get_contents("php://input"), true) ?: [];
$nama    = trim($d["nama"] ?? "");
$hp      = trim($d["hp"] ?? "");
$metode  = ($d["metode"] ?? "") === "antar" ? "antar" : "ambil";
$alamat  = trim($d["alamat"] ?? "");
$catatan = trim($d["catatan"] ?? "");
$items   = $d["items"] ?? [];

try {
  if ($nama === "" || !preg_match('/^[0-9+\s-]{9,16}$/', $hp)) {
    throw new InvalidArgumentException("Nama atau nomor WhatsApp tidak valid");
  }
  if ($metode === "antar" && $alamat === "") {
    throw new InvalidArgumentException("Alamat wajib diisi");
  }
  if (!is_array($items) || count($items) < 1 || count($items) > 50) {
    throw new InvalidArgumentException("Keranjang kosong");
  }

  // Harga diambil dari database, bukan dari website, jadi tidak bisa dipalsukan
  $cari  = $pdo->prepare("SELECT id, nama, harga FROM produk WHERE id = ? AND aktif = 1");
  $total = 0;
  $baris = [];
  foreach ($items as $it) {
    $qty = (int)($it["qty"] ?? 0);
    $cari->execute([(int)($it["id"] ?? 0)]);
    $p = $cari->fetch();
    if (!$p || $qty < 1 || $qty > 999) {
      throw new InvalidArgumentException("Ada produk yang tidak valid");
    }
    $total  += $p["harga"] * $qty;
    $baris[] = [$p["id"], $p["nama"], $p["harga"], $qty];
  }

  $pdo->beginTransaction();

  $pdo->prepare("INSERT INTO pesanan (nama, hp, metode, alamat, catatan, total) VALUES (?,?,?,?,?,?)")
      ->execute([$nama, $hp, $metode, $alamat, $catatan, $total]);
  $id   = (int)$pdo->lastInsertId();
  $kode = "PS-" . str_pad($id, 5, "0", STR_PAD_LEFT);
  $pdo->prepare("UPDATE pesanan SET kode = ? WHERE id = ?")->execute([$kode, $id]);

  $ins = $pdo->prepare("INSERT INTO pesanan_item (pesanan_id, produk_id, nama_produk, harga, qty) VALUES (?,?,?,?,?)");
  foreach ($baris as $b) {
    $ins->execute([$id, $b[0], $b[1], $b[2], $b[3]]);
  }

  $pdo->commit();
  echo json_encode(["ok" => true, "kode" => $kode, "total" => $total]);

} catch (InvalidArgumentException $e) {
  http_response_code(400);
  echo json_encode(["ok" => false, "pesan" => $e->getMessage()]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(["ok" => false, "pesan" => "Gagal menyimpan pesanan"]);
}