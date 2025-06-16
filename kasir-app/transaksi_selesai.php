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

// AMBIL DETAIL ITEM DARI DETAIL_TRANSAKSI
// Pastikan kolom 'nama_item' sudah ada di tabel detail_transaksi
$items_query = $koneksi->prepare("
    SELECT nama_produk, qty, harga_satuan, subtotal, harga_setelah_diskon
    FROM detail_transaksi
    WHERE id_transaksi = ?
");
$items_query->bind_param("i", $data['id_transaksi']);
$items_query->execute();
$items_result = $items_query->get_result();

$total_items_count = 0; // Untuk menghitung jumlah item yang dibeli
if ($items_result->num_rows > 0) {
    while ($row = $items_result->fetch_assoc()) {
        $total_items_count += $row['qty']; // Menghitung total kuantitas item
    }
}
// Reset pointer result set agar bisa di-loop lagi di HTML
$items_result->data_seek(0);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Transaksi Selesai</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: auto; padding: 20px;}
        h2 { text-align: center; margin-bottom: 20px;}
        .ringkasan { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;}
        .ringkasan p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .total-item-row { font-weight: bold; }
        .btn-container { text-align: center; margin-top: 20px;}
        .btn-container a {
            padding: 10px 20px;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
        }
        .btn-container a:hover {
            background: #0056b3;
        }
        .btn-container a.print-btn {
            background: #28a745; /* Green for print */
        }
        .btn-container a.print-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<h2>Transaksi Selesai</h2>
<div class="ringkasan">
    <p><strong>Kode Transaksi:</strong> <?= htmlspecialchars($data['kode_transaksi']) ?></p>
    <p><strong>Kasir:</strong> <?= htmlspecialchars($kasir['nama_kasir']) ?></p>
</div>

<h3>Daftar Belanja:</h3>
<table>
    <thead>
        <tr>
            <th>Nama Produk</th>
            <th>Qty</th>
            <th>Harga</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $grand_total_belanja = 0;
        if ($items_result->num_rows > 0):
            while ($item = $items_result->fetch_assoc()):
                // Gunakan harga_setelah_diskon jika tersedia, atau subtotal jika tidak (untuk item bonus 0)
                $harga_tampil = $item['harga_satuan'];
                $subtotal_tampil = $item['subtotal'];
                $grand_total_belanja += $subtotal_tampil; // Summing up the actual subtotal

        ?>
        <tr>
            <td><?= htmlspecialchars($item['nama_produk']) ?></td>
            <td><?= $item['qty'] ?></td>
            <td>Rp <?= number_format($harga_tampil, 0, ',', '.') ?></td>
            <td>Rp <?= number_format($subtotal_tampil, 0, ',', '.') ?></td>
        </tr>
        <?php
            endwhile;
        else:
        ?>
        <tr>
            <td colspan="4">Tidak ada item dalam transaksi ini.</td>
        </tr>
        <?php endif; ?>
        <tr class="total-item-row">
            <td colspan="3">Total Item</td>
            <td><?= $total_items_count ?> item</td>
        </tr>
        <tr class="total-item-row">
            <td colspan="3">Total Belanja</td>
            <td>Rp <?= number_format($grand_total_belanja, 0, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<div class="ringkasan">
    <p><strong>Total Pembayaran:</strong> Rp <?= number_format($data['total'], 0, ',', '.') ?></p>
    <p><strong>Bayar:</strong> Rp <?= number_format($data['bayar'], 0, ',', '.') ?></p>
    <p><strong>Kembalian:</strong> Rp <?= number_format($data['kembalian'], 0, ',', '.') ?></p>
</div>

<div class="btn-container">
    <a href="print_struk.php?kode=<?= urlencode($kode) ?>" target="_blank" class="print-btn">Cetak Struk</a>
    <a href="transaksi_baru.php">Transaksi Baru</a>
</div>

</body>
</html>