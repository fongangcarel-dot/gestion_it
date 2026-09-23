<?php

declare(strict_types=1);

try {
	require_once 'config/database.php';
	echo 'Database connection successful!';
} catch (Throwable $exception) {
	http_response_code(500);
	echo 'Database connection failed.';
}