CREATE DATABASE IF NOT EXISTS db_savings;
USE db_savings;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('deposit', 'withdraw') NOT NULL,
    FOREIGN KEY (goal_id) REFERENCES goals(goal_id) ON DELETE CASCADE
);