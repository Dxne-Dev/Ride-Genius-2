-- Supprimer la colonne read_at de la table messages
ALTER TABLE messages DROP COLUMN IF EXISTS read_at;

-- S'assurer que la colonne is_read existe et a la bonne configuration
ALTER TABLE messages MODIFY COLUMN is_read TINYINT(1) DEFAULT 0 NOT NULL; 