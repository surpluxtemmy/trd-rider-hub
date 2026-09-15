<?php
declare(strict_types=1);

define('TRD_APP', true);

$root = __DIR__;
require $root . '/includes/helpers.php';
require $root . '/includes/i18n.php';
require $root . '/includes/schema.php';

start_app_session();
lang_boot();

$lock = $root . '/data/install.lock';
if (is_file($lock) && is_file($root . '/config.php')) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en"><head>
        <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Already installed</title>
        <link rel="stylesheet" href="assets/app.css">
    </head>
    <body class="guest">
    <div class="phone"><main class="main">
        <h1 class="h">Installer is locked</h1>
        <p class="sub">TRD Hub is already installed. Remove data/install.lock only if you intend to reinstall.</p>
        <a class="btn btn-primary" href="index.php">Open the app</a>
    </main></div>
    </body></html>
    <?php
    exit;
}

$errors = [];
$ok = false;

function write_config_file(string $root, string $host, string $name, string $user, string $pass): void
{
    $php = "<?php\n"
        . "if (!defined('TRD_APP')) { http_response_code(403); exit('Forbidden'); }\n"
        . 'define(\'DB_HOST\', ' . var_export($host, true) . ");\n"
        . 'define(\'DB_NAME\', ' . var_export($name, true) . ");\n"
        . 'define(\'DB_USER\', ' . var_export($user, true) . ");\n"
        . 'define(\'DB_PASS\', ' . var_export($pass, true) . ");\n"
        . "define('DB_CHARSET', 'utf8mb4');\n";
    $path = $root . '/config.php';
    if (file_put_contents($path, $php) === false) {
        throw new RuntimeException('Could not write config.php. Check folder permissions.');
    }
    @chmod($path, 0640);
}

