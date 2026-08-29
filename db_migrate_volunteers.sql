-- ReValue Hub - Volunteer Applications Migration
-- Run this file via phpMyAdmin or MySQL CLI

CREATE TABLE IF NOT EXISTS `volunteer_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `skills` TEXT DEFAULT NULL COMMENT 'Comma-separated skill values',
    `availability` VARCHAR(100) DEFAULT 'Flexible',
    `motivation` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

