<?php
// Session and authentication helpers. Include before output on protected pages.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params(['httponly'=>true, 'secure'=>$secure, 'samesite'=>'Lax']);
    session_start();
}
function auth_db(): PDO {
    require_once __DIR__ . '/config/Database.php';
    return (new Database())->connect();
}
function require_login(): void {
    if (empty($_SESSION['auth_user'])) {
        $next = basename($_SERVER['PHP_SELF'] ?? 'index.php');
        header('Location: login.php?next=' . urlencode($next)); exit;
    }
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

// ---- Session-based CSRF token ----
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_check($token): bool {
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}
function current_user_id(): ?int {
    return isset($_SESSION['auth_user']['id']) ? (int)$_SESSION['auth_user']['id'] : null;
}

// ---- One-time messages stored in the session ----
function asset_flash_set(bool $success, string $message): void {
    $_SESSION['asset_flash'] = ['success' => $success, 'message' => $message];
}
function asset_flash_get(): ?array {
    $f = $_SESSION['asset_flash'] ?? null;
    unset($_SESSION['asset_flash']);
    return $f;
}
