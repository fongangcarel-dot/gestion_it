CREATE TABLE IF NOT EXISTS printer_user (
    printer_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (printer_id, user_id),
    KEY idx_printer_user_user (user_id),
    CONSTRAINT fk_printer_user_printer FOREIGN KEY (printer_id) REFERENCES printers (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_printer_user_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
