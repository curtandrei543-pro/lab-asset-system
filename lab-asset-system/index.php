<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);
$assets = $asset->getAssets();

$total = count($assets);
$counts = ["Working" => 0, "Damaged" => 0, "Under Repair" => 0, "Retired" => 0];
foreach ($assets as $row) {
    $counts[$row["Status"]] = ($counts[$row["Status"]] ?? 0) + 1;
}

$pageTitle = "Dashboard";
require_once "includes/header.php";
?>

<div class="page-head">
    <h1>Dashboard</h1>
    <p>Snapshot of every tracked asset across all laboratories.</p>
</div>

<div class="stat-row">
    <div class="stat"><div class="num"><?= $total ?></div><div class="label">TOTAL ASSETS</div></div>
    <div class="stat working"><div class="num"><?= $counts["Working"] ?></div><div class="label">WORKING</div></div>
    <div class="stat damaged"><div class="num"><?= $counts["Damaged"] ?></div><div class="label">DAMAGED</div></div>
    <div class="stat repair"><div class="num"><?= $counts["Under Repair"] ?></div><div class="label">UNDER REPAIR</div></div>
    <div class="stat retired"><div class="num"><?= $counts["Retired"] ?></div><div class="label">RETIRED</div></div>
</div>

<div class="panel">
    <h2>Welcome, Custodian</h2>
    <p>This board replaces the spreadsheet-and-paper-form process with one place to record, update,
       and search every desktop, monitor, printer, and networking device across the school's
       computer laboratories.</p>
    <p style="margin-top:16px;">
        <a href="add_asset.php" class="btn primary">+ Add asset</a>
        <a href="view_assets.php" class="btn secondary">View ledger</a>
        <a href="search_assets.php" class="btn secondary">Search</a>
    </p>
</div>

<?php require_once "includes/footer.php"; ?>
