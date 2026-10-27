<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);

$id = (int)($_GET["id"] ?? $_POST["asset_id"] ?? 0);
if ($id <= 0) { die("Invalid asset ID."); }

$record = $asset->getAssetById($id);
if (!$record) { die("Asset not found."); }

$message = null;

// --- Transfer Snippet (TRANSACTION 2) -----------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!csrf_check($_POST["csrf"] ?? "")) {
        $message = "Invalid form token. Reload the page and try again.";
    } else {
        $result = $asset->transferAsset(
            $id,
            (int)($_POST["laboratory_id"] ?? 0),
            (string)($_POST["remarks"] ?? ""),
            current_user_id()
        );
        if ($result["success"]) {
            asset_flash_set(true, $result["message"]);
            header("Location: view_assets.php");
            exit;
        }
        $message = $result["message"];
    }
}
// ------------------------------------------------------------------------

$labs = $db->query("SELECT Laboratory_id, Laboratrory_Name FROM Laboratories ORDER BY Laboratrory_Name")->fetchAll();

$pageTitle = "Transfer Asset";
require_once "includes/header.php";
?>

<div class="page-head">
    <h1>Transfer asset</h1>
    <p>Move <?= htmlspecialchars($record["Asset_name"]) ?> (S/N <?= htmlspecialchars($record["Serial_Number"]) ?>) to another laboratory.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="panel">
    <form method="POST" action="transfer_asset.php?id=<?= $id ?>">
        <input type="hidden" name="asset_id" value="<?= $id ?>">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

        <label for="laboratory_id">Destination laboratory</label>
        <select id="laboratory_id" name="laboratory_id" required>
            <?php foreach ($labs as $lab): ?>
                <option value="<?= $lab["Laboratory_id"] ?>"
                    <?= (int)$record["Laboratory_id"] === (int)$lab["Laboratory_id"] ? "disabled" : "" ?>>
                    <?= htmlspecialchars($lab["Laboratrory_Name"]) ?><?= (int)$record["Laboratory_id"] === (int)$lab["Laboratory_id"] ? " (current)" : "" ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="remarks">Remarks</label>
        <input type="text" id="remarks" name="remarks" maxlength="45" placeholder="e.g. Reassigned for midterm exams">

        <div style="margin-top:20px;">
            <button type="submit">Transfer asset</button>
            <a href="view_assets.php" class="btn secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once "includes/footer.php"; ?>
