<?php
session_start(); // Memulai sesi PHP
include("config/db.php"); // Pastikan path ke db.php sudah benar

// --- KEAMANAN DAN VALIDASI AWAL ---
// Cek apakah kasir sudah login
if (!isset($_SESSION['id_kasir'])) {
    header("Location: login.php"); // Arahkan ke login jika belum
    exit;
}

// Pastikan ada ID promo yang dikirimkan melalui POST
if (!isset($_POST['id_promo'])) {
    // Jika tidak ada ID promo yang dikirimkan, kembalikan ke halaman transaksi dengan pesan error
    $_SESSION['pesan_error'] = "Permintaan tidak valid: ID Promo tidak ditemukan.";
    header("Location: transaksi_baru.php");
    exit;
}

$id_promo = intval($_POST['id_promo']); // Pastikan ID promo adalah integer untuk keamanan

// --- AMBIL DETAIL PROMO DARI DATABASE ---
// Gunakan prepared statement untuk keamanan ekstra, meskipun intval sudah membantu
$stmt = $koneksi->prepare("SELECT * FROM promo WHERE id_promo = ?");
$stmt->bind_param("i", $id_promo);
$stmt->execute();
$result = $stmt->get_result();
$promo = $result->fetch_assoc();
$stmt->close();

// Jika promo tidak ditemukan di database
if (!$promo) {
    $_SESSION['pesan_error'] = "Promo tidak ditemukan atau tidak valid.";
    header("Location: transaksi_baru.php");
    exit;
}

// --- INISIALISASI KERANJANG DAN PEMBERSIHAN PROMO LAMA ---
// Inisialisasi keranjang jika belum ada di sesi
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// Hapus semua baris yang merupakan hasil dari promo sebelumnya (tipe 'promo' atau 'bonus')
// Ini penting agar hanya satu promo yang aktif di keranjang
$_SESSION['keranjang'] = array_filter($_SESSION['keranjang'], function($item) {
    // Pertahankan item yang bukan merupakan hasil dari promo ('produk' biasa)
    // atau jika Anda punya kebutuhan untuk mempertahankan beberapa jenis item lain, tambahkan di sini.
    return !isset($item['tipe']) || ($item['tipe'] !== 'promo' && $item['tipe'] !== 'bonus');
});
$_SESSION['keranjang'] = array_values($_SESSION['keranjang']); // Re-index array setelah filter

// --- MENAMBAHKAN ITEM PROMO BARU KE KERANJANG ---
$jenis_promo = $promo['jenis'];
$nama_promo_display = htmlspecialchars($promo['nama_promo']);

// Tentukan harga item promo yang akan ditambahkan ke keranjang
$harga_item_promo = 0; // Default harga untuk bonus atau diskon yang tidak menambah nilai item di keranjang
if ($jenis_promo === 'paket') {
    // Jika promo adalah jenis 'paket', tambahkan baris dengan harga paket yang sudah ditentukan
    $harga_item_promo = intval($promo['harga_promo']);
}

// Tambahkan baris utama promo ke keranjang
$_SESSION['keranjang'][] = [
    'id_produk' => 0, // ID 0 sering digunakan untuk item non-fisik/promo
    'nama_produk' => $nama_promo_display . ($jenis_promo === 'paket' ? " (Paket)" : ""),
    'harga' => $harga_item_promo,
    'qty' => 1,
    'tipe' => 'promo', // Menandai tipe ini sebagai baris promo utama
    'id_promo' => $promo['id_promo'] // Simpan ID promo untuk referensi jika diperlukan
];

// Jika promo adalah jenis 'bonus', tambahkan juga produk bonusnya sebagai item terpisah
if ($jenis_promo === 'bonus') {
    $bonus_id = intval($promo['id_produk_bonus']);
    // Ambil detail produk bonus
    $produk_bonus_stmt = $koneksi->prepare("SELECT id_produk, nama_produk FROM produk WHERE id_produk = ?");
    $produk_bonus_stmt->bind_param("i", $bonus_id);
    $produk_bonus_stmt->execute();
    $produk_bonus_result = $produk_bonus_stmt->get_result();
    $produk_bonus = $produk_bonus_result->fetch_assoc();
    $produk_bonus_stmt->close();

    if ($produk_bonus) {
        $_SESSION['keranjang'][] = [
            'id_produk' => $produk_bonus['id_produk'],
            'nama_produk' => htmlspecialchars($produk_bonus['nama_produk']) . " (Bonus)",
            'harga' => 0, // Produk bonus selalu harga 0
            'qty' => 1,
            'tipe' => 'bonus', // Menandai tipe ini sebagai produk bonus
            'id_promo' => $promo['id_promo'] // Kaitkan dengan ID promo yang memberikannya
        ];
    } else {
        // Jika produk bonus tidak ditemukan (misalnya, ID produk bonus di tabel promo salah)
        $_SESSION['pesan_error'] = "Produk bonus untuk promo '{$nama_promo_display}' tidak ditemukan. Promo utama sudah ditambahkan.";
    }
}

// --- REDIREKSI AKHIR ---
// Arahkan kembali ke halaman transaksi_baru.php setelah berhasil menambahkan promo
header("Location: transaksi_baru.php");
exit;
?>