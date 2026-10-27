<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$asset = new Asset((new Database())->connect());
$entries = $asset->getAuditLog(100);

$pageTitle = "Audit Log";
require_once "includes/header.php";
?>

<div class="page-head">
    <h1>Audit log</h1>
    <p>Every add, transfer, and delete is recorded inside its database transaction.</p>
</div>

<div class="panel">
    <?php if (empty($entries)): ?>
        <p>No activity yet. If this stays empty after adding assets, import <code>transactions_setup.sql</code>.</p>
    <?php else: ?>
    <table>
        <thead>
        <tr><th>When</th><th>Action</th><th>Asset ID</th><th>Details</th><th>By</th></tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $e): ?>
            <tr>
                <td class="mono"><?= htmlspecialchars($e["created_at"]) ?></td>
                <td><?= htmlspecialchars($e["action"]) ?></td>
                <td class="mono"><?= htmlspecialchars((string)($e["asset_id"] ?? "—")) ?></td>
                <td><?= htmlspecialchars($e["details"]) ?></td>
                <td><?= htmlspecialchars($e["full_name"] ?? "Unknown") ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>
