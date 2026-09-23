<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

$pdo = require dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/Computer.php';
require_once dirname(__DIR__) . '/models/Printer.php';
require_once dirname(__DIR__) . '/models/Ticket.php';
require_once dirname(__DIR__) . '/models/TicketActivity.php';
require_once dirname(__DIR__) . '/models/Maintenance.php';
require_once dirname(__DIR__) . '/models/MaintenanceTask.php';
require_once dirname(__DIR__) . '/models/Department.php';