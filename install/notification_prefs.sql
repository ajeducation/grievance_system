-- Table for user notification preferences
CREATE TABLE user_notification_prefs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    grievance_id INT DEFAULT NULL,
    disabled TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pref (user_id, category_id, grievance_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (grievance_id) REFERENCES grievances(id)
);
