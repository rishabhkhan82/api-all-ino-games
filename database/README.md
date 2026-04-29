# Database Setup Guide

## Option 1: Using phpMyAdmin (Recommended for beginners)

### Step 1: Create the Database
1. Open phpMyAdmin in your browser (usually `http://localhost/phpmyadmin`)
2. Click on "Databases" tab
3. In the "Create database" field, enter: `orange_city_games`
4. Select "utf8mb4_unicode_ci" from the collation dropdown
5. Click "Create"

### Step 2: Import the Schema
1. Select the `orange_city_games` database from the left sidebar
2. Click on "Import" tab
3. Click "Choose File" and select `database/schema.sql`
4. Click "Go" to import the tables

## Option 2: Using the Setup Script

### Step 1: Run the Setup Script
```bash
cd php-backend
php database/setup.php
```

Or access it via web browser:
```
http://localhost/php-backend/database/setup.php
```

## Option 3: Manual SQL Execution

Copy and paste these SQL commands in phpMyAdmin SQL tab:

```sql
-- Create database
CREATE DATABASE IF NOT EXISTS `orange_city_games` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `orange_city_games`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
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
  `active` tinyint(1) DEFAULT 0,
  `open_time` timestamp NULL DEFAULT NULL,
  `open_number` varchar(10) DEFAULT NULL,
  `close_time` timestamp NULL DEFAULT NULL,
  `close_number` varchar(10) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$7K1ojNl6HfLV9wB5vLk8ue8tJc5gG6mKQ7VzJnKq8QyF2VzKjJvG', 'OWNER');
```

## Default Login Credentials

- **Username:** `admin`
- **Password:** `admin`

## Verification

After setup, you should see:
- `orange_city_games` database
- `users` table with 1 record (admin user)
- `games` table (empty initially)

## Troubleshooting

1. **Connection Error**: Check your MySQL credentials in `config.php`
2. **Permission Error**: Make sure your MySQL user has CREATE DATABASE permissions
3. **Port Issue**: Verify MySQL is running on port 3307 (or update config.php)

## Database Configuration

Current settings in `config.php`:
- Host: localhost
- Port: 3307
- Database: orange_city_games
- Username: root
- Password: (empty)