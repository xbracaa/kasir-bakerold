<?php
session_start();
include("config/db.php");

function getHariIndonesia() {
    $hariInggris = date('l');
    $mapHari = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu'
    ];
    return $mapHari[$hariInggris] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_promo'])) {
    $id_promo = intval($_POST['id_promo']);
    $tanggal  = date('Y-m-d');
    $waktu    = date('H:i:s');
    $hari     = getHariIndonesia();

    // Ambil data promo yang aktif
    $promo = $koneksi->query("
        SELECT * FROM promo 
        WHERE 
            id_promo = $id_promo AND 
            tanggal_mulai <= '$tanggal' AND tanggal_akhir >= '$tanggal' AND 
            waktu_mulai <= '$waktu' AND waktu_selesai >= '$waktu' AND 
            FIND_IN_SET('$hari', berlaku_hari)
    ")->fetch_assoc();

    if (!$promo) {
        // Promo tidak ditemukan atau tidak aktif
        header("Location: transaksi_baru.php");
        exit;
    }

    // Siapkan keranjang
    $keranjang = $_SESSION['keranjang'] ?? [];

    // Ambil produk trigger
    $id_trigger = intval($promo['id_produk_trigger']);
    $trigger = $koneksi->query("SELECT * FROM produk WHERE id_produk = $id_trigger")->fetch_assoc();

    if (!$trigger) {
        header("Location: transaksi_baru.php");
        exit;
    }

    // Proses promo
    if ($promo['jenis'] === 'bonus') {
        $id_bonus = intval($promo['id_produk_bonus']);
        $bonus = $koneksi->query("SELECT * FROM produk WHERE id_produk = $id_bonus")->fetch_assoc();

        if ($bonus) {
            // Tambahkan produk trigger (misalnya beli 2)
            $keranjang[] = [
                'id_produk'   => $trigger['id_produk'],
                'nama_produk' => $trigger['nama_produk'],
                'harga'       => $trigger['harga'],
                'qty'         => $promo['minimal_qty']
            ];

            // Tambahkan produk bonus (gratis)
            $keranjang[] = [
                'id_produk'   => $bonus['id_produk'],
                'nama_produk' => $bonus['nama_produk'] . " (Bonus)",
                'harga'       => 0,
                'qty'         => 1
            ];
        }
    } elseif ($promo['jenis'] === 'paket') {
        // Tambahkan sebagai satu bundling dengan harga paket
        $keranjang[] = [
            'id_produk'   => $trigger['id_produk'],
            'nama_produk' => $promo['nama_promo'] . " (Paket)",
            'harga'       => $promo['harga_promo'],
            'qty'         => 1
        ];
    }

    $_SESSION['keranjang'] = $keranjang;
}

header("Location: transaksi_baru.php");
exit;
