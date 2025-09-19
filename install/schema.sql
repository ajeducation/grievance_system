-- Create grievance_appeals table first
CREATE TABLE grievance_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grievance_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(32) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    comment TEXT,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE grievance_appeal_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appeal_id INT NOT NULL,
    action VARCHAR(64) NOT NULL,
    performed_by INT NOT NULL,
    performed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    FOREIGN KEY (appeal_id) REFERENCES grievance_appeals(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);
CREATE TABLE grievance_appeal_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appeal_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appeal_id) REFERENCES grievance_appeals(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grievance_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    attachment VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    parent_id INT DEFAULT NULL,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES comments(id)
);
CREATE TABLE grievance_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grievance_id INT NOT NULL,
    comment_id INT DEFAULT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id),
    FOREIGN KEY (comment_id) REFERENCES grievance_actions(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255),
    role VARCHAR(32) NOT NULL,
    display_label VARCHAR(64) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    reminder_days INT DEFAULT 3,
    escalation_days INT DEFAULT 7,
    allow_appeal TINYINT(1) DEFAULT 1,
    appeal_window_days INT DEFAULT 7,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE grievances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('ongoing','completed') DEFAULT 'ongoing',
    assigned_to INT,
    allow_appeal TINYINT(1) DEFAULT NULL,
    appeal_window_days INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE grievance_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grievance_id INT NOT NULL,
    action_taken TEXT NOT NULL,
    action_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    marked_by INT NOT NULL,
    FOREIGN KEY (grievance_id) REFERENCES grievances(id),
    FOREIGN KEY (marked_by) REFERENCES users(id)
);

-- Config table for workflow automation settings
CREATE TABLE config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(64) NOT NULL UNIQUE,
    config_value VARCHAR(255) NOT NULL
);

-- Default values for reminder and escalation days
INSERT INTO config (config_key, config_value) VALUES
('reminder_days', '3'),
('escalation_days', '7');
