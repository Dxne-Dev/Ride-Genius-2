-- Add type column to messages table
ALTER TABLE messages
ADD COLUMN type VARCHAR(10) DEFAULT 'text' NOT NULL,
ADD COLUMN is_read TINYINT(1) DEFAULT 0 NOT NULL,
MODIFY COLUMN content TEXT NOT NULL;

-- Create message_reactions table
CREATE TABLE IF NOT EXISTS message_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (message_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create uploads directory if it doesn't exist
-- Note: This needs to be executed from PHP, not SQL
-- mkdir -p uploads/messages
-- chmod 777 uploads/messages 