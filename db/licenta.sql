-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 12 Iun 2026 la 15:40
-- Versiune server: 10.1.29-MariaDB
-- PHP Version: 7.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `licenta`
--

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'disponibil',
  `vizibil` tinyint(1) NOT NULL DEFAULT '1',
  `pret_vanzare` decimal(10,2) DEFAULT '0.00',
  `pret_inchiriere` decimal(10,2) DEFAULT '0.00',
  `vin` varchar(50) DEFAULT NULL,
  `kilometraj` int(11) DEFAULT '0',
  `an` int(11) DEFAULT '2000',
  `combustibil` varchar(20) DEFAULT NULL,
  `transmisie` varchar(20) DEFAULT NULL,
  `numar_inmatriculare` varchar(20) DEFAULT NULL,
  `motorizare` varchar(50) DEFAULT NULL,
  `detalii` text,
  `poza_principala` varchar(255) CHARACTER SET utf16 NOT NULL COMMENT 'Calea către imaginea principală a mașinii (ex: resurse/imagini/masini/...jpg)',
  `putere` varchar(50) DEFAULT NULL,
  `pret_promo` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Salvarea datelor din tabel `cars`
--

INSERT INTO `cars` (`id`, `marca`, `model`, `status`, `vizibil`, `pret_vanzare`, `pret_inchiriere`, `vin`, `kilometraj`, `an`, `combustibil`, `transmisie`, `numar_inmatriculare`, `motorizare`, `detalii`, `poza_principala`, `putere`, `pret_promo`) VALUES
(1, 'BMW', 'i8', 'disponibil', 1, '80000.00', '150.00', 'WBANC2322 ', 39483, 2015, 'Benzina', 'Automat', 'B 640 BMW', '1.5', 'Istoric inchirieri: 2022-05-12 - client Alex\nContract vanzare: 2021-10-03 - client Y\nAccidente: 2020-08-20 - zgarietura pe portiera\nRevizii/ITP/RCA/CASCO: ITP OK 2023, RCA valabil pana la 2024\n', 'resurse/imagini/masini/car_1/main.png', '362', '80.00'),
(2, 'BMW', 'Seria 5 G30 540i', 'disponibil', 1, '76500.00', '250.00', 'AJ456222', 8654, 2021, 'Benzina', 'Automat', 'IS 29 SSS', '3.0', 'BMW Seria 5 G30 540i este sedanul executiv suprem care combinÄƒ luxul de business cu performanÈ›a purÄƒ. Dotat cu un motor de 3.0 litri pe benzinÄƒ È™i 340 CP, acest model oferÄƒ o dinamicÄƒ impresionantÄƒ È™i confort absolut la rulare, avÃ¢nd un kilometraj aproape nou de doar 8,654 km. Cu o transmisie automatÄƒ precisÄƒ È™i finisaje premium, este maÈ™ina idealÄƒ pentru o experienÈ›Äƒ de Ã®nchiriere selectÄƒ È™i plinÄƒ de rafinament.', 'resurse/imagini/masini/car_2/main.png', '340', '216.00'),
(3, 'Tesla', 'Model S', 'vandut', 1, '100000.00', '0.00', 'NUSH2373', 1232, 2020, 'Electric', 'Automat', 'B 21 TLA', '-', 'ITP expirat', 'resurse/imagini/masini/car_3/main.png', '540', '330.00'),
(13, 'VW', 'Golf', 'disponibil', 1, '16000.00', '50.00', '222', 93844, 2017, 'Diesel', 'Manual', 'B 100 VWG', '2.0 TDI', NULL, 'resurse/imagini/masini/car_13/main.png', '150', NULL),
(14, 'Audi', 'R8', 'disponibil', 1, '180000.00', '200.00', '222', 3234234, 2006, 'Benzina', 'Automat', 'B 22 FUG', '5.2 FSi', 'ceva', 'resurse/imagini/masini/car_14/main.png', '520', NULL),
(17, 'Toyota', 'Supra', 'disponibil', 1, '60000.00', '180.00', 'sdfsdf', 59833, 2020, 'Benzina', 'Automat', 'B 292 RUP', '3.0', 'da', 'resurse/imagini/masini/car_17/main.png', '340', NULL),
(18, 'Renault', 'Arkana', 'disponibil', 1, '30000.00', '80.00', '213213', 14238, 2023, 'Benzina', 'Automat', 'B 787 ALC', '1.3 TCe', NULL, 'resurse/imagini/masini/car_18/main.png', '160', NULL),
(19, 'Hyundai', 'I30', 'disponibil', 1, '13400.00', '30.00', '23233424', 45333, 2018, 'Benzina', 'Manual', 'B 222 HUD', '1.4', NULL, 'resurse/imagini/masini/car_19/main.png', '120', NULL),
(20, 'Porsche', 'Panamera', 'inchiriat', 1, '40000.00', '120.00', '-', 60043, 2016, 'Benzina', 'Automat', 'B 999 AAA', '4.8', 'eee', 'resurse/imagini/masini/car_20/main.jpg', '520', NULL),
(21, 'Ferrari', '488 GTB', 'disponibil', 1, '300000.00', '500.00', '-', 32600, 2019, 'Benzina', 'Automat', 'B 200 FER', '3.9', NULL, 'resurse/imagini/masini/car_21/main.png', '670', NULL),
(22, 'Skoda', 'Octavia', 'inchiriat', 1, '32000.00', '80.00', '-', 1000, 2023, 'Benzina', 'Automat', 'B 430 SKD', '2.0 TSI', NULL, 'resurse/imagini/masini/car_22/main.png', '200', NULL),
(24, 'Honda', 'Civic Type R', 'disponibil', 1, '30000.00', '100.00', '222', 324234, 2020, 'Benzina', 'Manual', 'B 322 HUD', '2.0i', 'eddasasads', 'resurse/imagini/masini/car_24/main.png', '315', NULL),
(28, 'Skoda', 'Fabia', 'disponibil', 1, '12000.00', '20.00', 'WB983487674', 173623, 2018, 'Benzina', 'Automat', 'B 888 SMQ', '1.4', 'OFerta', 'resurse/imagini/masini/car_28/main.png', '150', '15.00'),
(35, 'Audi', 'A8', 'de vanzare', 1, '200000.00', '0.00', '2334', 22323, 2023, 'Diesel', 'Automat', 'B 787 ALC', '4.2 TDi', 'dsa', 'resurse/imagini/masini/car_35/main.jpg', '385', '150000.00'),
(36, 'Toyota', 'Corrola', 'de vanzare', 1, '20000.00', '0.00', '2424432', 26633, 2020, 'Hybrid', 'Automat', 'B 222 TOY', '1.8 ', 'sss', 'resurse/imagini/masini/car_36/main.png', '120', '18000.00');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `contacte`
--

