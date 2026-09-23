<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core/bootstrap.php';
require_role('admin');
$users = (new User($pdo))->all();
require dirname(__DIR__) . '/app/views/layout.php'; page_header('Users');
?>
<div class="toolbar"><div><p class="eyebrow">Administration</p><h1>Users</h1></div><a class="button" href="admin_user.php">Add user</a></div>
<?php if ($message = flash('message')): ?><p class="message"><?= e($message) ?></p><?php endif; ?>
<div class="panel"><table><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr><?php foreach ($users as $user): ?><tr><td><?= e($user['first_name'].' '.$user['last_name']) ?></td><td><?= e($user['email']) ?></td><td><span class="role-badge role-<?= e($user['role']) ?>"><?= e($user['role']) ?></span></td><td><?= e($user['status']) ?></td><td><a href="admin_user.php?id=<?= (int) $user['id'] ?>">Edit</a></td></tr><?php endforeach; ?></table></div>
<?php page_footer(); ?>