<?php
declare(strict_types=1);

const STATUSES = [
    'pending' => ['label' => 'Pending', 'hint' => 'Application received'],
    'accepted' => ['label' => 'Accepted', 'hint' => 'Documents accepted for review'],
    'processing' => ['label' => 'Processing', 'hint' => 'Operations is reviewing your file'],
    'awaiting_sponsor' => ['label' => 'Awaiting sponsor', 'hint' => 'Sent to sponsor'],
    'sponsor_approved' => ['label' => 'Sponsor approved', 'hint' => 'Sponsor has approved'],
    'completed' => ['label' => 'Completed', 'hint' => 'Fleet ID issued'],
    'rejected' => ['label' => 'Rejected', 'hint' => 'Not approved'],
];

const STATUS_FLOW = ['pending', 'accepted', 'processing', 'awaiting_sponsor', 'sponsor_approved', 'completed'];

const RIDER_DOCS = [
    'passport' => ['label' => 'International passport data page', 'hint' => 'Clear photo of the photo page'],
    'resident_front' => ['label' => 'Resident card — front', 'hint' => 'Oman resident card, front'],
    'resident_back' => ['label' => 'Resident card — back', 'hint' => 'Oman resident card, back'],
    'selfie' => ['label' => 'Live selfie', 'hint' => 'Face clearly visible, no sunglasses'],
];

const DRIVER_DOCS = [
    'passport' => ['label' => 'International passport data page', 'hint' => 'Clear photo of the photo page'],
    'resident_front' => ['label' => 'Resident card — front', 'hint' => 'Oman resident card, front'],
    'resident_back' => ['label' => 'Resident card — back', 'hint' => 'Oman resident card, back'],
    'license' => ['label' => 'Driving licence', 'hint' => 'Valid Oman / GCC licence'],
    'car_reg' => ['label' => 'Car registration (Mulkiya)', 'hint' => 'Vehicle registration card'],
    'rop' => ['label' => 'ROP clearance', 'hint' => 'Royal Oman Police clearance'],
    'selfie' => ['label' => 'Live selfie', 'hint' => 'Face clearly visible, no sunglasses'],
];

const NATIONALITIES = [
    'Omani', 'Indian', 'Pakistani', 'Bangladeshi', 'Filipino', 'Egyptian', 'Nepalese',
    'Kenyan', 'Ugandan', 'Tanzanian', 'Sri Lankan', 'Sudanese', 'Ethiopian', 'Yemeni',
    'Jordanian', 'Syrian', 'Lebanese', 'Moroccan', 'Tunisian', 'Nigerian', 'Ghanaian', 'Other',
];

const TICKET_CATEGORIES = [
    'pay' => 'Pay & incentives',
    'account' => 'Account / app access',
    'vehicle' => 'Bike / car issue',
    'order' => 'Order or customer issue',
    'documents' => 'Documents & ID',
    'other' => 'Something else',
];

const APPOINTMENT_KINDS = [
    'documents' => 'Document collection',
    'interview' => 'Interview',
    'inspection' => 'Bike / car inspection',
    'uniform' => 'Uniform pickup',
    'training' => 'Onboarding training',
    'other' => 'Other',
];

const APPOINTMENT_STATUS = [
    'requested' => 'Requested',
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

const ALLOWED_UPLOAD_MIMES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_root(): string
{
    return dirname(__DIR__);
}

function app_base(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    if (defined('APP_BASE_PATH')) {
        $base = rtrim((string)APP_BASE_PATH, '/');
        return $base;
    }
    $doc = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string)$_SERVER['DOCUMENT_ROOT']) : false;
    $root = realpath(app_root());
    if ($doc && $root) {
        $docN = rtrim(str_replace('\\', '/', $doc), '/');
        $rootN = str_replace('\\', '/', $root);
        if ($docN !== '' && str_starts_with($rootN, $docN)) {
            $b = substr($rootN, strlen($docN));
            $base = rtrim($b, '/');
            return $base;
        }
    }
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $bn = basename($script);
    if (in_array($bn, ['index.php', 'install.php', 'router.php'], true)) {
        $dir = dirname($script);
        $base = ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/');
        return $base;
    }
    $base = '';
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $b = app_base();
    if ($path === '') {
        return $b === '' ? '/' : $b . '/';
    }
    return $b . '/' . $path;
}

