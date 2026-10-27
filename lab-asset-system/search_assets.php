<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);

$keyword = trim($_GET["keyword"] ?? "");
$results = [];

// --- Search Records Snippet ----------------------------------------------
if ($keyword !== "") {
    $results = $asset->searchAssets($keyword);
}
// ---------------------------------------------------------------------------

$pageTitle = "Search Assets";
require_once "includes/header.php";

function statusClass(string $status): string
{
    return "status-" . strtolower(str_replace(" ", "-", $status));
}
?>

<div class="page-head">
    <h1>Search assets</h1>
    <p>Matches on name, brand, serial number, status, category, or laboratory.</p>
</div>

<div class="panel">
    <form method="GET" action="search_assets.php" style="display:flex; gap:10px; align-items:flex-end;">
        <div style="flex:1;">
            <label for="keyword" style="margin-top:0;">Keyword</label>
            <input type="text" id="keyword" name="keyword" placeholder="e.g. Dell, Working, Lab 2..."
                   value="<?= htmlspecialchars($keyword) ?>">
        </div>
        <button type="submit">Search</button>
    </form>
</div>

<?php if ($keyword !== ""): ?>
<div class="panel">
    <h2>Results for "<?= htmlspecialchars($keyword) ?>" — <?= count($results) ?></h2>

    <?php if (empty($results)): ?>
        <p style="margin-top:14px;">No matching assets found.</p>
    <?php else: ?>
    <table style="margin-top:14px;">
        <thead>
        <tr>
            <th>Name</th><th>Brand</th><th>Serial</th><th>Category</th><th>Laboratory</th><th>Status</th><th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row["Asset_name"]) ?></td>
                <td><?= htmlspecialchars($row["Brand"]) ?></td>
                <td class="mono"><?= htmlspecialchars($row["Serial_Number"]) ?></td>
                <td><?= htmlspecialchars($row["Category_Name"]) ?></td>
                <td><?= htmlspecialchars($row["Laboratrory_Name"]) ?></td>
                <td><span class="chip <?= statusClass($row["Status"]) ?>"><?= htmlspecialchars($row["Status"]) ?></span></td>
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
<?php endif; ?>

<?php require_once "includes/footer.php"; ?>
