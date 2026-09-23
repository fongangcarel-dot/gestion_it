<?php

declare(strict_types=1);

// DEVELOPMENT ONLY: run once locally, then delete this file.

$pdo = require dirname(__DIR__) . '/config/database.php';

$credentials = [
    'admin@test.com' => [
        'password' => 'Admin123!',
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'role' => 'admin',
    ],
    'technician@test.com' => [
        'password' => 'Tech123!',
        'first_name' => 'Test',
        'last_name' => 'Technician',
        'role' => 'technician',
    ],
    'employee@test.com' => [
        'password' => 'Employee123!',
        'first_name' => 'Test',
        'last_name' => 'Employee',
        'role' => 'employee',
    ],
];

try {
    $pdo->beginTransaction();

    $findUser = $pdo->prepare(
        'SELECT id, password FROM users WHERE email = :email LIMIT 1'
    );
    $createUser = $pdo->prepare(
        'INSERT INTO users (first_name, last_name, email, password, role, status)
         VALUES (:first_name, :last_name, :email, :password, :role, :status)'
    );
    $updatePassword = $pdo->prepare(
        'UPDATE users SET password = :password WHERE email = :email'
    );

    $verified = [];
    foreach ($credentials as $email => $account) {
        $findUser->execute(['email' => $email]);
        $user = $findUser->fetch();
        $passwordHash = password_hash($account['password'], PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException("Could not hash password for {$email}");
        }

        if ($user === false) {
            $createUser->execute([
                'first_name' => $account['first_name'],
                'last_name' => $account['last_name'],
                'email' => $email,
                'password' => $passwordHash,
                'role' => $account['role'],
                'status' => 'active',
            ]);
        } else {
            $updatePassword->execute([
                'password' => $passwordHash,
                'email' => $email,
            ]);
        }

        $findUser->execute(['email' => $email]);
        $updatedUser = $findUser->fetch();
        $verified[$email] = $updatedUser !== false
            && password_verify($account['password'], $updatedUser['password']);
    }

    if (count($verified) !== count($credentials) || in_array(false, $verified, true)) {
        throw new RuntimeException('Password verification failed for one or more test users.');
    }

    $pdo->commit();
    foreach ($verified as $email => $isVerified) {
        echo "PASS {$email}: password_verify()\n";
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Development user seeding failed: {$exception->getMessage()}\n");
    exit(1);
}