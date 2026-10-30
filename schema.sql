
CREATE DATABASE IF NOT EXISTS quanlybieumau
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE quanlybieumau;

-- 1. TAI KHOAN
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150),
    role ENUM('designer', 'respondent', 'viewer', 'admin')
        NOT NULL DEFAULT 'respondent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. BIEU MAU
CREATE TABLE forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    owner_id INT NOT NULL,
    current_version_id INT NULL,
    status ENUM('draft', 'published', 'archived')
        NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

-- 3. PHIEN BAN BIEU MAU
CREATE TABLE form_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    version_number INT NOT NULL,
    status ENUM('draft', 'published', 'archived')
        NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    UNIQUE (form_id, version_number),
    UNIQUE (form_id, id),
    FOREIGN KEY (form_id) REFERENCES forms(id)
        ON DELETE RESTRICT
);

-- Moi bieu mau chi tham chieu phien ban cua chinh no
ALTER TABLE forms
ADD CONSTRAINT fk_forms_current_version
FOREIGN KEY (id, current_version_id)
REFERENCES form_versions(form_id, id)
ON DELETE RESTRICT;

-- 4. TRUONG DU LIEU
CREATE TABLE fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    field_key VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    field_type ENUM(
        'text', 'number', 'date',
        'single_choice', 'multiple_choice'
    ) NOT NULL,
    options_json JSON NULL,
    validation_json JSON NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (version_id, field_key),
    UNIQUE (version_id, id),
    FOREIGN KEY (version_id) REFERENCES form_versions(id)
        ON DELETE RESTRICT
);

-- 5. DIEU KIEN HIEN THI / BAT BUOC
CREATE TABLE conditions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_id INT NOT NULL,
    target_field_id INT NOT NULL,
    source_field_id INT NOT NULL,
    operator ENUM(
        'equals', 'not_equals',
        'greater_than', 'less_than',
        'contains', 'is_empty', 'is_not_empty'
    ) NOT NULL,
    comparison_value JSON NULL,
    action ENUM('show', 'hide', 'require')
        NOT NULL DEFAULT 'show',
    logic_group INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (version_id, target_field_id)
        REFERENCES fields(version_id, id)
        ON DELETE RESTRICT,
    FOREIGN KEY (version_id, source_field_id)
        REFERENCES fields(version_id, id)
        ON DELETE RESTRICT
);

-- 6. LUOT TRA LOI
CREATE TABLE responses (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    version_id INT NOT NULL,
    respondent_id INT NULL,
    response_status ENUM('submitted', 'cancelled')
        NOT NULL DEFAULT 'submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id, version_id)
        REFERENCES form_versions(form_id, id)
        ON DELETE RESTRICT,
    FOREIGN KEY (respondent_id) REFERENCES users(id)
        ON DELETE SET NULL
);

-- 7. CAU TRA LOI
CREATE TABLE answers (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    response_id BIGINT NOT NULL,
    version_id INT NOT NULL,
    field_id INT NOT NULL,
    answer_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (response_id, field_id),
    FOREIGN KEY (response_id) REFERENCES responses(id)
        ON DELETE RESTRICT,
    FOREIGN KEY (version_id, field_id)
        REFERENCES fields(version_id, id)
        ON DELETE RESTRICT
);

-- 8. QUYEN XEM DU LIEU
CREATE TABLE form_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    user_id INT NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 0,
    can_export TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE (form_id, user_id),
    FOREIGN KEY (form_id) REFERENCES forms(id)
        ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
);

-- CAC CHI MUC TIM KIEM
CREATE INDEX idx_versions_form
ON form_versions(form_id, version_number);

CREATE INDEX idx_fields_version
ON fields(version_id, sort_order);

CREATE INDEX idx_conditions_version
ON conditions(version_id);

CREATE INDEX idx_responses_form_date
ON responses(form_id, submitted_at);

CREATE INDEX idx_responses_version
ON responses(version_id);

CREATE INDEX idx_answers_response
ON answers(response_id);
