<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (!isset($GLOBALS['_trd_user'])) {
        $GLOBALS['_trd_user'] = false;
        $id = (int)($_SESSION['uid'] ?? 0);
        if ($id > 0) {
            $row = qone('SELECT u.*, c.name AS city_name FROM users u LEFT JOIN cities c ON c.id = u.city_id WHERE u.id = ?', [$id]);
            $GLOBALS['_trd_user'] = $row ?: false;
        }
    }
    return $GLOBALS['_trd_user'] ?: null;
}

function auth_login(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = $userId;
    $GLOBALS['_trd_user'] = null;
    unset($GLOBALS['_trd_user']);
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'] ?? '/', $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
    start_app_session();
    $GLOBALS['_trd_user'] = false;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['return_to'] = current_route();
        flash_set('error', 'Sign in to continue.');
        redirect('login');
    }
    return $u;
}

function require_role(array $roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        flash_set('error', 'You do not have access to that page.');
        redirect(home_path_for($u));
    }
    return $u;
}

function home_path_for(array $user): string
{
    return match ($user['role']) {
        'admin' => 'admin',
        'supervisor' => 'supervisor',
        default => 'home',
    };
}

function latest_application(int $userId): ?array
{
    return qone('SELECT a.*, c.name AS city_name FROM applications a LEFT JOIN cities c ON c.id = a.city_id WHERE a.user_id = ? ORDER BY a.id DESC LIMIT 1', [$userId]);
}

function is_fleet_member(array $user, ?array $app = null): bool
{
    if (in_array($user['role'], ['rider', 'driver'], true)) {
        return true;
    }
    $app = $app ?? latest_application((int)$user['id']);
    return $app && $app['status'] === 'completed' && !empty($app['rider_code']);
}

function supervisor_city_id(array $user): ?int
{
    if ($user['role'] !== 'supervisor') {
        return null;
    }
    $row = qone('SELECT city_id FROM supervisors WHERE user_id = ?', [$user['id']]);
    return $row ? (int)$row['city_id'] : (int)($user['city_id'] ?? 0);
}

function login_allowed(): bool
{
    $n = (int)($_SESSION['login_attempts'] ?? 0);
    $t = (int)($_SESSION['login_last'] ?? 0);
    if ($n >= 8 && (time() - $t) < 300) {
        return false;
    }
    if ((time() - $t) >= 300) {
        $_SESSION['login_attempts'] = 0;
    }
    return true;
}

function login_fail(): void
{
    $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_last'] = time();
}

function login_ok(): void
{
    $_SESSION['login_attempts'] = 0;
}
