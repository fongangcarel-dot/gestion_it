<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core/bootstrap.php';
require_role('employee');
$user = current_user(); $ticketModel = new Ticket($pdo); $computerModel = new Computer($pdo); $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = post_text('title'); $description = post_text('description'); $category = post_text('category'); $priority = post_text('priority');
    $computerId = (int) ($_POST['computer_id'] ?? 0);
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) $errors[] = 'Invalid form token.';
    if ($title === '' || strlen($title) > 200) $errors[] = 'Title is required and must be 200 characters or fewer.';
    if ($description === '') $errors[] = 'Description is required.';
    if (!valid_enum($category, Ticket::categories()) || !valid_enum($priority, Ticket::priorities())) $errors[] = 'Invalid category or priority.';
    if (!$computerId || !$computerModel->belongsToUser($computerId, (int) $user['id'])) $errors[] = 'Select a computer assigned to your account.';
    if (!$errors) { $ticketModel->create((int) $user['id'], $computerId ?: null, $title, $description, $category, $priority); flash('message', 'Ticket created.'); redirect('tickets.php'); }
}
$tickets = $ticketModel->forEmployee((int) $user['id']); $computers = $computerModel->forEmployee((int) $user['id']); require dirname(__DIR__) . '/app/views/layout.php'; page_header('My tickets');
?>
<div class="toolbar"><h1>My tickets</h1><a class="button" href="#new-ticket">New ticket</a></div>
<?php if ($message = flash('message')): ?><p class="message"><?= e($message) ?></p><?php endif; ?>
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<div class="panel"><table><tr><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Assignment</th><th></th></tr><?php foreach ($tickets as $ticket): ?><tr><td><?= e($ticket['title']) ?></td><td><?= e($ticket['category']) ?></td><td><?= e($ticket['priority']) ?></td><td><?= e($ticket['status']) ?></td><td><?= $ticket['technician_name'] ? 'Assigned to ' . e($ticket['technician_name']) : 'Waiting for assignment' ?></td><td><a href="ticket.php?id=<?= (int) $ticket['id'] ?>">View</a></td></tr><?php endforeach; ?></table></div>
<section id="new-ticket" class="panel"><h2>Create ticket</h2><?php if (!$computers): ?><p class="error">No computer is assigned to your account. Contact an administrator before creating a ticket.</p><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label for="title">Title</label><input id="title" name="title" maxlength="200" required><label for="description">Description</label><textarea id="description" name="description" required></textarea><div class="grid"><p><label for="category">Category</label><select id="category" name="category"><?php foreach (Ticket::categories() as $value): ?><option value="<?= e($value) ?>"><?= e(ucfirst($value)) ?></option><?php endforeach; ?></select></p><p><label for="priority">Priority</label><select id="priority" name="priority"><?php foreach (Ticket::priorities() as $value): ?><option value="<?= e($value) ?>"><?= e(ucfirst($value)) ?></option><?php endforeach; ?></select></p><p><label for="computer_id">Assigned computer</label><select id="computer_id" name="computer_id" required <?= !$computers ? 'disabled' : '' ?>><option value="">Select your computer</option><?php foreach ($computers as $computer): ?><option value="<?= (int) $computer['id'] ?>"><?= e($computer['asset_tag'] . ' ' . $computer['brand'] . ' ' . $computer['model']) ?></option><?php endforeach; ?></select></p></div><button type="submit" <?= !$computers ? 'disabled' : '' ?>>Create ticket</button></form></section>
<?php page_footer(); ?>