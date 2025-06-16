-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 17 Jun 2025 pada 00.22
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bakerold`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `buat_transaksi_baru` (IN `p_id_kasir` INT, OUT `p_id_transaksi` INT)   BEGIN
  DECLARE new_kode VARCHAR(50);

  SET new_kode = CONCAT('TRX-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));

  INSERT INTO transaksi (kode_transaksi, id_kasir, total, bayar, kembalian)
  VALUES (new_kode, p_id_kasir, 0, 0, 0);

  SET p_id_transaksi = LAST_INSERT_ID();
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_transaksi`
--

CREATE TABLE `detail_transaksi` (
  `id_detail` int(11) NOT NULL,
  `id_transaksi` int(11) DEFAULT NULL,
  `id_produk` int(11) DEFAULT NULL,
  `nama_produk` varchar(255) NOT NULL,
  `qty` int(11) DEFAULT NULL,
  `harga_satuan` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL,
  `harga_setelah_diskon` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `nama_produk`, `qty`, `harga_satuan`, `subtotal`, `harga_setelah_diskon`) VALUES
(1, 36, 5, '0', 1, 7000, 7000, 7000),
(2, 36, 0, '0', 1, 1000, 1000, 1000),
(3, 37, 5, '0', 1, 7000, 7000, 7000),
(4, 37, 0, '0', 1, 1000, 1000, 1000);

--
-- Trigger `detail_transaksi`
--
DELIMITER $$
CREATE TRIGGER `trg_update_total` AFTER INSERT ON `detail_transaksi` FOR EACH ROW BEGIN
  UPDATE transaksi
  SET total = (
    SELECT SUM(harga_setelah_diskon)
    FROM detail_transaksi
    WHERE id_transaksi = NEW.id_transaksi
  )
  WHERE id_transaksi = NEW.id_transaksi;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kasir`
--

CREATE TABLE `kasir` (
  `id_kasir` int(11) NOT NULL,
  `nama_kasir` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kasir`
--

INSERT INTO `kasir` (`id_kasir`, `nama_kasir`, `username`, `password`) VALUES
(1, 'kasir jayaraga garut', 'kasirjayaraga', 'admin123');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `harga` int(11) NOT NULL,
  `stok` int(11) DEFAULT 0
) ;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`, `harga`, `stok`) VALUES
(1, 'Paket Berbagi Berkah 6 Roti', 45000, 0),
(2, 'Paket Berbagi Berkah 12 Roti', 80000, 0),
(3, 'Paket Berbagi Berkah 50 Roti', 300000, 0),
(4, 'Roti Vanila', 7000, 0),
(5, 'Roti Ori', 7000, 0),
(6, 'Roti Keju', 8000, 0),
(7, 'Roti Coklat', 8000, 0),
(8, 'Roti Pandan Banana', 7000, 0),
(9, 'Roti Pandan Coklat', 8000, 0),
(10, 'Roti Pandan Butter', 7000, 0),
(11, 'Es Krim', 7000, 0),
(12, 'Roti Es Krim', 12000, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `promo`
--

CREATE TABLE `promo` (
  `id_promo` int(11) NOT NULL,
  `nama_promo` varchar(100) NOT NULL,
  `jenis` enum('paket','bonus') NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_akhir` date NOT NULL,
  `waktu_mulai` time NOT NULL,
  `waktu_selesai` time NOT NULL,
  `berlaku_hari` varchar(100) DEFAULT NULL,
  `minimal_qty` int(11) NOT NULL DEFAULT 1,
  `id_produk_trigger` int(11) DEFAULT NULL,
  `id_produk_bonus` int(11) DEFAULT NULL,
  `harga_promo` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `promo`
--

