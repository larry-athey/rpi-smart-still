DROP USER IF EXISTS 'rssdbuser'@'localhost';
DROP DATABASE IF EXISTS `rpismartstill`;
CREATE DATABASE `rpismartstill`;
CREATE USER rssdbuser@localhost IDENTIFIED BY 'rssdbpasswd';
USE `rpismartstill`;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES, EXECUTE, CREATE ROUTINE, ALTER ROUTINE, TRIGGER ON `rpismartstill`.* TO 'rssdbuser'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE `input_table` (
  `ID` int(11) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `boiler_temp` float DEFAULT NULL,
  `dephleg_temp` float DEFAULT NULL,
  `column_temp` float DEFAULT NULL,
  `distillate_temp` float DEFAULT NULL,
  `distillate_abv` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `input_table` ADD PRIMARY KEY (`ID`);

ALTER TABLE `input_table` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `output_table` (
  `ID` int(11) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `valve_id` tinyint(4) DEFAULT NULL,
  `direction` tinyint(4) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `executed` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `output_table` ADD PRIMARY KEY (`ID`);

ALTER TABLE `output_table` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `settings` (
  `ID` int(11) NOT NULL,
  `boiler_addr` varchar(24) DEFAULT NULL,
  `dephleg_addr` varchar(24) DEFAULT NULL,
  `column_addr` varchar(24) DEFAULT NULL,
  `valve1_total` int(11) DEFAULT NULL,
  `valve1_pulse` int(11) DEFAULT NULL,
  `valve1_current` int(11) DEFAULT NULL,
  `valve2_total` int(11) DEFAULT NULL,
  `valve2_pulse` int(11) DEFAULT NULL,
  `valve2_current` int(11) DEFAULT NULL,
  `heating_total` int(11) DEFAULT NULL,
  `heating_pulse` int(11) DEFAULT NULL,
  `heating_current` int(11) DEFAULT NULL,
  `serial_data` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `settings` ADD PRIMARY KEY (`ID`);

ALTER TABLE `settings` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
