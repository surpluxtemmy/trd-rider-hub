<?php
declare(strict_types=1);

function page_landing(): void
{
    $u = current_user();
    if ($u) {
        redirect(home_path_for($u));
    }
    html_head(t('landing.headline'), ['body' => 'guest landing-body']);
    ?>
    <div class="phone phone-flush">
        <header class="top top-guest">
            <a class="brand" href="<?= e(url('')) ?>"><?= trd_mark(32) ?><span>TRD <em>Hub</em></span></a>
            <div class="top-actions">
                <?= lang_switch_html() ?>
                <a class="ghost-link" href="<?= e(url('install-app')) ?>"><?= icon('download', 18) ?> <?= e(t('nav.install')) ?></a>
            </div>
        </header>
        <main class="main landing">
            <?php render_flashes(); ?>
            <p class="kicker"><?= e(t('brand.kicker')) ?></p>
            <h1 class="display"><?= e(t('landing.headline')) ?></h1>
            <p class="lede"><?= e(t('landing.sub')) ?></p>
            <div class="stack-btns">
                <a class="btn btn-primary" href="<?= e(url('signup')) ?>"><?= e(t('landing.create')) ?> <?= icon('arrow-right', 18) ?></a>
                <a class="btn btn-ghost" href="<?= e(url('login')) ?>"><?= e(t('landing.signIn')) ?></a>
            </div>
            <div class="vehicle-row">
                <a class="vcard" href="<?= e(url('signup')) ?>?type=rider">
                    <img src="<?= e(asset('rider.jpg')) ?>" alt="">
                    <div class="vcard-meta">
                        <strong><?= e(t('type.rider')) ?></strong>
                        <span><?= e(t('type.riderFleet')) ?></span>
                    </div>
                </a>
                <a class="vcard" href="<?= e(url('signup')) ?>?type=driver">
                    <img src="<?= e(asset('driver.jpg')) ?>" alt="">
                    <div class="vcard-meta">
                        <strong><?= e(t('type.driver')) ?></strong>
                        <span><?= e(t('type.driverFleet')) ?></span>
                    </div>
                </a>
            </div>
            <section class="how">
                <h2><?= e(t('landing.f3t')) ?></h2>
                <ol class="steps">
                    <li><i>1</i><div><strong><?= e(t('landing.f1t')) ?></strong><span><?= e(t('landing.f1d')) ?></span></div></li>
                    <li><i>2</i><div><strong><?= e(t('nav.overview')) ?></strong><span><?= e(t('landing.f2d')) ?></span></div></li>
                    <li><i>3</i><div><strong><?= e(t('landing.f3t')) ?></strong><span><?= e(t('landing.f3d')) ?></span></div></li>
                </ol>
            </section>
            <p class="hours"><?= icon('clock', 16) ?> <?= e(setting('support_hours', 'Sunday–Thursday, 9:00–18:00 GST')) ?></p>
        </main>
        <footer class="fineprint"><?= e(setting('company_legal', 'Talabat Rider Division · Oman')) ?></footer>
    </div>
    <?php
    html_foot();
}

function page_login(): void
{
    $u = current_user();
    if ($u) {
        redirect(home_path_for($u));
    }
    $errors = [];
    if (is_post()) {
        csrf_verify();
        if (!login_allowed()) {
            $errors[] = 'Too many attempts. Wait five minutes and try again.';
        } else {
            $email = strtolower(post_str('email'));
            $password = (string)($_POST['password'] ?? '');
            if (!valid_email($email) || $password === '') {
                $errors[] = 'Enter email and password.';
                login_fail();
            } else {
                $row = qone('SELECT * FROM users WHERE email = ?', [$email]);
                if (!$row || !password_verify($password, (string)$row['password_hash'])) {
                    $errors[] = 'Email or password is not correct.';
                    login_fail();
                } else {
                    login_ok();
                    auth_login((int)$row['id']);
                    $to = (string)($_SESSION['return_to'] ?? '');
                    unset($_SESSION['return_to']);
                    if ($to === '' || str_starts_with($to, 'login') || str_starts_with($to, 'signup')) {
                        $to = home_path_for($row);
                    }
                    flash_set('ok', 'Signed in.');
                    redirect($to);
                }
            }
        }
    }
    layout_guest_start(t('login.submit'));
    errors_box($errors);
    ?>
    <h1 class="h"><?= e(t('login.title')) ?></h1>
    <p class="sub"><?= e(t('login.sub')) ?></p>
    <form method="post" class="form" autocomplete="on">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label"><?= e(t('login.email')) ?></span>
            <input class="input" type="email" name="email" required value="<?= e(post_str('email')) ?>" autocomplete="username">
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('login.password')) ?></span>
            <input class="input" type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="btn btn-primary" type="submit"><?= e(t('login.submit')) ?></button>
    </form>
    <p class="center-note"><?= e(t('login.new')) ?> <a href="<?= e(url('signup')) ?>"><?= e(t('login.create')) ?></a></p>
    <?php
    layout_guest_end();
}