function asset(string $file): string
{
    return url('assets/' . ltrim($file, '/'));
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

function current_route(): string
{
    if (isset($_GET['r']) && is_string($_GET['r']) && $_GET['r'] !== '') {
        return trim($_GET['r'], '/');
    }
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $uri = is_string($uri) ? $uri : '/';
    $base = app_base();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = trim($uri, '/');
    if (in_array($uri, ['index.php', 'router.php'], true)) {
        $uri = '';
    }
    return $uri;
}

function is_post(): bool
{
    return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST';
}

function start_app_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    if ($https) {
        ini_set('session.cookie_secure', '1');
    }
    session_name('trdhub');
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    if (!is_post()) {
        return;
    }
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || $t === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $t)) {
        http_response_code(403);
        echo 'Invalid or expired form token. Go back and try again.';
        exit;
    }
}

function flash_set(string $type, string $msg): void
{
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($f) ? $f : [];
}

function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function query_str(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function request_int(string $key, int $default = 0): int
{
    if (isset($_POST[$key])) {
        return (int)$_POST[$key];
    }
    if (isset($_GET[$key])) {
        return (int)$_GET[$key];
    }
    return $default;
}

function normalize_phone(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($d, '00')) {
        $d = substr($d, 2);
    }
    if (str_starts_with($d, '968') && strlen($d) === 11) {
        return $d;
    }
    if (str_starts_with($d, '0') && strlen($d) === 9) {
        return '968' . substr($d, 1);
    }
    if (strlen($d) === 8) {
        return '968' . $d;
    }
    return $d;
}

function format_phone(string $phone): string
{
    $n = normalize_phone($phone);
    if (preg_match('/^968(\d{4})(\d{4})$/', $n, $m)) {
        return '+968 ' . $m[1] . ' ' . $m[2];
    }
    return $phone;
}

function valid_email(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_phone(string $phone): bool
{
    $n = normalize_phone($phone);
    return (bool)preg_match('/^\d{10,15}$/', $n);
}

function status_label(string $id): string
{
    if (function_exists('t')) {
        return t('status.' . $id, [], STATUSES[$id]['label'] ?? $id);
    }
    return STATUSES[$id]['label'] ?? $id;
}

function status_hint(string $id): string
{
    if (function_exists('t')) {
        return t('statusHint.' . $id, [], STATUSES[$id]['hint'] ?? '');
    }
    return STATUSES[$id]['hint'] ?? '';
}

function docs_for(string $type): array
{
    return $type === 'driver' ? DRIVER_DOCS : RIDER_DOCS;
}

function next_status(string $current): ?string
{
    $i = array_search($current, STATUS_FLOW, true);
    if ($i === false || $i >= count(STATUS_FLOW) - 1) {
        return null;
    }
    return STATUS_FLOW[$i + 1];
}

function settings_cache_reset(): void
{
    $GLOBALS['_trd_settings'] = null;
}

function setting(string $key, string $default = ''): string
{
    if (!isset($GLOBALS['_trd_settings']) || !is_array($GLOBALS['_trd_settings'])) {
        $GLOBALS['_trd_settings'] = [];
        try {
            $rows = db()->query('SELECT `key`, `value` FROM settings')->fetchAll();
            foreach ($rows as $r) {
                $GLOBALS['_trd_settings'][$r['key']] = $r['value'];
            }
        } catch (Throwable $e) {
            $GLOBALS['_trd_settings'] = [];
        }
    }
    return (string)($GLOBALS['_trd_settings'][$key] ?? $default);
}

function setting_set(string $key, string $value): void
{
    q('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, $value]);
    if (!isset($GLOBALS['_trd_settings']) || !is_array($GLOBALS['_trd_settings'])) {
        $GLOBALS['_trd_settings'] = [];
    }
    $GLOBALS['_trd_settings'][$key] = $value;
}

function next_number(string $type): int
{
    $year = (int)date('Y');
    $pdo = db();
    $own = !$pdo->inTransaction();
    if ($own) {
        $pdo->beginTransaction();
    }
    try {
        $st = $pdo->prepare('SELECT last_n FROM id_sequences WHERE year = ? AND type = ? FOR UPDATE');
        $st->execute([$year, $type]);
        $row = $st->fetch();
        if (!$row) {
            $ins = $pdo->prepare('INSERT INTO id_sequences (year, type, last_n) VALUES (?, ?, 0)');
            $ins->execute([$year, $type]);
            $n = 0;
        } else {
            $n = (int)$row['last_n'];
        }
        $n++;
        $upd = $pdo->prepare('UPDATE id_sequences SET last_n = ? WHERE year = ? AND type = ?');
        $upd->execute([$n, $year, $type]);
        if ($own) {
            $pdo->commit();
        }
        return $n;
    } catch (Throwable $e) {
        if ($own && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function make_application_no(string $type): string
{
    $letter = strtoupper(substr($type, 0, 1));
    $n = next_number('APP-' . $letter);
    return sprintf('TRD-A%s-%d-%04d', $letter, (int)date('Y'), $n);
}

function make_fleet_id(string $type): string
{
    $letter = $type === 'driver' ? 'D' : 'R';
    $n = next_number($letter);
    return sprintf('TRD-%s-%d-%04d', $letter, (int)date('Y'), $n);
}

function save_upload(string $field, string $kind): ?array
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $kind . '.');
    }
    if ((int)$f['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Each file must be 2 MB or smaller.');
    }
    $tmp = (string)$f['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid upload.');
    }
    $mime = 'application/octet-stream';
    if (class_exists('finfo')) {
        $fi = new finfo(FILEINFO_MIME_TYPE);
        $det = $fi->file($tmp);
        if (is_string($det) && $det !== '') {
            $mime = $det;
        }
    } elseif (function_exists('mime_content_type')) {
        $det = @mime_content_type($tmp);
        if (is_string($det) && $det !== '') {
            $mime = $det;
        }
    }
    if ($mime === 'image/jpg') {
        $mime = 'image/jpeg';
    }
    if (!isset(ALLOWED_UPLOAD_MIMES[$mime])) {
        throw new RuntimeException('Only JPG, PNG, WebP or PDF files are allowed.');
    }
    $ext = ALLOWED_UPLOAD_MIMES[$mime];
    if (str_starts_with($mime, 'image/') && function_exists('getimagesize')) {
        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new RuntimeException('Image file could not be read.');
        }
    }
    $relDir = 'uploads/' . date('Y/m');
    $dir = app_root() . '/' . $relDir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not create upload folder. Set uploads/ writable.');
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Could not store the uploaded file.');
    }
    @chmod($dest, 0644);
    $orig = (string)($f['name'] ?? $kind);
    $orig = preg_replace('/[^\w.\- ()]+/', '_', $orig) ?: $kind;
    return [
        'kind' => $kind,
        'filename' => substr($orig, 0, 180),
        'mime' => $mime,
        'path' => $relDir . '/' . $name,
    ];
}

