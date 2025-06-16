<?php
include("config/db.php");

$kode = $_GET['kode'] ?? '';
if (!$kode) {
    echo "Kode transaksi tidak tersedia.";
    exit;
}

// Ambil data transaksi utama
$stmt = $koneksi->prepare("SELECT * FROM transaksi WHERE kode_transaksi = ?");
$stmt->bind_param("s", $kode);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "Transaksi tidak ditemukan.";
    exit;
}

// Ambil nama kasir
$id_kasir = $data['id_kasir'];
$kasir = $koneksi->query("SELECT nama_kasir FROM kasir WHERE id_kasir = $id_kasir")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaksi Selesai</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: auto; }
        h2 { text-align: center; }
        .ringkasan { margin-top: 20px; }
        .ringkasan p { margin: 5px 0; }
        .btn { margin-top: 20px; text-align: center; }
        .btn a { padding: 8px 16px; background: #007bff; color: #fff; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>

<h2>Transaksi Selesai</h2>
<div class="ringkasan">
    <p><strong>Kode Transaksi:</strong> <?= htmlspecialchars($data['kode_transaksi']) ?></p>
    <p><strong>Kasir:</strong> <?= htmlspecialchars($kasir['nama_kasir']) ?></p>
    <p><strong>Total:</strong> Rp <?= number_format($data['total'], 0, ',', '.') ?></p>
    <p><strong>Bayar:</strong> Rp <?= number_format($data['bayar'], 0, ',', '.') ?></p>
    <p><strong>Kembalian:</strong> Rp <?= number_format($data['kembalian'], 0, ',', '.') ?></p>
</div>

<div class="btn">
    <a href="print_struk.php?kode=<?= urlencode($kode) ?>" target="_blank">Cetak Struk</a>
</div>

<div class="btn">
    <a href="transaksi_baru.php">Transaksi Baru</a>
</div>

</body>
</html>
