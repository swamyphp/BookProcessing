CREATE DATABASE IF NOT EXISTS bookprocessing DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bookprocessing;

-- Users and authentication
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor','uploader','viewer') DEFAULT 'uploader',
  email VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL,
  active TINYINT(1) DEFAULT 1
);

-- Books
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  short_name VARCHAR(100) UNIQUE NOT NULL,
  title VARCHAR(255),
  source_folder VARCHAR(512),
  total_chapters INT DEFAULT 0,
  total_files INT DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'created',
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Chapters
CREATE TABLE IF NOT EXISTS chapters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  chapter_number INT,
  chapter_title VARCHAR(255),
  filename VARCHAR(512),
  status VARCHAR(50) DEFAULT 'created',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Upload history (ZIP uploads and file uploads during interactive session)
CREATE TABLE IF NOT EXISTS upload_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  original_filename VARCHAR(512),
  storage_path VARCHAR(1024),
  checksum VARCHAR(128) DEFAULT NULL,
  filesize BIGINT,
  extraction_id VARCHAR(255),
  status ENUM('uploaded','extracted','failed') DEFAULT 'uploaded',
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Processing logs / history
CREATE TABLE IF NOT EXISTS processing_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NULL,
  started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  status ENUM('running','success','failed') DEFAULT 'running',
  output LONGTEXT,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
);

-- Validation rules (configurable rules for packages)
CREATE TABLE IF NOT EXISTS validation_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255),
  pattern VARCHAR(255),
  required TINYINT(1) DEFAULT 1,
  file_type ENUM('file','folder') DEFAULT 'file',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- File mapping (maps source files to book folders)
CREATE TABLE IF NOT EXISTS file_mapping (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NULL,
  original_path VARCHAR(1024),
  mapped_path VARCHAR(1024),
  file_type VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Configuration store
CREATE TABLE IF NOT EXISTS configuration (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(255) UNIQUE,
  `value` TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Error logs
CREATE TABLE IF NOT EXISTS error_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level VARCHAR(20),
  message TEXT,
  context JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Processing logs (detailed)
CREATE TABLE IF NOT EXISTS processing_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  processing_history_id INT NULL,
  log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  message TEXT,
  level VARCHAR(20) DEFAULT 'info',
  FOREIGN KEY (processing_history_id) REFERENCES processing_history(id) ON DELETE CASCADE
);

-- Book files (catalog of files for each book)
CREATE TABLE IF NOT EXISTS book_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  filename VARCHAR(1024),
  relative_path VARCHAR(1024),
  file_size BIGINT,
  file_type VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Indexes for faster lookups
CREATE INDEX idx_upload_extraction ON upload_history(extraction_id);
CREATE INDEX idx_books_short ON books(short_name);
-- Additional tables requested: roles, user_roles mapping, chapter_files, book_metadata,
-- python_logs, activity_logs, settings, book_errors, user_logs, client_templates,
-- file_extensions, file_types

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

-- user_roles mapping (many-to-many users <-> roles)
CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- chapter_files: files specific to chapters (e.g., images per chapter)
CREATE TABLE IF NOT EXISTS chapter_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  chapter_id INT NOT NULL,
  filename VARCHAR(1024) NOT NULL,
  relative_path VARCHAR(1024),
  file_size BIGINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- book_metadata: key/value or JSON metadata for a book
CREATE TABLE IF NOT EXISTS book_metadata (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  `key` VARCHAR(255) NOT NULL,
  `value` TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  UNIQUE KEY uq_bookmeta (book_id, `key`)
) ENGINE=InnoDB;

-- python_logs: logs from python processing scripts
CREATE TABLE IF NOT EXISTS python_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  processing_history_id INT NULL,
  log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  level VARCHAR(20) DEFAULT 'info',
  message LONGTEXT,
  meta JSON NULL,
  FOREIGN KEY (processing_history_id) REFERENCES processing_history(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- activity_logs: user actions in the UI
CREATE TABLE IF NOT EXISTS activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(255) NOT NULL,
  context JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_activity_user (user_id)
) ENGINE=InnoDB;

-- settings: key/value global settings
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(255) NOT NULL UNIQUE,
  `value` TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- book_errors: per-book error records
CREATE TABLE IF NOT EXISTS book_errors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NULL,
  error_level VARCHAR(20) DEFAULT 'error',
  message TEXT NOT NULL,
  context JSON NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- user_logs: audit log entries for user authentication events
CREATE TABLE IF NOT EXISTS user_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  event VARCHAR(100) NOT NULL,
  ip_address VARCHAR(45),
  user_agent VARCHAR(512),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- password_resets: tokens for forgot-password flow
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(255) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
) ENGINE=InnoDB;

-- client_templates: reusable templates for clients
CREATE TABLE IF NOT EXISTS client_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  template JSON,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- file_types and file_extensions for validation and mapping
CREATE TABLE IF NOT EXISTS file_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS file_extensions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  extension VARCHAR(20) NOT NULL UNIQUE,
  file_type_id INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (file_type_id) REFERENCES file_types(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- sample data
INSERT IGNORE INTO roles (name, description) VALUES
  ('admin','Full system administrator'),
  ('editor','Can edit books and metadata'),
  ('uploader','Can upload packages'),
  ('viewer','Read-only access');

INSERT IGNORE INTO file_types (name, description) VALUES
  ('document','Document files (docx, pdf, etc.)'),
  ('image','Image files'),
  ('font','Font files'),
  ('archive','Archive files like zip');

INSERT IGNORE INTO file_extensions (extension, file_type_id) VALUES
  ('docx', (SELECT id FROM file_types WHERE name='document' LIMIT 1)),
  ('pdf', (SELECT id FROM file_types WHERE name='document' LIMIT 1)),
  ('jpg', (SELECT id FROM file_types WHERE name='image' LIMIT 1)),
  ('jpeg', (SELECT id FROM file_types WHERE name='image' LIMIT 1)),
  ('png', (SELECT id FROM file_types WHERE name='image' LIMIT 1)),
  ('ttf', (SELECT id FROM file_types WHERE name='font' LIMIT 1)),
  ('zip', (SELECT id FROM file_types WHERE name='archive' LIMIT 1));

-- sample admin user (password: changeit) -- adapt to your environment
INSERT IGNORE INTO users (username, password_hash, role, email)
  VALUES ('admin', '$2y$10$abcdefghijklmnopqrstuv', 'admin', 'admin@example.com');
-- ensure user_logs table exists for auth events
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('password_reset_expires_minutes', '60');
