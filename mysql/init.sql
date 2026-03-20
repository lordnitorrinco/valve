CREATE DATABASE IF NOT EXISTS evolve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evolve;

CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(150) NOT NULL,
    gender ENUM('hombre', 'mujer') NOT NULL,
    email VARCHAR(512) NOT NULL,
    phone_prefix VARCHAR(10) DEFAULT '+34',
    phone VARCHAR(512) NOT NULL,
    country_of_residence VARCHAR(100) NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    nationality_other VARCHAR(100) DEFAULT NULL,
    work_permit VARCHAR(20) DEFAULT NULL,
    relocation VARCHAR(20) NOT NULL,
    date_of_birth DATE DEFAULT NULL,
    education VARCHAR(50) NOT NULL,
    study_area VARCHAR(100) DEFAULT NULL,
    graduation_year VARCHAR(20) DEFAULT NULL,
    english_level VARCHAR(10) NOT NULL,
    situation VARCHAR(50) NOT NULL,
    job_role VARCHAR(150) DEFAULT NULL,
    tech_years_experience VARCHAR(10) DEFAULT NULL,
    linkedin_url VARCHAR(500) DEFAULT NULL,
    willing_to_train VARCHAR(30) NOT NULL,
    cv_filename VARCHAR(255) DEFAULT NULL,
    utm_source VARCHAR(100) DEFAULT NULL,
    lead_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email(191)),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restrict app user to minimum required permissions
REVOKE ALL PRIVILEGES ON evolve.* FROM 'evolve'@'%';
GRANT SELECT, INSERT ON evolve.submissions TO 'evolve'@'%';
GRANT SELECT, INSERT, DELETE ON evolve.rate_limits TO 'evolve'@'%';
FLUSH PRIVILEGES;
