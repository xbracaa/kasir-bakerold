-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 15 Jun 2025 pada 15.59
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
  `qty` int(11) DEFAULT NULL,
  `harga_satuan` int(11) DEFAULT NULL,
  `subtotal` int(11) DEFAULT NULL,
  `harga_setelah_diskon` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_transaksi`
--

INSERT INTO `detail_transaksi` (`id_detail`, `id_transaksi`, `id_produk`, `qty`, `harga_satuan`, `subtotal`, `harga_setelah_diskon`) VALUES
(5, 1, 7, 4, 8000, 32000, 32000),
(6, 2, 3, 1, 300000, 300000, 300000),
(7, 2, 4, 2, 7000, 14000, 14000),
(8, 3, 7, 1, 8000, 8000, 8000),
(9, 3, 11, 1, 7000, 7000, 7000),
(10, 3, 2, 1, 80000, 80000, 80000),
(11, 4, 4, 3, 7000, 21000, 21000),
(12, 5, 5, 3, 7000, 21000, 21000),
(13, 9, 8, 0, 20000, 0, 20000);

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
(2, 'Bonus Es Krim Pagi', 'bonus', 'Beli 3 roti dapat es krim', '2025-06-15', '2025-06-30', '07:00:00', '10:00:00', 'Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu', 3, NULL, 11, NULL);

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
(9, 'TRX-20250615152528', 1, '2025-06-15 20:25:28', 20000, 20000, 20000);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_transaksi` (`id_transaksi`),
  ADD KEY `id_produk` (`id_produk`);

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
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
  MODIFY `id_promo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_transaksi`
--
ALTER TABLE `detail_transaksi`
  ADD CONSTRAINT `detail_transaksi_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksi` (`id_transaksi`),
  ADD CONSTRAINT `detail_transaksi_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`);

--
-- Ketidakleluasaan untuk tabel `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_kasir`) REFERENCES `kasir` (`id_kasir`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
