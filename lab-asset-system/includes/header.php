<?php if (!isset($pageTitle)) { $pageTitle = "Computer Laboratory Asset Management System"; }
$currentPage = basename($_SERVER['PHP_SELF']);
function navClass($file, $current) { return $file === $current ? "active" : ""; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — Lab Asset System</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand">
        <span class="eyebrow">LAB-ASSET-SYS</span>
        <div class="title">Computer Laboratory<br>Asset Management</div>
    </div>
    <nav>
        <a href="index.php" class="<?= navClass('index.php', $currentPage) ?>">Dashboard</a>
        <a href="view_assets.php" class="<?= navClass('view_assets.php', $currentPage) ?>">Asset Ledger</a>
        <a href="add_asset.php" class="<?= navClass('add_asset.php', $currentPage) ?>">Add Asset</a>
        <a href="search_assets.php" class="<?= navClass('search_assets.php', $currentPage) ?>">Search</a>
        <a href="audit_log.php" class="<?= navClass('audit_log.php', $currentPage) ?>">Audit Log</a>
    </nav>
</aside>
<div class="main">
<div class="topbar"><div><span class="topbar-label">WORKSPACE</span><strong>Computer Laboratory Assets</strong></div><div class="topbar-user"><span class="avatar"><?= h(mb_substr($_SESSION['auth_user']['name'] ?? 'U',0,1)) ?></span><span><strong><?= h($_SESSION['auth_user']['name'] ?? 'User') ?></strong><small><?= h($_SESSION['auth_user']['role'] ?? 'Staff') ?></small></span><a href="logout.php">Sign out</a></div></div>
