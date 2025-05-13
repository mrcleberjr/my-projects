CREATE DATABASE apalette CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apalette;

-- Tabela de Usuários aprimorada
CREATE TABLE Users (
    cpf CHAR(11) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    bio TEXT,
    profile_picture_url VARCHAR(255),
    cover_photo_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_email CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT chk_cpf CHECK (cpf REGEXP '^[0-9]{11}$')
);

-- Tabela de Posts aprimorada
CREATE TABLE Posts (
    post_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cpf CHAR(11),
    title VARCHAR(255),
    content TEXT,
    image_url VARCHAR(255),
    status ENUM('rascunho', 'publicado', 'arquivado', 'removido') DEFAULT 'publicado',
    view_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cpf) REFERENCES Users(cpf) ON DELETE CASCADE
);

-- Tabela de Tags
CREATE TABLE Tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de relacionamento Posts-Tags
CREATE TABLE PostTags (
    post_id BIGINT,
    tag_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES Tags(tag_id) ON DELETE CASCADE
);

-- Tabela de Comentários aprimorada
CREATE TABLE Comments (
    comment_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT,
    cpf CHAR(11),
    parent_comment_id BIGINT,
    content TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    status ENUM('ativo', 'removido') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (cpf) REFERENCES Users(cpf) ON DELETE SET NULL,
    FOREIGN KEY (parent_comment_id) REFERENCES Comments(comment_id) ON DELETE CASCADE
);

-- Tabela de Likes aprimorada
CREATE TABLE Reactions (
    reaction_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT,
    comment_id BIGINT,
    cpf CHAR(11),
    type ENUM('like', 'love', 'wow', 'sad', 'angry') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES Comments(comment_id) ON DELETE CASCADE,
    FOREIGN KEY (cpf) REFERENCES Users(cpf) ON DELETE CASCADE,
    UNIQUE KEY unique_user_post_reaction (cpf, post_id, comment_id)
);

-- Tabela de Amizades aprimorada
CREATE TABLE Friendships (
    friendship_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cpf_1 CHAR(11),
    cpf_2 CHAR(11),
    status ENUM('pendente', 'aceito', 'bloqueado', 'recusado') DEFAULT 'pendente',
    action_user_cpf CHAR(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cpf_1) REFERENCES Users(cpf) ON DELETE CASCADE,
    FOREIGN KEY (cpf_2) REFERENCES Users(cpf) ON DELETE CASCADE,
    FOREIGN KEY (action_user_cpf) REFERENCES Users(cpf) ON DELETE SET NULL,
    UNIQUE KEY unique_friendship (cpf_1, cpf_2)
);

-- Tabela de Mensagens aprimorada
CREATE TABLE Messages (
    message_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    sender_cpf CHAR(11),
    receiver_cpf CHAR(11),
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    deleted_by_sender BOOLEAN DEFAULT FALSE,
    deleted_by_receiver BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_cpf) REFERENCES Users(cpf) ON DELETE SET NULL,
    FOREIGN KEY (receiver_cpf) REFERENCES Users(cpf) ON DELETE SET NULL
);

-- Tabela de Notificações aprimorada
CREATE TABLE Notifications (
    notification_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    cpf CHAR(11),
    type ENUM('novo_post', 'novo_comentario', 'nova_mensagem', 'nova_amizade', 'post_destacado', 'mencao') NOT NULL,
    reference_id BIGINT,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cpf) REFERENCES Users(cpf) ON DELETE CASCADE
);

-- Tabela de Hashes de Plágio aprimorada
CREATE TABLE PlagiarismHashes (
    hash_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT,
    content_hash CHAR(64) NOT NULL,
    blockchain_transaction_id VARCHAR(255),
    verification_status ENUM('pendente', 'verificado', 'suspeito') DEFAULT 'pendente',
    last_checked_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES Posts(post_id) ON DELETE CASCADE,
    UNIQUE KEY unique_content_hash (content_hash)
);

-- Índices adicionais para otimização
CREATE INDEX idx_posts_status ON Posts(status);
CREATE INDEX idx_posts_created_at ON Posts(created_at);
CREATE INDEX idx_comments_status ON Comments(status);
CREATE INDEX idx_messages_created_at ON Messages(created_at);
CREATE INDEX idx_notifications_type ON Notifications(type);
CREATE INDEX idx_notifications_created_at ON Notifications(created_at);
CREATE INDEX idx_plagiarism_status ON PlagiarismHashes(verification_status);