function page_signup(): void
{
    $u = current_user();
    if ($u) {
        redirect(home_path_for($u));
    }
    $pref = query_str('type');
    if (in_array($pref, ['rider', 'driver'], true)) {
        $_SESSION['apply_type'] = $pref;
    }
    $errors = [];
    if (is_post()) {
        csrf_verify();
        $name = post_str('name');
        $email = strtolower(post_str('email'));
        $phone = normalize_phone(post_str('phone'));
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password2'] ?? '');
        if (strlen($name) < 2) {
            $errors[] = 'Enter your name as it should appear on your file.';
        }
        if (!valid_email($email)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!valid_phone($phone)) {
            $errors[] = 'Enter a valid mobile number. Oman numbers: 968 + 8 digits.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $password2) {
            $errors[] = 'Passwords do not match.';
        }
        if (!$errors) {
            $exists = qone('SELECT id FROM users WHERE email = ?', [$email]);
            if ($exists) {
                $errors[] = 'That email is already registered. Sign in instead.';
            }
        }
        if (!$errors) {
            q(
                'INSERT INTO users (role, name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)',
                ['applicant', $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]
            );
            $id = (int)db()->lastInsertId();
            auth_login($id);
            flash_set('ok', 'Account created. Choose rider or driver.');
            redirect('onboarding');
        }
    }
    layout_guest_start(t('signup.title'));
    errors_box($errors);
    ?>
    <h1 class="h"><?= e(t('signup.title')) ?></h1>
    <p class="sub"><?= e(t('signup.sub')) ?></p>
    <form method="post" class="form" autocomplete="on">
        <?= csrf_field() ?>
        <label class="field">
            <span class="field-label"><?= e(t('signup.name')) ?></span>
            <input class="input" type="text" name="name" required maxlength="120" value="<?= e(post_str('name')) ?>" autocomplete="name">
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('signup.email')) ?></span>
            <input class="input" type="email" name="email" required value="<?= e(post_str('email')) ?>" autocomplete="email">
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('signup.phone')) ?></span>
            <span class="field-hint"><?= e(t('signup.phoneHint')) ?></span>
            <input class="input" type="tel" name="phone" required value="<?= e(post_str('phone')) ?>" autocomplete="tel" placeholder="968">
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('signup.password')) ?></span>
            <input class="input" type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label class="field">
            <span class="field-label"><?= e(t('signup.password')) ?></span>
            <input class="input" type="password" name="password2" required minlength="8" autocomplete="new-password">
        </label>
        <button class="btn btn-primary" type="submit"><?= e(t('common.continue')) ?></button>
    </form>
    <p class="center-note"><?= e(t('signup.have')) ?> <a href="<?= e(url('login')) ?>"><?= e(t('signup.signIn')) ?></a></p>
    <?php
    layout_guest_end();
}

