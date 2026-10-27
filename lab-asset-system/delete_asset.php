<?php
require_once __DIR__ . "/auth.php"; require_login();
require_once "config/Database.php";
require_once "classes/Asset.php";

$db = (new Database())->connect();
$asset = new Asset($db);

$id = (int)($_GET["id"] ?? 0);

// --- Delete Record Snippet (TRANSACTION 3) -------------------------------
if ($id > 0) {
    $result = $asset->deleteAsset($id, current_user_id());
} else {
    $result = ["success" => false, "message" => "Invalid asset ID."];
}
// --------------------------------------------------------------------------

// Message is kept in the session and shown once on the ledger page
asset_flash_set($result["success"], $result["message"]);
header("Location: view_assets.php");
exit;
