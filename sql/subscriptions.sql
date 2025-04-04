-- Table des abonnements
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plan_type` varchar(20) NOT NULL COMMENT 'eco, pro, business',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'active, cancelled, expired',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajouter une colonne pour le type d'abonnement dans la table des utilisateurs
ALTER TABLE `users` ADD COLUMN `subscription_type` varchar(20) DEFAULT NULL COMMENT 'eco, pro, business' AFTER `role`;

-- Supprimer les triggers s'ils existent
DROP TRIGGER IF EXISTS `update_user_subscription_type`;
DROP TRIGGER IF EXISTS `clear_user_subscription_type`;

-- Créer le trigger pour la mise à jour du type d'abonnement
CREATE TRIGGER `update_user_subscription_type` AFTER INSERT ON `subscriptions`
FOR EACH ROW
UPDATE `users` SET `subscription_type` = NEW.plan_type WHERE `id` = NEW.user_id;

-- Créer le trigger pour l'annulation d'abonnement
CREATE TRIGGER `clear_user_subscription_type` AFTER UPDATE ON `subscriptions`
FOR EACH ROW
BEGIN
    IF NEW.status = 'cancelled' AND OLD.status = 'active' THEN
        UPDATE `users` SET `subscription_type` = NULL WHERE `id` = NEW.user_id;
    END IF;
END; 