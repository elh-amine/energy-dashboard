-- ============================================
-- Energy Dashboard - Schéma de base de données
-- ============================================

-- Supprimer les tables existantes (attention en production !)
-- DROP TABLE IF EXISTS monthly_billing;
-- DROP TABLE IF EXISTS energy_exchange_data;
-- DROP TABLE IF EXISTS central_battery_data;
-- DROP TABLE IF EXISTS houses;
-- DROP TABLE IF EXISTS users;

-- ============================================
-- Table des utilisateurs
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des maisons/habitations
-- ============================================
CREATE TABLE IF NOT EXISTS houses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('residential', 'commercial', 'industrial') DEFAULT 'residential',
    address VARCHAR(255),
    owner_name VARCHAR(100),
    owner_email VARCHAR(100),
    owner_phone VARCHAR(20),
    thingsboard_device_id VARCHAR(100) UNIQUE,
    thingsboard_entity_id VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    installation_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_thingsboard_device_id (thingsboard_device_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des échanges énergétiques
-- ============================================
CREATE TABLE IF NOT EXISTS energy_exchange_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    house_id INT NOT NULL,
    energy_injected DECIMAL(10, 4) DEFAULT 0 COMMENT 'Énergie injectée dans le réseau (kWh)',
    energy_taken DECIMAL(10, 4) DEFAULT 0 COMMENT 'Énergie soutirée du réseau (kWh)',
    power DECIMAL(10, 4) DEFAULT 0 COMMENT 'Puissance instantanée (kW)',
    voltage DECIMAL(8, 2) DEFAULT 0 COMMENT 'Tension (V)',
    current DECIMAL(8, 2) DEFAULT 0 COMMENT 'Courant (A)',
    frequency DECIMAL(5, 2) DEFAULT 0 COMMENT 'Fréquence (Hz)',
    power_factor DECIMAL(4, 3) DEFAULT 0 COMMENT 'Facteur de puissance',
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
    INDEX idx_house_id (house_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_house_date (house_id, timestamp),
    INDEX idx_date (DATE(timestamp))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des données de la batterie centrale
-- ============================================
CREATE TABLE IF NOT EXISTS central_battery_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    energy_received DECIMAL(10, 4) DEFAULT 0 COMMENT 'Énergie reçue des maisons (kWh)',
    energy_distributed DECIMAL(10, 4) DEFAULT 0 COMMENT 'Énergie distribuée aux maisons (kWh)',
    battery_level DECIMAL(5, 2) DEFAULT 0 COMMENT 'Niveau de charge de la batterie (%)',
    battery_capacity DECIMAL(10, 2) DEFAULT 0 COMMENT 'Capacité totale de la batterie (kWh)',
    battery_health DECIMAL(5, 2) DEFAULT 100 COMMENT 'État de santé de la batterie (%)',
    power DECIMAL(10, 4) DEFAULT 0 COMMENT 'Puissance instantanée (kW)',
    temperature DECIMAL(5, 2) DEFAULT 0 COMMENT 'Température de la batterie (°C)',
    status ENUM('charging', 'discharging', 'idle', 'maintenance') DEFAULT 'idle',
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp),
    INDEX idx_status (status),
    INDEX idx_date (DATE(timestamp))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table de la facturation mensuelle
-- ============================================
CREATE TABLE IF NOT EXISTS monthly_billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    house_id INT NOT NULL,
    month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    total_energy_injected DECIMAL(10, 4) DEFAULT 0,
    total_energy_taken DECIMAL(10, 4) DEFAULT 0,
    amount_to_pay DECIMAL(10, 2) DEFAULT 0 COMMENT 'Montant à payer (€)',
    payment_status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    payment_date DATE NULL,
    payment_method VARCHAR(50),
    invoice_number VARCHAR(50) UNIQUE,
    notes TEXT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_house_month (house_id, month),
    INDEX idx_month (month),
    INDEX idx_house_id (house_id),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Table des logs système (optionnel)
-- ============================================
CREATE TABLE IF NOT EXISTS system_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    log_type ENUM('error', 'warning', 'info', 'sync') DEFAULT 'info',
    message TEXT NOT NULL,
    context JSON,
    user_id INT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_type (log_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Données d'exemple pour les tests
-- ============================================

-- Insérer un utilisateur admin par défaut
-- Mot de passe: admin123 (à changer en production !)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@energy-dashboard.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insérer des maisons d'exemple
INSERT INTO houses (name, type, address, thingsboard_device_id, is_active) VALUES
('Maison A', 'residential', '123 Rue de l\'Énergie', 'device-house-a-001', TRUE),
('Maison B', 'residential', '456 Avenue Solaire', 'device-house-b-002', TRUE),
('Maison C', 'residential', '789 Boulevard Vert', 'device-house-c-003', TRUE),
('Commerce Local', 'commercial', '10 Place du Marché', 'device-commerce-004', TRUE)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================
-- Vues utiles
-- ============================================

-- Vue pour les statistiques quotidiennes par maison
CREATE OR REPLACE VIEW daily_house_statistics AS
SELECT
    h.id as house_id,
    h.name as house_name,
    h.type as house_type,
    DATE(e.timestamp) as date,
    SUM(e.energy_injected) as total_injected,
    SUM(e.energy_taken) as total_taken,
    SUM(e.energy_taken) - SUM(e.energy_injected) as net_consumption,
    COUNT(*) as data_points
FROM houses h
LEFT JOIN energy_exchange_data e ON h.id = e.house_id
GROUP BY h.id, h.name, h.type, DATE(e.timestamp);

-- Vue pour les totaux mensuels par maison
CREATE OR REPLACE VIEW monthly_house_totals AS
SELECT
    h.id as house_id,
    h.name as house_name,
    h.type as house_type,
    DATE_FORMAT(e.timestamp, '%Y-%m') as month,
    SUM(e.energy_injected) as total_injected,
    SUM(e.energy_taken) as total_taken,
    SUM(e.energy_taken) - SUM(e.energy_injected) as net_consumption
FROM houses h
LEFT JOIN energy_exchange_data e ON h.id = e.house_id
GROUP BY h.id, h.name, h.type, DATE_FORMAT(e.timestamp, '%Y-%m');

-- Vue pour le dashboard global
CREATE OR REPLACE VIEW dashboard_overview AS
SELECT
    (SELECT COUNT(*) FROM houses WHERE is_active = TRUE) as total_active_houses,
    (SELECT SUM(energy_injected) FROM energy_exchange_data WHERE DATE(timestamp) = CURDATE()) as today_total_injected,
    (SELECT SUM(energy_taken) FROM energy_exchange_data WHERE DATE(timestamp) = CURDATE()) as today_total_taken,
    (SELECT battery_level FROM central_battery_data ORDER BY timestamp DESC LIMIT 1) as current_battery_level,
    (SELECT COUNT(*) FROM monthly_billing WHERE month = DATE_FORMAT(NOW(), '%Y-%m') AND payment_status = 'pending') as pending_invoices;

-- ============================================
-- Procédures stockées
-- ============================================

-- Procédure pour nettoyer les anciennes données (plus de 2 ans)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS cleanup_old_data()
BEGIN
    DECLARE deleted_rows INT;

    -- Supprimer les données énergétiques de plus de 2 ans
    DELETE FROM energy_exchange_data
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 YEAR);
    SET deleted_rows = ROW_COUNT();

    -- Supprimer les données de batterie de plus de 2 ans
    DELETE FROM central_battery_data
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 2 YEAR);

    -- Logger l'opération
    INSERT INTO system_logs (log_type, message, context)
    VALUES ('info', 'Nettoyage automatique des anciennes données', JSON_OBJECT('deleted_rows', deleted_rows));

    SELECT CONCAT('Nettoyage terminé : ', deleted_rows, ' lignes supprimées') as result;
