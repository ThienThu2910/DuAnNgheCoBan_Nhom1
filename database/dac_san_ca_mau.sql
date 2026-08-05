-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2026 at 09:13 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dac_san_ca_mau`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `ho_ten`, `ten_dang_nhap`, `mat_khau`, `trang_thai`, `ngay_tao`) VALUES
(1, 'Quản trị viên', 'admin', '$2y$10$xWbn3zGm08Y5Sg.eW/tdheIj7ZOKYOfG3sR0rATZ4mnLOgdEDby/.', 1, '2026-08-01 11:02:17');

-- --------------------------------------------------------

--
-- Table structure for table `bai_viet`
--

CREATE TABLE `bai_viet` (
  `id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `slug` varchar(280) NOT NULL,
  `tom_tat` text DEFAULT NULL,
  `noi_dung` longtext NOT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `trang_thai` enum('nhap','xuat_ban') NOT NULL DEFAULT 'nhap',
  `ngay_dang` datetime DEFAULT NULL,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bai_viet`
--

INSERT INTO `bai_viet` (`id`, `tieu_de`, `slug`, `tom_tat`, `noi_dung`, `hinh_anh`, `trang_thai`, `ngay_dang`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'Nghề gác kèo ong U Minh', 'nghe-gac-keo-ong-u-minh', 'Nghề gác kèo ong là một nghề truyền thống gắn với rừng tràm U Minh.', 'Nghề gác kèo ong đã tồn tại qua nhiều thế hệ và tạo nên sản phẩm\r\nmật ong rừng U Minh đặc trưng của Cà Mau.', NULL, 'xuat_ban', '2026-08-02 07:17:09', '2026-08-02 12:17:09', '2026-08-02 12:17:09');

-- --------------------------------------------------------

--
-- Table structure for table `co_so_san_xuat`
--

CREATE TABLE `co_so_san_xuat` (
  `id` int(11) NOT NULL,
  `ten_co_so` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `dia_chi` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `vi_do` decimal(10,7) DEFAULT NULL,
  `kinh_do` decimal(10,7) DEFAULT NULL,
  `google_maps_url` text DEFAULT NULL,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `co_so_san_xuat`
--

INSERT INTO `co_so_san_xuat` (`id`, `ten_co_so`, `slug`, `dia_chi`, `so_dien_thoai`, `email`, `mo_ta`, `hinh_anh`, `vi_do`, `kinh_do`, `google_maps_url`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'Cơ sở đặc sản Cà Mau', 'co-so-dac-san-ca-mau', 'Phường Tân Thành, tỉnh Cà Mau', NULL, NULL, NULL, 'co-so-dac-san-ca-mau-14c50bf2.jpg', 9.1765000, 105.1524000, NULL, 1, '2026-08-01 12:14:02', '2026-08-01 12:14:02'),
(2, 'TestLocation', 'testlocation', 'test', NULL, NULL, NULL, NULL, NULL, NULL, 'https://maps.app.goo.gl/C6qM6ESfGCZ1uvfC6', 1, '2026-08-05 14:02:59', '2026-08-05 14:02:59');

-- --------------------------------------------------------

--
-- Table structure for table `dac_san`
--

CREATE TABLE `dac_san` (
  `id` int(11) NOT NULL,
  `danh_muc_id` int(11) DEFAULT NULL,
  `ten_dac_san` varchar(200) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `mo_ta_ngan` text DEFAULT NULL,
  `nguon_goc` text DEFAULT NULL,
  `mo_ta_chi_tiet` longtext DEFAULT NULL,
  `cach_su_dung` text DEFAULT NULL,
  `cach_bao_quan` text DEFAULT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `noi_bat` tinyint(1) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dac_san`
--

INSERT INTO `dac_san` (`id`, `danh_muc_id`, `ten_dac_san`, `slug`, `mo_ta_ngan`, `nguon_goc`, `mo_ta_chi_tiet`, `cach_su_dung`, `cach_bao_quan`, `hinh_anh`, `noi_bat`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 1, 'Cua Cà Mau', 'cua-ca-mau', 'Cua Cà Mau nổi tiếng với thịt chắc, ngọt và thơm.', 'Các vùng rừng ngập mặn và ven biển Cà Mau.', NULL, NULL, NULL, 'cua-ca-mau-d3982a01.jpg', 1, 1, '2026-08-01 11:37:32', '2026-08-01 11:37:32');

-- --------------------------------------------------------

--
-- Table structure for table `dac_san_co_so`
--

