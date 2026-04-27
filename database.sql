CREATE DATABASE IF NOT EXISTS nkflow_nk_flower_dreams CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nkflow_nk_flower_dreams;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    description TEXT NULL,
    price DECIMAL(10,2) NULL,
    image_path VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attempt_window (attempted_at),
    INDEX idx_attempt_identity (email, ip_address)
) ENGINE=InnoDB;

INSERT INTO products (name, slug, description, price, image_path, is_active, sort_order)
VALUES
('Door Floral Display 1', 'door-floral-display-1', 'Handmade decorative arrangement for elegant spaces.', 2500.00, 'images/collections/1.png', 1, 1),
('Door Floral Display 2', 'door-floral-display-2', 'Soft-toned everlasting flower arrangement.', 2750.00, 'images/collections/2.png', 1, 2),
('Door Floral Display 3', 'door-floral-display-3', 'Warm botanical style with custom finishing.', 3200.00, 'images/collections/3.png', 1, 3),
('Door Floral Display 4', 'door-floral-display-4', 'Premium handcrafted decor arrangement.', 3500.00, 'images/collections/4.png', 1, 4),
('Door Floral Display 5', 'door-floral-display-5', 'Signature NK Flower Dreams custom bloom set.', 3800.00, 'images/collections/5.png', 1, 5)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
