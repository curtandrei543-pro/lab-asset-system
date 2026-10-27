<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);

$id = (int)($_GET["id"] ?? $_POST["asset_id"] ?? 0);
if ($id <= 0) {
    die("Invalid asset ID.");
}

$message = null;
$messageType = null;

// --- Update Record Snippet ---------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $result = $asset->updateAsset($id, [
        "Asset_name"     => trim($_POST["asset_name"]),
        "Brand"          => trim($_POST["brand"]),
        "Serial_Number"  => (int)$_POST["serial_number"],
        "Purchase_Date"  => $_POST["purchase_date"],
        "Status"         => $_POST["status"],
        "Category_id"    => (int)$_POST["category_id"],
        "Laboratory_id"  => (int)$_POST["laboratory_id"],
    ]);

    $message = $result["message"];
    $messageType = $result["success"] ? "success" : "error";
}
// -----------------------------------------------------------------------

$record = $asset->getAssetById($id);
if (!$record) {
    die("Asset not found.");
}

$categories = $db->query("SELECT Category_id, Category_Name FROM Asset_Categories ORDER BY Category_Name")->fetchAll();
$labs = $db->query("SELECT Laboratory_id, Laboratrory_Name FROM Laboratories ORDER BY Laboratrory_Name")->fetchAll();

// Values used for both the form and the initial preview state
$currentCategoryName = "";
foreach ($categories as $c) { if ($c["Category_id"] == $record["Category_id"]) { $currentCategoryName = $c["Category_Name"]; } }
$currentLabName = "";
foreach ($labs as $l) { if ($l["Laboratory_id"] == $record["Laboratory_id"]) { $currentLabName = $l["Laboratrory_Name"]; } }

$pageTitle = "Update Asset";
require_once "includes/header.php";
?>

<div class="page-head">
    <h1>Update asset</h1>
    <p>Editing <?= htmlspecialchars($record["Asset_name"]) ?></p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="form-grid">
    <div class="panel">
        <form method="POST" action="update_asset.php?id=<?= $id ?>" id="assetForm">
            <input type="hidden" name="asset_id" value="<?= $id ?>">

            <label for="asset_name">Asset name</label>
            <input type="text" id="asset_name" name="asset_name" value="<?= htmlspecialchars($record["Asset_name"]) ?>" required>

            <div class="field-row">
                <div>
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($record["Brand"]) ?>" required>
                </div>
                <div>
                    <label for="serial_number">Serial number</label>
                    <input type="number" id="serial_number" name="serial_number" value="<?= htmlspecialchars($record["Serial_Number"]) ?>" required>
                </div>
            </div>

            <div class="field-row">
                <div>
                    <label for="purchase_date">Purchase date</label>
                    <input type="date" id="purchase_date" name="purchase_date" value="<?= htmlspecialchars($record["Purchase_Date"]) ?>" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <?php foreach (["Working","Damaged","Under Repair","Retired"] as $st): ?>
                            <option value="<?= $st ?>" <?= $record["Status"] === $st ? "selected" : "" ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat["Category_id"] ?>" <?= $record["Category_id"] == $cat["Category_id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($cat["Category_Name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="laboratory_id">Laboratory</label>
            <select id="laboratory_id" name="laboratory_id" required>
                <?php foreach ($labs as $lab): ?>
                    <option value="<?= $lab["Laboratory_id"] ?>" <?= $record["Laboratory_id"] == $lab["Laboratory_id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($lab["Laboratrory_Name"]) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="margin-top:20px;">
                <button type="submit">Save changes</button>
                <a href="view_assets.php" class="btn secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="tag-preview">
        <div class="tag-eyebrow">ASSET TAG PREVIEW</div>
        <div class="tag-name" id="pv-name"><?= htmlspecialchars($record["Asset_name"]) ?></div>
        <dl>
            <dt>Brand</dt><dd id="pv-brand"><?= htmlspecialchars($record["Brand"]) ?></dd>
            <dt>Serial</dt><dd id="pv-serial"><?= htmlspecialchars($record["Serial_Number"]) ?></dd>
            <dt>Category</dt><dd id="pv-category"><?= htmlspecialchars($currentCategoryName ?: "—") ?></dd>
            <dt>Lab</dt><dd id="pv-lab"><?= htmlspecialchars($currentLabName ?: "—") ?></dd>
            <dt>Status</dt><dd id="pv-status"><?= htmlspecialchars($record["Status"]) ?></dd>
        </dl>
        <div class="tag-barcode"></div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById("assetForm");
    if (!form) return;

    const map = {
        asset_name: "pv-name",
        brand: "pv-brand",
        serial_number: "pv-serial",
        status: "pv-status"
    };

    function fallback(el, text) {
        el.textContent = text && text.trim() !== "" ? text : "—";
    }

    Object.keys(map).forEach(function (fieldName) {
        const field = form.elements[fieldName];
        const preview = document.getElementById(map[fieldName]);
        if (!field || !preview) return;
        field.addEventListener("input", function () {
            if (fieldName === "asset_name") {
                preview.textContent = field.value.trim() !== "" ? field.value : "Untitled asset";
            } else {
                fallback(preview, field.value);
            }
        });
    });

    const categorySelect = form.elements["category_id"];
    const labSelect = form.elements["laboratory_id"];
    const categoryPreview = document.getElementById("pv-category");
    const labPreview = document.getElementById("pv-lab");

    function updateSelectPreview(select, preview) {
        if (!select || !preview) return;
        const opt = select.options[select.selectedIndex];
        fallback(preview, opt ? opt.textContent : "");
    }

    if (categorySelect) {
        categorySelect.addEventListener("change", function () { updateSelectPreview(categorySelect, categoryPreview); });
    }
    if (labSelect) {
        labSelect.addEventListener("change", function () { updateSelectPreview(labSelect, labPreview); });
    }
})();
</script>

<?php require_once "includes/footer.php"; ?>
