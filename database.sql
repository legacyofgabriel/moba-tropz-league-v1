-- MOBA Tropz League Database Schema

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(20) DEFAULT 'admin'
);

CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tournament_code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `organizer` VARCHAR(100),
  `format_type` VARCHAR(50),
  `team_count` INT,
  `status` VARCHAR(20) DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT,
  `name` VARCHAR(100) NOT NULL,
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `players` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT,
  `team_id` INT,
  `name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50),
  `is_captain` TINYINT(1) DEFAULT 0,
  `photo_path` VARCHAR(255),
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT,
  `team1_id` INT,
  `team2_id` INT,
  `team1_score` INT DEFAULT 0,
  `team2_score` INT DEFAULT 0,
  `mvp_player_id` INT,
  `status` VARCHAR(20) DEFAULT 'Scheduled',
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mvp_player_id`) REFERENCES `players`(`id`) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS `player_match_stats` (
  `match_id` INT,
  `player_id` INT,
  `tournament_id` INT,
  `hero_name` VARCHAR(50),
  `kills` INT DEFAULT 0,
  `deaths` INT DEFAULT 0,
  `assists` INT DEFAULT 0,
  `hero_damage` INT DEFAULT 0,
  `tf_participation` DECIMAL(5,2) DEFAULT 0,
  `total_gold` INT DEFAULT 0,
  PRIMARY KEY (`match_id`, `player_id`),
  FOREIGN KEY (`match_id`) REFERENCES `matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`player_id`) REFERENCES `players`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `standings` (
  `tournament_id` INT,
  `team_id` INT,
  `played` INT DEFAULT 0,
  `wins` INT DEFAULT 0,
  `losses` INT DEFAULT 0,
  `points` INT DEFAULT 0,
  PRIMARY KEY (`tournament_id`, `team_id`),
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE
);