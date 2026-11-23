-- ============================================================
-- HELPHUB 2.0 PRO - Complete Database Schema (MySQL 8.0+)
-- "Connect. Help. Make a Difference."
-- ============================================================

CREATE DATABASE IF NOT EXISTS helphub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helphub;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS blocked_users;
DROP TABLE IF EXISTS follows;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS likes;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS user_badges;
DROP TABLE IF EXISTS badges;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversation_members;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS help_offers;
DROP TABLE IF EXISTS help_requests;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL DEFAULT 'Bhopal',
    state VARCHAR(100) NOT NULL DEFAULT 'Madhya Pradesh',
    latitude DECIMAL(10, 8) DEFAULT 23.259933,
    longitude DECIMAL(11, 8) DEFAULT 77.412615,
    avatar VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    skills TEXT NULL,
    bio TEXT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    reputation_points INT DEFAULT 20,
    rating DECIMAL(3, 2) DEFAULT 5.00,
    total_reviews INT DEFAULT 0,
    helps_given INT DEFAULT 0,
    helps_received INT DEFAULT 0,
    status ENUM('active', 'suspended', 'blocked') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. CATEGORIES
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(80) NOT NULL UNIQUE,
    icon VARCHAR(40) NOT NULL DEFAULT 'help',
    description VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. HELP REQUESTS
