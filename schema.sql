-- ===========================================================================
--  Password Manager — database schema
--  Run with:  mysql -u root -p < schema.sql
-- ===========================================================================

CREATE DATABASE IF NOT EXISTS password_manager
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE password_manager;

-- ---------------------------------------------------------------------------
--  users
--  password_hash : bcrypt hash, used only to verify login.
--  encrypted_key : the per-user data KEY, AES-wrapped with the master password.
--  key_salt      : base64 PBKDF2 salt used to derive the wrapping key.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    login         VARCHAR(64)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    encrypted_key TEXT         NOT NULL,
    key_salt      VARCHAR(64)  NOT NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------------
--  password_entries
--  secret_enc : the saved password, AES-256-GCM encrypted with the data KEY.
--  created_at : filled automatically when the row is inserted.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_entries (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    service_name VARCHAR(128) NOT NULL,
    secret_enc   TEXT         NOT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entry_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE = InnoDB;