function page_onboarding(): void
{
    $user = require_login();
    if (in_array($user['role'], ['admin', 'supervisor'], true)) {
        redirect(home_path_for($user));
    }
    $app = latest_application((int)$user['id']);
    if ($app && $app['status'] !== 'rejected') {
        redirect('home');
    }
    if (is_post()) {
        csrf_verify();
        $type = post_str('type');
        if (!in_array($type, ['rider', 'driver'], true)) {
            flash_set('error', 'Choose rider or driver.');
            redirect('onboarding');
        }
        $_SESSION['apply_type'] = $type;
        redirect('register');
    }
    $pref = (string)($_SESSION['apply_type'] ?? query_str('type'));
    layout_app_start(t('onboard.title'), ['user' => $user, 'nav' => 'home', 'app' => $app]);
    ?>
    <h1 class="h"><?= e(t('onboard.title')) ?></h1>
    <p class="sub"><?= e(t('onboard.sub')) ?></p>
    <form method="post" class="choose-form">
        <?= csrf_field() ?>
        <label class="choose <?= $pref === 'rider' ? 'is-on' : '' ?>">
            <input type="radio" name="type" value="rider" <?= $pref === 'rider' ? 'checked' : '' ?> required>
            <img src="<?= e(asset('rider.jpg')) ?>" alt="">
            <div>
                <?= icon('bike', 20) ?>
                <strong><?= e(t('type.rider')) ?></strong>
                <span><?= e(t('onboard.riderText')) ?></span>
            </div>
        </label>
        <label class="choose <?= $pref === 'driver' ? 'is-on' : '' ?>">
            <input type="radio" name="type" value="driver" <?= $pref === 'driver' ? 'checked' : '' ?> required>
            <img src="<?= e(asset('driver.jpg')) ?>" alt="">
            <div>
                <?= icon('car', 20) ?>
                <strong><?= e(t('type.driver')) ?></strong>
                <span><?= e(t('onboard.driverText')) ?></span>
            </div>
        </label>
        <button class="btn btn-primary" type="submit"><?= e(t('common.continue')) ?></button>
    </form>
    <?php
    layout_app_end();
}

