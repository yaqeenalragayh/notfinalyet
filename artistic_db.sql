-- Create Database
CREATE DATABASE IF NOT EXISTS artistic;
USE artistic;

-- Users Table with Admin Roles
CREATE TABLE Users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255),
    bio TEXT,
    role ENUM('admin', 'artist', 'enthusiast', 'both') NOT NULL DEFAULT 'enthusiast',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    is_super_admin BOOLEAN DEFAULT FALSE
);

-- Create Root Admin (Password: Admin@123)
INSERT INTO Users (username, email, password_hash, role, is_super_admin)
VALUES (
    'root_admin',
    'admin@artistic.com',
    '$2a$12$4H5G7fUoO7zQbBk6L8h/N.FuDnVq7N3JZ8Wn1pKlr7Sv9JQyY1XaO',
    'admin',
    TRUE
);

-- Artworks Table
CREATE TABLE Artworks (
    artwork_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    price DECIMAL(10,2),
    creation_date DATE,
    medium VARCHAR(100),
    dimensions VARCHAR(50),
    is_available BOOLEAN DEFAULT TRUE,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_by INT,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (approved_by) REFERENCES Users(user_id)
);

-- Categories Table
CREATE TABLE Categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
);

-- Artwork Categories Junction
CREATE TABLE ArtworkCategories (
    artwork_id INT,
    category_id INT,
    PRIMARY KEY (artwork_id, category_id),
    FOREIGN KEY (artwork_id) REFERENCES Artworks(artwork_id),
    FOREIGN KEY (category_id) REFERENCES Categories(category_id)
);

-- Events Table
CREATE TABLE Events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    image_url VARCHAR(255),
    organizer_id INT,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES Users(user_id),
    FOREIGN KEY (created_by) REFERENCES Users(user_id)
);

-- Courses Table
CREATE TABLE Courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    instructor VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(5,2) DEFAULT 0,
    duration_weeks INT,
    created_by INT NOT NULL,
    thumbnail_url VARCHAR(255),
    FOREIGN KEY (created_by) REFERENCES Users(user_id)
);

-- Challenges Table
CREATE TABLE Challenges (
    challenge_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    rules TEXT NOT NULL,
    prize_details TEXT,
    thumbnail_url VARCHAR(255),
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES Users(user_id)
);

-- Admin System Tables
CREATE TABLE AdminLogs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    target_id INT,
    target_type VARCHAR(50),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE SystemSettings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    last_modified DATETIME DEFAULT CURRENT_TIMESTAMP,
    modified_by INT,
    FOREIGN KEY (modified_by) REFERENCES Users(user_id)
);

CREATE TABLE AdminPermissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    role ENUM('admin', 'super_admin') NOT NULL,
    can_manage_users BOOLEAN DEFAULT FALSE,
    can_manage_content BOOLEAN DEFAULT TRUE,
    can_manage_finances BOOLEAN DEFAULT FALSE,
    can_configure_system BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Permissions
INSERT INTO AdminPermissions (role, can_manage_users, can_manage_content, can_manage_finances, can_configure_system)
VALUES
    ('admin', TRUE, TRUE, FALSE, FALSE),
    ('super_admin', TRUE, TRUE, TRUE, TRUE);

-- User Interaction Tables
CREATE TABLE Favorites (
    favorite_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    artwork_id INT,
    course_id INT,
    event_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (artwork_id) REFERENCES Artworks(artwork_id),
    FOREIGN KEY (course_id) REFERENCES Courses(course_id),
    FOREIGN KEY (event_id) REFERENCES Events(event_id)
);

CREATE TABLE CartItems (
    cart_item_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    artwork_id INT NOT NULL,
    quantity INT DEFAULT 1,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (artwork_id) REFERENCES Artworks(artwork_id)
);

CREATE TABLE Orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES Users(user_id)
);

CREATE TABLE OrderDetails (
    order_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    artwork_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES Orders(order_id),
    FOREIGN KEY (artwork_id) REFERENCES Artworks(artwork_id)
);

-- Security Setup
CREATE USER 'artistic_admin'@'localhost' IDENTIFIED BY 'StrongPassword123!';
GRANT SELECT, INSERT, UPDATE, DELETE ON artistic.* TO 'artistic_admin'@'localhost';
GRANT CREATE, ALTER, DROP ON artistic.* TO 'artistic_admin'@'localhost';
FLUSH PRIVILEGES;