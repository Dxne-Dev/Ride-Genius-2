-- Création de la base de données
CREATE DATABASE IF NOT EXISTS ride_genius;
USE ride_genius;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('passager', 'conducteur', 'admin') NOT NULL DEFAULT 'passager',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des trajets
CREATE TABLE rides (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    departure VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    available_seats INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des réservations
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ride_id INT NOT NULL,
    passenger_id INT NOT NULL,
    seats INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES rides(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des évaluations/avis
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    author_id INT NOT NULL,
    recipient_id INT NOT NULL,
    rating INT NOT NULL CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insérer un admin par défaut (mot de passe: admin123)
INSERT INTO users (first_name, last_name, email, password, role) 
VALUES ('Admin', 'Système', 'admin@ridegenius.com', '$2y$10$8W4tLTeBKFygVAKMGV.G7.aG7FKpJ3eFMn0OIz5MGbZVvwqQkZ5Ne', 'admin');