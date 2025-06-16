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
    // Pastikan harga promo dihitung dengan benar dari 'harga' item
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
    // Pastikan 'id_produk' adalah integer. Jika 'id_produk' 0 (untuk promo paket), itu akan valid.
    $id_produk  = intval($item['id_produk']);
    $nama_item_yang_disimpan = (string)$item['nama_produk']; // Ini akan menjadi nama produk atau nama promo/bonus
    if (empty($nama_produk_yang_disimpan)) {
        $nama_produk_yang_disimpan = "Nama Produk Tidak Dikenal"; // Fallback jika string kosong
    }
    $qty        = intval($item['qty']);
    $harga      = intval($item['harga']); // Ini harga satuan per item (termasuk 0 untuk bonus)
    $subtotal   = $qty * $harga;
    // Harga setelah diskon (jika ada, dari promo paket misalnya, atau sama dengan harga satuan jika tidak ada diskon per item)
    // Di logic tambah_keranjang.php, untuk 'paket', harga produk individual di-set 0, lalu ada item baru dengan harga promo.
    // Jadi di sini, harga_setelah_diskon bisa disamakan dengan harga jika tidak ada diskon per item.
    $harga_setelah_diskon = $subtotal; // Asumsi subtotal sudah mencerminkan harga diskon/0 untuk bonus

    $stmt2 = $koneksi->prepare("
        INSERT INTO detail_transaksi
        (id_transaksi, id_produk, nama_produk, qty, harga_satuan, subtotal, harga_setelah_diskon)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt2->bind_param("isisiii", $id_transaksi, $id_produk, $nama_item_yang_disimpan, $qty, $harga, $subtotal, $harga_setelah_diskon);
    $stmt2->execute();
    $stmt2->close();
}

// ✅ Kosongkan keranjang
unset($_SESSION['keranjang']);

// ✅ Redirect ke halaman transaksi_selesai
header("Location: transaksi_selesai.php?kode=$kode_transaksi");
exit;
?>