SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `toxbot`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE `toxbot`;

CREATE TABLE `blacklist` (
    `user` varchar(50) DEFAULT NULL,
    `guild` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `guilds` (
    `id` varchar(50) DEFAULT NULL,
    `welcome_channel` varchar(50) DEFAULT NULL,
    `bye_channel` varchar(50) DEFAULT NULL,
    `welcome_msg` longtext DEFAULT NULL,
    `bye_msg` longtext DEFAULT NULL,
    `autorole` varchar(50) DEFAULT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
    `id` varchar(50) NOT NULL,
    `language` longtext NOT NULL DEFAULT 'en_us',
    `color` varchar(50) NOT NULL DEFAULT '#7289da'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `blacklist`
    ADD KEY `user` (`user`),
    ADD KEY `guild` (`guild`);

ALTER TABLE `guilds`
    ADD UNIQUE KEY `id` (`id`);

ALTER TABLE `users`
    ADD KEY `id` (`id`);

COMMIT;