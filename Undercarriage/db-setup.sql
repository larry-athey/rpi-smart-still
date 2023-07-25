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
  `distillate_abv` tinyint(4) DEFAULT NULL,
  `distillate_flowing` tinyint(4) DEFAULT NULL
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

CREATE TABLE `programs` (
  `ID` int(11) NOT NULL,
  `program_name` varchar(100) DEFAULT NULL,
  `mode` tinyint(4) DEFAULT NULL,
  `distillate_abv` tinyint(4) DEFAULT NULL,
  `minimum_flow` tinyint(4) DEFAULT NULL,
  `condenser_rate` int(11) DEFAULT NULL,
  `boiler_temp` float DEFAULT NULL,
  `dephleg_temp` float DEFAULT NULL,
  `column_temp` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `programs` ADD PRIMARY KEY (`ID`);

ALTER TABLE `programs` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `settings` (
  `ID` int(11) NOT NULL,
  `boiler_addr` varchar(24) DEFAULT NULL,
  `boiler_temp` float DEFAULT NULL,
  `dephleg_addr` varchar(24) DEFAULT NULL,
  `dephleg_temp` float DEFAULT NULL,
  `column_addr` varchar(24) DEFAULT NULL,
  `column_temp` float DEFAULT NULL,
  `valve1_total` int(11) DEFAULT NULL,
  `valve1_pulse` int(11) DEFAULT NULL,
  `valve1_position` int(11) DEFAULT NULL,
  `valve2_total` int(11) DEFAULT NULL,
  `valve2_pulse` int(11) DEFAULT NULL,
  `valve2_position` int(11) DEFAULT NULL,
  `distillate_temp` float DEFAULT NULL,
  `distillate_abv` tinyint(4) DEFAULT NULL,
  `distillate_flowing` tinyint(4) DEFAULT NULL,
  `speech_enabled` tinyint(4) DEFAULT NULL,
  `active_run` tinyint(4) DEFAULT NULL,
  `active_program` int(11) DEFAULT NULL,
  `paused` tinyint(4) DEFAULT NULL,
  `serial_data` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `settings` ADD PRIMARY KEY (`ID`);

ALTER TABLE `settings` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
