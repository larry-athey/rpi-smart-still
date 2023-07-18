DROP USER 'rssdbuser'@'localhost';
DROP DATABASE rpismartstill;
CREATE DATABASE rpismartstill;
CREATE USER rssdbuser@localhost IDENTIFIED BY 'rssdbpasswd';
USE rpismartstill;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, LOCK TABLES, EXECUTE, CREATE ROUTINE, ALTER ROUTINE, TRIGGER ON `rpismartstill`.* TO 'rssdbuser'@'localhost';
FLUSH PRIVILEGES;