CREATE TABLE `contacte` (
  `id` int(11) NOT NULL,
  `nume` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mesaj` text NOT NULL,
  `data_trimitere` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Salvarea datelor din tabel `contacte`
--

INSERT INTO `contacte` (`id`, `nume`, `email`, `mesaj`, `data_trimitere`) VALUES
(1, 'Alexandru SarÄƒu', 'alex@yahoo.com', 'mesaj test', '2026-02-15 16:00:51');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `contracts`
--

CREATE TABLE `contracts` (
  `id` int(11) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `contract_number` varchar(50) DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `pret_total` decimal(10,2) NOT NULL,
  `depozit` decimal(10,2) DEFAULT '500.00',
  `status` enum('activ','finalizat','reziliat') DEFAULT 'activ',
  `check_in_km` int(11) DEFAULT NULL,
  `check_in_fuel` varchar(50) DEFAULT NULL,
  `check_in_photos` text,
  `check_in_notes` text,
  `check_out_km` int(11) DEFAULT NULL,
  `check_out_fuel` varchar(50) DEFAULT NULL,
  `check_out_damage` text,
  `check_out_cost` decimal(10,2) DEFAULT '0.00',
  `generated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `check_in_docs` varchar(10) DEFAULT 'nu',
  `check_in_keys` varchar(10) DEFAULT 'nu',
  `check_in_kit` varchar(10) DEFAULT 'nu',
  `check_in_cleaning` varchar(50) DEFAULT 'perfect',
  `check_out_docs` varchar(10) DEFAULT 'nu',
  `check_out_keys` varchar(10) DEFAULT 'nu',
  `check_out_kit` varchar(10) DEFAULT 'nu',
  `check_out_cleaning` varchar(50) DEFAULT 'perfect',
  `check_in_date` datetime DEFAULT NULL,
  `check_out_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Salvarea datelor din tabel `contracts`
--

INSERT INTO `contracts` (`id`, `rental_id`, `user_id`, `car_id`, `contract_number`, `start_date`, `end_date`, `pret_total`, `depozit`, `status`, `check_in_km`, `check_in_fuel`, `check_in_photos`, `check_in_notes`, `check_out_km`, `check_out_fuel`, `check_out_damage`, `check_out_cost`, `generated_at`, `check_in_docs`, `check_in_keys`, `check_in_kit`, `check_in_cleaning`, `check_out_docs`, `check_out_keys`, `check_out_kit`, `check_out_cleaning`, `check_in_date`, `check_out_date`) VALUES
(5, 46, 1, 1, 'CON-20260428-00046', '2026-07-15 09:00:00', '2026-07-24 18:00:00', '800.00', '500.00', 'finalizat', 43534534, '3/4', NULL, 'lovita pe usa stanga', 54345345, '1/2', 'sdfggdf', '200.00', '2026-04-28 18:40:18', 'nu', 'nu', 'nu', 'perfect', 'nu', 'nu', 'nu', 'perfect', NULL, NULL),
(6, 47, 1, 28, 'CON-20260428-00047', '2026-04-29 09:00:00', '2026-04-30 18:00:00', '30.00', '500.00', 'activ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '2026-04-28 18:49:19', 'nu', 'nu', 'nu', 'perfect', 'nu', 'nu', 'nu', 'perfect', NULL, NULL),
(14, 48, 1, 18, 'CON-20260531-00048', '2026-06-01 09:00:00', '2026-06-05 18:00:00', '400.00', '500.00', 'finalizat', 23233, '1/2', NULL, 'asdad6', 233422, 'plin', '', '0.00', '2026-05-31 19:05:14', 'da', 'da', 'da', 'dirty_ext', 'da', 'da', 'da', 'perfect', '2026-06-03 18:39:37', '2026-06-05 18:59:33'),
(15, 48, 1, 18, 'CON-20260605-00048', '2026-06-01 09:00:00', '2026-06-05 18:00:00', '400.00', '500.00', 'activ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '2026-06-05 20:18:51', 'nu', 'nu', 'nu', 'perfect', 'nu', 'nu', 'nu', 'perfect', NULL, NULL),
(16, 50, 1, 20, 'CON-20260531-00050', '2026-06-03 09:00:00', '2026-06-06 18:00:00', '480.00', '500.00', 'activ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0.00', '2026-06-01 00:09:32', 'nu', 'nu', 'nu', 'perfect', 'nu', 'nu', 'nu', 'perfect', NULL, NULL),
(17, 51, 1, 24, 'CON-20260531-00051', '2026-06-03 09:00:00', '2026-06-06 18:00:00', '400.00', '500.00', 'finalizat', 234423, '1/2', NULL, '', 324234, 'plin', '', '0.00', '2026-06-01 00:53:54', 'da', 'da', 'nu', 'perfect', 'da', 'da', 'nu', 'perfect', '2026-06-01 00:01:35', '2026-06-05 00:03:03'),
(18, 52, 1, 21, 'CON-20260603-00052', '2026-06-04 09:00:00', '2026-06-07 18:00:00', '2000.00', '500.00', 'finalizat', 32423, '3/4', NULL, '', 32600, '1/2', '', '50.00', '2026-06-03 18:16:10', 'da', 'nu', 'da', 'perfect', 'da', 'nu', 'da', 'perfect', '2026-06-03 17:19:23', '2026-06-04 17:25:13'),
(19, 53, 1, 14, 'CON-20260604-00053', '2026-06-05 09:00:00', '2026-06-07 18:00:00', '600.00', '500.00', 'finalizat', 766767, 'plin', NULL, '', 3234234, 'plin', '', '50.00', '2026-06-04 20:29:31', 'da', 'da', 'da', 'perfect', 'nu', 'nu', 'nu', 'perfect', '2026-06-04 19:30:45', '2026-06-05 19:33:43');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `rentals`
--

CREATE TABLE `rentals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Salvarea datelor din tabel `rentals`
--

INSERT INTO `rentals` (`id`, `user_id`, `car_id`, `start_date`, `end_date`, `price`, `status`) VALUES
(36, 1, 1, '2026-02-22 14:00:00', '2026-02-25 09:00:00', '88.00', 'finalizat'),
(46, 1, 1, '2026-07-15 09:00:00', '2026-07-24 18:00:00', '800.00', 'finalizat'),
(47, 1, 28, '2026-04-29 09:00:00', '2026-04-30 18:00:00', '30.00', 'confirmat'),
(48, 1, 18, '2026-06-01 09:00:00', '2026-06-05 18:00:00', '400.00', 'finalizat'),
(49, 1, 20, '2026-06-03 09:00:00', '2026-06-05 18:00:00', '360.00', 'anulata'),
(50, 1, 20, '2026-06-03 09:00:00', '2026-06-06 18:00:00', '480.00', 'confirmat'),
(51, 1, 24, '2026-06-03 09:00:00', '2026-06-06 18:00:00', '400.00', 'finalizat'),
(52, 1, 21, '2026-06-04 09:00:00', '2026-06-07 18:00:00', '2000.00', 'finalizat'),
(53, 1, 14, '2026-06-05 09:00:00', '2026-06-07 18:00:00', '600.00', 'finalizat'),
(54, 1, 21, '2026-06-11 09:00:00', '2026-06-20 18:00:00', '5000.00', 'pending');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `nume` varchar(100) NOT NULL,
  `telefon` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mesaj` text,
  `sale_date` datetime DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'finalizat'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Salvarea datelor din tabel `sales`
--

INSERT INTO `sales` (`id`, `user_id`, `car_id`, `nume`, `telefon`, `email`, `mesaj`, `sale_date`, `price`, `status`) VALUES
(9, 1, 36, 'alex', '12', 'vigad68707@gyknife.com', '0', '2026-06-04 19:54:13', '18000.00', 'finalizat'),
(12, 1, 35, 'alex', '123123123', 'vigad68707@gyknife.com', '0', '2026-06-05 20:36:46', '150000.00', 'finalizat');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `sales_invoices`
--

CREATE TABLE `sales_invoices` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `contract_number` varchar(50) NOT NULL,
  `pret_total` decimal(10,2) NOT NULL,
  `baza_impozabila` decimal(10,2) NOT NULL,
  `tva` decimal(10,2) NOT NULL,
  `generated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Salvarea datelor din tabel `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `sale_id`, `user_id`, `car_id`, `invoice_number`, `contract_number`, `pret_total`, `baza_impozabila`, `tva`, `generated_at`) VALUES
(2, 9, 1, 36, 'AP-FT-20260604-00009', 'VNZ-20260604-00009', '18000.00', '14876.03', '3123.97', '2026-06-04 19:54:18'),
(4, 12, 1, 35, 'AP-FT-20260605-00012', 'VNZ-20260605-00012', '150000.00', '123966.94', '26033.06', '2026-06-05 20:37:12');

-- --------------------------------------------------------

--
-- Structura de tabel pentru tabelul `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `gender` enum('m','f','o') NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(256) NOT NULL,
  `role` varchar(15) NOT NULL DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Salvarea datelor din tabel `users`
--

INSERT INTO `users` (`id`, `username`, `gender`, `email`, `password`, `role`) VALUES
(1, 'alex', 'm', 'alex@yahoo.com', '$2y$10$5e2Tx/kJWWsk.a5M7x1Uh.SnNbl70tT382loEFVIKGKQOTGVgXcGC', 'administrator'),
(18, 'asdasddsaasd', 'm', 'vigad68707@gyknife.com', '$2y$10$Caw0QsgWafS4L', 'client'),
(19, 'parola222', 'f', 'alexandru.sarau@yahoo.com', '$2y$10$xedfAv8nwRzSkL7AGa/Ubus1XFZI4KSWur.KDl20jsOzjOz3715Xy', 'administrator');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contacte`
--
ALTER TABLE `contacte`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contracts`
--
ALTER TABLE `contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `contract_number` (`contract_number`),
  ADD KEY `rental_id` (`rental_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `car_id` (`car_id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `contacte`
--
ALTER TABLE `contacte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contracts`
--
ALTER TABLE `contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sales_invoices`
--
ALTER TABLE `sales_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restrictii pentru tabele sterse
--

--
-- Restrictii pentru tabele `contracts`
--
ALTER TABLE `contracts`
  ADD CONSTRAINT `contracts_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contracts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `contracts_ibfk_3` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`);

--
-- Restrictii pentru tabele `sales_invoices`
--
ALTER TABLE `sales_invoices`
  ADD CONSTRAINT `sales_invoices_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
