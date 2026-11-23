-- HelpHub 2.0 Database Schema
CREATE DATABASE IF NOT EXISTS helphub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helphub;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Help Requests Table
CREATE TABLE IF NOT EXISTS help_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    category VARCHAR(50) NOT NULL,
    location VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    target_amount DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('pending', 'verified', 'rejected', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Volunteers Table
CREATE TABLE IF NOT EXISTS volunteers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    area VARCHAR(120),
    skills VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Donations Table
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_reference VARCHAR(120) UNIQUE NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'demo_pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Seed Data
-- Note: Password for admin is 'admin123'
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Platform Admin', 'admin@helphub.org', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'admin'),
(2, 'Rajesh Sharma', 'rajesh@example.com', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'user')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO help_requests (id, user_id, title, category, location, description, target_amount, status) VALUES
(1, 2, 'Urgent medicines needed for senior citizen', 'Medical', 'Bhopal', 'Blood pressure and cardiac medications needed for an elderly pensioner.', 1800.00, 'verified'),
(2, 2, 'School books & stationery for Class 10 student', 'Education', 'Indore', 'Science, Mathematics textbooks and notebooks needed for board exams.', 1200.00, 'verified'),
(3, 2, 'Monthly ration kit for stranded family', 'Food', 'Bhopal', 'Basic wheat flour, rice, pulses, cooking oil required for a family of four.', 2500.00, 'pending')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO volunteers (name, email, area, skills, status) VALUES
('Priya Nair', 'priya@example.com', 'Bhopal', 'Teaching, student mentorship', 'approved'),
('Vikram Singh', 'vikram@example.com', 'Indore', 'Logistics, delivery, emergency aid', 'approved')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO donations (user_id, amount, payment_reference, status) VALUES
(2, 1000.00, 'DEMO-8A39F1', 'paid'),
(NULL, 2500.00, 'DEMO-4B91D2', 'paid')
ON DUPLICATE KEY UPDATE id=id;
