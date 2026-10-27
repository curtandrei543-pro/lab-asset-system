<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);

// --- Fetch Records Snippet -------------------------------------------
$assets = $asset->getAssets();
// -----------------------------------------------------------------------

$pageTitle = "Asset Ledger";
require_once "includes/header.php";

function statusClass(string $status): string
{
    return "status-" . strtolower(str_replace(" ", "-", $status));
}
?>

<div class="page-head">
    <h1>Asset Ledger</h1>
    <p><?= count($assets) ?> record<?= count($assets) === 1 ? "" : "s" ?> across all laboratories.</p>
</div>

<?php $flash = asset_flash_get(); if ($flash): ?>
    <div class="alert alert-<?= $flash["success"] ? "success" : "error" ?>">
        <?= htmlspecialchars($flash["message"]) ?>
    </div>
<?php endif; ?>

<div class="panel">
    <?php if (empty($assets)): ?>
        <p>No asset records yet. <a href="add_asset.php">Add the first one.</a></p>
    <?php else: ?>
    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Brand</th>
            <th>Serial</th>
            <th>Category</th>
            <th>Laboratory</th>
            <th>Status</th>
            <th>Purchased</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($assets as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row["Asset_name"]) ?></td>
                <td><?= htmlspecialchars($row["Brand"]) ?></td>
                <td class="mono"><?= htmlspecialchars($row["Serial_Number"]) ?></td>
                <td><?= htmlspecialchars($row["Category_Name"]) ?></td>
                <td><?= htmlspecialchars($row["Laboratrory_Name"]) ?></td>
                <td><span class="chip <?= statusClass($row["Status"]) ?>"><?= htmlspecialchars($row["Status"]) ?></span></td>
                <td class="mono"><?= htmlspecialchars($row["Purchase_Date"]) ?></td>
                <td class="actions">
                    <a href="update_asset.php?id=<?= $row["Asset_id"] ?>">Edit</a>
                    <a href="transfer_asset.php?id=<?= $row["Asset_id"] ?>">Transfer</a>
                    <a href="delete_asset.php?id=<?= $row["Asset_id"] ?>" class="danger"
                       onclick="return confirm('Delete asset <?= htmlspecialchars($row['Asset_name']) ?> and its assignment/maintenance records?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php require_once "includes/footer.php"; ?>
