-- Insertion des données dans la table `users`
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `phone`, `role`, `status`) VALUES
('admin', 'admin@ridegenius.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '0600000000', 'admin', 'active'),
('driver1', 'driver1@ridegenius.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jean', 'Dupont', '0600000001', 'driver', 'active'),
('driver2', 'driver2@ridegenius.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marie', 'Martin', '0600000002', 'driver', 'active'),
('passenger1', 'passenger1@ridegenius.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pierre', 'Durand', '0600000003', 'passenger', 'active'),
('passenger2', 'passenger2@ridegenius.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophie', 'Leroy', '0600000004', 'passenger', 'active');

-- Insertion des données dans la table `villes`
INSERT INTO `villes` (`nom`, `code_postal`) VALUES
('Paris', '75000'),
('Lyon', '69000'),
('Marseille', '13000'),
('Bordeaux', '33000'),
('Toulouse', '31000'),
('Lille', '59000'),
('Nantes', '44000'),
('Strasbourg', '67000'),
('Nice', '06000'),
('Montpellier', '34000');

-- Insertion des données dans la table `rides`
INSERT INTO `rides` (`driver_id`, `departure_city`, `arrival_city`, `departure_date`, `arrival_date`, `available_seats`, `price_per_seat`, `status`) VALUES
(2, 'Paris', 'Lyon', '2024-04-15 08:00:00', '2024-04-15 12:00:00', 3, 25.00, 'active'),
(2, 'Lyon', 'Paris', '2024-04-16 14:00:00', '2024-04-16 18:00:00', 3, 25.00, 'active'),
(3, 'Marseille', 'Nice', '2024-04-15 09:00:00', '2024-04-15 11:30:00', 2, 20.00, 'active'),
(3, 'Bordeaux', 'Toulouse', '2024-04-16 10:00:00', '2024-04-16 12:30:00', 2, 15.00, 'active');

-- Insertion des données dans la table `bookings`
INSERT INTO `bookings` (`ride_id`, `passenger_id`, `seats`, `total_price`, `status`, `driver_id`) VALUES
(1, 4, 1, 25.00, 'accepted', 2),
(1, 5, 1, 25.00, 'accepted', 2),
(3, 4, 1, 20.00, 'accepted', 3);

-- Insertion des données dans la table `booking_transactions`
INSERT INTO `booking_transactions` (`booking_id`, `passenger_id`, `driver_id`, `amount`, `commission_amount`, `status`) VALUES
(1, 4, 2, 25.00, 2.50, 'completed'),
(2, 5, 2, 25.00, 2.50, 'completed'),
(3, 4, 3, 20.00, 2.00, 'completed');

-- Insertion des données dans la table `commissions`
INSERT INTO `commissions` (`booking_id`, `amount`, `rate`, `status`) VALUES
(1, 2.50, 10.00, 'completed'),
(2, 2.50, 10.00, 'completed'),
(3, 2.00, 10.00, 'completed');

-- Insertion des données dans la table `conversations`
INSERT INTO `conversations` (`user1_id`, `user2_id`) VALUES
(2, 4),
(2, 5),
(3, 4);

-- Insertion des données dans la table `messages`
INSERT INTO `messages` (`sender_id`, `receiver_id`, `content`, `type`, `is_read`) VALUES
(2, 4, 'Bonjour, je confirme votre réservation pour le trajet Paris-Lyon', 'text', 1),
(4, 2, 'Merci beaucoup !', 'text', 1),
(2, 5, 'Bonjour, je confirme votre réservation pour le trajet Paris-Lyon', 'text', 1),
(5, 2, 'Parfait, à bientôt !', 'text', 1);

-- Insertion des données dans la table `message_permissions`
INSERT INTO `message_permissions` (`user_id`, `can_send_messages`, `can_receive_messages`, `can_send_attachments`, `can_make_calls`) VALUES
(1, 1, 1, 1, 1),
(2, 1, 1, 1, 1),
(3, 1, 1, 1, 1),
(4, 1, 1, 1, 1),
(5, 1, 1, 1, 1);

-- Insertion des données dans la table `reviews`
INSERT INTO `reviews` (`booking_id`, `reviewer_id`, `reviewed_id`, `rating`, `comment`) VALUES
(1, 4, 2, 5, 'Excellent conducteur, très ponctuel et sympathique'),
(2, 5, 2, 4, 'Bon trajet, voiture confortable'),
(3, 4, 3, 5, 'Super expérience, je recommande');

-- Insertion des données dans la table `subscriptions`
INSERT INTO `subscriptions` (`user_id`, `plan_id`, `status`, `end_date`) VALUES
(1, 1, 'active', '2025-04-15 00:00:00'),
(2, 2, 'active', '2024-10-15 00:00:00'),
(3, 2, 'active', '2024-10-15 00:00:00'),
(4, 3, 'active', '2024-07-15 00:00:00'),
(5, 3, 'active', '2024-07-15 00:00:00');

-- Insertion des données dans la table `wallets`
INSERT INTO `wallets` (`user_id`, `balance`, `currency`) VALUES
(1, 1000.00, 'EUR'),
(2, 500.00, 'EUR'),
(3, 500.00, 'EUR'),
(4, 200.00, 'EUR'),
(5, 200.00, 'EUR');

-- Insertion des données dans la table `wallet_transactions`
INSERT INTO `wallet_transactions` (`wallet_id`, `amount`, `type`, `description`, `status`) VALUES
(1, 1000.00, 'credit', 'Initial deposit', 'completed'),
(2, 500.00, 'credit', 'Initial deposit', 'completed'),
(3, 500.00, 'credit', 'Initial deposit', 'completed'),
(4, 200.00, 'credit', 'Initial deposit', 'completed'),
(5, 200.00, 'credit', 'Initial deposit', 'completed'); 