DROP USER IF EXISTS 'rssdbuser'@'localhost';
DROP DATABASE IF EXISTS `rpismartstill`;
CREATE DATABASE `rpismartstill`;
CREATE USER rssdbuser@localhost IDENTIFIED BY 'rssdbpasswd';
USE `rpismartstill`;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES, EXECUTE, CREATE ROUTINE, ALTER ROUTINE, TRIGGER ON `rpismartstill`.* TO 'rssdbuser'@'localhost';
FLUSH PRIVILEGES;

CREATE TABLE `heating_translation` (
  `ID` int(11) NOT NULL,
  `percent` tinyint(4) DEFAULT NULL,
  `position` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `heating_translation` (`ID`, `percent`, `position`) VALUES
(1, 90, 134),
(2, 80, 130),
(3, 70, 127),
(4, 60, 124),
(5, 50, 120),
(6, 40, 117),
(7, 30, 114),
(8, 20, 111),
(9, 10, 108);

ALTER TABLE `heating_translation` ADD PRIMARY KEY (`ID`);

ALTER TABLE `heating_translation` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

CREATE TABLE `input_table` (
  `ID` int(11) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `boiler_temp` float DEFAULT NULL,
  `dephleg_temp` float DEFAULT NULL,
  `column_temp` float DEFAULT NULL,
  `distillate_temp` float DEFAULT NULL,
  `distillate_abv` int(11) DEFAULT NULL,
  `distillate_flow` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `input_table` ADD PRIMARY KEY (`ID`);

ALTER TABLE `input_table` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `logic_tracker` (
  `ID` int(11) NOT NULL,
  `run_start` tinyint(4) DEFAULT NULL,
  `boiler_timer` timestamp NULL DEFAULT NULL,
  `boiler_done` tinyint(4) DEFAULT NULL,
  `boiler_last_adjustment` timestamp NULL DEFAULT NULL,
  `boiler_note` varchar(255) DEFAULT NULL,
  `dephleg_timer` timestamp NULL DEFAULT NULL,
  `dephleg_done` tinyint(4) DEFAULT NULL,
  `dephleg_last_adjustment` timestamp NULL DEFAULT NULL,
  `dephleg_note` varchar(255) DEFAULT NULL,
  `column_timer` timestamp NULL DEFAULT NULL,
  `column_done` tinyint(4) DEFAULT NULL,
  `column_last_adjustment` timestamp NULL DEFAULT NULL,
  `column_note` varchar(255) DEFAULT NULL,
  `flow_last_check` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `logic_tracker` ADD PRIMARY KEY (`ID`);

ALTER TABLE `logic_tracker` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `output_table` (
  `ID` int(11) NOT NULL,
  `timestamp` timestamp NULL DEFAULT NULL,
  `auto_manual` tinyint(4) DEFAULT NULL,
  `valve_id` tinyint(4) DEFAULT NULL,
  `direction` tinyint(4) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `muted` tinyint(4) DEFAULT NULL,
  `executed` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `output_table` ADD PRIMARY KEY (`ID`);

ALTER TABLE `output_table` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `programs` (
  `ID` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL DEFAULT 'New Program',
  `mode` tinyint(4) NOT NULL DEFAULT 0,
  `distillate_abv` int(11) NOT NULL DEFAULT 0,
  `abv_managed` tinyint(4) NOT NULL DEFAULT 0,
  `minimum_flow` tinyint(4) NOT NULL DEFAULT 0,
  `flow_managed` tinyint(4) NOT NULL DEFAULT 0,
  `dephleg_start` float NOT NULL DEFAULT 0,
  `condenser_rate` float NOT NULL DEFAULT 0,
  `boiler_managed` tinyint(4) NOT NULL DEFAULT 0,
  `boiler_temp_low` float NOT NULL DEFAULT 0,
  `boiler_temp_high` float NOT NULL DEFAULT 0,
  `dephleg_managed` tinyint(4) NOT NULL DEFAULT 0,
  `dephleg_temp_low` float NOT NULL DEFAULT 0,
  `dephleg_temp_high` float NOT NULL DEFAULT 0,
  `column_managed` tinyint(4) NOT NULL DEFAULT 0,
  `column_temp_low` float NOT NULL DEFAULT 0,
  `column_temp_high` float NOT NULL DEFAULT 0,
  `heating_idle` int(11) NOT NULL DEFAULT 0,
  `notes` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `programs` (`ID`, `program_name`, `mode`, `distillate_abv`, `abv_managed`, `minimum_flow`, `flow_managed`, `dephleg_start`, `condenser_rate`, `boiler_managed`, `boiler_temp_low`, `boiler_temp_high`, `dephleg_managed`, `dephleg_temp_low`, `dephleg_temp_high`, `column_managed`, `column_temp_low`, `column_temp_high`, `heating_idle`, `notes`) VALUES
(1, 'Maximum Reflux', 1, 180, 0, 50, 0, 39, 35, 1, 82.2, 87.8, 1, 62, 67, 0, 0, 0, 126, 'This is an example program for my Still Spirits boiler and T-500 column, using the exact parts shown in the diagrams provided on GitHub. Unless you are using the 100% exact setup, this program likely will not work for you. Always create your own programs from scratch since there is no way to create a program that is guaranteed to work with every still under the sun.'),
(2, 'Pot Still, 70 Proof Cutoff', 0, 70, 1, 50, 0, 0, 38, 1, 82.2, 90.6, 0, 0, 0, 1, 85, 88, 120, 'This is an example program for my Still Spirits boiler and T-500 column, using the exact parts shown in the diagrams provided on GitHub. Unless you are using the 100% exact setup, this program likely will not work for you. Always create your own programs from scratch since there is no way to create a program that is guaranteed to work with every still under the sun.');

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
  `heating_analog` tinyint(4) DEFAULT NULL,
  `heating_total` int(11) DEFAULT NULL,
  `heating_position` int(11) DEFAULT NULL,
  `distillate_temp` float DEFAULT NULL,
  `distillate_abv` int(11) DEFAULT NULL,
  `distillate_flow` varchar(255) DEFAULT NULL,
  `speech_enabled` tinyint(4) DEFAULT NULL,
  `active_run` tinyint(4) DEFAULT NULL,
  `active_program` int(11) DEFAULT NULL,
  `paused` tinyint(4) DEFAULT NULL,
  `pause_return` int(11) DEFAULT NULL,
  `saved_upper` int(11) DEFAULT NULL,
  `saved_lower` int(11) DEFAULT NULL,
  `run_start` timestamp NULL DEFAULT NULL,
  `run_end` timestamp NULL DEFAULT NULL,
  `serial_data` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`ID`, `boiler_addr`, `boiler_temp`, `dephleg_addr`, `dephleg_temp`, `column_addr`, `column_temp`, `valve1_total`, `valve1_pulse`, `valve1_position`, `valve2_total`, `valve2_pulse`, `valve2_position`, `heating_enabled`, `heating_polarity`, `heating_analog`, `heating_total`, `heating_position`, `distillate_temp`, `distillate_abv`, `distillate_flow`, `speech_enabled`, `active_run`, `active_program`, `paused`, `pause_return`, `saved_upper`, `saved_lower`, `run_start`, `run_end`, `serial_data`) VALUES
(1, '28-3ce104578ab4', 89.8, '28-3c1cf6499389', 87.8, '28-3ce104572467', 81.8, 10258, 102, 3876, 10413, 104, 4462, 1, 1, 1, 140, 127, -127, 85, '0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0|0', 1, 1, 1, 0, 0, NULL, NULL, '2023-08-11 01:12:46', NULL, 'Uptime: 00:38:18\nWeight: 64.03 64.04\nFlow: 0\nEthanol: 85\nTempC: -127.0');

ALTER TABLE `settings` ADD PRIMARY KEY (`ID`);

ALTER TABLE `settings` MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;
