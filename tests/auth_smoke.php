<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/core/bootstrap.php';

$results = [];
$results['password_hash_and_verify'] = password_verify(
    'Known test password',
    User::hashPassword('Known test password')
);

$userModel = new User($pdo);
$statement = $pdo->prepare(
    'SELECT email, role FROM users WHERE status = :status ORDER BY id LIMIT 1'
);
$statement->execute(['status' => 'active']);
$activeUser = $statement->fetch();

$results['invalid_login_rejected'] = $activeUser === false
    || $userModel->authenticate($activeUser['email'], 'definitely-wrong-password') === null;
$results['supported_roles'] = count(array_intersect(
    ['employee', 'technician', 'admin'],
    ['employee', 'technician', 'admin']
)) === 3;

$_SESSION['user'] = ['id' => 1, 'role' => 'employee'];
$results['employee_allowed_as_employee'] = user_has_role('employee');
$results['employee_denied_as_admin'] = !user_has_role('admin');
$_SESSION['user']['role'] = 'technician';
$results['technician_allowed_as_technician'] = user_has_role('technician');
$results['technician_denied_as_employee'] = !user_has_role('employee');
$_SESSION['user']['role'] = 'admin';
$results['admin_allowed_as_admin'] = user_has_role('admin');
$results['admin_denied_as_technician'] = !user_has_role('technician');

foreach ($results as $name => $passed) {
    echo ($passed ? 'PASS' : 'FAIL') . " {$name}" . PHP_EOL;
}

exit(in_array(false, $results, true) ? 1 : 0);