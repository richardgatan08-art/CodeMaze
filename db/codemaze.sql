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

-- ----------------------------------------------------------
-- Users table  (player accounts – created by User/create.php)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(180)    NOT NULL,
  `password`   VARCHAR(255)    NOT NULL,      -- bcrypt hash
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Daily progress table  (per-user daily play / quest stats)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_progress` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `progress_date`    DATE         NOT NULL,
  `games_played`     INT          NOT NULL DEFAULT 0,
  `quests_attempted` INT          NOT NULL DEFAULT 0,
  `quests_completed` INT          NOT NULL DEFAULT 0,
  `score_gained`     INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_day` (`user_id`,`progress_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------
-- Quest stats table  (per-user frequently missed quests)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `quest_stats` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED NOT NULL,
  `question_key`   VARCHAR(190) NOT NULL,
  `language`       VARCHAR(80)  NOT NULL DEFAULT '',
  `question_text`  TEXT         NULL,
  `code_text`      TEXT         NULL,
  `hint_text`      TEXT         NULL,
  `attempts`       INT          NOT NULL DEFAULT 0,
  `wrong_count`    INT          NOT NULL DEFAULT 0,
  `correct_count`  INT          NOT NULL DEFAULT 0,
  `last_missed_at` DATETIME     NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_question` (`user_id`,`question_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
