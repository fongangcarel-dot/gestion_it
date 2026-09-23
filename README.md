# IT Help Desk and Maintenance Management System

PHP/MySQL application using PDO and an MVC-style structure. The existing database named `gestion_it` is used as-is; this project does not change its schema.

## Local setup

1. Start Apache and MySQL in XAMPP.
2. Open the application through `public/` (for example, `http://localhost/gestion_it/public/`).
3. Apply `database/001_printer_user_assignments.sql` to create the printer-to-user assignment table.
4. Apply `database/002_standalone_preventive_maintenance.sql` to allow preventive maintenance without a ticket.
5. Apply `database/003_ticket_history_notifications.sql` to enable ticket history and notifications.
6. Apply `database/004_computer_user_assignments.sql` to allow one computer to be shared by multiple users.
7. Use an existing active account, or run `tests/seed_dev_users.php` once in development to create/update the local test accounts, then delete that script.

## Roles and routes

- Employees use `tickets.php` to create and view their own tickets.
- Technicians use `technician_tickets.php` to work on assigned tickets and record maintenance/tasks.
- Administrators use `admin_tickets.php` to assign/filter/close tickets and can inspect users, computers, and maintenance records.

All private pages require a PHP session and role authorization. POST forms use CSRF tokens, output is HTML-escaped, and database access uses PDO prepared statements.

## Testing

Run the focused checks with XAMPP's PHP executable:

```powershell
C:\xampp\php\php.exe test_db.php
C:\xampp\php\php.exe tests\auth_smoke.php
```

The workflow smoke check uses a transaction and rolls back its temporary ticket, maintenance record, and task.