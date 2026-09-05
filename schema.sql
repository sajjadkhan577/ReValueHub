-- schema.sql – core tables for ReValueHub admin dashboard
CREATE DATABASE IF NOT EXISTS revalue_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE revalue_hub;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user',
    status ENUM('Active','Flagged','Pending') DEFAULT 'Active',
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Items table (listings shared by users)
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    location VARCHAR(200),
    `condition` VARCHAR(50),
    image_url VARCHAR(255),
    donor_id INT NOT NULL,
    status ENUM('pending','approved','rejected','donated') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Requests table (user requests for items)
CREATE TABLE IF NOT EXISTS requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    requester_id INT NOT NULL,
    status ENUM('open','closed','cancelled') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample data (optional)
-- Passwords are 'password123' hashed with PASSWORD_BCRYPT cost=10 (verified correct)
INSERT INTO users (name, email, password, status) VALUES
    ('Alex Monroe', 'alex@example.com', '$2y$10$iidRIBiWR7IiPckTZgtWT.7ECWE5KYECS.rOaENfZ3TqwwXozEZGW', 'Active'),
    ('Kevin Park', 'kevin@example.com', '$2y$10$iidRIBiWR7IiPckTZgtWT.7ECWE5KYECS.rOaENfZ3TqwwXozEZGW', 'Flagged'),
    ('Sarah Chen', 'sarah@example.com', '$2y$10$iidRIBiWR7IiPckTZgtWT.7ECWE5KYECS.rOaENfZ3TqwwXozEZGW', 'Active'),
    ('James Wilson', 'james@example.com', '$2y$10$iidRIBiWR7IiPckTZgtWT.7ECWE5KYECS.rOaENfZ3TqwwXozEZGW', 'Active');

-- Demo admin account (password: admin123 — verified correct bcrypt hash)
INSERT INTO users (name, email, password, role, status) VALUES
    ('Admin', 'admin@revalue.com', '$2y$10$Plv7wapx4diNcV2aeqz2COq..yrBR4j/Hjl55/89UWoqbf2KZpEpa', 'admin', 'Active');

INSERT INTO items (title, category, donor_id) VALUES
    ('Minimalist Steel Watch','Electronics',1),
    ('Premium Wool Blend Coat','Clothes',3);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Messages table (direct peer-to-peer chat)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    item_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    message_type VARCHAR(20) NOT NULL DEFAULT 'text',
    attachment_url VARCHAR(255) DEFAULT NULL,
    attachment_name VARCHAR(255) DEFAULT NULL,
    attachment_mime VARCHAR(100) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    delivered_at DATETIME DEFAULT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Online and typing state for private chat polling
CREATE TABLE IF NOT EXISTS user_presence (
    user_id INT PRIMARY KEY,
    last_seen DATETIME NOT NULL,
    typing_to INT DEFAULT NULL,
    typing_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Volunteer applications table (submitted via become_a_volunteer.html)
CREATE TABLE IF NOT EXISTS volunteer_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    skills TEXT,
    availability VARCHAR(100) DEFAULT 'Flexible',
    motivation TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Donations table (records completed hand-offs so they can be removed from the live feed)
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    request_id INT DEFAULT NULL,
    donor_id INT NOT NULL,
    recipient_id INT NOT NULL,
    item_title VARCHAR(200) NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL,
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