CREATE TABLE help_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    location VARCHAR(150) NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT 23.259933,
    longitude DECIMAL(11, 8) DEFAULT 77.412615,
    required_date DATE NULL,
    urgency ENUM('normal', 'important', 'urgent', 'emergency') DEFAULT 'normal',
    skills_required VARCHAR(255) NULL,
    additional_info TEXT NULL,
    status ENUM('open', 'in_discussion', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
    accepted_helper_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (accepted_helper_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_category (category_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. HELP OFFERS
CREATE TABLE help_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category_id INT NOT NULL,
    skills VARCHAR(255) NOT NULL,
    experience VARCHAR(150) NULL,
    location VARCHAR(150) NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT 23.259933,
    longitude DECIMAL(11, 8) DEFAULT 77.412615,
    availability VARCHAR(100) DEFAULT 'Weekdays & Weekends',
    about_service TEXT NULL,
    status ENUM('active', 'paused', 'closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. CONVERSATIONS
CREATE TABLE conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES help_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. CONVERSATION MEMBERS
CREATE TABLE conversation_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_conv_user (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. MESSAGES
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) NULL,
    read_status TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conv (conversation_id),
    INDEX idx_read (read_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. NOTIFICATIONS
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255) NULL,
    read_status TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_read (user_id, read_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. REVIEWS
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_review_request (request_id, reviewer_id, reviewee_id),
    FOREIGN KEY (request_id) REFERENCES help_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewee_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. BADGES
CREATE TABLE badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL,
    icon VARCHAR(20) NOT NULL,
    min_points INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. USER BADGES
CREATE TABLE user_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_badge (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. POSTS (COMMUNITY FEED)
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255) NULL,
    category VARCHAR(80) DEFAULT 'General',
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. COMMENTS
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. LIKES
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_post_like (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. REPORTS
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT NULL,
    item_type ENUM('user', 'request', 'post', 'message') NOT NULL,
    item_id INT NOT NULL,
    reason VARCHAR(150) NOT NULL,
    details TEXT NULL,
    status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. FOLLOWS
CREATE TABLE follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_follows (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. BLOCKED USERS
CREATE TABLE blocked_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT NOT NULL,
    blocked_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_blocked (blocker_id, blocked_id),
    FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Insert Categories
INSERT INTO categories (id, name, slug, icon, description) VALUES
(1, 'Education & Tutoring', 'education', '📚', 'School, college tutoring, exam preparation, and book sharing.'),
(2, 'Technology & IT', 'technology', '💻', 'Computer repair, software help, programming, and electronics setup.'),
(3, 'Emergency Assistance', 'emergency', '🆘', 'Immediate urgent help, medical support, and local crisis relief.'),
(4, 'Transport & Logistics', 'transport', '🚗', 'Ride shares, patient transit, and heavy goods movement.'),
(5, 'Food & Groceries', 'food', '🍱', 'Ration kits, cooked meal delivery, and emergency groceries.'),
(6, 'Jobs & Career Guidance', 'jobs', '💼', 'Resume reviews, mock interviews, and career counseling.'),
(7, 'Household & Handyman', 'household', '🔧', 'Plumbing, electrical fixing, elderly home maintenance.'),
(8, 'Financial Guidance', 'financial-guidance', '💰', 'Budgeting, government scheme assistance, and legal aid.'),
(9, 'Healthcare & Senior Care', 'healthcare', '🏥', 'Elder care, wheelchair transport, medical guidance, and blood donor connect.'),
(10, 'Other Community Needs', 'other', '🤝', 'Pet care, lost & found, translation, and miscellaneous neighborly help.')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert Badges
INSERT INTO badges (id, name, description, icon, min_points) VALUES
(1, 'New Helper', 'Joined the community and started contributing', '🌱', 0),
(2, 'Trusted Helper', 'Successfully completed 3+ verified helps', '⭐', 50),
(3, 'Community Hero', 'A recognized pillar of local neighborhood support', '🏆', 150),
(4, 'Top Volunteer', 'Outstanding dedication with 10+ completed acts of help', '💎', 300)
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Insert Users (Password: 'admin123' and 'user123' bcrypt hash)
-- Bcrypt for admin123 / user123: $2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS
INSERT INTO users (id, full_name, username, email, phone, password, city, state, latitude, longitude, avatar, skills, bio, role, reputation_points, rating, total_reviews, helps_given, helps_received) VALUES
(1, 'Platform Admin', 'admin', 'admin@helphub.org', '+91 98260 00001', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'Bhopal', 'Madhya Pradesh', 23.259933, 77.412615, 'assets/images/admin-avatar.png', 'Platform Management, Emergency Response, Moderation', 'Head of moderation and community safety at HelpHub.', 'admin', 500, 5.00, 18, 25, 0),
(2, 'Rahul Sharma', 'rahul_tech', 'rahul@example.com', '+91 98260 11112', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'Bhopal', 'Madhya Pradesh', 23.238400, 77.432000, 'assets/images/user1.png', 'Computer Repair, Python, Networking, Laptop Hardware', 'Software engineer passionate about helping students and elders with technology issues.', 'user', 180, 4.90, 14, 15, 2),
(3, 'Priya Nair', 'priya_tutor', 'priya@example.com', '+91 98260 22223', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'Bhopal', 'Madhya Pradesh', 23.215600, 77.408900, 'assets/images/user2.png', 'Mathematics, Physics, Class 10/12 Mentorship, English', 'MSc Mathematics graduate teaching underprivileged school students on weekends.', 'user', 140, 4.85, 11, 12, 1),
(4, 'Aman Verma', 'aman_volunteer', 'aman@example.com', '+91 98260 33334', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'Indore', 'Madhya Pradesh', 22.719600, 75.857700, 'assets/images/user3.png', 'First Aid, Blood Donation Coordination, Emergency Logistics', 'Active Red Cross volunteer ready to assist with emergency patient transit and medicines.', 'user', 220, 5.00, 16, 18, 0),
(5, 'Neha Gupta', 'neha_care', 'neha@example.com', '+91 98260 44445', '$2y$10$e8wUfxj8nK6.Fw3sE7j1IOf5n5FhOaN2b8E1Pz9i.d7c1eK3zD8iS', 'Bhopal', 'Madhya Pradesh', 23.271000, 77.398000, 'assets/images/user4.png', 'Elder Care, Medicine Delivery, Wheelchair Assistance', 'Certified nursing assistant volunteering during off-duty hours for senior citizens.', 'user', 95, 4.75, 8, 7, 3)
ON DUPLICATE KEY UPDATE id=id;

-- Assign Badges
INSERT INTO user_badges (user_id, badge_id) VALUES
(1, 4), (1, 3), (1, 2), (1, 1),
(2, 3), (2, 2), (2, 1),
(3, 2), (3, 1),
(4, 4), (4, 3), (4, 2), (4, 1),
(5, 2), (5, 1)
ON DUPLICATE KEY UPDATE id=id;

-- Insert Help Requests
INSERT INTO help_requests (id, user_id, title, description, category_id, location, latitude, longitude, required_date, urgency, skills_required, status) VALUES
(1, 5, 'Urgent blood pressure medication needed for senior citizen', 'Need someone to pick up prescribed cardiac and BP medicines from Hamidia Pharmacy and drop them at Arera Colony.', 3, 'Arera Colony, Bhopal', 23.218000, 77.427000, CURDATE(), 'emergency', 'Two-wheeler, Medicine Delivery', 'open'),
(2, 3, 'Laptop screen repair for board exam student', 'My brother is preparing for Class 10 board exams and our refurbished HP laptop screen started flickering black. Need hardware diagnostic help.', 2, 'MP Nagar, Bhopal', 23.232000, 77.430000, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'urgent', 'Laptop Hardware, Computer Repair', 'open'),
(3, 2, 'Monthly ration kit needed for stranded construction family', 'A family of 4 laborers near Kolar road is without groceries due to site shutdown. Need wheat flour, pulses, cooking oil.', 5, 'Kolar Road, Bhopal', 23.175000, 77.419000, CURDATE(), 'important', 'Grocery, Ration Delivery', 'in_progress'),
(4, 5, 'Mathematics & Science guidance for Class 9 girl', 'Looking for an elder sibling/tutor who can guide a bright student with quadratic equations and chemistry basics.', 1, 'Shahpura, Bhopal', 23.201000, 77.423000, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'normal', 'Mathematics, Science, Teaching', 'accepted')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Help Offers
INSERT INTO help_offers (id, user_id, title, description, category_id, skills, experience, location, latitude, longitude, availability, about_service) VALUES
(1, 2, 'Free PC/Laptop Troubleshooting & Software Installation', 'I can diagnose hardware faults, install Linux/Windows, recover lost files, and clean malware for students and non-profits.', 2, 'Computer Repair, Software, OS Install', '7 years in tech', 'MP Nagar, Bhopal', 23.238400, 77.432000, 'Saturdays & Sundays 10am-6pm', 'Helping bridge the digital divide for students.'),
(2, 3, 'Free Weekend Math & Physics Tuition for Classes 8-12', 'Experienced tutor offering 2 hours every weekend to students who cannot afford commercial coaching centers.', 1, 'Mathematics, Physics, Exam Prep', '3 years teaching experience', 'Arera Colony, Bhopal', 23.215600, 77.408900, 'Sundays 9am-12pm', 'Dedicated to making quality STEM education accessible.'),
(3, 4, 'Emergency Blood Coordination & Urgent Patient Transport', 'Can mobilize verified donors for O-ve, B+ve and assist in hospital transit across Bhopal and Indore.', 3, 'First Aid, Logistics, Blood Bank Network', '5 years NGO experience', 'Bhopal / Indore', 22.719600, 75.857700, '24/7 on call for genuine emergencies', 'Community volunteer working with local hospitals.')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Community Posts
INSERT INTO posts (id, user_id, title, content, category, likes_count, comments_count) VALUES
(1, 2, 'Successfully set up a refurbished desktop lab for 12 local kids!', 'Huge thanks to everyone who donated their old CPU towers and keyboards last month! Today we finished installing Ubuntu and open educational software. The smile on their faces made every late-night solder worth it!', 'Success Story', 24, 5),
(2, 4, 'Community Blood Drive Reminder: Next Sunday at Red Cross Center', 'Blood banks are running low on A-negative and O-negative units. If you are healthy and eligible, please step forward. Every unit can save up to three lives.', 'Announcement', 19, 3),
(3, 3, '5 Free online resources for Class 10 Board exam preparation', 'Here is a curated list of free question banks, NCERT solutions, and concept cheat-sheets that our students found most helpful. Hope this benefits more learners across the community!', 'Tips & Advice', 31, 8)
ON DUPLICATE KEY UPDATE id=id;

-- Insert Sample Reviews
INSERT INTO reviews (request_id, reviewer_id, reviewee_id, rating, review_text) VALUES
(3, 5, 2, 5, 'Rahul reached out within 30 minutes of our post and personally delivered the ration package. Exceptionally kind and humble human being!'),
(4, 5, 3, 5, 'Priya is an amazing tutor! My niece understood the geometry concepts within an hour. Highly recommend her to everyone!')
ON DUPLICATE KEY UPDATE id=id;

-- Insert Sample Notifications
INSERT INTO notifications (user_id, sender_id, type, title, message, link) VALUES
(2, 5, 'review', 'New 5-Star Review Received', 'Neha Gupta left a 5-star review: "Rahul reached out within 30 minutes..."', 'profile/profile.php?id=2'),
(2, 3, 'request_contact', 'New Message Regarding Laptop Repair', 'Priya Nair sent you a message regarding request #2.', 'messages/index.php'),
(1, 4, 'emergency_alert', 'Urgent Blood Group Request Verified', 'Aman Verma verified urgent medication distribution in Arera Colony.', 'requests/view.php?id=1')
ON DUPLICATE KEY UPDATE id=id;
