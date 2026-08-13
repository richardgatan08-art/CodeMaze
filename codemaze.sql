-- ============================================================
--  CodeMaze Database Schema
--  Import via phpMyAdmin or:  mysql -u root < codemaze.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS `codemaze`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `codemaze`;

-- ----------------------------------------------------------
-- Admins table  (session-based login)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(180)    NOT NULL,
  `password`   VARCHAR(255)    NOT NULL,      -- bcrypt hash
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Slides table  (demo slider on website.php)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `slides` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(255)             DEFAULT NULL,
  `description` TEXT                     DEFAULT NULL,
  `image`       VARCHAR(255)    NOT NULL,   -- filename stored in /uploads/
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Blog entries table  (progress blog on website.php)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_entries` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `week`       SMALLINT UNSIGNED NOT NULL,
  `text`       TEXT            NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_week` (`week`)       -- one entry per week
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