function wa_link(string $phone, string $text): string
{
    $n = normalize_phone($phone);
    return 'https://wa.me/' . $n . '?text=' . rawurlencode($text);
}

function http_post_json(string $url, array $data, array $headers = [], int $timeout = 8): ?string
{
    $hdr = array_merge(['Content-Type: application/json'], $headers);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $hdr),
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $res = @file_get_contents($url, false, $ctx);
    return $res === false ? null : $res;
}

function dt_format(?string $s, string $fmt = 'd M Y, H:i'): string
{
    if (!$s) {
        return '';
    }
    $t = strtotime($s);
    if ($t === false) {
        return $s;
    }
    return date($fmt, $t);
}

function display_name_of(array $user, ?array $app = null): string
{
    if ($app) {
        $parts = array_filter([$app['first_name'] ?? '', $app['middle_name'] ?? '', $app['last_name'] ?? '']);
        if ($parts) {
            return implode(' ', $parts);
        }
    }
    return (string)($user['name'] ?? 'Rider');
}

function unread_count(int $userId): int
{
    $row = qone('SELECT COUNT(*) AS n FROM notifications WHERE user_id = ? AND channel = ? AND read_at IS NULL', [$userId, 'inapp']);
    return (int)($row['n'] ?? 0);
}

function clip(string $s, int $n = 80): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($s, 0, $n, '...', 'UTF-8');
    }
    if (strlen($s) <= $n) {
        return $s;
    }
    return substr($s, 0, max(0, $n - 3)) . '...';
}

function initial(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '?';
    }
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($s, 0, 1));
}

function is_installed(): bool
{
    return is_file(app_root() . '/config.php') && is_file(app_root() . '/data/install.lock');
}

function client_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return preg_match('/^[0-9a-fA-F:.]+$/', $ip) ? $ip : '';
}
