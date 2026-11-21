<?php
require "koneksi.php";

$produk = $pdo->query(
  "SELECT id, nama, harga, warna, label, deskripsi, bahan FROM produk WHERE aktif = 1 ORDER BY id"
)->fetchAll();

echo json_encode($produk);
