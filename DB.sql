CREATE DATABASE IF NOT EXISTS cash;
USE cash;

DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=INNODB;

CREATE TABLE cashier (
    cashier_id INT AUTO_INCREMENT PRIMARY KEY,
    cashier_name VARCHAR(255) NOT NULL
) ENGINE=INNODB;

CREATE TABLE valeurs (
    value_cents INT AUTO_INCREMENT PRIMARY KEY,
    value_quantity INT NOT NULL,
    value_name VARCHAR(255) NOT NULL,
    cashier_id INT NOT NULL
) ENGINE=INNODB;

INSERT INTO cashier (cashier_id, cashier_name) VALUES
(1, 'Caisse n°1'),
(2, 'Caisse n°2'),
(3, 'Caisse n°3');

INSERT INTO users (email, password, role) VALUES
('user1@cash.com', '12345', 'user'),
('user2@cash.com', '12345', 'user'),
('admin@cash.com', '123456', 'admin');

INSERT INTO valeurs (value_cents, value_quantity, value_name, cashier_id) VALUES
(50000, 1, 'Billet de 500', 1),
(20000, 1, 'Billet de 200', 1),
(10000, 1, 'Billet de 100', 1),
(5000, 1, 'Billet de 50', 1),
(2000, 1, 'Billet de 20', 1),
(1000, 1, 'Billet de 10', 1),
(500, 1, 'Billet de 5', 1),
(200, 1, 'Pièce de 2', 1),
(100, 1, 'Pièce de 1', 1),
(50, 1, 'Pièce de 0.5', 1),
(20, 1, 'Pièce de 0.2', 1),
(10, 1, 'Pièce de 0.1', 1),
(5, 1, 'Pièce de 0.05', 1),
(2, 1, 'Pièce de 0.02', 1),
(1, 1, 'Pièce de 0.01', 1);
