<?php
declare(strict_types=1);

function schema_statements(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS cities (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(80) NOT NULL UNIQUE,
            governorate VARCHAR(80) NOT NULL DEFAULT '',
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(20) NOT NULL DEFAULT 'applicant',
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL UNIQUE,
            phone VARCHAR(32) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            city_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_users_role (role),
            INDEX idx_users_city (city_id),
            INDEX idx_users_phone (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS applications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            type VARCHAR(16) NOT NULL,
            status VARCHAR(32) NOT NULL DEFAULT 'pending',
            application_no VARCHAR(40) NOT NULL UNIQUE,
            first_name VARCHAR(80) NULL,
            middle_name VARCHAR(80) NULL,
            last_name VARCHAR(80) NULL,
            nationality VARCHAR(80) NULL,
            dob DATE NULL,
            reason_to_join TEXT NULL,
            city_id INT UNSIGNED NULL,
            phone VARCHAR(32) NULL,
            rider_code VARCHAR(40) NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_app_user (user_id),
            INDEX idx_app_status (status),
            INDEX idx_app_city (city_id),
            INDEX idx_app_type (type),
            CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_app_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS documents (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            kind VARCHAR(40) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            mime VARCHAR(80) NOT NULL,
            path VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_doc_app (application_id),
            CONSTRAINT fk_doc_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS application_events (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            from_status VARCHAR(32) NULL,
            to_status VARCHAR(32) NOT NULL,
            note TEXT NULL,
            actor_id INT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ev_app (application_id),
            CONSTRAINT fk_ev_app FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS tickets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            category VARCHAR(40) NOT NULL,
            subject VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_t_user (user_id),
            INDEX idx_t_status (status),
            CONSTRAINT fk_t_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS ticket_replies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            body TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tr_t (ticket_id),
            CONSTRAINT fk_tr_t FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS appointments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            city_id INT UNSIGNED NULL,
            kind VARCHAR(40) NOT NULL,
            scheduled_for DATETIME NOT NULL,
            location VARCHAR(180) NULL,
            notes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'requested',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ap_user (user_id),
            INDEX idx_ap_status (status),
            CONSTRAINT fk_ap_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS emergencies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            lat VARCHAR(32) NULL,
            lng VARCHAR(32) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_em_user (user_id),
            INDEX idx_em_status (status),
            CONSTRAINT fk_em_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS notifications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            channel VARCHAR(20) NOT NULL,
            title VARCHAR(180) NOT NULL,
            body TEXT NOT NULL,
            meta TEXT NULL,
            read_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_n_user (user_id, created_at),
            CONSTRAINT fk_n_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS supervisors (
            user_id INT UNSIGNED PRIMARY KEY,
            city_id INT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL,
            phone VARCHAR(32) NULL,
            CONSTRAINT fk_sup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_sup_city FOREIGN KEY (city_id) REFERENCES cities(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(80) PRIMARY KEY,
            `value` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS id_sequences (
            year INT NOT NULL,
            type VARCHAR(16) NOT NULL,
            last_n INT NOT NULL DEFAULT 0,
            PRIMARY KEY (year, type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

function schema_oman_cities(): array
{
    return [
        ['Muscat', 'Muscat'],
        ['Seeb', 'Muscat'],
        ['Bausher', 'Muscat'],
        ['Muttrah', 'Muscat'],
        ['Al Amarat', 'Muscat'],
        ['Barka', 'Al Batinah South'],
        ['Sohar', 'Al Batinah North'],
        ['Saham', 'Al Batinah North'],
        ['Shinas', 'Al Batinah North'],
        ['Al Suwaiq', 'Al Batinah North'],
        ['Nizwa', 'Ad Dakhiliyah'],
        ['Bahla', 'Ad Dakhiliyah'],
        ['Izki', 'Ad Dakhiliyah'],
        ['Ibri', 'Ad Dhahirah'],
        ['Rustaq', 'Al Batinah South'],
        ['Sur', 'Ash Sharqiyah South'],
        ['Ibra', 'Ash Sharqiyah North'],
        ['Salalah', 'Dhofar'],
        ['Taqah', 'Dhofar'],
        ['Khasab', 'Musandam'],
        ['Al Buraimi', 'Al Buraimi'],
    ];
}

function schema_default_settings(): array
{
    return [
        'company_name' => 'TRD Rider Hub',
        'company_legal' => 'Talabat Rider Division · Oman',
        'admin_whatsapp' => '96800000000',
        'admin_email' => 'riders@trd.om',
        'whatsapp_token' => '',
        'whatsapp_phone_id' => '',
        'email_from' => 'TRD Rider Hub <noreply@trd.om>',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_secure' => 'tls',
        'support_hours' => 'Sunday–Thursday, 9:00–18:00 GST',
        'emergency_phone' => '96800000000',
    ];
}
