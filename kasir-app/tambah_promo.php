<?php
session_start();
include("config/db.php");

if (!isset($_POST['id_promo'])) {
    header("Location: transaksi_baru.php");
    exit;
}

$id_promo = $_POST['id_promo'];

// Ambil detail promo
$promo = $koneksi->query("SELECT * FROM promo WHERE id_promo = '$id_promo'")->fetch_assoc();
if (!$promo) {
    header("Location: transaksi_baru.php");
    exit;
}

$id_trigger = $promo['id_produk_trigger'];
$id_bonus   = $promo['id_produk_bonus'];
$qty        = $promo1;

// Inisialisasi keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Ambil info produk trigger
$produk_trigger = $koneksi->query("SELECT * FROM produk WHERE id_produk = '$id_trigger'")->fetch_assoc();
if (!$produk_trigger) {
    header("Location: transaksi_baru.php");
    exit;
}

// Tambah produk trigger
$_SESSION['keranjang'][] = [
    'id_produk' => $produk_trigger['id_produk'],
    'nama_produk' => $produk_trigger['nama_produk'],
    'qty' => $qty,
    'harga' => ($promo['jenis'] === 'paket') ? $promo['harga_promo'] : $produk_trigger['harga']
];

// Tambah produk bonus jika jenisnya 'bonus'
if ($promo['jenis'] === 'bonus' && $id_bonus) {
    $produk_bonus = $koneksi->query("SELECT * FROM produk WHERE id_produk = '$id_bonus'")->fetch_assoc();
    if ($produk_bonus) {
        $_SESSION['keranjang'][] = [
            'id_produk' => $produk_bonus['id_produk'],
            'nama_produk' => $produk_bonus['nama_produk'] . ' (Bonus)',
            'qty' => 1,
            'harga' => 0
        ];
    }
}

header("Location: transaksi_baru.php");
exit;
