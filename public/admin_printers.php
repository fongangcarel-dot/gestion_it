<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/core/bootstrap.php';
require_role('admin');

$printers = (new Printer($pdo))->all();
require dirname(__DIR__) . '/app/views/layout.php';
page_header('Printers');
?>
<div class="toolbar"><div><p class="eyebrow">Administration / inventory</p><h1>Printers</h1></div><a class="button" href="admin_printer.php">Add printer</a></div>
<?php if ($message = flash('message')): ?><p class="message"><?= e($message) ?></p><?php endif; ?>
<div class="panel"><table><tr><th>Asset tag</th><th>Brand/model</th><th>Type</th><th>Location</th><th>Assigned users</th><th>Status</th><th>Action</th></tr><?php foreach ($printers as $printer): ?><tr><td><?= e($printer['asset_tag']) ?></td><td><?= e($printer['brand'] . ' ' . ($printer['model'] ?? '')) ?></td><td><?= e($printer['printer_type']) ?></td><td><?= e($printer['location'] ?? 'Not specified') ?></td><td><?= e($printer['assigned_users'] ?? 'Unassigned') ?></td><td><?= e($printer['status']) ?></td><td><a href="admin_printer.php?id=<?= (int) $printer['id'] ?>">Edit</a></td></tr><?php endforeach; ?></table></div>
<?php page_footer(); ?>
