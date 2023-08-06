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
  `distillate_abv` int(11) DEFAULT NULL,
  `distillate_flowing` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `input_table` ADD PRIMARY KEY (`ID`);

ALTER TABLE `input_table` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `output_table` (
  `ID` int(11) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `auto_manual` tinyint(4) DEFAULT NULL,
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
  `distillate_abv` int(11) DEFAULT NULL,
  `minimum_flow` int(11) DEFAULT NULL,
  `condenser_rate` int(11) DEFAULT NULL,
  `boiler_managed` tinyint(4) DEFAULT NULL,
  `boiler_temp_low` float DEFAULT NULL,
  `boiler_temp_high` float DEFAULT NULL,
  `dephleg_managed` tinyint(4) DEFAULT NULL,
  `dephleg_temp_low` float DEFAULT NULL,
  `dephleg_temp_high` float DEFAULT NULL,
  `column_managed` tinyint(4) DEFAULT NULL,
  `column_temp_low` float DEFAULT NULL,
  `column_temp_high` float DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `programs` (`ID`, `program_name`, `mode`, `distillate_abv`, `minimum_flow`, `condenser_rate`, `boiler_managed`, `boiler_temp_low`, `boiler_temp_high`, `dephleg_managed`, `dephleg_temp_low`, `dephleg_temp_high`, `column_managed`, `column_temp_low`, `column_temp_high`, `notes`) VALUES
(1, 'Maximum Reflux', 1, 180, 30, 50, 1, 82.2, 87.8, 1, 55, 60, 0, 82.2, 87.8, NULL);

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
  `heating_enabled` tinyint(11) DEFAULT NULL,
  `heating_polarity` tinyint(4) DEFAULT NULL,
  `heating_total` int(11) DEFAULT NULL,
  `heating_position` int(11) DEFAULT NULL,
  `distillate_temp` float DEFAULT NULL,
  `distillate_abv` int(11) DEFAULT NULL,
  `distillate_flowing` tinyint(4) DEFAULT NULL,
  `speech_enabled` tinyint(4) DEFAULT NULL,
  `active_run` tinyint(4) DEFAULT NULL,
  `active_program` int(11) DEFAULT NULL,
  `paused` tinyint(4) DEFAULT NULL,
  `run_start` timestamp NULL DEFAULT NULL,
  `run_end` timestamp NULL DEFAULT NULL,
  `serial_data` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`ID`, `boiler_addr`, `boiler_temp`, `dephleg_addr`, `dephleg_temp`, `column_addr`, `column_temp`, `valve1_total`, `valve1_pulse`, `valve1_position`, `valve2_total`, `valve2_pulse`, `valve2_position`, `heating_enabled`, `heating_polarity`, `heating_total`, `heating_position`, `distillate_temp`, `distillate_abv`, `distillate_flowing`, `speech_enabled`, `active_run`, `active_program`, `paused`, `run_start`, `run_end`, `serial_data`) VALUES
(1, '28-3c12f649f6eb', 26.9, '28-3c1cf6499389', 21.8, '28-3c02f649bef7', 21.8, 10000, 100, 0, 10000, 100, 0, 1, 1, 140, 0, 21.8, 86, 0, 1, 0, 1, 0, '2023-08-06 15:47:40', '2023-08-06 19:20:15', 'Uptime: 35:25:33\nWeight: 63.76 63.79\nFlow: 0\nEthanol: 86\nTempC: 21.8');

ALTER TABLE `settings` ADD PRIMARY KEY (`ID`);

ALTER TABLE `settings` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
