<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/core/bootstrap.php';
require_role('admin');

$model = new Printer($pdo);
$userModel = new User($pdo);
$departmentModel = new Department($pdo);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$existing = $id ? $model->find($id) : null;
if ($id && !$existing) { http_response_code(404); exit('Printer not found.'); }

$values = $existing ?: ['asset_tag' => '', 'brand' => '', 'model' => '', 'serial_number' => '', 'printer_type' => 'other', 'ip_address' => '', 'location' => '', 'department_id' => '', 'status' => 'functional', 'purchase_date' => ''];
$assignedUserIds = $id ? $model->assignedUserIds($id) : [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = ['asset_tag' => post_text('asset_tag'), 'brand' => post_text('brand'), 'model' => post_text('model'), 'serial_number' => post_text('serial_number'), 'printer_type' => post_text('printer_type'), 'ip_address' => post_text('ip_address'), 'location' => post_text('location'), 'department_id' => post_text('department_id'), 'status' => post_text('status'), 'purchase_date' => post_text('purchase_date')];
    $assignedUserIds = array_values(array_unique(array_filter(array_map('intval', is_array($_POST['user_ids'] ?? null) ? $_POST['user_ids'] : []))));
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $errors[] = 'Invalid form token.';
    if ($values['asset_tag'] === '' || strlen($values['asset_tag']) > 50) $errors[] = 'Asset tag is required and must be 50 characters or fewer.';
    if ($values['brand'] === '') $errors[] = 'Brand is required.';
    if ($model->assetExists($values['asset_tag'], $id ?: null)) $errors[] = 'That asset tag is already in use.';
    if (!valid_enum($values['printer_type'], ['laser', 'inkjet', 'multifunction', 'thermal', 'other'])) $errors[] = 'Invalid printer type.';
    if (!valid_enum($values['status'], ['functional', 'maintenance', 'broken', 'retired'])) $errors[] = 'Invalid printer status.';
    if ($values['department_id'] !== '' && !$departmentModel->exists((int) $values['department_id'])) $errors[] = 'Invalid department.';
    $users = $userModel->all();
    $validUserIds = array_map('intval', array_column($users, 'id'));
    if (array_diff($assignedUserIds, $validUserIds)) $errors[] = 'Select valid users.';
    if ($values['purchase_date'] !== '' && !valid_date_value($values['purchase_date'], 'Y-m-d')) $errors[] = 'Invalid purchase date.';
    if (!$errors) {
        $data = ['asset_tag' => $values['asset_tag'], 'brand' => $values['brand'], 'model' => $values['model'] ?: null, 'serial_number' => $values['serial_number'] ?: null, 'printer_type' => $values['printer_type'], 'ip_address' => $values['ip_address'] ?: null, 'location' => $values['location'] ?: null, 'department_id' => $values['department_id'] !== '' ? (int) $values['department_id'] : null, 'status' => $values['status'], 'purchase_date' => $values['purchase_date'] ?: null];
        $pdo->beginTransaction();
        try {
            $printerId = $id ?: $model->create($data);
            if ($id) $model->update($id, $data);
            $model->replaceUserAssignments((int) $printerId, $assignedUserIds);
            $pdo->commit();
            flash('message', $id ? 'Printer updated.' : 'Printer created.');
            redirect('admin_printers.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Printer could not be saved.';
        }
    }
} else {
    $users = $userModel->all();
}
$departments = $departmentModel->all();
require dirname(__DIR__) . '/app/views/layout.php';
page_header($id ? 'Edit printer' : 'Add printer');
?>
<div class="toolbar"><div><p class="eyebrow">Administration / inventory</p><h1><?= $id ? 'Edit printer' : 'Add printer' ?></h1></div><a href="admin_printers.php">Back to printers</a></div>
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<section class="panel form-panel"><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="grid">
<p><label>Asset tag</label><input name="asset_tag" maxlength="50" value="<?= e($values['asset_tag']) ?>" required></p>
<p><label>Brand</label><input name="brand" value="<?= e($values['brand']) ?>" required></p>
<p><label>Model</label><input name="model" value="<?= e($values['model'] ?? '') ?>"></p>
<p><label>Serial number</label><input name="serial_number" value="<?= e($values['serial_number'] ?? '') ?>"></p>
<p><label>Printer type</label><select name="printer_type"><?php foreach (['laser', 'inkjet', 'multifunction', 'thermal', 'other'] as $type): ?><option value="<?= e($type) ?>" <?= $values['printer_type'] === $type ? 'selected' : '' ?>><?= e(ucfirst($type)) ?></option><?php endforeach; ?></select></p>
<p><label>IP address</label><input name="ip_address" value="<?= e($values['ip_address'] ?? '') ?>"></p>
<p><label>Location</label><input name="location" value="<?= e($values['location'] ?? '') ?>"></p>
<p><label>Department</label><select name="department_id"><option value="">None</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) $values['department_id'] === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?></select></p>
<p><label>Status</label><select name="status"><?php foreach (['functional', 'maintenance', 'broken', 'retired'] as $status): ?><option <?= $values['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></p>
<p><label>Purchase date</label><input name="purchase_date" type="date" value="<?= e($values['purchase_date'] ?? '') ?>"></p>
<p><label>Assign to users</label><select name="user_ids[]" multiple size="5"><?php foreach ($users as $user): ?><option value="<?= (int) $user['id'] ?>" <?= in_array((int) $user['id'], $assignedUserIds, true) ? 'selected' : '' ?>><?= e($user['first_name'] . ' ' . $user['last_name'] . ' - ' . $user['email']) ?></option><?php endforeach; ?></select></p>
</div><button type="submit">Save printer</button></form></section><?php page_footer(); ?>
