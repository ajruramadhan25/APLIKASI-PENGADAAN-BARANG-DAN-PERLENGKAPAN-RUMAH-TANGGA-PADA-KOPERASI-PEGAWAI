-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2025 at 12:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pos_penjualan`
--

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `id_customer` int(10) UNSIGNED NOT NULL,
  `nama_customer` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telp` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`id_customer`, `nama_customer`, `alamat`, `telp`, `fax`, `email`) VALUES
(6, 'Ajru', 'Jl. Bintaro 2', '082217662664', '222222222', 'ajru@contoh.com'),
(10, 'Eko', 'Jl.lurus', '022-9876543', '14021', 'naditya956@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `identitas`
--

CREATE TABLE `identitas` (
  `id_identitas` tinyint(3) UNSIGNED NOT NULL,
  `nama_identitas` varchar(150) NOT NULL,
  `badan_hukum` varchar(100) DEFAULT NULL,
  `npwp` varchar(32) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telp` varchar(50) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `rekening` varchar(120) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `identitas`
--

INSERT INTO `identitas` (`id_identitas`, `nama_identitas`, `badan_hukum`, `npwp`, `email`, `url`, `alamat`, `telp`, `fax`, `rekening`, `foto`) VALUES
(1, 'Koperasi Pegawai', 'Koperasi Pegawai Negeri', NULL, 'info@koperasipegawai.com', NULL, 'Jl. Raya No. 123, Jakarta', '021-1234567', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `id_item` int(10) UNSIGNED NOT NULL,
  `nama_item` varchar(150) NOT NULL,
  `uom` varchar(30) NOT NULL,
  `harga_beli` decimal(15,2) DEFAULT 0.00,
  `harga_jual` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`id_item`, `nama_item`, `uom`, `harga_beli`, `harga_jual`) VALUES
(1, 'Beras Premium', 'kg', 12000.00, 15000.00),
(2, 'Minyak Goreng', 'liter', 18000.00, 22000.00),
(3, 'Gula Pasir', 'kg', 14000.00, 17000.00),
(4, 'Garam', 'kg', 5000.00, 7000.00),
(5, 'Kecap Manis', 'botol', 8000.00, 12000.00),
(6, 'Bawang Merah', 'kg', 20000.00, 24000.00),
(7, 'Bawang Putih', 'kg', 15000.00, 20000.00),
(11, 'Ikan', 'kg', 15000.00, 20000.00),
(12, 'Ikan', 'kg', 15000.00, 20000.00),
(13, 'Ikan', 'kg', 15000.00, 20000.00),
(14, '123', 'pcs', 2.00, 22.00),
(15, '123', 'pcs', 2.00, 22.00),
(16, '123', 'pcs', 2.00, 22.00);

-- --------------------------------------------------------

--
-- Table structure for table `level`
--

CREATE TABLE `level` (
  `id_level` tinyint(3) UNSIGNED NOT NULL,
  `level` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `level`
--

INSERT INTO `level` (`id_level`, `level`) VALUES
(1, 'Petugas'),
(2, 'Manager'),
(3, 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `manager`
--

CREATE TABLE `manager` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `manager`
--

INSERT INTO `manager` (`id_user`, `nama_user`, `username`, `password`, `level`) VALUES
(3, 'Manager', 'manager', '$2y$10$wZrbQxWb4JWRFl.1iVs.j.C3vMHt4v1rqtZVy0VxYzQ0IeCftVePK', 2);

-- --------------------------------------------------------

--
-- Table structure for table `petugas`
--

CREATE TABLE `petugas` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `nama_user` varchar(100) NOT NULL,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `level` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `petugas`
--

INSERT INTO `petugas` (`id_user`, `nama_user`, `username`, `password`, `level`) VALUES
(5, 'Administrator', 'admin', '$2y$10$RjJV/Kc.8T/pq.5nNGDOYe2/y4ts5Qvz12zfVs1.5aWxpJvK2n6.m', 3),
(6, 'kasir', 'kasir', '$2y$10$2HPOB94OTdk1NNsiIOZmAutlyYUmioG4To0Pk40LInOk8uov5y/Wy', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id_sales` bigint(20) UNSIGNED NOT NULL,
  `tgl_sales` datetime NOT NULL,
  `id_customer` int(10) UNSIGNED DEFAULT NULL,
  `do_number` varchar(50) DEFAULT NULL,
  `status` enum('DRAFT','FINAL','CANCELED') DEFAULT 'DRAFT'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id_sales`, `tgl_sales`, `id_customer`, `do_number`, `status`) VALUES
(1, '2025-09-21 11:11:00', 6, 'DO-20250921-111213', 'FINAL'),
(2, '2025-09-21 14:56:00', 10, 'DO-20250921-145649', 'FINAL');

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `id_transaction` bigint(20) UNSIGNED NOT NULL,
  `id_sales` bigint(20) UNSIGNED NOT NULL,
  `id_item` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `amount` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`id_transaction`, `id_sales`, `id_item`, `quantity`, `price`, `amount`) VALUES
(1, 1, 6, 2.000, 24000.00, 48000.00),
(2, 1, 6, 1.000, 24000.00, 24000.00),
(5, 2, 11, 1.000, 20000.00, 20000.00),
(6, 2, 11, 1.000, 20000.00, 20000.00),
(7, 2, 7, 1.000, 20000.00, 20000.00),
(8, 1, 2, 1.000, 22000.00, 22000.00),
(9, 1, 2, 2.000, 22000.00, 44000.00),
(10, 1, 2, 2.000, 22000.00, 44000.00),
(11, 1, 2, 2.000, 22000.00, 44000.00),
(12, 1, 2, 2.000, 22000.00, 44000.00);

-- --------------------------------------------------------

--
-- Table structure for table `transaction_temp`
--

CREATE TABLE `transaction_temp` (
  `id_transaction` bigint(20) UNSIGNED NOT NULL,
  `id_item` int(10) UNSIGNED NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `session_id` varchar(64) NOT NULL,
  `remark` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`id_customer`);

--
-- Indexes for table `identitas`
--
ALTER TABLE `identitas`
  ADD PRIMARY KEY (`id_identitas`);

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`id_item`);

--
-- Indexes for table `level`
--
ALTER TABLE `level`
  ADD PRIMARY KEY (`id_level`);

--
-- Indexes for table `manager`
--
ALTER TABLE `manager`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `level` (`level`);

--
-- Indexes for table `petugas`
--
ALTER TABLE `petugas`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `level` (`level`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id_sales`),
  ADD KEY `id_customer` (`id_customer`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`id_transaction`),
  ADD KEY `id_sales` (`id_sales`),
  ADD KEY `id_item` (`id_item`);

--
-- Indexes for table `transaction_temp`
--
ALTER TABLE `transaction_temp`
  ADD PRIMARY KEY (`id_transaction`),
  ADD KEY `id_item` (`id_item`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `id_customer` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `identitas`
--
ALTER TABLE `identitas`
  MODIFY `id_identitas` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `item`
--
ALTER TABLE `item`
  MODIFY `id_item` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `level`
--
ALTER TABLE `level`
  MODIFY `id_level` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `manager`
--
ALTER TABLE `manager`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `petugas`
--
ALTER TABLE `petugas`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id_sales` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `id_transaction` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `transaction_temp`
--
ALTER TABLE `transaction_temp`
  MODIFY `id_transaction` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `manager`
--
ALTER TABLE `manager`
  ADD CONSTRAINT `manager_ibfk_1` FOREIGN KEY (`level`) REFERENCES `level` (`id_level`);

--
-- Constraints for table `petugas`
--
ALTER TABLE `petugas`
  ADD CONSTRAINT `petugas_ibfk_1` FOREIGN KEY (`level`) REFERENCES `level` (`id_level`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`id_customer`) REFERENCES `customer` (`id_customer`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`id_sales`) REFERENCES `sales` (`id_sales`),
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`id_item`) REFERENCES `item` (`id_item`);

--
-- Constraints for table `transaction_temp`
--
ALTER TABLE `transaction_temp`
  ADD CONSTRAINT `transaction_temp_ibfk_1` FOREIGN KEY (`id_item`) REFERENCES `item` (`id_item`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
