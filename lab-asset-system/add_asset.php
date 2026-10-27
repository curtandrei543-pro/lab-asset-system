<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);

$message = null;
$messageType = null;

// --- Add Record Snippet -------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $result = $asset->addAsset([
        "Asset_name"     => trim($_POST["asset_name"]),
        "Brand"          => trim($_POST["brand"]),
        "Serial_Number"  => (int)$_POST["serial_number"],
        "Purchase_Date"  => $_POST["purchase_date"],
        "Status"         => $_POST["status"],
        "Category_id"    => (int)$_POST["category_id"],
        "Laboratory_id"  => (int)$_POST["laboratory_id"],
    ], current_user_id());

    $message = $result["message"];
    $messageType = $result["success"] ? "success" : "error";
}
// --------------------------------------------------------------------------

$categories = $db->query("SELECT Category_id, Category_Name FROM Asset_Categories ORDER BY Category_Name")->fetchAll();
$labs = $db->query("SELECT Laboratory_id, Laboratrory_Name FROM Laboratories ORDER BY Laboratrory_Name")->fetchAll();

$pageTitle = "Add Asset";
require_once "includes/header.php";
?>

<div class="page-head">
    <h1>Add asset</h1>
    <p>Register a new piece of laboratory equipment.</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if (empty($categories) || empty($labs)): ?>
    <div class="notice">
        <?= empty($categories) ? "No categories found in Asset_Categories. " : "" ?>
        <?= empty($labs) ? "No laboratories found in Laboratories. " : "" ?>
        Run <code>seed_data.sql</code> against your database, then reload this page.
    </div>
<?php endif; ?>

<div class="form-grid">
    <div class="panel">
        <form method="POST" action="add_asset.php" id="assetForm">
            <label for="asset_name">Asset name</label>
            <input type="text" id="asset_name" name="asset_name" placeholder="e.g. OptiPlex 3080" required>

            <div class="field-row">
                <div>
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" placeholder="e.g. Dell" required>
                </div>
                <div>
                    <label for="serial_number">Serial number</label>
                    <input type="number" id="serial_number" name="serial_number" placeholder="e.g. 100005" required>
                </div>
            </div>

            <div class="field-row">
                <div>
                    <label for="purchase_date">Purchase date</label>
                    <input type="date" id="purchase_date" name="purchase_date" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="Working">Working</option>
                        <option value="Damaged">Damaged</option>
                        <option value="Under Repair">Under Repair</option>
                        <option value="Retired">Retired</option>
                    </select>
                </div>
            </div>

            <label for="category_id">Category</label>
            <select id="category_id" name="category_id" required <?= empty($categories) ? "disabled" : "" ?>>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat["Category_id"] ?>"><?= htmlspecialchars($cat["Category_Name"]) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="laboratory_id">Laboratory</label>
            <select id="laboratory_id" name="laboratory_id" required <?= empty($labs) ? "disabled" : "" ?>>
                <?php foreach ($labs as $lab): ?>
                    <option value="<?= $lab["Laboratory_id"] ?>"><?= htmlspecialchars($lab["Laboratrory_Name"]) ?></option>
                <?php endforeach; ?>
            </select>

            <div style="margin-top:20px;">
                <button type="submit" <?= (empty($categories) || empty($labs)) ? "disabled" : "" ?>>Add asset</button>
                <a href="view_assets.php" class="btn secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="tag-preview">
        <div class="tag-eyebrow">ASSET TAG PREVIEW</div>
        <div class="tag-name" id="pv-name">Untitled asset</div>
        <dl>
            <dt>Brand</dt><dd id="pv-brand">—</dd>
            <dt>Serial</dt><dd id="pv-serial">—</dd>
            <dt>Category</dt><dd id="pv-category">—</dd>
            <dt>Lab</dt><dd id="pv-lab">—</dd>
            <dt>Status</dt><dd id="pv-status">Working</dd>
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
        updateSelectPreview(categorySelect, categoryPreview);
        categorySelect.addEventListener("change", function () { updateSelectPreview(categorySelect, categoryPreview); });
    }
    if (labSelect) {
        updateSelectPreview(labSelect, labPreview);
        labSelect.addEventListener("change", function () { updateSelectPreview(labSelect, labPreview); });
    }
})();
</script>

<?php require_once "includes/footer.php"; ?>
