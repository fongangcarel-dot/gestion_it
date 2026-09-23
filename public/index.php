<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/core/bootstrap.php';

if (is_authenticated()) {
	redirect('dashboard.php');
}

redirect('login.php');