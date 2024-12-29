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

CREATE TABLE IF NOT EXISTS `boilermaker` (
  `ID` int(11) NOT NULL,
  `enabled` tinyint(4) DEFAULT NULL,
  `ip_address` varchar(15) DEFAULT NULL,
  `online` tinyint(4) DEFAULT NULL,
  `fixed_temp` tinyint(4) DEFAULT NULL,
  `time_spread` tinyint(4) DEFAULT NULL,
  `target_temp` float DEFAULT NULL,
  `inc_temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `boilermaker`
--

INSERT INTO `boilermaker` (`ID`, `enabled`, `ip_address`, `online`, `fixed_temp`, `time_spread`, `target_temp`, `inc_temp`) VALUES
(1, 0, '0.0.0.0', 0, 0, 3, 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `boilermaker`
--
ALTER TABLE `boilermaker`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `boilermaker`
--
ALTER TABLE `boilermaker`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