function ensure_writable_dirs(string $root): void
{
    foreach (['uploads', 'data', 'uploads/' . date('Y'), 'uploads/' . date('Y/m')] as $d) {
        $p = $root . '/' . $d;
        if (!is_dir($p) && !mkdir($p, 0755, true) && !is_dir($p)) {
            throw new RuntimeException('Cannot create ' . $d . '. Set that folder writable (0755 or 0775).');
        }
        if (!is_writable($p)) {
            throw new RuntimeException($d . ' is not writable. In cPanel set permissions to 0755.');
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (empty($_SESSION['csrf']) || !hash_equals((string)$_SESSION['csrf'], (string)($_POST['_csrf'] ?? ''))) {
        $errors[] = 'Invalid form token. Reload and try again.';
    } else {
        $host = trim((string)($_POST['db_host'] ?? 'localhost'));
        $name = trim((string)($_POST['db_name'] ?? ''));
        $user = trim((string)($_POST['db_user'] ?? ''));
        $pass = (string)($_POST['db_pass'] ?? '');
        $adminName = trim((string)($_POST['admin_name'] ?? ''));
        $adminEmail = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $adminPhone = normalize_phone((string)($_POST['admin_phone'] ?? ''));
        $adminPass = (string)($_POST['admin_password'] ?? '');
        $wa = normalize_phone((string)($_POST['admin_whatsapp'] ?? ''));

        if ($host === '' || $name === '' || $user === '') {
            $errors[] = 'Database host, name and user are required.';
        }
        if (strlen($adminName) < 2) {
            $errors[] = 'Enter the first administrator name.';
        }
        if (!valid_email($adminEmail)) {
            $errors[] = 'Enter a valid admin email.';
        }
        if (!valid_phone($adminPhone)) {
            $errors[] = 'Enter a valid admin mobile number.';
        }
        if (strlen($adminPass) < 8) {
            $errors[] = 'Admin password must be at least 8 characters.';
        }
        if ($wa === '' || !valid_phone($wa)) {
            $errors[] = 'Enter the company WhatsApp number in international digits.';
        }

        if (!$errors) {
            try {
                ensure_writable_dirs($root);
                require $root . '/includes/db.php';
                $pdo = db_connect_raw($host, $name, $user, $pass);
                $pdo->exec('SET NAMES utf8mb4');
                foreach (schema_statements() as $sql) {
                    $pdo->exec($sql);
                }
                $insCity = $pdo->prepare('INSERT INTO cities (name, governorate, active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE governorate = VALUES(governorate)');
                foreach (schema_oman_cities() as $c) {
                    $insCity->execute([$c[0], $c[1]]);
                }
                $insSet = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
                foreach (schema_default_settings() as $k => $v) {
                    $insSet->execute([$k, $v]);
                }
                $insSet->execute(['admin_whatsapp', $wa]);
                $insSet->execute(['emergency_phone', $wa]);
                $insSet->execute(['admin_email', $adminEmail]);
                $insSet->execute(['email_from', 'TRD Rider Hub <' . $adminEmail . '>']);

                $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $exists->execute([$adminEmail]);
                if (!$exists->fetch()) {
                    $st = $pdo->prepare('INSERT INTO users (role, name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)');
                    $st->execute(['admin', $adminName, $adminEmail, $adminPhone, password_hash($adminPass, PASSWORD_DEFAULT)]);
                } else {
                    $st = $pdo->prepare('UPDATE users SET role = ?, name = ?, phone = ?, password_hash = ? WHERE email = ?');
                    $st->execute(['admin', $adminName, $adminPhone, password_hash($adminPass, PASSWORD_DEFAULT), $adminEmail]);
                }

                write_config_file($root, $host, $name, $user, $pass);
                if (!is_dir($root . '/data')) {
                    mkdir($root . '/data', 0755, true);
                }
                file_put_contents($lock, date('c') . "\n");
                $ok = true;
            } catch (Throwable $e) {
                $errors[] = 'Install failed: ' . $e->getMessage();
            }
        }
    }
}

$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(lang_dir()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Install TRD Rider Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Figtree:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/app.css">
    <link rel="icon" type="image/png" href="assets/favicon.png">
</head>
<body class="guest">
<div class="phone">
    <header class="top top-guest">
        <div class="brand">
            <svg class="mark" width="32" height="32" viewBox="0 0 64 64" aria-hidden="true"><rect width="64" height="64" rx="16" fill="#E85D04"/><circle cx="32" cy="32" r="20" fill="#FBF7F2"/><circle cx="32" cy="32" r="16" fill="none" stroke="#0F766E" stroke-width="2.4"/><rect x="18" y="22" width="28" height="6" rx="3" fill="#E85D04"/><rect x="29" y="22" width="6" height="24" rx="3" fill="#E85D04"/><rect x="22" y="48" width="20" height="3" rx="1.5" fill="#0F766E"/></svg>
            <span>TRD <em>Hub</em></span>
        </div>
        <?= lang_switch_html() ?>
    </header>
    <main class="main">
        <?php if ($ok): ?>
            <p class="kicker">Ready</p>
            <h1 class="h">Installation complete</h1>
            <p class="sub">Tables created, Oman cities seeded, and your admin account is live. The installer is now locked.</p>
            <a class="btn btn-primary" href="index.php">Open TRD Hub</a>
            <p class="tiny">Optional: delete install.php from the server. Keep data/install.lock in place. Make sure uploads/ stays writable.</p>
        <?php else: ?>
            <p class="kicker">Namecheap · MySQL</p>
            <h1 class="h">Install TRD Rider Hub</h1>
            <p class="sub">Create a MySQL database in cPanel first, then fill this form. PHP 8+ with PDO MySQL is required.</p>
            <?php if ($errors): ?>
                <div class="flash flash-error" role="alert"><ul class="err-list"><?php foreach ($errors as $err) echo '<li>' . e($err) . '</li>'; ?></ul></div>
            <?php endif; ?>
            <form method="post" class="form" autocomplete="off">
                <input type="hidden" name="_csrf" value="<?= e($token) ?>">
                <h2 class="h2">Database</h2>
                <label class="field"><span class="field-label">Host</span><input class="input" name="db_host" value="<?= e((string)($_POST['db_host'] ?? 'localhost')) ?>" required></label>
                <label class="field"><span class="field-label">Database name</span><input class="input" name="db_name" required value="<?= e((string)($_POST['db_name'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label">User</span><input class="input" name="db_user" required value="<?= e((string)($_POST['db_user'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label">Password</span><input class="input" type="password" name="db_pass" value="<?= e((string)($_POST['db_pass'] ?? '')) ?>"></label>

                <h2 class="h2">First administrator</h2>
                <label class="field"><span class="field-label">Name</span><input class="input" name="admin_name" required value="<?= e((string)($_POST['admin_name'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label">Email</span><input class="input" type="email" name="admin_email" required value="<?= e((string)($_POST['admin_email'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label">Mobile</span><input class="input" name="admin_phone" required placeholder="968" value="<?= e((string)($_POST['admin_phone'] ?? '')) ?>"></label>
                <label class="field"><span class="field-label">Password</span><input class="input" type="password" name="admin_password" required minlength="8"></label>
                <label class="field"><span class="field-label">Company WhatsApp</span><span class="field-hint">Used for SOS and application alerts. Example: 9689XXXXXXX</span>
                    <input class="input" name="admin_whatsapp" required value="<?= e((string)($_POST['admin_whatsapp'] ?? '')) ?>"></label>
                <button class="btn btn-primary" type="submit">Create database & lock installer</button>
            </form>
        <?php endif; ?>
    </main>
    <footer class="fineprint">Talabat Rider Division · Oman</footer>
</div>
</body>
</html>
