-- Database: orange_city_games
-- Create the database first, then run these SQL commands

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'OWNER',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games table
CREATE TABLE IF NOT EXISTS `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT 'Kalyan Matka',
  `game_type` tinyint(1) NOT NULL DEFAULT 1,
  `active` tinyint(1) DEFAULT 0,
  `open_time` timestamp NULL DEFAULT NULL,
  `open_number` varchar(10) DEFAULT NULL,
  `close_time` timestamp NULL DEFAULT NULL,
  `close_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
-- Password is 'admin' (hashed)
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$7K1ojNl6HfLV9wB5vLk8ue8tJc5gG6mKQ7VzJnKq8QyF2VzKjJvG', 'OWNER')
ON DUPLICATE KEY UPDATE `username`=`username`;