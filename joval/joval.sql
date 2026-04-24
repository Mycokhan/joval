-- Database selection
USE joval_microfinance;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- TABLE: loan_applications
-- --------------------------------------------------------

CREATE TABLE loan_applications (
  id int(11) NOT NULL AUTO_INCREMENT,
  first_name varchar(100) NOT NULL,
  middle_name varchar(100) DEFAULT NULL,
  last_name varchar(100) NOT NULL,
  residential_location text NOT NULL,
  guarantor_name varchar(200) NOT NULL,
  loan_amount decimal(15,2) NOT NULL,
  id_document varchar(255) DEFAULT NULL,
  status enum('pending','approved','rejected','under_review') DEFAULT 'pending',
  notes text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY idx_status (status),
  KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SAMPLE DATA: loan_applications
-- --------------------------------------------------------

INSERT INTO loan_applications 
(id, first_name, middle_name, last_name, residential_location, guarantor_name, loan_amount, id_document, status, notes, created_at, updated_at)
VALUES
(1, 'John', 'Micheal', 'Mwenda', 'Kibaha District, Pwani', 'David Samuel Msangi', 15000.00, NULL, 'pending', NULL, '2026-04-18 10:16:37', '2026-04-18 10:16:37'),
(2, 'Sarah', 'Abdallah', 'Juma', 'Mwanza City, Mwanza', 'Emmanuel Joseph Kondo', 25000.00, NULL, 'approved', NULL, '2026-04-18 10:16:37', '2026-04-18 10:16:37'),
(3, 'Peter', NULL, 'Kaguru', 'Arusha Region, Arusha', 'John William Mushi', 10000.00, NULL, 'under_review', NULL, '2026-04-18 10:16:37', '2026-04-18 10:16:37'),
(4, 'Mary', 'Joseph', 'Nkrumah', 'Dodoma Municipality, Dodoma', 'Thomas Ayubu Mbwana', 35000.00, NULL, 'rejected', NULL, '2026-04-18 10:16:37', '2026-04-18 10:16:37'),
(5, 'James', 'David', 'Ochieng', 'Kilimanjaro Region, Moshi', 'Samuel Jacob Mwakidudu', 20000.00, NULL, 'pending', NULL, '2026-04-18 10:16:37', '2026-04-18 10:16:37');

-- --------------------------------------------------------
-- TABLE: loan_status
-- --------------------------------------------------------

CREATE TABLE loan_status (
  id int(11) NOT NULL AUTO_INCREMENT,
  application_id int(11) NOT NULL,
  status varchar(50) NOT NULL,
  notes text DEFAULT NULL,
  changed_by int(11) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY changed_by (changed_by),
  KEY idx_application_id (application_id),
  CONSTRAINT loan_status_ibfk_1 FOREIGN KEY (application_id) REFERENCES loan_applications (id) ON DELETE CASCADE,
  CONSTRAINT loan_status_ibfk_2 FOREIGN KEY (changed_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- TABLE: users
-- --------------------------------------------------------

CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  email varchar(100) NOT NULL,
  password_hash varchar(255) NOT NULL,
  full_name varchar(100) NOT NULL,
  role enum('admin','manager','viewer') DEFAULT 'viewer',
  is_active tinyint(1) DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  last_login timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username),
  UNIQUE KEY email (email),
  KEY idx_username (username),
  KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SAMPLE USER
-- --------------------------------------------------------

INSERT INTO users 
(id, username, email, password_hash, full_name, role, is_active, created_at, last_login)
VALUES
(1, 'admin', 'admin@jovalmicrofinance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1, '2026-04-18 10:16:37', NULL);

-- --------------------------------------------------------
-- VIEW: v_loan_applications (FIXED)
-- --------------------------------------------------------

CREATE VIEW v_loan_applications AS
SELECT 
  loan_applications.id AS id,
  CONCAT(
    loan_applications.first_name,' ',
    IFNULL(CONCAT(loan_applications.middle_name,' '), ''),
    loan_applications.last_name
  ) AS full_name,
  loan_applications.residential_location,
  loan_applications.guarantor_name,
  loan_applications.loan_amount,
  loan_applications.status,
  loan_applications.created_at,
  loan_applications.updated_at
FROM loan_applications
ORDER BY loan_applications.created_at DESC;

-- --------------------------------------------------------
-- VIEW: v_application_stats (FIXED)
-- --------------------------------------------------------

CREATE VIEW v_application_stats AS
SELECT 
  COUNT(*) AS total_applications,
  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
  SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
  SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
  SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) AS under_review,
  SUM(loan_amount) AS total_amount_requested,
  AVG(loan_amount) AS average_loan_amount
FROM loan_applications;

COMMIT;