<?php
session_start();
include("config/db.php");

// Pastikan kasir sudah login dan keranjang tidak kosong
if (!isset($_SESSION['id_kasir']) || empty($_SESSION['keranjang'])) {
    header("Location: transaksi_baru.php");
    exit;
}

$id_kasir   = $_SESSION['id_kasir'];
$keranjang  = $_SESSION['keranjang'];

// Hitung total langsung dari keranjang
$total = 0;
foreach ($keranjang as $item) {
    $total += $item['harga'] * $item['qty'];
}

$bayar      = isset($_POST['bayar']) ? intval($_POST['bayar']) : 0;
$kembalian  = $bayar - $total;
$kode_transaksi = 'TRX-' . date('YmdHis');

// ✅ SIMPAN KE TABEL TRANSAKSI
$stmt = $koneksi->prepare("
    INSERT INTO transaksi (kode_transaksi, id_kasir, total, bayar, kembalian)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("siiii", $kode_transaksi, $id_kasir, $total, $bayar, $kembalian);
$stmt->execute();
$id_transaksi = $stmt->insert_id;
$stmt->close();

// ✅ SIMPAN KE TABEL DETAIL_TRANSAKSI
foreach ($keranjang as $item) {
    $id_produk  = $item['id_produk'];
    $nama_produk = $item['nama_produk'];
    $qty        = intval($item['qty']);
    $harga      = intval($item['harga']);
    $subtotal   = $qty * $harga;
    $harga_setelah_diskon = isset($item['harga_setelah_diskon']) ? intval($item['harga_setelah_diskon']) : $harga;

    $stmt2 = $koneksi->prepare("
        INSERT INTO detail_transaksi
        (id_transaksi, id_produk, nama_produk, qty, harga_satuan, subtotal, harga_setelah_diskon)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param("isiiiii", $id_transaksi, $id_produk, $nama_produk, $qty, $harga, $subtotal, $harga_setelah_diskon);
    $stmt2->execute();
    $stmt2->close();
}

// ✅ Kosongkan keranjang
unset($_SESSION['keranjang']);

// ✅ Redirect ke halaman struk
header("Location: transaksi_selesai.php?kode=$kode_transaksi");
exit;
?>
