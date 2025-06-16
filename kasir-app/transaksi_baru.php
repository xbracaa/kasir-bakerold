<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include("config/db.php"); // Pastikan path ke db.php sudah benar

// Cek login kasir
if (!isset($_SESSION['id_kasir'])) {
    header("Location: login.php");
    exit;
}

$id_kasir = $_SESSION['id_kasir'];
$nama_kasir = $_SESSION['nama_kasir'];
$produk_query = $koneksi->query("SELECT * FROM produk ORDER BY nama_produk"); // Order products for better display

$tanggal = date('Y-m-d');
$waktu   = date('H:i:s');
$hari_map = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$hari = $hari_map[date('l')];

$promo_aktif = [];
$promo_q = $koneksi->query("
    SELECT * FROM promo
    WHERE
        tanggal_mulai <= '$tanggal' AND tanggal_akhir >= '$tanggal' AND
        waktu_mulai <= '$waktu' AND waktu_selesai >= '$waktu' AND
        FIND_IN_SET('$hari', berlaku_hari)
    ORDER BY nama_promo ASC
");
while ($p = $promo_q->fetch_assoc()) {
    $promo_aktif[] = $p;
}

$keranjang = $_SESSION['keranjang'] ?? [];

// Inisialisasi variabel untuk pesan error/sukses
$alert_message_html = '';

// Display error alert if a promo couldn't be added (or any other general error)
if (isset($_SESSION['pesan_error'])) {
    $error_message = htmlspecialchars($_SESSION['pesan_error']);
    $alert_message_html = <<<HTML
        <div class="alert alert-warning alert-dismissible fade show mx-auto mt-3" role="alert" style="max-width: 600px;">
            <strong>Perhatian!</strong> ${error_message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    HTML;
    unset($_SESSION['pesan_error']); // Hapus pesan setelah ditampilkan
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Baru - Aplikasi Kasir</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .container-fluid {
            max-width: 1500px; /* Wider container for better product display */
        }
        .header-section {
            margin-bottom: 30px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            font-weight: bold;
            padding: 15px 20px;
            display: flex;
            align-items: center;
        }
        .card-header.promo-header {
            background-color: #ffc107; /* Warning yellow for promo */
            color: #343a40;
        }
        .card-header.cart-header {
            background-color: #28a745; /* Success green for cart */
            color: white;
        }
        .card-header i {
            margin-right: 10px;
        }
        .product-grid, .promo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            padding: 15px;
        }
        .product-card, .promo-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Ensures buttons are at the bottom */
        }
        .product-card:hover, .promo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        .product-card img, .promo-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-bottom: 1px solid #e0e0e0;
        }
        .product-card .card-body, .promo-card .card-body {
            padding: 10px;
            text-align: center;
            flex-grow: 1; /* Allow content to grow */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .product-card .card-title, .promo-card .card-title {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #343a40;
        }
        .product-card .card-text, .promo-card .card-text {
            font-size: 1rem;
            color: #28a745; /* Green for price */
            font-weight: bold;
            margin-bottom: 10px;
        }
        .product-card form, .promo-card form {
            margin-top: auto; /* Push form to the bottom */
            padding-top: 10px; /* Space above button/input */
            border-top: 1px solid #f0f0f0;
        }
        .product-card .form-control-sm {
            width: 70px;
            display: inline-block;
            margin-right: 5px;
        }
        .table thead th {
            background-color: #28a745; /* Green header for cart table */
            color: white;
        }
        .table tbody tr:nth-of-type(even) {
            background-color: #f2f2f2;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #ffffff; /* Override default striped to match design */
        }
        .table tbody tr:hover {
            background-color: #e9ecef;
        }
        .total-row {
            font-weight: bold;
            background-color: #e6ffed !important; /* Light green for total row */
            color: #1a5e2a;
        }
        .btn-remove {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        .btn-remove:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .checkout-form label {
            font-weight: bold;
            margin-top: 10px;
        }
        .btn-checkout {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            font-size: 1.1rem;
            padding: 10px 20px;
            margin-top: 15px;
        }
        .btn-checkout:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .no-promo-message, .empty-cart-message {
            text-align: center;
            color: #6c757d;
            padding: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <?= $alert_message_html; ?>

        <a href="home.php" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <div class="header-section text-center">
            <h2 class="display-4"><i class="fas fa-cash-register"></i> Transaksi Baru</h2>
            <p class="lead text-muted">Kasir: <strong><?= htmlspecialchars($nama_kasir) ?></strong> | Tanggal: <strong><?= date("d F Y") ?></strong> | Jam: <span id="waktu_sekarang"></span> WIB</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bread-slice"></i> Daftar Produk
                    </div>
                    <div class="card-body">
                        <div class="product-grid">
                            <?php if ($produk_query->num_rows > 0): ?>
                                <?php while ($p = $produk_query->fetch_assoc()): ?>
                                    <div class="product-card">
                                        <img src="images/<?= strtolower(str_replace(' ', '_', $p['nama_produk'])) ?>.jpg" class="card-img-top" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($p['nama_produk']) ?></h5>
                                            <p class="card-text">Rp <?= number_format($p['harga'], 0, ',', '.') ?></p>
                                            <form method="post" action="tambah_keranjang.php" class="form-inline justify-content-center">
                                                <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                                                <input type="number" name="qty" value="1" min="1" class="form-control form-control-sm mr-2 text-center" style="width: 70px;" />
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12 text-center text-muted py-5">
                                    <p>Tidak ada produk tersedia saat ini.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header promo-header">
                        <i class="fas fa-tags"></i> Promo Tersedia Hari Ini
                    </div>
                    <div class="card-body">
                        <div class="promo-grid">
                            <?php if (empty($promo_aktif)): ?>
                                <div class="col-12 no-promo-message">
                                    <p>Tidak ada promo aktif untuk saat ini.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($promo_aktif as $p_item): ?>
                                    <div class="promo-card">
                                        <img src="images/promo_generic.jpg" class="card-img-top" alt="<?= htmlspecialchars($p_item['nama_promo']) ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($p_item['nama_promo']) ?></h5>
                                            <p class="card-text">
                                                <strong>
                                                    <?= ($p_item['jenis'] == 'paket') ? "Rp " . number_format($p_item['harga_promo'], 0, ',', '.') : 'Bonus Produk' ?>
                                                </strong>
                                            </p>
                                            <form method="post" action="tambah_promo.php" class="text-center">
                                                <input type="hidden" name="id_promo" value="<?= $p_item['id_promo'] ?>">
                                                <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-check-circle"></i> Pilih Promo</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header cart-header">
                        <i class="fas fa-shopping-cart"></i> Keranjang Belanja
                    </div>
                    <div class="card-body">
                        <?php if (empty($keranjang)): ?>
                            <div class="empty-cart-message">
                                <p>Keranjang kosong. Silakan tambahkan produk atau promo.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-right">Harga</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-right">Subtotal</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $total = 0;
                                        foreach ($keranjang as $i => $item):
                                            $subtotal = $item['harga'] * $item['qty'];
                                            $total += $subtotal;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                                            <td class="text-right">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                            <td class="text-center"><?= $item['qty'] ?></td>
                                            <td class="text-right">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                            <td class="text-center">
                                                <form method="post" action="hapus_item.php" class="d-inline">
                                                    <input type="hidden" name="index" value="<?= $i ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm btn-remove" title="Hapus Item"><i class="fas fa-times"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr class="total-row">
                                            <td colspan="3" class="text-right">TOTAL</td>
                                            <td colspan="2" class="text-right">Rp <?= number_format($total, 0, ',', '.') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <form method="post" action="proses_transaksi.php" class="checkout-form mt-3">
                                <div class="form-group">
                                    <label for="bayar">Bayar:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" class="form-control form-control-lg" id="bayar" name="bayar" required min="<?= $total ?>" placeholder="Masukkan jumlah uang">
                                    </div>
                                    <small class="form-text text-muted">Minimal pembayaran adalah Rp <?= number_format($total, 0, ',', '.') ?></small>
                                </div>
                                <input type="hidden" name="total" value="<?= $total ?>">
                                <button type="submit" class="btn btn-primary btn-block btn-checkout">
                                    <i class="fas fa-coins"></i> Selesaikan Transaksi
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Real-time clock update
        function updateCurrentTime() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            document.getElementById('waktu_sekarang').textContent = now.toLocaleTimeString('id-ID', options);
        }
        setInterval(updateCurrentTime, 1000);
        updateCurrentTime(); // Initial call to display time immediately
    </script>
</body>
</html>