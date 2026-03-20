-- Database initialization script.
-- Runs automatically on first container start via docker-entrypoint-initdb.d.

CREATE DATABASE IF NOT EXISTS evolve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evolve;

-- ── Main submissions table ──────────────────────────────────
-- Stores all admission form data. Email and phone are encrypted
-- with AES-256-CBC, hence the larger VARCHAR(512) columns.
CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(150) NOT NULL,
    gender ENUM('hombre', 'mujer') NOT NULL,
    email VARCHAR(512) NOT NULL,           -- AES-256 encrypted
    phone_prefix VARCHAR(10) DEFAULT '+34',
    phone VARCHAR(512) NOT NULL,           -- AES-256 encrypted
    country_of_residence VARCHAR(100) NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    nationality_other VARCHAR(100) DEFAULT NULL,
    work_permit ENUM('si', 'no', 'en_tramite') DEFAULT NULL,
    relocation ENUM('si', 'no', 'depende') NOT NULL,
    date_of_birth DATE DEFAULT NULL,
    education VARCHAR(50) NOT NULL,
    study_area VARCHAR(100) DEFAULT NULL,
    graduation_year VARCHAR(20) DEFAULT NULL,
    english_level ENUM('A1', 'A2', 'B1', 'B2', 'C1', 'C2') NOT NULL,
    situation VARCHAR(50) NOT NULL,
    job_role VARCHAR(150) DEFAULT NULL,
    tech_years_experience TINYINT UNSIGNED DEFAULT NULL,
    linkedin_url VARCHAR(500) DEFAULT NULL,
    willing_to_train ENUM('si', 'no', 'necesito_mas_info') NOT NULL,
    cv_filename VARCHAR(255) DEFAULT NULL,
    utm_source VARCHAR(100) DEFAULT NULL,  -- UTM tracking parameter
    lead_id VARCHAR(100) DEFAULT NULL,     -- External lead identifier
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email(191)),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Rate limiting table ─────────────────────────────────────
-- Tracks API request attempts per IP for application-level rate limiting.
-- Entries are periodically cleaned up by the RateLimiter service.
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,       -- Supports IPv6 (max 45 chars)
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Least-privilege permissions ─────────────────────────────
-- The app user can only SELECT/INSERT submissions and manage rate limits.
-- No UPDATE, DELETE, DROP, or ALTER — minimizes damage from SQL injection.
REVOKE ALL PRIVILEGES ON evolve.* FROM 'evolve'@'%';
GRANT SELECT, INSERT ON evolve.submissions TO 'evolve'@'%';
GRANT SELECT, INSERT, DELETE ON evolve.rate_limits TO 'evolve'@'%';
FLUSH PRIVILEGES;