CREATE TABLE `dac_san_co_so` (
  `dac_san_id` int(11) NOT NULL,
  `co_so_id` int(11) NOT NULL,
  `ghi_chu` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dac_san_co_so`
--

INSERT INTO `dac_san_co_so` (`dac_san_id`, `co_so_id`, `ghi_chu`) VALUES
(1, 1, NULL),
(1, 2, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `danh_muc`
--

CREATE TABLE `danh_muc` (
  `id` int(11) NOT NULL,
  `ten_danh_muc` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danh_muc`
--

INSERT INTO `danh_muc` (`id`, `ten_danh_muc`, `slug`, `mo_ta`, `thu_tu`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'Thủy hải sản', 'thuy-hai-san', 'Các đặc sản được chế biến từ thủy hải sản Cà Mau', 1, 1, '2026-08-01 10:16:27', '2026-08-01 10:16:27'),
(2, 'Các loại khô', 'cac-loai-kho', 'Các sản phẩm khô đặc trưng của Cà Mau', 2, 1, '2026-08-01 10:16:27', '2026-08-01 10:16:27'),
(3, 'Mắm và món truyền thống', 'mam-va-mon-truyen-thong', 'Các loại mắm và món ăn truyền thống địa phương', 3, 1, '2026-08-01 10:16:27', '2026-08-01 10:16:27'),
(4, 'Sản vật U Minh', 'san-vat-u-minh', 'Các sản vật nổi bật của vùng rừng U Minh', 4, 1, '2026-08-01 10:16:27', '2026-08-01 10:16:27');

-- --------------------------------------------------------

--
-- Table structure for table `hinh_anh_dac_san`
--

CREATE TABLE `hinh_anh_dac_san` (
  `id` int(11) NOT NULL,
  `dac_san_id` int(11) NOT NULL,
  `duong_dan` varchar(255) NOT NULL,
  `mo_ta` varchar(255) DEFAULT NULL,
  `thu_tu` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lien_he`
--

CREATE TABLE `lien_he` (
  `id` int(11) NOT NULL,
  `ho_ten` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `chu_de` varchar(255) DEFAULT NULL,
  `noi_dung` text NOT NULL,
  `trang_thai` enum('moi','da_xem','da_phan_hoi') NOT NULL DEFAULT 'moi',
  `ngay_gui` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1,
  `ngay_tao` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id`, `ho_ten`, `ten_dang_nhap`, `email`, `mat_khau`, `trang_thai`, `ngay_tao`) VALUES
(1, 'daden', 'daden', NULL, '$2y$10$/EYpEGj6QSVRFkFSO/.i0.yljXdZcQ.Pmg8PcUKY7F.XpYPYKgBs2', 1, '2026-08-01 11:23:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`);

--
-- Indexes for table `bai_viet`
--
ALTER TABLE `bai_viet`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `co_so_san_xuat`
--
ALTER TABLE `co_so_san_xuat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `dac_san`
--
ALTER TABLE `dac_san`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_dac_san_danh_muc` (`danh_muc_id`);

--
-- Indexes for table `dac_san_co_so`
--
ALTER TABLE `dac_san_co_so`
  ADD PRIMARY KEY (`dac_san_id`,`co_so_id`),
  ADD KEY `fk_dscs_co_so` (`co_so_id`);

--
-- Indexes for table `danh_muc`
--
ALTER TABLE `danh_muc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `hinh_anh_dac_san`
--
ALTER TABLE `hinh_anh_dac_san`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_hinh_anh_dac_san` (`dac_san_id`);

--
-- Indexes for table `lien_he`
--
ALTER TABLE `lien_he`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bai_viet`
--
ALTER TABLE `bai_viet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `co_so_san_xuat`
--
ALTER TABLE `co_so_san_xuat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `dac_san`
--
ALTER TABLE `dac_san`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `danh_muc`
--
ALTER TABLE `danh_muc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `hinh_anh_dac_san`
--
ALTER TABLE `hinh_anh_dac_san`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lien_he`
--
ALTER TABLE `lien_he`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dac_san`
--
ALTER TABLE `dac_san`
  ADD CONSTRAINT `fk_dac_san_danh_muc` FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_muc` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `dac_san_co_so`
--
ALTER TABLE `dac_san_co_so`
  ADD CONSTRAINT `fk_dscs_co_so` FOREIGN KEY (`co_so_id`) REFERENCES `co_so_san_xuat` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_dscs_dac_san` FOREIGN KEY (`dac_san_id`) REFERENCES `dac_san` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hinh_anh_dac_san`
--
ALTER TABLE `hinh_anh_dac_san`
  ADD CONSTRAINT `fk_hinh_anh_dac_san` FOREIGN KEY (`dac_san_id`) REFERENCES `dac_san` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