END//
DELIMITER ;

-- Procédure pour générer un rapport mensuel
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS generate_monthly_report(IN target_month VARCHAR(7))
BEGIN
    SELECT
        h.name,
        h.type,
        b.total_energy_injected,
        b.total_energy_taken,
        b.amount_to_pay,
        b.payment_status
    FROM monthly_billing b
    JOIN houses h ON b.house_id = h.id
    WHERE b.month = target_month
    ORDER BY h.name;
END//
DELIMITER ;

-- ============================================
-- Triggers
-- ============================================

-- Trigger pour générer automatiquement un numéro de facture
DELIMITER //
CREATE TRIGGER IF NOT EXISTS generate_invoice_number
BEFORE INSERT ON monthly_billing
FOR EACH ROW
BEGIN
    IF NEW.invoice_number IS NULL THEN
        SET NEW.invoice_number = CONCAT('INV-', NEW.month, '-', LPAD(NEW.house_id, 4, '0'));
    END IF;
END//
DELIMITER ;

-- ============================================
-- Index pour optimisation des performances
-- ============================================

-- Index composites pour les requêtes fréquentes
-- ALTER TABLE energy_exchange_data ADD INDEX idx_house_timestamp_energy (house_id, timestamp, energy_injected, energy_taken);
-- ALTER TABLE monthly_billing ADD INDEX idx_month_payment_status (month, payment_status);

-- ============================================
-- Commentaires sur les tables
-- ============================================

ALTER TABLE users COMMENT = 'Table des utilisateurs de l\'application';
ALTER TABLE houses COMMENT = 'Table des maisons/habitations connectées';
ALTER TABLE energy_exchange_data COMMENT = 'Données d\'échange énergétique par maison';
ALTER TABLE central_battery_data COMMENT = 'Données de la batterie centrale';
ALTER TABLE monthly_billing COMMENT = 'Facturation mensuelle par maison';
ALTER TABLE system_logs COMMENT = 'Logs système pour le suivi et le débogage';

-- ============================================
-- Grants et permissions (à ajuster selon vos besoins)
-- ============================================

-- GRANT SELECT, INSERT, UPDATE, DELETE ON energy_dashboard.* TO 'energy_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================
-- Fin du schéma
-- ============================================

-- Afficher les tables créées
SHOW TABLES;
