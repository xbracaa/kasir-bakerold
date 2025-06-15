<?php
session_start();
include("config/db.php");

// Pastikan kasir sudah login dan keranjang tidak kosong
if (!isset($_SESSION['id_kasir']) || empty($_SESSION['keranjang'])) {
    header("Location: transaksi_baru.php");
    exit;
}

$id_kasir = $_SESSION['id_kasir'];
$keranjang = $_SESSION['keranjang'];
$total     = isset($_POST['total']) ? intval($_POST['total']) : 0;
$bayar     = isset($_POST['bayar']) ? intval($_POST['bayar']) : 0;
$kembalian = $bayar - $total;
$kode_transaksi = 'TRX-' . date('YmdHis');

// Simpan ke tabel transaksi
$stmt = $koneksi->prepare("
    INSERT INTO transaksi (kode_transaksi, id_kasir, total, bayar, kembalian) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("siiii", $kode_transaksi, $id_kasir, $total, $bayar, $kembalian);
$stmt->execute();
$id_transaksi = $stmt->insert_id;
$stmt->close();

// Simpan detail transaksi
foreach ($keranjang as $item) {
    $id_produk    = $item['id_produk'];
    $qty          = intval($item['qty']);
    $harga        = intval($item['harga']);
    $subtotal     = $qty * $harga;

    // Harga setelah diskon jika ada
    $harga_setelah_diskon = isset($item['harga_setelah_diskon']) ? intval($item['harga_setelah_diskon']) : $harga;

    $stmt2 = $koneksi->prepare("
        INSERT INTO detail_transaksi 
        (id_transaksi, id_produk, qty, harga_satuan, subtotal, harga_setelah_diskon) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt2) {
        die("Prepare gagal: " . $koneksi->error);
    }
    $stmt2->bind_param("iiiiii", $id_transaksi, $id_produk, $qty, $harga, $subtotal, $harga_setelah_diskon);
    $stmt2->execute();
    $stmt2->close();
}

// Kosongkan keranjang
unset($_SESSION['keranjang']);

// Redirect ke halaman nota
header("Location: transaksi_selesai.php?kode=$kode_transaksi");
exit;
?>
