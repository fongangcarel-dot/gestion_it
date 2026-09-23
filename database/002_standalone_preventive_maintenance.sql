ALTER TABLE maintenance
    MODIFY ticket_id INT NULL,
    DROP FOREIGN KEY fk_maintenance_ticket;

ALTER TABLE maintenance
    ADD CONSTRAINT fk_maintenance_ticket
    FOREIGN KEY (ticket_id) REFERENCES tickets (id)
    ON DELETE CASCADE ON UPDATE CASCADE;
