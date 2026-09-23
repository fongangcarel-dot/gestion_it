<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/core/bootstrap.php';
require_role('admin');

$userModel = new User($pdo);
$computerModel = new Computer($pdo);
$departmentModel = new Department($pdo);
$departments = $departmentModel->all();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$existing = $id ? $userModel->find($id) : null;
if ($id && $existing === null) { http_response_code(404); exit('User not found.'); }

$values = $existing ?: ['department_id' => '', 'first_name' => '', 'last_name' => '', 'email' => '', 'phone' => '', 'role' => 'employee', 'status' => 'active'];
$assignedComputers = $id ? $computerModel->forEmployee($id) : [];
$computerIds = array_map('intval', array_column($assignedComputers, 'id'));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = ['department_id' => post_text('department_id'), 'first_name' => post_text('first_name'), 'last_name' => post_text('last_name'), 'email' => post_text('email'), 'phone' => post_text('phone'), 'role' => post_text('role'), 'status' => post_text('status')];
    $computerIds = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['computer_ids'] ?? [])))));
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $errors[] = 'Invalid form token.';
    if ($values['first_name'] === '' || $values['last_name'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Names and valid email are required.';
    if ($userModel->emailExists($values['email'], $id ?: null)) $errors[] = 'That email is already in use.';
    if (!valid_enum($values['role'], ['employee', 'technician', 'admin']) || !valid_enum($values['status'], ['active', 'inactive'])) $errors[] = 'Invalid role or status.';
    if ($values['department_id'] !== '' && !$departmentModel->exists((int) $values['department_id'])) $errors[] = 'Invalid department.';
    if (!$id && !$computerIds) $errors[] = 'A computer must be assigned when creating a user.';
    foreach ($computerIds as $computerId) {
        if (!$computerModel->exists($computerId)) $errors[] = 'Select valid computers.';
    }
    if (!$id && strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if ($password !== '' && strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if (!$errors) {
        $data = ['department_id' => $values['department_id'] !== '' ? (int) $values['department_id'] : null, 'first_name' => $values['first_name'], 'last_name' => $values['last_name'], 'email' => $values['email'], 'phone' => $values['phone'] ?: null, 'role' => $values['role'], 'status' => $values['status']];
        if ($password !== '') $data['password'] = User::hashPassword($password);
        $pdo->beginTransaction();
        try {
            $userId = $id ?: $userModel->create($data + ['password' => User::hashPassword($password)]);
            if ($id) $userModel->update($id, $data);
            if (!$computerModel->replaceUserAssignments($computerIds, (int) $userId)) throw new RuntimeException('Assignment failed.');
            $pdo->commit();
            flash('message', $id ? 'User updated.' : 'User created.');
            redirect('admin_users.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'User could not be saved.';
        }
    }
}

$computers = $computerModel->all();
require dirname(__DIR__) . '/app/views/layout.php';
page_header($id ? 'Edit user' : 'Add user');
?>
<div class="toolbar"><div><p class="eyebrow">Administration / users</p><h1><?= $id ? 'Edit user' : 'Add user' ?></h1></div><a href="admin_users.php">Back to users</a></div>
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<section class="panel form-panel"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="grid">
<p><label>First name</label><input name="first_name" value="<?= e($values['first_name']) ?>" required></p>
<p><label>Last name</label><input name="last_name" value="<?= e($values['last_name']) ?>" required></p>
<p><label>Email</label><input name="email" type="email" value="<?= e($values['email']) ?>" required></p>
<p><label>Phone</label><input name="phone" value="<?= e($values['phone']) ?>"></p>
<p><label>Department</label><select name="department_id"><option value="">None</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) $values['department_id'] === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></p>
<p><label>Role</label><select name="role"><?php foreach (['employee', 'technician', 'admin'] as $role): ?><option <?= $values['role'] === $role ? 'selected' : '' ?>><?= e($role) ?></option><?php endforeach; ?></select></p>
<p><label>Status</label><select name="status"><option <?= $values['status'] === 'active' ? 'selected' : '' ?>>active</option><option <?= $values['status'] === 'inactive' ? 'selected' : '' ?>>inactive</option></select></p>
<p><label>Password <?= $id ? '(leave blank to keep current)' : '' ?></label><input name="password" type="password" autocomplete="new-password" <?= $id ? '' : 'required' ?>></p>
<p><label>Assigned computers <?= $id ? '(select one or more)' : '(required)' ?></label><select name="computer_ids[]" multiple size="4" <?= !$id ? 'required' : '' ?>><?php foreach ($computers as $computer): ?><option value="<?= (int) $computer['id'] ?>" <?= in_array((int) $computer['id'], $computerIds, true) ? 'selected' : '' ?>><?= e($computer['asset_tag'] . ' ' . $computer['brand'] . ' ' . $computer['model']) ?></option><?php endforeach; ?></select></p>
</div><button type="submit">Save user</button></form></section><?php page_footer(); ?>