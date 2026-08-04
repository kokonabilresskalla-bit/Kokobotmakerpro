<?php
error_reporting(0);
require_once __DIR__ . '/env.php';

$dataDir = $_DATA_DIR;
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$usersFile = $dataDir . '/users.json';
$botsFile = $dataDir . '/bots.json';
$blockedFile = $dataDir . '/blocked_users.json';
$hiddenFile = $dataDir . '/hidden_bots.json';
$hiddenTypesFile = $dataDir . '/hidden_types.json';

function setAuthCookie(array $user): void {
    $data = [
        'user_id'  => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'] ?? 'user',
        'exp'      => time() + 3600,
    ];
    setcookie('bs_auth', base64_encode(json_encode($data)), time() + 3600, '/', '', false, true);
}

function getAuthCookie(): ?array {
    if (empty($_COOKIE['bs_auth'])) return null;
    $d = json_decode(base64_decode($_COOKIE['bs_auth']), true);
    if (!$d || empty($d['exp']) || $d['exp'] < time()) {
        setcookie('bs_auth', '', time() - 3600, '/');
        return null;
    }
    return $d;
}

function clearAuthCookie(): void {
    setcookie('bs_auth', '', time() - 3600, '/');
}

function loadUsers(): array {
    global $usersFile;
    if (!file_exists($usersFile)) return [];
    $data = json_decode(file_get_contents($usersFile), true);
    return is_array($data) ? $data : [];
}

function saveUsers(array $users): void {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
}

function loadBots(): array {
    global $botsFile;
    if (!file_exists($botsFile)) return [];
    $data = json_decode(file_get_contents($botsFile), true);
    return is_array($data) ? $data : [];
}

function saveBots(array $bots): void {
    global $botsFile;
    file_put_contents($botsFile, json_encode($bots, JSON_PRETTY_PRINT));
}

function isLoggedIn(): bool {
    return getAuthCookie() !== null;
}

function redirect($page): void {
    header("Location: $page");
    exit;
}

function getUser(): ?array {
    $cookie = getAuthCookie();
    if (!$cookie) return null;
    $users = loadUsers();
    foreach ($users as $u) {
        if ($u['id'] === $cookie['user_id']) return $u;
    }
    return null;
}

// === Admin / Block / Hide helpers ===

function loadBlocked(): array {
    global $blockedFile;
    if (!file_exists($blockedFile)) return [];
    $d = json_decode(file_get_contents($blockedFile), true);
    return is_array($d) ? $d : [];
}

function saveBlocked(array $data): void {
    global $blockedFile;
    file_put_contents($blockedFile, json_encode($data, JSON_PRETTY_PRINT));
}

function loadHidden(): array {
    global $hiddenFile;
    if (!file_exists($hiddenFile)) return [];
    $d = json_decode(file_get_contents($hiddenFile), true);
    return is_array($d) ? $d : [];
}

function saveHidden(array $data): void {
    global $hiddenFile;
    file_put_contents($hiddenFile, json_encode($data, JSON_PRETTY_PRINT));
}

function isBlocked(string $userId): bool {
    return in_array($userId, loadBlocked());
}

function checkBlocked(): void {
    $cookie = getAuthCookie();
    if (!$cookie) return;
    $uid = $cookie['user_id'];
    $users = loadUsers();
    foreach ($users as $u) {
        if ($u['id'] === $uid && ($u['role'] ?? '') === 'admin') return;
    }
    if (isBlocked($uid)) {
        clearAuthCookie();
        redirect('login.php');
    }
}

function isBotHidden(string $botId): bool {
    return in_array($botId, loadHidden());
}

function loadHiddenTypes(): array {
    global $hiddenTypesFile;
    if (!file_exists($hiddenTypesFile)) return [];
    $d = json_decode(file_get_contents($hiddenTypesFile), true);
    return is_array($d) ? $d : [];
}

function saveHiddenTypes(array $data): void {
    global $hiddenTypesFile;
    file_put_contents($hiddenTypesFile, json_encode($data, JSON_PRETTY_PRINT));
}

function isTypeHidden(string $typeCode): bool {
    return in_array($typeCode, loadHiddenTypes());
}

function ensureAdmin(): void {
    global $_ADMIN_USER, $_ADMIN_PASS;
    $users = loadUsers();
    foreach ($users as $u) {
        if ($u['username'] === $_ADMIN_USER) return;
    }
    $users[] = [
        'id' => 'admin_' . uniqid(),
        'username' => $_ADMIN_USER,
        'email' => 'admin@local',
        'password' => password_hash($_ADMIN_PASS, PASSWORD_DEFAULT),
        'role' => 'admin',
        'created' => date('Y-m-d H:i:s'),
    ];
    saveUsers($users);
}

function ensureAccounts(): void {
    global $_ACCOUNTS;
    if (empty($_ACCOUNTS)) return;
    $users = loadUsers();
    $existing = array_map(fn($u) => $u['username'], $users);
    $changed = false;
    foreach ($_ACCOUNTS as $acc) {
        if (!in_array($acc['username'], $existing)) {
            $users[] = [
                'id' => $acc['username'] . '_' . uniqid(),
                'username' => $acc['username'],
                'email' => ($acc['email'] ?? $acc['username'] . '@local'),
                'password' => password_hash($acc['password'], PASSWORD_DEFAULT),
                'role' => $acc['role'] ?? 'user',
                'created' => date('Y-m-d H:i:s'),
            ];
            $existing[] = $acc['username'];
            $changed = true;
        }
    }
    if ($changed) saveUsers($users);
}

ensureAccounts();
