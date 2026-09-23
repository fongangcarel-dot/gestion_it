CREATE TABLE IF NOT EXISTS computer_user (
    computer_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (computer_id, user_id),
    KEY idx_computer_user_user (user_id),
    CONSTRAINT fk_computer_user_computer FOREIGN KEY (computer_id) REFERENCES computers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_computer_user_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO computer_user (computer_id, user_id)
SELECT id, user_id FROM computers WHERE user_id IS NOT NULL;
