CREATE DATABASE IF NOT EXISTS giic_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE giic_cms;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','viewer') NOT NULL DEFAULT 'viewer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  company VARCHAR(190) NULL,
  message TEXT NULL,
  lead_type ENUM('full','partial') NOT NULL DEFAULT 'full',
  capture_reason VARCHAR(50) NULL,
  source_lang VARCHAR(5) NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cookie_consents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consent ENUM('accepted','rejected') NOT NULL,
  lang VARCHAR(5) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed admin user: email admin@local / password "changeme"
-- Hash generated with PHP password_hash('changeme', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password_hash, role)
VALUES ('Administrator', 'admin@local', '$2y$10$eO2ISWZPAx/KrO8h9PKRQu78j0lxhDzY4orTjW05ewUC.l8rVfonq', 'admin')
ON DUPLICATE KEY UPDATE email = email;
