<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include("config/db.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_produk = intval($_POST['id_produk']);
    $qty = intval($_POST['qty']);

    $produk = $koneksi->query("SELECT * FROM produk WHERE id_produk = $id_produk")->fetch_assoc();
    if (!$produk) die("Produk tidak ditemukan.");

    // Inisialisasi keranjang
    if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];

    // Tambahkan atau update produk (selain promo/bonus)
    $found = false;
    foreach ($_SESSION['keranjang'] as &$item) {
        if ($item['id_produk'] == $id_produk && strpos($item['nama_produk'], '(Bonus)') === false && $item['harga'] != 0) {
            $item['qty'] += $qty;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['keranjang'][] = [
            'id_produk' => $produk['id_produk'],
            'nama_produk' => $produk['nama_produk'],
            'harga' => $produk['harga'],
            'qty' => $qty
        ];
    }

    // ================================
    // 💥 CEK PROMO
    // ================================
    $tanggal = date('Y-m-d');
    $waktu = date('H:i:s');
    $hari_map = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
    ];
    $hari = $hari_map[date('l')];

    $promo_q = $koneksi->query("
        SELECT * FROM promo 
        WHERE 
            tanggal_mulai <= '$tanggal' AND tanggal_akhir >= '$tanggal' AND 
            waktu_mulai <= '$waktu' AND waktu_selesai >= '$waktu' AND 
            FIND_IN_SET('$hari', berlaku_hari)
    ");

    // 🔁 Hapus semua baris promo & bonus
    $_SESSION['keranjang'] = array_filter($_SESSION['keranjang'], function($item) {
        return strpos($item['nama_produk'], '(Bonus)') === false && $item['id_produk'] != 0;
    });
    $_SESSION['keranjang'] = array_values($_SESSION['keranjang']);

    foreach ($promo_q as $promo) {
        $jenis = $promo['jenis'];
        $minimal_qty = intval($promo['minimal_qty']);
        $harga_promo = intval($promo['harga_promo']);
        $trigger_ids = explode(',', $promo['id_produk_trigger']); // ← bisa banyak id

        // 🔍 Hitung total qty produk trigger (bisa banyak)
        $total_qty = 0;
        foreach ($_SESSION['keranjang'] as $item) {
            if (in_array($item['id_produk'], $trigger_ids)) {
                $total_qty += $item['qty'];
            }
        }

        if ($total_qty >= $minimal_qty) {
            if ($jenis == 'bonus') {
                $bonus_id = intval($promo['id_produk_bonus']);
                $bonus = $koneksi->query("SELECT * FROM produk WHERE id_produk = $bonus_id")->fetch_assoc();
                if ($bonus) {
                    $_SESSION['keranjang'][] = [
                        'id_produk' => $bonus['id_produk'],
                        'nama_produk' => $bonus['nama_produk'] . " (Bonus)",
                        'harga' => 0,
                        'qty' => 1
                    ];
                }
            } elseif ($jenis == 'paket') {
                // Set harga produk trigger jadi 0
                $sisa = $minimal_qty;
                foreach ($_SESSION['keranjang'] as &$item) {
                    if (in_array($item['id_produk'], $trigger_ids) && $sisa > 0) {
                        if ($item['qty'] <= $sisa) {
                            $sisa -= $item['qty'];
                            $item['harga'] = 0;
                        } else {
                            $item['qty'] -= $sisa;
                            $_SESSION['keranjang'][] = [
                                'id_produk' => $item['id_produk'],
                                'nama_produk' => $item['nama_produk'],
                                'harga' => 0,
                                'qty' => $sisa
                            ];
                            $sisa = 0;
                        }
                    }
                }
                // Tambahkan baris promo sebagai satuan
                $_SESSION['keranjang'][] = [
                    'id_produk' => 0,
                    'nama_produk' => $promo['nama_promo'] ?? 'Paket Promo',
                    'harga' => $harga_promo,
                    'qty' => 1
                ];
            }
        }
    }

    header("Location: transaksi_baru.php");
    exit;
}
?>