function page_register(): void
{
    $user = require_login();
    if (in_array($user['role'], ['admin', 'supervisor'], true)) {
        redirect(home_path_for($user));
    }
    $existing = latest_application((int)$user['id']);
    if ($existing && $existing['status'] !== 'rejected') {
        redirect('home');
    }
    $type = (string)($_SESSION['apply_type'] ?? '');
    if (!in_array($type, ['rider', 'driver'], true)) {
        redirect('onboarding');
    }
    $docs = docs_for($type);
    $cities = qall('SELECT id, name, governorate FROM cities WHERE active = 1 ORDER BY name');
    $errors = [];
    if (is_post()) {
        csrf_verify();
        $first = post_str('first_name');
        $middle = post_str('middle_name');
        $last = post_str('last_name');
        $nat = post_str('nationality');
        $dob = post_str('dob');
        $reason = post_str('reason_to_join');
        $cityId = request_int('city_id');
        $phone = normalize_phone(post_str('phone') !== '' ? post_str('phone') : (string)$user['phone']);
        if ($first === '' || $last === '') {
            $errors[] = 'Enter first and last name exactly as in the passport.';
        }
        if ($nat === '' || !in_array($nat, NATIONALITIES, true)) {
            $errors[] = 'Select nationality.';
        }
        if ($dob === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
            $errors[] = 'Enter date of birth.';
        } else {
            $age = (int)date('Y') - (int)substr($dob, 0, 4);
            if ($age < 18 || $age > 70) {
                $errors[] = 'Applicants must be between 18 and 70.';
            }
        }
        if (strlen($reason) < 8) {
            $errors[] = 'Tell us why you want to join Talabat with TRD.';
        }
        $cityOk = false;
        foreach ($cities as $c) {
            if ((int)$c['id'] === $cityId) {
                $cityOk = true;
                break;
            }
        }
        if (!$cityOk) {
            $errors[] = 'Select your working city.';
        }
        if (!valid_phone($phone)) {
            $errors[] = 'Enter a valid mobile number.';
        }
        $uploads = [];
        if (!$errors) {
            try {
                foreach ($docs as $kind => $meta) {
                    $up = save_upload('doc_' . $kind, $kind);
                    if (!$up) {
                        $errors[] = 'Upload required: ' . $meta['label'];
                    } else {
                        $uploads[] = $up;
                    }
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }
        if (!$errors) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $no = make_application_no($type);
                q(
                    'INSERT INTO applications (user_id, type, status, application_no, first_name, middle_name, last_name, nationality, dob, reason_to_join, city_id, phone)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [(int)$user['id'], $type, 'pending', $no, $first, $middle, $last, $nat, $dob, $reason, $cityId, $phone]
                );
                $appId = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare('INSERT INTO documents (application_id, kind, filename, mime, path) VALUES (?, ?, ?, ?, ?)');
                foreach ($uploads as $up) {
                    $ins->execute([$appId, $up['kind'], $up['filename'], $up['mime'], $up['path']]);
                }
                q(
                    'INSERT INTO application_events (application_id, from_status, to_status, note, actor_id) VALUES (?, NULL, ?, ?, ?)',
                    [$appId, 'pending', 'Submitted by applicant', (int)$user['id']]
                );
                q('UPDATE users SET name = ?, phone = ?, city_id = ? WHERE id = ?', [
                    trim($first . ' ' . $last),
                    $phone,
                    $cityId,
                    (int)$user['id'],
                ]);
                $pdo->commit();
                $app = qone(
                    'SELECT a.*, c.name AS city_name FROM applications a LEFT JOIN cities c ON c.id = a.city_id WHERE a.id = ?',
                    [$appId]
                );
                $wa = '';
                if ($app) {
                    $wa = notify_new_application($app, $user);
                    $_SESSION['last_wa_admin'] = $wa;
                }
                unset($_SESSION['apply_type']);
                flash_set('ok', 'Application ' . $no . ' submitted. Operations has been notified.');
                redirect('home');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Could not save the application. Try again.';
            }
        }
    }
    layout_app_start(t('register.title'), ['user' => $user, 'nav' => 'home', 'back' => 'onboarding', 'back_label' => t('common.back')]);
    errors_box($errors);
    ?>
    <p class="kicker"><?= $type === 'driver' ? 'Driver file' : 'Rider file' ?></p>
    <h1 class="h">Passport details & documents</h1>
    <p class="sub">Names must match the passport data page. Files: JPG, PNG, WebP or PDF, max 2 MB each.</p>
    <form method="post" class="form" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <div class="grid-2">
            <label class="field">
                <span class="field-label">First name</span>
                <input class="input" name="first_name" required maxlength="80" value="<?= e(post_str('first_name')) ?>">
            </label>
            <label class="field">
                <span class="field-label">Middle name</span>
                <input class="input" name="middle_name" maxlength="80" value="<?= e(post_str('middle_name')) ?>">
            </label>
        </div>
        <label class="field">
            <span class="field-label">Last name</span>
            <input class="input" name="last_name" required maxlength="80" value="<?= e(post_str('last_name')) ?>">
        </label>
        <label class="field">
            <span class="field-label">Nationality</span>
            <select class="input" name="nationality" required>
                <option value="">Select</option>
                <?php foreach (NATIONALITIES as $n): ?>
                    <option value="<?= e($n) ?>" <?= post_str('nationality') === $n ? 'selected' : '' ?>><?= e($n) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Date of birth</span>
            <input class="input" type="date" name="dob" required value="<?= e(post_str('dob')) ?>">
        </label>
        <label class="field">
            <span class="field-label">Working city</span>
            <select class="input" name="city_id" required>
                <option value="">Select city</option>
                <?php
                $curGov = '';
                foreach ($cities as $c):
                    if ($curGov !== (string)$c['governorate']) {
                        if ($curGov !== '') {
                            echo '</optgroup>';
                        }
                        $curGov = (string)$c['governorate'];
                        echo '<optgroup label="' . e($curGov) . '">';
                    }
                    ?>
                    <option value="<?= (int)$c['id'] ?>" <?= request_int('city_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php
                endforeach;
                if ($curGov !== '') {
                    echo '</optgroup>';
                }
                ?>
            </select>
        </label>
        <label class="field">
            <span class="field-label">Mobile</span>
            <input class="input" type="tel" name="phone" required value="<?= e(post_str('phone') !== '' ? post_str('phone') : (string)$user['phone']) ?>">
        </label>
        <label class="field">
            <span class="field-label">Why join Talabat with TRD?</span>
            <textarea class="input textarea" name="reason_to_join" required minlength="8" rows="4"><?= e(post_str('reason_to_join')) ?></textarea>
        </label>
        <h2 class="h2">Uploads</h2>
        <?php foreach ($docs as $kind => $meta): ?>
            <label class="doc-slot">
                <span class="doc-copy">
                    <strong><?= e($meta['label']) ?></strong>
                    <em><?= e($meta['hint']) ?></em>
                </span>
                <input type="file" name="doc_<?= e($kind) ?>" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required data-preview="prev-<?= e($kind) ?>">
                <span class="doc-preview" id="prev-<?= e($kind) ?>"><?= icon('upload', 16) ?> Choose file</span>
            </label>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Submit application</button>
        <p class="tiny">Submitting notifies TRD operations by email and WhatsApp. Keep this phone on.</p>
    </form>
    <?php
    layout_app_end();
}

function page_install_app(): void
{
    $u = current_user();
    html_head('Install app', ['body' => $u ? 'app-body' : 'guest']);
    echo '<div class="phone">';
    echo '<header class="top">';
    echo back_link($u ? 'home' : '', 'Back');
    echo '<span class="top-label">Add to Home Screen</span><span></span>';
    echo '</header><main class="main">';
    render_flashes();
    ?>
    <div class="install-hero">
        <?= trd_mark(64) ?>
        <h1 class="h">Install TRD Hub</h1>
        <p class="sub">Works like an app: full screen, home-screen icon, offline shell.</p>
        <button class="btn btn-primary" type="button" data-install hidden>Install on this device</button>
    </div>
    <section class="card">
        <h2 class="h2"><?= icon('smartphone', 18) ?> Android · Chrome</h2>
        <ol class="plain-ol">
            <li>Open this site in Chrome.</li>
            <li>Tap the install banner, or menu then <strong>Install app</strong> / <strong>Add to Home screen</strong>.</li>
            <li>Confirm. Launch TRD Hub from your home screen.</li>
        </ol>
    </section>
    <section class="card">
        <h2 class="h2"><?= icon('share', 18) ?> iPhone & iPad · Safari</h2>
        <ol class="plain-ol">
            <li>Open this page in Safari (not Chrome or in-app browsers).</li>
            <li>Tap the Share button at the bottom of the screen.</li>
            <li>Scroll and tap <strong>Add to Home Screen</strong>.</li>
            <li>Tap Add. The orange TRD icon appears on your home screen.</li>
        </ol>
    </section>
    <p class="tiny">Notifications inside the app are always available. WhatsApp and email alerts use the number and address on your file.</p>
    <?php
    echo '</main></div>';
    html_foot();
}

function page_logout(): void
{
    if (!is_post()) {
        redirect('login');
    }
    csrf_verify();
    auth_logout();
    flash_set('ok', 'Signed out.');
    redirect('login');
}

function page_file(): void
{
    $user = require_login();
    $id = request_int('id');
    $doc = qone(
        'SELECT d.*, a.user_id, a.city_id FROM documents d JOIN applications a ON a.id = d.application_id WHERE d.id = ?',
        [$id]
    );
    if (!$doc) {
        http_response_code(404);
        echo 'Not found';
        return;
    }
    $allowed = false;
    if ((int)$doc['user_id'] === (int)$user['id']) {
        $allowed = true;
    } elseif ($user['role'] === 'admin') {
        $allowed = true;
    } elseif ($user['role'] === 'supervisor') {
        $cid = supervisor_city_id($user);
        $allowed = $cid && $cid === (int)$doc['city_id'];
    }
    if (!$allowed) {
        http_response_code(403);
        echo 'Forbidden';
        return;
    }
    $path = app_root() . '/' . $doc['path'];
    $real = realpath($path);
    $uploadRoot = realpath(app_root() . '/uploads');
    if (!$real || !$uploadRoot || !str_starts_with($real, $uploadRoot . DIRECTORY_SEPARATOR)) {
        http_response_code(404);
        echo 'Missing file';
        return;
    }
    if (!is_file($real)) {
        http_response_code(404);
        echo 'Missing file';
        return;
    }
    $mime = (string)$doc['mime'];
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    $disp = str_starts_with($mime, 'image/') || $mime === 'application/pdf' ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disp . '; filename="' . str_replace(['"', "\n"], '', (string)$doc['filename']) . '"');
    header('Content-Length: ' . (string)filesize($real));
    readfile($real);
}

function page_404(): void
{
    http_response_code(404);
    layout_guest_start('Not found');
    echo '<h1 class="h">Page not found</h1><p class="sub">That link is not part of TRD Hub.</p>';
    echo '<a class="btn btn-primary" href="' . e(url('')) . '">Go home</a>';
    layout_guest_end();
}
