<?php

declare(strict_types=1);

function page_header(string $title): void
{
    $user = current_user();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | Savana Islamic Finance</title>
        <link rel="stylesheet" href="assets/css/app.css?v=20260915">
    </head>
    <body>
    <header class="topbar">
        <a class="brand" href="dashboard.php" aria-label="Savana Islamic Finance - Dashboard"><img src="assets/savana-logo.svg" alt="SAVANA Islamic Finance"></a>
        <?php if ($user !== null): ?>
            <div class="topbar-user"><span class="avatar"><?= e(strtoupper(substr($user['first_name'], 0, 1))) ?></span><span><?= e($user['first_name']) ?></span></div>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <?php if ($user['role'] === 'employee'): ?><a href="tickets.php">My tickets</a><?php endif; ?>
                <?php if ($user['role'] === 'technician'): ?><a href="technician_tickets.php">Assigned tickets</a><a href="maintenance.php">Preventive maintenance</a><?php endif; ?>
                <?php if ($user['role'] === 'admin'): ?><a href="admin_tickets.php">Manage tickets</a><a href="admin_users.php">Users</a><a href="admin_computers.php">Computers</a><a href="admin_printers.php">Printers</a><a href="admin_maintenance.php">Maintenance</a><?php endif; ?>
                <a href="logout.php">Sign out</a>
            </nav>
        <?php endif; ?>
    </header>
    <main class="container">
    <?php
}

function page_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}