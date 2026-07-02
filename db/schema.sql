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
