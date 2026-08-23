-- phpMyAdmin SQL Dump
-- version 5.0.4deb2+deb11u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 27, 2024 at 08:18 AM
-- Server version: 10.5.26-MariaDB-0+deb11u2
-- PHP Version: 7.4.33

USE `rpismartstill`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rpismartstill`
--

-- --------------------------------------------------------

--
-- Table structure for table `boilermaker`
--

CREATE TABLE `logic_tuning` (
  `ID` int(11) NOT NULL,
  `boiler_update` int(11) NOT NULL DEFAULT 900,
  `dephleg_update` int(11) NOT NULL DEFAULT 120,
  `dephleg_driver` tinyint(4) NOT NULL DEFAULT 0,
  `dephleg_microstep` float NOT NULL DEFAULT 0.1,
  `dephleg_largestep` float NOT NULL DEFAULT 0.25,
  `driver_microstep` tinyint(4) NOT NULL DEFAULT 1,
  `driver_largestep` tinyint(4) NOT NULL DEFAULT 3,
  `column_update` int(11) NOT NULL DEFAULT 300,
  `distillate_timer` int(11) NOT NULL DEFAULT 300,
  `distillate_temp` float NOT NULL DEFAULT 26.6,
  `abv_update` int(11) NOT NULL DEFAULT 300,
  `flow_update` int(11) NOT NULL DEFAULT 300
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logic_tuning`
--

INSERT INTO `logic_tuning` (`ID`, `boiler_update`, `dephleg_update`, `dephleg_driver`, `dephleg_microstep`, `dephleg_largestep`, `driver_microstep`, `driver_largestep`, `column_update`, `distillate_timer`, `distillate_temp`, `abv_update`, `flow_update`) VALUES
(1, 900, 120, 1, 0.1, 0.25, 1, 3, 300, 300, 26.6, 300, 300);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `logic_tuning`
--
ALTER TABLE `logic_tuning`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `logic_tuning`
--
ALTER TABLE `logic_tuning`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
