<?php
include("config/db.php");

// Ambil kode transaksi dari GET
$kode = $_GET['kode'] ?? '';

// Validasi input
if (empty($kode)) {
    die("Kode transaksi tidak ditemukan!");
}

// Query transaksi
$q_transaksi = $koneksi->query("SELECT * FROM transaksi WHERE kode_transaksi = '$kode'");
if (!$q_transaksi) {
    die("Error transaksi: " . $koneksi->error);
}
$data = $q_transaksi->fetch_assoc();

// Query kasir
$q_kasir = $koneksi->query("SELECT nama_kasir FROM kasir WHERE id_kasir = {$data['id_kasir']}");
if (!$q_kasir) {
    die("Error kasir: " . $koneksi->error);
}
$kasir = $q_kasir->fetch_assoc();

// Query detail transaksi
$items = $koneksi->query("SELECT * FROM detail_transaksi WHERE kode_transaksi = '$kode'");
if (!$items) {
    die("Error detail transaksi: " . $koneksi->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Struk Transaksi</title>
    <style>
        body { font-family: monospace; font-size: 14px; width: 300px; margin: auto; }
        .center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        .right { text-align: right; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

<div class="center">
    <h3>BAKEROLD</h3>
    <p>309 - Jayaraga Garut</p>
</div>

<div class="line"></div>
<p>
    Kode : <?= htmlspecialchars($data['kode_transaksi']) ?><br>
    Tanggal : <?= htmlspecialchars($data['tanggal']) ?><br>
    Kasir : <?= htmlspecialchars($kasir['nama_kasir']) ?>
</p>
<div class="line"></div>

<?php while ($item = $items->fetch_assoc()): ?>
    <p>
        <?= htmlspecialchars($item['nama_produk']) ?><br>
        <?= $item['qty'] ?> x <?= number_format($item['harga'], 0, ',', '.') ?> 
        <span class="right"><?= number_format($item['total'], 0, ',', '.') ?></span>
    </p>
<?php endwhile; ?>

<div class="line"></div>
<p class="right"><strong>Total: Rp <?= number_format($data['total'], 0, ',', '.') ?></strong></p>
<?php if ($data['diskon'] > 0): ?>
    <p class="right">Diskon: Rp <?= number_format($data['diskon'], 0, ',', '.') ?></p>
<?php endif; ?>
<p class="right">Bayar: Rp <?= number_format($data['bayar'], 0, ',', '.') ?></p>
<p class="right">Kembali: Rp <?= number_format($data['kembali'], 0, ',', '.') ?></p>

<div class="line"></div>
<div class="center">~ Terima Kasih ~</div>
<div class="no-print center"><a href="transaksi_baru.php">Kembali</a></div>

</body>
</html>