INSERT INTO `promo` (`id_promo`, `nama_promo`, `jenis`, `deskripsi`, `tanggal_mulai`, `tanggal_akhir`, `waktu_mulai`, `waktu_selesai`, `berlaku_hari`, `minimal_qty`, `id_produk_trigger`, `id_produk_bonus`, `harga_promo`) VALUES
(1, 'Paket Roti 20rb', 'paket', 'Beli 3 roti bebas varian hanya Rp 20.000', '2025-06-01', '2025-06-30', '10:00:00', '22:00:00', 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu', 3, NULL, NULL, 20000),
(2, 'Bonus Es Krim Pagi', 'bonus', 'Beli 3 roti dapat es krim', '2025-06-15', '2025-06-30', '07:00:00', '10:00:00', 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu', 3, NULL, 11, NULL),
(3, 'promo 24/7', 'paket', 'semua rasa seribu', '2025-06-15', '2025-06-21', '00:05:00', '23:59:00', 'Senin,Selasa,Rabu,Kamis,Jumat', 1, NULL, NULL, 1000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL,
  `kode_transaksi` varchar(50) NOT NULL,
  `id_kasir` int(11) DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `total` int(11) DEFAULT NULL,
  `bayar` int(11) DEFAULT NULL,
  `kembalian` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `kode_transaksi`, `id_kasir`, `tanggal`, `total`, `bayar`, `kembalian`) VALUES
(1, 'TRX1749976499', 1, '2025-06-15 15:34:59', 32000, 32000, 0),
(2, 'TRX1749977209', 1, '2025-06-15 15:46:49', 314000, 400000, 86000),
(3, 'TRX1749977483', 1, '2025-06-15 15:51:23', 95000, 800000, 705000),
(4, 'TRX-20250615114008', 1, '2025-06-15 16:40:08', 21000, 25000, 4000),
(5, 'TRX-20250615114251', 1, '2025-06-15 16:42:51', 21000, 25000, 4000),
(6, 'TRX-20250615132812', 1, '2025-06-15 18:28:12', 21000, 25000, 4000),
(7, 'TRX-20250615152138', 1, '2025-06-15 20:21:38', 0, 20000, 20000),
(8, 'TRX-20250615152518', 1, '2025-06-15 20:25:18', 0, 20000, 20000),
(9, 'TRX-20250615152528', 1, '2025-06-15 20:25:28', 20000, 20000, 20000),
(10, 'TRX-20250615160322', 1, '2025-06-15 21:03:22', 8000, 10000, 2000),
(11, 'TRX-20250616103914', 1, '2025-06-16 15:39:14', 20000, 20000, 0),
(12, 'TRX-20250616111724', 1, '2025-06-16 16:17:24', 8000, 10000, 2000),
(13, 'TRX-20250616125758', 1, '2025-06-16 17:57:58', 7000, 30000, 3000),
(14, 'TRX-20250616131100', 1, '2025-06-16 18:11:00', 80000, 100000, 20000),
(15, 'TRX-20250616132107', 1, '2025-06-16 18:21:07', 20000, 20000, 0),
(16, 'TRX-20250616135457', 1, '2025-06-16 18:54:57', 7000, 30000, 3000),
(17, 'TRX-20250616140435', 1, '2025-06-16 19:04:35', 80000, 100000, 0),
(18, 'TRX-20250616141301', 1, '2025-06-16 19:13:01', 8000, 30000, 2000),
(19, 'TRX-20250616143207', 1, '2025-06-16 19:32:07', 20000, 20000, 0),
(20, 'TRX-20250616143942', 1, '2025-06-16 19:39:42', 20000, 50000, 30000),
(21, 'TRX-20250616151911', 1, '2025-06-16 20:19:11', 20000, 20000, 0),
(22, 'TRX-20250616154119', 1, '2025-06-16 20:41:19', 8000, 30000, 2000),
(23, 'TRX-20250616160911', 1, '2025-06-16 21:09:11', 80000, 100000, 0),
(24, 'TRX-20250616163112', 1, '2025-06-16 21:31:12', 28000, 50000, 22000),
(25, 'TRX-20250616163536', 1, '2025-06-16 21:35:36', NULL, 40000, 6000),
(26, 'TRX-20250616164009', 1, '2025-06-16 21:40:09', 27000, 30000, 3000),
(27, 'TRX-20250616165833', 1, '2025-06-16 21:58:33', NULL, 46000, 2000),
(28, 'TRX-20250616170059', 1, '2025-06-16 22:00:59', NULL, 46000, 2000),
(29, 'TRX-20250616170112', 1, '2025-06-16 22:01:12', NULL, 46000, 2000),
(30, 'TRX-20250616170446', 1, '2025-06-16 22:04:46', NULL, 30000, 5000),
(31, 'TRX-20250616172627', 1, '2025-06-16 22:26:27', 25000, 30000, 5000),
(32, 'TRX-20250616173030', 1, '2025-06-16 22:30:30', 24000, 30000, 5000),
(33, 'TRX-20250616173423', 1, '2025-06-16 22:34:23', 7000, 9000, 1000),
(34, 'TRX-20250616234825', 1, '2025-06-17 04:48:25', 9000, 20000, 11000),
(35, 'TRX-20250617000329', 1, '2025-06-17 05:03:29', 8000, 9000, 1000),
(36, 'TRX-20250617001712', 1, '2025-06-17 05:17:12', 8000, 9000, 1000),
(37, 'TRX-20250617001747', 1, '2025-06-17 05:17:47', 8000, 9000, 1000);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`);

--
-- Indeks untuk tabel `kasir`
--
ALTER TABLE `kasir`
  ADD PRIMARY KEY (`id_kasir`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indeks untuk tabel `promo`
--
ALTER TABLE `promo`
  ADD PRIMARY KEY (`id_promo`);

--
-- Indeks untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_kasir` (`id_kasir`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `kasir`
--
ALTER TABLE `kasir`
  MODIFY `id_kasir` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id_produk` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `promo`
--
ALTER TABLE `promo`
  MODIFY `id_promo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`);

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_kasir`) REFERENCES `kasir` (`id_kasir`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
