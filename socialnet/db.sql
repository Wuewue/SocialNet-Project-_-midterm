-- ============================================================
-- SocialNet Database Setup
-- File: db.sql
-- Description: Creates the socialnet database and account table
-- ============================================================

-- Create and select the database
CREATE DATABASE IF NOT EXISTS socialnet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE socialnet;

-- Drop table if it exists (for clean reinstall)
DROP TABLE IF EXISTS `account`;

-- Create the account table
CREATE TABLE `account` (
    `Id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `username`    VARCHAR(50)  NOT NULL,
    `fullname`    VARCHAR(100) NOT NULL,
    `password`    VARCHAR(255) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    PRIMARY KEY (`Id`),
    UNIQUE KEY `unique_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Optional: Seed a default admin account for testing
-- Password: admin123 (hashed with PASSWORD_BCRYPT)
-- ============================================================
-- INSERT INTO `account` (`username`, `fullname`, `password`, `description`) VALUES
-- ('admin', 'Administrator', '$2y$12$...', 'System administrator account.');
