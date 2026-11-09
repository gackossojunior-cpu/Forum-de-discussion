CREATE TABLE IF NOT EXISTS conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('direct', 'group') NOT NULL,
    title VARCHAR(120) NULL,
    created_by INT NOT NULL,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversations_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS conversation_members (
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
    date_joined TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_read_message_id INT NULL,
    last_read_at TIMESTAMP NULL,
    PRIMARY KEY (conversation_id, user_id),
    INDEX idx_conversation_members_user (user_id, conversation_id),
    CONSTRAINT fk_conversation_members_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    body TEXT NOT NULL,
    message_type ENUM('text', 'image', 'file', 'voice', 'system') NOT NULL DEFAULT 'text',
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    edited_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_messages_conversation (conversation_id, id),
    CONSTRAINT fk_messages_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS message_receipts (
    message_id BIGINT NOT NULL,
    user_id INT NOT NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    PRIMARY KEY (message_id, user_id),
    CONSTRAINT fk_message_receipts_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_receipts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS user_presence (
    user_id INT PRIMARY KEY,
    status ENUM('online', 'offline') NOT NULL DEFAULT 'offline',
    last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
